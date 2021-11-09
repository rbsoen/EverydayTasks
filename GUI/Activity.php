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

    /*
     * View activities for the current day
     */
    Route::add('/', function(){
        $today = new DateTime();
        $activities_today = Activity::getCustom(
            Util::$db,
            'date(date_time) = ? order by date_time desc',
            [$today->format('Y-m-d')]
        );

        Template::view('Templates/activities.html', [
            'activities' => $activities_today,
            'is_view_today' => true,
            'today' => $today
        ]);
    }, 'get');

    /*
     * View all activities currently logged
     */
    Route::add('/all', function(){
        $today = new DateTime();
        $activities = Activity::getAll(Util::$db);

        Template::view('Templates/activities.html', [
            'activities' => $activities,
            'is_view_today' => false
        ]);
    }, 'get');

    /*
     * View details of one activity
     */
    Route::add('/([0-9a-f]{8})/', function($id){

    });


    // Execute GUI route
    Route::run('/activity');
?>
