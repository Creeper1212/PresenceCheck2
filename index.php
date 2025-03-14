<?php
// Include the header file (essential for page structure)
require 'Dashboard/header.php';
?>

<main>
    <!-- Main content container -->
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>Home</h1>
                <p>Welcome to the home page. Here you can find information about the presence system.</p>
            </div>
        </div>
    </div>
    
    <!-- Include AI features (optional content) -->
    <?php include 'AI.php'; ?>
    
    <!-- Include translation utilities (optional content) -->
    <?php include 'Utilities/translate.php'; ?>
</main>

<?php
// Include the footer file (essential for page structure)
require 'Dashboard/footer.php';
?>