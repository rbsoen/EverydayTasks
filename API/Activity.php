<?php

    namespace EverydayTasks\API\Activity;

    use DateTime;
    use EverydayTasks\Category;
    use EverydayTasks\Util;
    use EverydayTasks\Activity;
    use EverydayTasks\ResponseCode;
    use EverydayTasks\Idempotency;
    use Steampixel\Route;

    Route::clearRoutes();

    /**
     * Return a representation of a generated activity.
     * @param Activity $activity
     */
    function convertActivityIntoApiArray(Activity $activity): array
    {
        $activity_array = Util::convertIntoApiArray(
            '/api/activity/' . $activity->getID(),
            $activity->toArray(),
            $activity->getID(),
            'Activity'
        );

        // modify the array to make the category a link instead
        // add link
        if (!empty($activity_array['category'])) {
            $activity_array['links']['category'] = [
                'id' => $activity->getCategory()->getID(),
                'href' => '/api/category/' . $activity->getCategory()->getID(),
                'method' => 'GET'
            ];
        }
        // remove original key
        unset($activity_array['category']);

        return $activity_array;
    }

    /**
     * Return a JSON representation of a generated activity.
     * @param Activity $activity
     */
    function returnActivityJson(Activity $activity)
    {
        Util::jsonResponse(convertActivityIntoApiArray($activity));
    }

    /*
     * Create an activity from an input array and inserts it into the database
     */
    function activityFromArray(array $array) {
        $category = null;
        $username = null;

        // subject and description is required
        if (
            !array_key_exists('subject', $array) ||
            !array_key_exists('description', $array)
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

        // date_time is optional
        if (array_key_exists('date_time', $array)) {
            try {
                $date_time = new DateTime($array['date_time']);
            } catch (Exception) {
                http_response_code(ResponseCode::BAD_REQUEST);
                return;
            }
        } else {
            $date_time = new DateTime();
        }

        // category is optional
        if (array_key_exists('category', $array)) {
            $category = Category::searchById(Util::$db, $array['category']);
        }

        // username is optional
        if (array_key_exists('username', $array)) {
            $username = $array['username'];
        }

        // create activity and add it to the database
        $activity = new Activity(
            Util::$db,
            bin2hex(random_bytes(4)),
            Util::sanitize($array['subject']),
            Util::sanitize($array['description']),
            $date_time,
            $category,
            $username
        );

        $activity->addToDatabase();
        return returnActivityJson($activity);
    }

    /*
     * Read all activities
     *
     * GET arguments:
     * for: Gets activities for a certain date. Possible values:
     *          - today: Activities from the current day.
     */
    Route::add('/', function()
    {
        $activities = [];

        $activity_query = Activity::getCustom(
            Util::$db,
            '1=1 order by date_time desc',
            []
        );

        $get = Util::getParams();

        if (array_key_exists('for', $get)) {
            switch ($get['for']) {
                case 'today':
                    $today = new DateTime();
                    $activity_query = Activity::getCustom(
                        Util::$db,
                        'date(date_time) = ? order by date_time desc',
                        [$today->format('Y-m-d')]
                    );
                    break;
                default:
                    break;
            }
        }

        if (array_key_exists('username', $get)) {
            $activity_query = Activity::getCustom(
                Util::$db,
                'username=?',
                [Util::sanitize($get['username'])]
            );
        }

        foreach ($activity_query as $activity) {
            array_push($activities, convertActivityIntoApiArray($activity));
        }

        Util::jsonResponse($activities);
    }, 'get');

    // Read one activity
    Route::add('/([0-9a-f]+)', function($id)
    {
        // Set default state
        http_response_code(ResponseCode::NOT_FOUND);

        // check if activity exists
        $activity = Activity::searchById(Util::$db, $id);

        /*
         * If activity exists, return OK, display data as
         * well as REST links
         */
        if (isset($activity)) returnActivityJson($activity);
    }, 'get');

    // Update or Edit activity
    Route::add('/([0-9a-f]+)', function($id)
    {
        /*
         * Request has a body, that MUST be JSON
         * (specified by using the header "Content-Type: application/json")
         */
        if ($_SERVER['CONTENT_TYPE'] != 'application/json') {
            http_response_code(ResponseCode::BAD_REQUEST);
            return;
        }

        /*
         * Find the requested activity and throw Not Found
         * otherwise
         */
        $activity = Activity::searchById(Util::$db, $id);

        /**
         * Return not found if activity does not exist
         */
        if (empty($activity)){
            http_response_code(ResponseCode::NOT_FOUND);
            return;
        }

        // attempt to decode request body
        $arguments = json_decode(file_get_contents('php://input'));

        // if it does not produce a valid array, return Bad Request
        if (empty($arguments)) {
            http_response_code(ResponseCode::BAD_REQUEST);
            return;
        }

        // default state is unchanged
        $changed = false;

        // Change activity subject
        if (isset($arguments->subject)){
            // subject must not be empty
            if (empty($arguments->subject)) {
                http_response_code(ResponseCode::BAD_REQUEST);
                return;
            }
            $activity->setSubject($arguments->subject);
            $changed = true;
        }

        // Change activity description
        if (isset($arguments->description)){
            $activity->setDescription($arguments->description);
            $changed = true;
        }

        // update date and time
        if (isset($arguments->date_time)){
            $activity->date_time = DateTime::createFromFormat('Y-m-d H:i:s', $arguments->date_time);
            $changed = true;
        }

        // update category
        if (isset($arguments->category)){
            $category = Category::searchById(Util::$db, $arguments->category);
            $activity->setCategory($category);
            $changed = true;
        }

        // update the activity in the database
        if ($changed) {
            $activity->replaceDatabaseEntry();
        } else {
            http_response_code(ResponseCode::NOT_MODIFIED);
        }
        returnActivityJson($activity);

    }, 'put');

    // Delete activity
    Route::add('/([0-9a-f]+)', function($id)
    {
        /*
         * Find the requested activity and throw Not Found
         * otherwise
         */
        $activity = Activity::searchById(Util::$db, $id);

        /**
         * Return not found if activity does not exist
         */
        if (empty($activity)){
            http_response_code(ResponseCode::NOT_FOUND);
            return;
        }

        $activity->deleteFromDatabase();

        // OK
        http_response_code(ResponseCode::NO_CONTENT);
    }, 'delete');

    // Create activity
    Route::add('/', function()
    {
        if (!Idempotency::useKeyFromHttp()) {
            http_response_code(ResponseCode::NOT_MODIFIED);
            return;
        }

        // Prefer form data
        if (!empty($_POST)) {
            return activityFromArray($_POST);
        }

        // if not, use JSON
        if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
            $arguments = json_decode(file_get_contents('php://input'), true);

            // return bad request on empty array
            if (empty($arguments)) {
                http_response_code(ResponseCode::BAD_REQUEST);
                return;
            }

            return activityFromArray($arguments);
        }

        http_response_code(ResponseCode::BAD_REQUEST);
    }, 'post');

    // Execute API routes
    Route::run('/api/activity');
?>
