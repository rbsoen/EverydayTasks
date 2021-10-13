<?php
    use EverydayTasks\Util;
    use EverydayTasks\Activity;
    use EverydayTasks\ResponseCode;
    use Steampixel\Route;

    /**
     * Return a JSON representation of a generated activity.
     * @param Activity $activity
     */
    function returnActivityJson(Activity $activity)
    {
        // TODO: $self might not be /api/activity/<id>
        $self = $_SERVER['REQUEST_URI'];

        http_response_code(ResponseCode::OK);
        $output = [
            'id' => $activity->getID(),
            'status' => 'Activity'
        ];
        $output = array_merge($output, $activity->serialize());

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
        Util::jsonResponse($output);
    }

    // Create
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
         * find the requested activity and throw Not Found
         * otherwise
         */
        $activity = Activity::searchById(Util::$db, $id);

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

        $changed = false;

        if (
            isset($arguments->subject) &&
            ($activity->getSubject() != $arguments->subject  )
        ){
            $activity->setSubject($arguments->subject);
            $changed = true;
        }

        if (
            isset($arguments->description) &&
            ($activity->getDescription() != $arguments->description  )
        ){
            $activity->setDescription($arguments->description);
            $changed = true;
        }

        if (isset($arguments->date_time)){
            $activity->date_time = DateTime::createFromFormat('Y-m-d H:i:s', $arguments->date_time);
            $changed = true;
        }

        if ($changed) {
            // update the activity in the database
            $activity->replaceDatabaseEntry();
            returnActivityJson($activity);
        } else {
            http_response_code(ResponseCode::NOT_MODIFIED);
            returnActivityJson($activity);
        }
    }, 'put');

    // Execute API routes
    Route::run('/api');
?>