<?php

    namespace EverydayTasks\GUI\Task;

    use DateTime;
    use EverydayTasks\Category;
use EverydayTasks\Task;
use EverydayTasks\Util;
    use EverydayTasks\Activity;
    use Steampixel\Route;
    use CodeShack\Template;

    Route::clearRoutes();

    /*
     * View tasks left to be done
     */
    Route::add('/', function(){
        $tasks = Task::getCustom(
            Util::$db,
            'activity is null',
            []
        );

        Template::view('Templates/tasks.html', [
            'tasks' => $tasks,
            'is_view_today' => true
        ]);
    }, 'get');

    /*
     * View all tasks currently logged
     */
    Route::add('/all', function(){
        $tasks = Task::getCustom(
            Util::$db,
            '1=1 order by due desc',
            []
        );

        Template::view('Templates/tasks.html', [
            'tasks' => $tasks,
            'is_view_today' => false
        ]);
    }, 'get');

//    /*
//     * Edit an activity
//     */
//    Route::add('/([0-9a-f]{8})/edit', function($id){
//        $activity = Activity::searchById(Util::$db, $id);
//        $category_list = Category::getAll(Util::$db);
//
//        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//            // handle invalid activity
//            if (empty($activity)) {
//                Template::view('Templates/activities_edit.html', [
//                    'page_title' => 'Activity edit',
//                    'page_heading' => 'Edit Activity',
//                    'activity' => $activity
//                ]);
//                return;
//            }
//
//            // Apply changes
//            $activity->setSubject($_POST['subject']);
//            $activity->setDescription($_POST['description']);
//            $category = Category::searchById(Util::$db, $_POST['category']);
//            $activity->setCategory($category);
//            if ($activity->replaceDatabaseEntry()){
//                // redirect to main page
//                header('Location: /activity/');
//                return;
//            }
//            Template::view('Templates/activities_edit.html', [
//                'page_title' => 'Activity edit',
//                'page_heading' => 'Edit Activity',
//                'activity' => $activity,
//                'category_list' => $category_list,
//                'notifications' => [
//                    ['type'=>'error', 'message'=>'Could not edit activity, or activity already modified.']
//                ]
//            ]);
//            return;
//        }
//
//        // GET
//        Template::view('Templates/activities_edit.html', [
//            'page_title' => 'Activity edit',
//            'page_heading' => 'Edit Activity',
//            'activity' => $activity,
//            'category_list' => $category_list
//        ]);
//    }, ['get', 'post']);
//
//    /*
//     * Delete an activity
//     */
//    Route::add('/([0-9a-f]{8})/delete', function($id){
//        $activity = Activity::searchById(Util::$db, $id);
//
//        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//            if ($activity->deleteFromDatabase()) {
//                header('Location: /activity/');
//                return;
//            }
//
//            Template::view('Templates/activities_delete.html', [
//                'page_title' => 'Activity delete',
//                'page_heading' => 'Delete Activity',
//                'activity' => $activity,
//                'notifications' => [
//                    ['type'=>'error', 'message'=>'Could not delete activity']
//                ]
//            ]);
//            return;
//        }
//
//        // GET
//        Template::view('Templates/activities_delete.html', [
//            'page_title' => 'Activity delete',
//            'page_heading' => 'Delete Activity',
//            'activity' => $activity
//        ]);
//    }, ['get', 'post']);

/*
* Add a task
*/
Route::add('/add', function(){
   $category_list = Category::getAll(Util::$db);

   // create base task
   $task = new Task(
       Util::$db,
       bin2hex(random_bytes(4)),
       '',
       '',
       null,
       null,
       null
   );

   if ($_SERVER['REQUEST_METHOD'] == 'POST') {
       $array = $_POST;
       // subject cannot be empty
       if (empty($array['subject'])) {
           Template::view('Templates/tasks_edit.html', [
               'page_title' => 'Tasks edit',
               'page_heading' => 'New Task',
               'task' => $task,
               'category_list' => $category_list,
               'notifications' => [
                   ['type'=>'error', 'message'=>'Subject or description must not be empty']
               ]
           ]);
           return;
       }

       // category is optional
       if (array_key_exists('category', $array)) {
           $task->setCategory(
               Category::searchById(Util::$db, $array['category'])
           );
       }

       if (!empty($array["due"])) {
           $task->due = new DateTime($array["due"]);
       }

       $task->setSubject(Util::sanitize($array['subject']));
       $task->setDescription(Util::sanitize($array['description']));
       $task->addToDatabase();
       header('Location: /task/');
       return;
   }

    Template::view('Templates/tasks_edit.html', [
        'page_title' => 'Tasks edit',
        'page_heading' => 'New Task',
        'task' => $task,
        'category_list' => $category_list
    ]);
}, ['get', 'post']);


    // Execute GUI route
    Route::run('/task');
?>
