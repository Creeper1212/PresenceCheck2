<?php
// Include configuration file instead of hardcoding connection
require '../../config/database.php';

try {
    // Sanitize and get POST input
    $name = isset($_POST['fname']) ? trim($_POST['fname']) : '';

    // Validate name
    if (empty($name) || strlen($name) < 2 || strlen($name) > 50 || !preg_match("/^[A-Za-z ]+$/", $name)) {
        header("Location: dashboard.php?error=invalid_name");
        exit();
    }

    // Sanitize name
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

    // Set default timezone to Germany/Berlin and get the current date and time
    date_default_timezone_set('Europe/Berlin');
    $time = date('H:i');
    $date = date('Y-m-d');

    // Function to check if current time is within a given range
    function isWithinTimeRange($currentTime, $start, $end) {
        return ($currentTime >= $start && $currentTime <= $end);
    }

    // Get current day of week (0 = Sunday, 6 = Saturday)
    $currentDayOfWeek = date('w');

    // Fetch time settings for today
    $timeStmt = $conn->prepare("SELECT start_time, end_time FROM timesettings WHERE day_id = ?");
    $timeStmt->bind_param("i", $currentDayOfWeek);
    $timeStmt->execute();
    $timeResult = $timeStmt->get_result();

    if ($timeRow = $timeResult->fetch_assoc()) {
        $startTime = $timeRow['start_time'];
        $endTime = $timeRow['end_time'];
    } else {
        // Default fallback if no settings found
        $startTime = '08:00';
        $endTime = '18:00';
    }
    $timeStmt->close();

    $ishere = isWithinTimeRange($time, $startTime, $endTime) ? 1 : 0;
    $status = $ishere === 1 ? "Is here" : "Is not here";

    // Check if the user already logged in today
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM presencetable WHERE name = ? AND date = ?");
    $checkStmt->bind_param("ss", $name, $date);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();

    if ($row['count'] > 0) {
        header("Location: alreadycheckedin.php");
        exit();
    } else {
        // Insert presence record
        $stmt = $conn->prepare("INSERT INTO presencetable (name, here, date, time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $name, $ishere, $date, $time);
        if ($stmt->execute()) {
            // Store username in session for later use
            session_start();
            $_SESSION['user_name'] = $name;
            $_SESSION['last_checkin'] = $date;
            
            header("Location: success.php");
            exit();
        } else {
            throw new Exception("Error: " . $conn->error . ". Statement Error: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    // Log error
    error_log("Presence Error: " . $e->getMessage());
    
    // Redirect to error page or display message
    header("Location: dashboard.php?error=system_error");
    exit();
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
