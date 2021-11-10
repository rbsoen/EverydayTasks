<?php
    // Only for debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // configuration
    require_once 'config.php';

    require_once 'Library/Util.php'; // various helper functions
    require_once 'Library/ResponseCode.php'; // response code constants
    require_once 'Library/Idempotency.php'; // idempotency feature

    // components
    require_once 'Library/Activity.php';
    require_once 'Library/Category.php';

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

    // GUI
    require_once 'GUI/Activity.php';
?>
