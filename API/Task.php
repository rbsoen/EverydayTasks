<?php

namespace EverydayTasks\API\Task;

use DateTime;
use EverydayTasks\Activity;
use EverydayTasks\Category;
use EverydayTasks\Util;
use EverydayTasks\Task;
use EverydayTasks\ResponseCode;
use EverydayTasks\Idempotency;
use Steampixel\Route;

Route::clearRoutes();

/**
 * Return a representation of a generated task.
 * @param Task $task
 * @return array
 */
function convertTaskIntoAPIArray(Task $task): array
{
    $activity_array = Util::convertIntoApiArray(
        '/api/task/' . $task->getID(),
        $task->toArray(),
        $task->getID(),
        'Task'
    );

    // modify the array to make the category and activity a link instead
    // add link
    if (!empty($activity_array['category'])) {
        $activity_array['links']['category'] = [
            'id' => $task->getCategory()->getID(),
            'href' => '/api/category/' . $task->getCategory()->getID(),
            'method' => 'GET'
        ];
    }

    if (!empty($activity_array['activity'])) {
    $activity_array['links']['activity'] = [
        'id' => $task->getActivity()->getID(),
        'href' => '/api/activity/' . $task->getActivity()->getID(),
        'method' => 'GET'
    ];
}
    // remove original key
    unset($activity_array['category']);
    unset($activity_array['activity']);
    return $activity_array;
}

/**
 * Return a JSON representation of a generated task.
 * @param Task $task
 */
function returnTaskJson(Task $task)
{
    Util::jsonResponse(convertTaskIntoAPIArray($task));
}

/*
 * Create an task from an input array and inserts it into the database
 */
function taskFromArray(array $array) {
    $category = null;
    $description = "";
    $activity = null;
    $due = null;

    // subject is required
    if (
        !array_key_exists('subject', $array)
    ) {
        http_response_code(ResponseCode::BAD_REQUEST);
        return;
    }


    // subject cannot be empty
    if (
        empty($array['subject'])
    ) {
        http_response_code(ResponseCode::BAD_REQUEST);
        return;
    }

    if (key_exists("description", $array)) {
        $description = $array["description"] = Util::sanitize($array["description"]);
    }

    // due date and time is optional
    if (array_key_exists('due', $array)) {
        try {
            $due = new DateTime($array['due']);
        } catch (Exception) {
            http_response_code(ResponseCode::BAD_REQUEST);
            return;
        }
    } else {
        $due = new DateTime();
    }

    // category is optional
    if (array_key_exists('category', $array)) {
        $category = Category::searchById(Util::$db, $array['category']);
    }

    // activity is optional
    if (array_key_exists('activity', $array)) {
        $activity = Activity::searchById(Util::$db, $array['activity']);
    }

    // create activity and add it to the database
    $task = new Task(
        Util::$db,
        bin2hex(random_bytes(4)),
        Util::sanitize($array['subject']),
        $description,
        $due,
        $category,
        $activity
    );

    $task->addToDatabase();
    return returnTaskJson($task);
}

/*
 * Read all tasks
 *
 * GET arguments:
 * for: Gets tasks for a certain date. Possible values:
 *          - today: Activities from the current day.
 */
Route::add('/', function()
{
    $tasks = [];

    $task_query = Task::getCustom(
        Util::$db,
        '1=1 order by due desc',
        []
    );

    $get = Util::getParams();

    if (array_key_exists('for', $get)) {
        switch ($get['for']) {
            case 'today':
                $today = new DateTime();
                $task_query = Task::getCustom(
                    Util::$db,
                    'date(date_time) = ? order by date_time desc',
                    [$today->format('Y-m-d')]
                );
                break;
            default:
                break;
        }
    }

    foreach ($task_query as $task) {
        array_push($tasks, convertTaskIntoAPIArray($task));
    }

    Util::jsonResponse($tasks);
}, 'get');

// Read one task
Route::add('/([0-9a-f]+)', function($id)
{
    // Set default state
    http_response_code(ResponseCode::NOT_FOUND);

    // check if activity exists
    $task = Task::searchById(Util::$db, $id);

    /*
     * If activity exists, return OK, display data as
     * well as REST links
     */
    if (isset($task)) returnTaskJson($task);
}, 'get');

// Update or Edit task
Route::add('/([0-9a-f]+)', function($id)
{
    if ($_SERVER['CONTENT_TYPE'] != 'application/json') {
        http_response_code(ResponseCode::BAD_REQUEST);
        return;
    }

    $task = Task::searchById(Util::$db, $id);

    if (empty($task)){
        http_response_code(ResponseCode::NOT_FOUND);
        return;
    }

    $arguments = json_decode(file_get_contents('php://input'));

    if (empty($arguments)) {
        http_response_code(ResponseCode::BAD_REQUEST);
        return;
    }

    // default state is unchanged
    $changed = false;

    // Change subject
    if (isset($arguments->subject)){
        // subject must not be empty
        if (empty($arguments->subject)) {
            http_response_code(ResponseCode::BAD_REQUEST);
            return;
        }
        $task->setSubject($arguments->subject);
        $changed = true;
    }

    // Change activity description
    if (isset($arguments->description)){
        $task->setDescription($arguments->description);
        $changed = true;
    }

    // update date and time
    if (isset($arguments->due)){
        $task->due = DateTime::createFromFormat('Y-m-d H:i:s', $arguments->due);
        $changed = true;
    }

    // update category
    if (isset($arguments->category)){
        $category = Category::searchById(Util::$db, $arguments->category);
        $task->setCategory($category);
        $changed = true;
    }

    // update activity
    if (isset($arguments->activity)){
        $activity = Activity::searchById(Util::$db, $arguments->activity);
        $task->setActivity($activity);
        $changed = true;
    }

    // update the activity in the database
    if ($changed) {
        $task->replaceDatabaseEntry();
    } else {
        http_response_code(ResponseCode::NOT_MODIFIED);
    }
    returnTaskJson($task);
}, 'put');

// Delete task
Route::add('/([0-9a-f]+)', function($id)
{
    /*
     * Find the requested activity and throw Not Found
     * otherwise
     */
    $task = Task::searchById(Util::$db, $id);

    /**
     * Return not found if activity does not exist
     */
    if (empty($task)){
        http_response_code(ResponseCode::NOT_FOUND);
        return;
    }

    $task->deleteFromDatabase();

    // OK
    http_response_code(ResponseCode::NO_CONTENT);
}, 'delete');

// Create task
Route::add('/', function()
{
    if (!Idempotency::useKeyFromHttp()) {
        http_response_code(ResponseCode::NOT_MODIFIED);
        return;
    }

    // Prefer form data
    if (!empty($_POST)) {
        return taskFromArray($_POST);
    }

    // if not, use JSON
    if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
        $arguments = json_decode(file_get_contents('php://input'), true);

        // return bad request on empty array
        if (empty($arguments)) {
            http_response_code(ResponseCode::BAD_REQUEST);
            return;
        }

        return taskFromArray($arguments);
    }

    http_response_code(ResponseCode::BAD_REQUEST);
}, 'post');

// Execute API routes
Route::run('/api/task');
?>
