<?php
// Configuration for the presencecheck database
$presencecheck_db_config = [
    'servername' => 'localhost',
    'username'   => 'root',
    'password'   => '',
    'database'   => 'presencecheck'
];

// Create connection
$conn = new mysqli(
    $presencecheck_db_config['servername'],
    $presencecheck_db_config['username'],
    $presencecheck_db_config['password'],
    $presencecheck_db_config['database']
);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    echo "We are experiencing technical difficulties. Please try again later.";
    exit;
}

// Configuration for the contacts database
$contacts_db_config = [
    'servername' => 'localhost',
    'username'   => 'root',
    'password'   => '',
    'database'   => 'contacts'
];
