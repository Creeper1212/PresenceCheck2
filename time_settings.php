<?php
// Include required files -  More descriptive comments
require 'Dashboard/config.php';      // Database configuration (ensure this path is correct)
require 'TimeSettings/setgettime.php'; // Time setting logic (ensure this path is correct)
include 'Dashboard/header.php';      // Page header (ensure this path is correct and the header is properly structured)

//  Consider adding error handling if any of the above files are missing.  For example:
// if (!file_exists('Dashboard/config.php')) {
//     die("Error: Configuration file not found!");
// }

//  Good practice:  Initialize $message here to avoid potential "undefined variable" notices.
$message = ""; // Initialize $message to an empty string


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Time Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Configure login time limits for each day of the week.">  <!-- More descriptive description -->

    <!-- Stylesheets - Keep external stylesheets separate -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/modern-normalize@v3.0.1/modern-normalize.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="icon" href="favicon.png">  <!-- Favicon is good! -->
    <!-- Consider moving the styles below to an external stylesheet (e.g., styles.css) -->
    <style>
        body {
            background-color: #ffeb3b; /* Yellow background */
        }
        h1, h2, h3 {
            color: #d40612; /* Red text */
        }
        /* Add some basic styling to improve appearance.  These are suggestions. */
        .table-hover tbody tr:hover {
            background-color: #f8f9fa; /* Light gray on hover */
        }
        .btn-danger { /* Style the button consistently */
            background-color: #d40612;
            border-color: #d40612;
        }
        .btn-danger:hover { /* Add a hover effect */
            background-color: #a80510;
            border-color: #a80510;
        }

    </style>
</head>
<body>
    <!-- Navigation (Assuming this is handled in header.php, which is good practice) -->

    <!-- Main Content -->
    <main class="container mt-4">

        <h1 class="mb-4">Configure Login Time Limits</h1>

        <section class="mb-5">
            <h2>Time Settings</h2>
            <p>Set the time limits for each day of the week.</p>

            <h3 class="mt-4">Current Settings</h3>
            <!-- No major change here, the structure is good -->
            <form method="POST" action="time_settings.php" class="mt-4">  <!-- Consider using a more descriptive action if this page handles multiple actions -->
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Day</th>
                            <th scope="col">Start Time</th>
                            <th scope="col">End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $index => $dayName):
                            //  Use more descriptive variable names, and combine the isset() check.
                            $startTime = $settings[$dayName]['start_time'] ?? "08:00";  // Use null coalescing operator (PHP 7+)
                            $endTime = $settings[$dayName]['end_time'] ?? "18:00";    // Use null coalescing operator (PHP 7+)
                        ?>
                        <tr>
                            <th scope="row"><?php echo htmlspecialchars($dayName); ?></th>
                            <td>
                                <input type="time"
                                       class="form-control"
                                       id="start_time_<?php echo $index; ?>"
                                       name="start_time_<?php echo $index; ?>"
                                       value="<?php echo htmlspecialchars($startTime); ?>">  <!--  Use consistent variable names -->
                            </td>
                            <td>
                                <input type="time"
                                       class="form-control"
                                       id="end_time_<?php echo $index; ?>"
                                       name="end_time_<?php echo $index; ?>"
                                       value="<?php echo htmlspecialchars($endTime); ?>">   <!-- Use consistent variable names -->
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-danger">Save Changes</button> <!-- Removed inline style -->
            </form>

            <?php if (isset($message) && $message != ""): ?><!--  Check for $message and that is not empty -->
                <div class="alert alert-warning mt-3">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include 'AI.php'; ?>       <!--  Ensure these files are correctly included and necessary -->
    <?php include 'Utilities/translate.php'; ?>  <!--  and that their paths are correct. -->
    <?php include 'Dashboard/footer.php'; ?> <!-- Consistent with header.php -->

<?php
//  Good practice to close the connection, BUT...
// ...make sure $conn is actually defined and a valid connection.
//  It's likely this is done in config.php, but double-check.
if (isset($conn) && is_object($conn) && method_exists($conn, 'close')) {
    $conn->close();
}
?>
</body>
</html>