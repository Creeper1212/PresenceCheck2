<?php
// Assume $conn is available (require config.php before including this file)

// Get current day of week (0 = Sunday, 6 = Saturday)
$currentDayOfWeek = date('w');

// Fetch time settings for today
$sql = "
    SELECT 
        start_time, 
        end_time,
        CASE 
            WHEN CURTIME() BETWEEN start_time AND end_time THEN 1 
            ELSE 0 
        END AS is_in_range 
    FROM timesettings 
    WHERE day_id = ? 
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $currentDayOfWeek);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $startTime = $row['start_time'];
    $endTime = $row['end_time'];
    $isInRange = $row['is_in_range'];
} else {
    // Default values if no settings found
    $startTime = '08:00';
    $endTime = '18:00';
    $isInRange = (date('H:i') >= '08:00' && date('H:i') <= '18:00') ? 1 : 0;
}
$stmt->close();

// Make the time range available for other scripts
$timeRangeInfo = [
    'start_time' => $startTime,
    'end_time' => $endTime,
    'is_in_range' => $isInRange
];

// Determine whether the submit button should be enabled
$disabled = ($isInRange == 1) ? '' : ' disabled';