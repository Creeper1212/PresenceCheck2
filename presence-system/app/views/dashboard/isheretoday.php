<?php
// Purpose: Display a list of users who have checked in today.

// Include required files
require '../../config/database.php';      // Database configuration
require '../../helpers/TimeHelper.php';  // Time range functionality
require_once '../../helpers/ErrorHandler.php'; // Error handling
include '../templates/header.php';      // Page header

// Get current date
$today = date('Y-m-d');

// Prepare and execute query to get today's presence records
try {
    $sql = "SELECT name, here, time FROM presencetable WHERE date = ?"; //Simplified Select Statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('s', $today);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
} catch (Exception $e) {
    handleError("Database Error", $e->getMessage(), null, true);
    $result = null; // Set result to null to avoid errors later
}

?>
<div class="flex-container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="welcome-section mb-4">
                    <h1>Presence</h1>
                    <p>Welcome to the presence system. Here you can see who is present today.</p>
                    <?php echo displayError(); // Display any errors ?>
                </div>
            </div>
        </div>
    </div>
<div class="container">
    <?php if ($result && $result->num_rows > 0): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <h2 class="mb-3">Today's Sign-ins</h2>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Status</th>
                            <th scope="col">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['here'] ? 'Present' : 'Not Present'; ?></td>
                            <td><?php echo htmlspecialchars($row['time']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($result): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info text-center" role="alert">
                No sign-ins today.
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php
        if ($result) {
            $stmt->close();
        }
     ?>
</div>

<?php include '../../views/templates/AI.php'; ?>
<?php include '../templates/translate.php'; ?>
<?php include '../templates/footer.php'; ?>
