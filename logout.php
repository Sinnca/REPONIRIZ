<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

// Include configurations
require_once 'config/constants.php';
require_once 'includes/session.php';

// Logout user
logout();
?>