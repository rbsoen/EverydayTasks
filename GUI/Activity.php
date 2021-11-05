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
        $today = new DateTime();
        $activities_today = Activity::getCustom(
            Util::$db,
            'date(date_time) = ?',
            [$today->format('Y-m-d')]
        );

        Template::view('Templates/activities.html', [
            'activities' => $activities_today,
            'today' => $today
        ]);
    }, 'get');

    // Execute GUI route
    Route::run('/activity');
?>
