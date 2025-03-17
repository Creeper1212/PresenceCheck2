<?php
// Purpose: Display a message when the user has already checked in.
// Redirects to index.php after 5 seconds.

// Consider starting the session here if it might be used for error messages
// session_start(); // Uncomment if you want to display errors here.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Checked In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /*  Add some basic styling for a cleaner look */
        body {
            background-color: #f8f9fa; /* Light gray background */
        }
        .container {
            margin-top: 50px; /* Add some top margin */
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
    <script>
        // Redirect to index.php after 5 seconds.  More robust approach:
        function redirect() {
            window.location.href = "index.php";
        }
        setTimeout(redirect, 5000);
    </script>
</head>
<body class="d-flex flex-column justify-content-center align-items-center vh-100">
    <div class="container text-center">
        <h1 class="mb-4 text-danger">You have already checked in today!</h1>
        <p>You will be redirected to the home page shortly.</p>
        <button class="btn btn-primary" onclick="redirect();">Go Back Now</button>
    </div>
</body>
</html>