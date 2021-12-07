<?php
date_default_timezone_set('Asia/Jakarta');

// Only for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

function check_cookie() {
    if (!isset($_SESSION['user'])) {
        header('Location: /login/');
        die();
    }
}

// configuration
require_once 'config.php';

require_once 'Library/Util.php'; // various helper functions
require_once 'Library/ResponseCode.php'; // response code constants
require_once 'Library/Idempotency.php'; // idempotency feature

// components
require_once 'Models/Activity.php';
require_once 'Models/Category.php';
require_once 'Models/Task.php';
require_once 'Models/User.php';

// Routing and Templating
require_once 'Library/External/Route.php';
require_once 'Library/External/Template.php';

// Use library
use Steampixel\Route;
use EverydayTasks\Activity;
use EverydayTasks\Util;
use EverydayTasks\Idempotency;

// API
require_once 'API/Activity.php';
require_once 'API/Category.php';
require_once 'API/Task.php';

// GUI
require_once 'GUI/Activity.php';
require_once 'GUI/Task.php';
require_once 'GUI/Authentication.php';
?>
