<?php

    namespace EverydayTasks\API\Activity;

    use DateTime;
    use EverydayTasks\Category;
    use EverydayTasks\Util;
    use EverydayTasks\Activity;
    use EverydayTasks\ResponseCode;
    use EverydayTasks\Idempotency;
    use Steampixel\Route;
    use CodeShack\Template;

    Route::clearRoutes();

    Route::add('/', function(){
        Template::view('Templates/activities.html', [
        ]);
    }, 'get');

    // Execute GUI route
    Route::run('/activity');
?>
