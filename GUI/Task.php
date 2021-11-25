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
            'activity is null order by -due desc',
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

    /*
     * Edit an activity
     */
    Route::add('/([0-9a-f]{8})/edit', function($id){
        $task = Task::searchById(Util::$db, $id);
        $category_list = Category::getAll(Util::$db);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // handle invalid activity
            if (empty($task)) {
                Template::view('Templates/tasks_edit.html', [
                    'page_title' => 'Task edit',
                    'page_heading' => 'Edit Task',
                    'task' => $task
                ]);
                return;
            }

            // Apply changes
            $task->setSubject($_POST['subject']);
            $task->setDescription($_POST['description']);

            if (!empty($_POST['due'])) {
                $task->due = new DateTime($_POST['due']);
            }

            $category = Category::searchById(Util::$db, $_POST['category']);
            $task->setCategory($category);
            if ($task->replaceDatabaseEntry()){
                // redirect to main page
                header('Location: /task/');
                return;
            }
            Template::view('Templates/tasks_edit.html', [
                'page_title' => 'Task edit',
                'page_heading' => 'Edit Task',
                'task' => $task,
                'category_list' => $category_list,
                'notifications' => [
                    ['type'=>'error', 'message'=>'Could not edit task, or task already modified.']
                ]
            ]);
            return;
        }

        // GET
        Template::view('Templates/tasks_edit.html', [
            'page_title' => 'Task edit',
            'page_heading' => 'Edit Task',
            'task' => $task,
            'category_list' => $category_list
        ]);
    }, ['get', 'post']);

    /*
     * Delete a task
     */
    Route::add('/([0-9a-f]{8})/delete', function($id){
        $task = Task::searchById(Util::$db, $id);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($task->deleteFromDatabase()) {
                header('Location: /task/');
                return;
            }

            Template::view('Templates/activities_delete.html', [
                'page_title' => 'Task delete',
                'page_heading' => 'Delete Task',
                'activity' => $task,
                'notifications' => [
                    ['type'=>'error', 'message'=>'Could not delete task']
                ]
            ]);
            return;
        }

        // GET
        Template::view('Templates/activities_delete.html', [
            'page_title' => 'Task delete',
            'page_heading' => 'Delete Task',
            'activity' => $task
        ]);
    }, ['get', 'post']);

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

Route::add('/([0-9a-f]{8})/finish', function($id){
    // chek for task existing
    $task = Task::searchById(Util::$db, $id);

    if (empty($task)) {
        Template::view('Templates/tasks_edit.html', [
            'page_title' => 'Tasks edit',
            'page_heading' => 'Finish Task',
            'task' => $task
        ]);
        return;
    }

    //
    $category_list = Category::getAll(Util::$db);

    // create base activity
    $activity = new Activity(
        Util::$db,
        bin2hex(random_bytes(4)),
        '',
        '',
        new DateTime(),
        null
    );

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $array = $_POST;
        // subject and description cannot be empty
        if (
            empty($array['subject']) ||
            empty($array['description'])
        ) {
            Template::view('Templates/activities_edit.html', [
                'page_title' => 'Activity edit',
                'page_heading' => 'Finish Task "'.$task->getSubject().'"',
                'activity' => $activity,
                'category_list' => $category_list,
                'notifications' => [
                    ['type'=>'error', 'message'=>'Subject or description must not be empty']
                ]
            ]);
            return;
        }

        // category is optional
        if (array_key_exists('category', $array)) {
            $activity->setCategory(
                Category::searchById(Util::$db, $array['category'])
            );
        }

        $activity->setSubject(Util::sanitize($array['subject']));
        $activity->setDescription(Util::sanitize($array['description']));
        $activity->addToDatabase();
        $task->setActivity($activity);
        $task->replaceDatabaseEntry();
        header('Location: /task/');
        return;
    }

    Template::view('Templates/activities_edit.html', [
        'page_title' => 'Activity edit',
        'page_heading' => 'Finish Task "'.$task->getSubject().'"',
        'activity' => $activity,
        'category_list' => $category_list
    ]);
}, ['get', 'post']);


    // Execute GUI route
    Route::run('/task');
?>
