<?php
// Configuration for the contacts database
include 'Dashboard/config.php';

// Create database connection
$contacts_conn = new mysqli(
    $contacts_db_config['servername'],
    $contacts_db_config['username'],
    $contacts_db_config['password'],
    $contacts_db_config['database']
);

if ($contacts_conn->connect_error) {
    error_log("Contacts DB Connection failed: " . $contacts_conn->connect_error);
    echo "Failed to connect to contacts database.\n";
    exit(1);
}

$ami_config = [
    'host' => '10.24.100.20',
    'port' => 5038,
    'username' => 'mark',
    'password' => 'Bremen2025'
];

// Fetch numbers to dial
$numbers_to_dial = [];
$sql = "SELECT id, phone_number, duration FROM contacts ORDER BY id";
try {
    $result = $contacts_conn->query($sql);
    if (!$result) throw new Exception($contacts_conn->error);
    while ($row = $result->fetch_assoc()) {
        $numbers_to_dial[] = [
            'id' => $row['id'],
            'number' => $row['phone_number'],
            'duration' => $row['duration']
        ];
    }
    $result->free();
} catch (Exception $e) {
    error_log("Error fetching contacts: " . $e->getMessage());
    echo "Error fetching contacts: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Contacts fetched successfully.\n";

// Establish AMI connection
$socket = fsockopen($ami_config['host'], $ami_config['port'], $errno, $errstr, 30);
if (!$socket) {
    echo "Connection failed: $errstr ($errno)\n";
    exit(1);
}

// AMI Login
fputs($socket, "Action: Login\r\n");
fputs($socket, "Username: {$ami_config['username']}\r\n");
fputs($socket, "Secret: {$ami_config['password']}\r\n\r\n");

echo "AMI login successful.\n";

// Modified originate call function
function originate_call($socket, $number, $duration, $audio_file) {
    printf("Dialing %s with duration %d seconds using audio file '%s'...\n", $number, $duration, $audio_file);
    
    // Originate call with audio playback macro
    fputs($socket, "Action: Originate\r\n");
    fputs($socket, "Channel: Local/{$number}@outbound-calls\r\n");
    fputs($socket, "Variable: AUDIO_FILE={$audio_file}\r\n");
    fputs($socket, "Application: Dial\r\n");
    fputs($socket, "Data: PJSIP/{$number}@easybell,,gU(play_audio)\r\n");
    fputs($socket, "CallerID: YourCallerID\r\n");
    fputs($socket, "Async: yes\r\n");
    fputs($socket, "Timeout: {$duration}000\r\n\r\n");

    // Response handling
    $response = '';
    $call_placed = false;
    
    while ($line = fgets($socket)) {
        $response .= $line;
        if (strpos($line, 'Message: Originate successfully queued') !== false) {
            $call_placed = true;
            echo "Call placed in queue\n";
        }
        if ($line == "\r\n") break;
    }
    
    if (!$call_placed) {
        echo "Failed to place call: $response\n";
        return ['status' => 'FAILED', 'response' => $response];
    }

    // Call monitoring
    $start_time = time();
    $call_status = 'UNKNOWN';
    echo "Monitoring call status (max $duration seconds)...\n";
    
    while ((time() - $start_time) < $duration) {
        fputs($socket, "Action: CoreShowChannels\r\n\r\n");
        $status_response = '';
        $channels_found = false;
        
        while ($line = fgets($socket)) {
            $status_response .= $line;
            if (strpos($line, $number) !== false) {
                $channels_found = true;
                if (strpos($status_response, 'BridgeID:') !== false) {
                    $call_status = 'ANSWERED';
                    echo "Call answered!\n";
                    break 2;
                }
            }
            if ($line == "\r\n" && strpos($status_response, 'CoreShowChannelsComplete') !== false) break;
        }
        
        if (!$channels_found) {
            $call_status = 'NOANSWER';
            echo "No answer\n";
            break;
        }
        sleep(1);
    }

    if ($call_status == 'UNKNOWN') {
        echo "Call timed out after $duration seconds\n";
        $call_status = 'TIMEOUT';
    }

    // Additional logging and error handling
    error_log("Call result: " . json_encode(['status' => $call_status, 'response' => $response]));
    
    return ['status' => $call_status, 'response' => $response];
}

// Audio file configuration
$selected_audio = 'Sound.mp3';

// Call processing
$called_numbers = [];
foreach ($numbers_to_dial as $contact) {
    $number = $contact['number'];
    $duration = $contact['duration'];
    $id = $contact['id'];

    if (in_array($number, $called_numbers)) {
        echo "Skipping $number (already called)\n";
        continue;
    }
    $called_numbers[] = $number;

    echo "Calling contact ID {$id}: {$number} (Duration: {$duration}s)\n";
    $call_result = originate_call($socket, $number, $duration, $selected_audio);
    
    // Log call attempt
    $log_sql = "INSERT INTO call_log (contact_id, phone_number, call_time, status, response) 
                VALUES (?, ?, NOW(), ?, ?)";
    try {
        $stmt = $contacts_conn->prepare($log_sql);
        $status = $call_result['status'];
        $response = substr($call_result['response'], 0, 255); // Truncate if needed
        $stmt->bind_param("isss", $id, $number, $status, $response);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error logging call: " . $e->getMessage());
        echo "Logging error: " . $e->getMessage() . "\n";
    }

    // Update last successful call
    if ($call_result['status'] == 'ANSWERED') {
        $update_sql = "UPDATE contacts SET last_successful_call = NOW() WHERE id = ?";
        try {
            $stmt = $contacts_conn->prepare($update_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
        }
    }
    
    sleep(5); // Interval between calls
}

// Cleanup
fputs($socket, "Action: Logoff\r\n\r\n");
fclose($socket);
$contacts_conn->close();

echo "Call campaign completed.\n";
?>