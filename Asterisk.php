<?php
// Configuration for the contacts database
$contacts_db_config = [
    'servername' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'contacts'
];

// Create a separate connection for the contacts database
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

// Fetch numbers to dial from contacts table using the contacts database connection
$numbers_to_dial = [];
$sql = "SELECT id, phone_number, duration FROM contacts ORDER BY id";
try {
    $result = $contacts_conn->query($sql);
    if (!$result) {
        throw new Exception($contacts_conn->error);
    }
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

// Establish a connection to the AMI
$socket = fsockopen($ami_config['host'], $ami_config['port'], $errno, $errstr, 30);
if (!$socket) {
    echo "Connection failed: $errstr ($errno)\n";
    exit(1);
}

echo "AMI connection established successfully.\n";

// Log in to the AMI
fputs($socket, "Action: Login\r\n");
fputs($socket, "Username: {$ami_config['username']}\r\n");
fputs($socket, "Secret: {$ami_config['password']}\r\n\r\n");

echo "AMI login successful.\n";

// Function to originate a call with selectable audio playback
function originate_call($socket, $number, $duration, $audio_file) {
    printf("Dialing %s with duration %d seconds using audio file '%s'...\n", $number, $duration, $audio_file);
    
    // Originate the call
    fputs($socket, "Action: Originate\r\n");
    fputs($socket, "Channel: Local/{$number}@outbound-calls\r\n");
    fputs($socket, "Application: Playback\r\n");
    fputs($socket, "Data: {$audio_file}\r\n");
    fputs($socket, "CallerID: YourCallerID\r\n");
    fputs($socket, "Async: yes\r\n");
    fputs($socket, "Timeout: {$duration}000\r\n\r\n");
    
    // Get the initial response
    $response = '';
    $call_placed = false;
    
    while ($line = fgets($socket)) {
        $response .= $line;
        if (strpos($line, 'Message: Originate successfully queued') !== false) {
            $call_placed = true;
            echo "Call placed in queue\n";
        }
        if ($line == "\r\n") {
            break;
        }
    }
    
    if (!$call_placed) {
        echo "Failed to place call: $response\n";
        return ['status' => 'FAILED', 'response' => $response];
    }
    
    // Wait for the call to finish or timeout
    $start_time = time();
    $call_status = 'UNKNOWN';
    echo "Waiting for call to complete (max $duration seconds)...\n";
    
    while ((time() - $start_time) < $duration) {
        fputs($socket, "Action: CoreShowChannels\r\n\r\n");
        $status_response = '';
        $channels_found = false;
        
        while ($line = fgets($socket)) {
            $status_response .= $line;
            if (strpos($line, $number) !== false) {
                $channels_found = true;
                if (strpos($status_response, 'BridgeID:') !== false) {
                    $call_status = 'ANSWER';
                    echo "Call answered!\n";
                    break 2; // Exit both loops
                }
            }
            if ($line == "\r\n" && strpos($status_response, 'CoreShowChannelsComplete') !== false) {
                break;
            }
        }
        
        if (!$channels_found) {
            if ($call_status == 'ANSWER') {
                echo "Call completed normally\n";
                break;
            } else {
                fputs($socket, "Action: Status\r\n\r\n");
                $final_status = '';
                while ($line = fgets($socket)) {
                    $final_status .= $line;
                    if ($line == "\r\n" && strpos($final_status, 'StatusComplete') !== false) {
                        break;
                    }
                }
                if (strpos($final_status, 'Status: BUSY') !== false) {
                    $call_status = 'BUSY';
                    echo "Line busy\n";
                    break;
                } else {
                    $call_status = 'NOANSWER';
                    echo "No answer\n";
                    break;
                }
            }
        }
        
        sleep(1);
    }
    
    if ($call_status == 'UNKNOWN') {
        echo "Call timed out after $duration seconds\n";
        $call_status = 'TIMEOUT';
    }
    
    return ['status' => $call_status, 'response' => $response];
}

// Select the audio file to be played (change this path as needed)
$selected_audio = 'Sound.mp3';

// Initialize an array for called numbers and a flag for the first call
$called_numbers = [];
$first_number_called = false;

// Iterate through each contact and attempt to call
foreach ($numbers_to_dial as $contact) {
    $number = $contact['number'];
    $duration = $contact['duration'];
    $id = $contact['id'];

    if (!$first_number_called) {
        $first_number_called = true;
    } else {
        if (in_array($number, $called_numbers)) {
            echo "Skipping $number as it has already been called.\n";
            continue;
        }
    }
    $called_numbers[] = $number;

    echo "Attempting to call contact ID {$id}: {$number} (Duration: {$duration} seconds)\n";
    $call_result = originate_call($socket, $number, $duration, $selected_audio);
    
    // Log the call attempt
    $log_sql = "INSERT INTO call_log (contact_id, phone_number, call_time, status, response) VALUES (?, ?, NOW(), ?, ?)";
    try {
        $stmt = $contacts_conn->prepare($log_sql);
        if (!$stmt) {
            throw new Exception($contacts_conn->error);
        }
        $status = $call_result['status'];
        $response = $call_result['response'];
        $stmt->bind_param("isss", $id, $number, $status, $response);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error logging call attempt: " . $e->getMessage());
        echo "Error logging call attempt: " . $e->getMessage() . "\n";
        exit(1);
    }

    // If the call was answered, update the contact's last successful call
    if ($call_result['status'] == 'ANSWER') {
        echo "Call to $number was answered.\n";
        $update_sql = "UPDATE contacts SET last_successful_call = NOW() WHERE id = ?";
        try {
            $stmt = $contacts_conn->prepare($update_sql);
            if (!$stmt) {
                throw new Exception($contacts_conn->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error updating contact's last successful call: " . $e->getMessage());
            echo "Error updating contact's last successful call: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    // Wait for 5 seconds before making the next call
    sleep(5);
}

echo "All calls completed.\n";

// Log off from the AMI
fputs($socket, "Action: Logoff\r\n\r\n");

// Close the AMI connection
fclose($socket);

// Close the database connection
$contacts_conn->close();
?>
