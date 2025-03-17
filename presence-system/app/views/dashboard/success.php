<?php
// Purpose: Display a success message when the user successfully checks in for the day.
session_start();
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In Success</title>
    <!-- Bootstrap CSS 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="text-center">
            <h1 class="mb-4 text-success">Successfully checked in for today!</h1>
            <p>Thank you, <?php echo $userName; ?>! You will be redirected shortly.</p>
            <button class="btn btn-primary" onclick="window.location.href = 'index.php';">Go Back Now</button>
        </div>
        <?php include 'Utilities/translate.php'; ?>
        <?php include 'Dashboard/footer.php'; ?>
    </div>
</body>
</html>
