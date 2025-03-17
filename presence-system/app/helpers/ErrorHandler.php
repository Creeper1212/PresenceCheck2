<?php
/**
 * Centralized error handler for the presence system
 * 
 * @param string $errorType Type of error (Database, Validation, System, etc)
 * @param string $message Detailed error message
 * @param string|null $redirectTo Optional URL to redirect to
 * @param bool $displayToUser Whether to display this error to the user
 */
function handleError($errorType, $message, $redirectTo = null, $displayToUser = true) {
    // Log the error to server logs
    error_log("[$errorType] $message");
    
    // Store error in session for displaying to user if needed
    if ($displayToUser) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error_type'] = $errorType;
        $_SESSION['error_message'] = $message;
    }
    
    // Redirect if specified
    if ($redirectTo) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Display error message if it exists in session
 * 
 * @return string HTML for error message or empty string if no error
 */
function displayError() {
    if (isset($_SESSION['error_type']) && isset($_SESSION['error_message'])) {
        $type = htmlspecialchars($_SESSION['error_type']);
        $message = htmlspecialchars($_SESSION['error_message']);
        
        // Clear error from session
        unset($_SESSION['error_type']);
        unset($_SESSION['error_message']);
        
        return "<div class='alert alert-danger' role='alert'>
                    <strong>$type Error:</strong> $message
                </div>";
    }
    return "";
}