<?php
    use EverydayTasks\Util;
    use EverydayTasks\Activity;
    use EverydayTasks\ResponseCode;
    use EverydayTasks\Idempotency;
    use Steampixel\Route;

    /**
     * Return a representation of a generated activity.
     * @param Activity $activity
     */
    function convertActivityIntoApiArray(Activity $activity): array
    {
        // TODO: $self might not be /api/activity/<id>
        $self = '/api/activity/' . $activity->getID();

        http_response_code(ResponseCode::OK);
        $output = [
            'id' => $activity->getID(),
            'status' => 'Activity'
        ];
        $output = array_merge($output, $activity->toArray());

        // append links
        $output = array_merge($output, [
            'links' => [
                [
                    'href' => $self,
                    'rel' => 'self',
                    'method' => 'GET'
                ],
                [
                    'href' => $self,
                    'rel' => 'edit',
                    'method' => 'PATCH'
                ],
                [
                    'href' => $self,
                    'rel' => 'delete',
                    'method' => 'DELETE'
                ]
            ]
        ]);

        return $output;
    }

    /**
     * Return a JSON representation of a generated activity.
     * @param Activity $activity
     */
    function returnActivityJson(Activity $activity)
    {
        Util::jsonResponse(convertActivityIntoApiArray($activity));
    }

    // Read
    Route::add('/activity', function()
    {
        $activities = [];
        foreach (Activity::getAll(Util::$db) as $activity) {
            array_push($activities, convertActivityIntoApiArray($activity));
        }

        Util::jsonResponse($activities);
    }, 'get');

    // Read
    Route::add('/activity/([0-9a-z]+)', function($id)
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

    // Update or Edit
    Route::add('/activity/([0-9a-z]+)', function($id)
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
        if (
            isset($arguments->subject) &&
            ($activity->getSubject() != $arguments->subject  )
        ){
            $activity->setSubject($arguments->subject);
            $changed = true;
        }

        // Change activity description
        if (
            isset($arguments->description) &&
            ($activity->getDescription() != $arguments->description  )
        ){
            $activity->setDescription($arguments->description);
            $changed = true;
        }

        // update date and time
        if (isset($arguments->date_time)){
            $activity->date_time = DateTime::createFromFormat('Y-m-d H:i:s', $arguments->date_time);
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

    // Delete
    Route::add('/activity/([0-9a-z]+)', function($id)
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

    // Create
    Route::add('/activity', function()
    {
        $headers = Util::getHttpHeaders();

        // Detect optional idempotency token
        $idempotency =
            key_exists('Idempotency-Token', $headers)?
                $headers['Idempotency-Token'] :
                '';

        if (!empty($idempotency)) {
            if (!Idempotency::useKey($idempotency)) {
                // Idempotency key already used, don't create a new Activity
                http_response_code(ResponseCode::NOT_MODIFIED);
                return;
            }
        }

        // Prefer form data
        if (!empty($_POST)) {
            // check required form values
            // TODO: check for empty values
            if (
                !array_key_exists('subject', $_POST) ||
                !array_key_exists('description', $_POST)
            ) {
                http_response_code(ResponseCode::BAD_REQUEST);
                return;
            }

            if (array_key_exists('date_time', $_POST)) {
                try {
                    $date_time = new DateTime($_POST['date_time']);
                } catch (Exception) {
                    http_response_code(ResponseCode::BAD_REQUEST);
                    return;
                }
            } else {
                $date_time = new DateTime();
            }

            $activity = new Activity(
                Util::$db,
                bin2hex(random_bytes(4)),
                Util::sanitize($_POST['subject']),
                Util::sanitize($_POST['description']),
                $date_time
            );

            $activity->addToDatabase();
            returnActivityJson($activity);
            return;
        }

        // if not, use JSON
        if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
            $arguments = json_decode(file_get_contents('php://input'), true);

            // return bad request on empty array
            if (empty($arguments)) {
                http_response_code(ResponseCode::BAD_REQUEST);
                return;
            }

            // check required form values
            // TODO: check for empty values
            if (
                !array_key_exists('subject', $arguments) ||
                !array_key_exists('description', $arguments)
            ) {
                http_response_code(ResponseCode::BAD_REQUEST);
                return;
            }

            if (array_key_exists('date_time', $arguments)) {
                try {
                    $date_time = new DateTime($arguments['date_time']);
                } catch (Exception) {
                    http_response_code(ResponseCode::BAD_REQUEST);
                    return;
                }
            } else {
                $date_time = new DateTime();
            }

            $activity = new Activity(
                Util::$db,
                bin2hex(random_bytes(4)),
                Util::sanitize($arguments['subject']),
                Util::sanitize($arguments['description']),
                $date_time
            );

            $activity->addToDatabase();
            returnActivityJson($activity);
            return;
        }

        http_response_code(ResponseCode::BAD_REQUEST);
    }, 'post');
    // Execute API routes
    Route::run('/api');
?>