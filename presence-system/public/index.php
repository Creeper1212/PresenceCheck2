<?php 
// Main entry point for the application 
session_start(); 
require_once '../config/database.php'; 
require_once '../app/helpers/ErrorHandler.php'; 
ECHO ist ausgeschaltet (OFF).
// Simple routing 
$page = isset($_GET['page']) ? $_GET['page'] : 'home'; 
ECHO ist ausgeschaltet (OFF).
switch($page) { 
    case 'home': 
        include '../app/views/dashboard/index.php'; 
        break; 
    case 'checkin': 
        include '../app/views/dashboard/checkin.php'; 
        break; 
    case 'isheretoday': 
        include '../app/views/dashboard/isheretoday.php'; 
        break; 
    case 'time_settings': 
        include '../app/views/settings/time_settings.php'; 
        break; 
    case 'login': 
        include '../app/views/auth/login.php'; 
        break; 
    case 'logout': 
        include '../app/views/auth/logout.php'; 
        break; 
    default: 
        include '../app/views/dashboard/index.php'; 
        break; 
} 
