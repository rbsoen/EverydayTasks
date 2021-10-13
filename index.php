<?php
    // Only for debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    require_once 'config.php';

    // Needed libraries
    require_once 'library/external/Route.php';
    require_once 'library/Activity.php';
    require_once 'library/ResponseCode.php';
    require_once 'library/Util.php';

    // Use library
    use Steampixel\Route;
    use EverydayTasks\Activity;
    use EverydayTasks\Util;

    // activity API
    require_once 'api/Activity.php';

    // Run website
    Route::run('/');
?>