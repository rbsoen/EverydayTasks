<?php

    namespace EverydayTasks\API\Category;

    use EverydayTasks\Util;
    use EverydayTasks\Category;
    use EverydayTasks\ResponseCode;
    use EverydayTasks\Idempotency;
    use Steampixel\Route;

    Route::clearRoutes();

    /**
     * Return a representation of a generated category.
     * @param Category $category
     */
    function convertCategoryIntoApiArray(Category $category): array
    {
        return Util::convertIntoApiArray(
            '/api/category/' . $category->getID(),
            $category->toArray(),
            $category->getID(),
            'Category'
        );
    }

    /**
     * Return a JSON representation of a generated activity.
     * @param Activity $activity
     */
    function returnCategoryJson(Category $category)
    {
        Util::jsonResponse(convertCategoryIntoApiArray($category));
    }

    /*
     * Create an category from an input array
     */
    function categoryFromArray(array $array) {
        $category = null;

        // title and color is required
        if (
            !array_key_exists('title', $array) ||
            !array_key_exists('color', $array)
        ) {
            http_response_code(ResponseCode::BAD_REQUEST);
            return;
        }

        // create activity and add it to the database
        $category = new Category(
            Util::$db,
            bin2hex(random_bytes(4)),
            Util::sanitize($array['title']),
            (int) ($array['color'])
        );

        $category->addToDatabase();
        return returnCategoryJson($category);
    }

    // Read all categories
    Route::add('', function()
    {
        $categories = [];
        foreach (Category::getAll(Util::$db) as $category) {
            array_push($categories, convertCategoryIntoApiArray($category));
        }

        Util::jsonResponse($categories);
    }, 'get');

    // Read one category
    Route::add('/([0-9a-f]+)', function($id)
    {
        // Set default state
        http_response_code(ResponseCode::NOT_FOUND);

        // check if activity exists
        $category = Category::searchById(Util::$db, $id);

        /*
         * If activity exists, return OK, display data as
         * well as REST links
         */
        if (isset($category)) returnCategoryJson($category);
    }, 'get');

    // Delete category
    Route::add('/([0-9a-f]+)', function($id)
{
    /*
     * Find the requested activity and throw Not Found
     * otherwise
     */
    $category = Category::searchById(Util::$db, $id);

    /**
     * Return not found if activity does not exist
     */
    if (empty($category)){
        http_response_code(ResponseCode::NOT_FOUND);
        return;
    }

    $category->deleteFromDatabase();

    // OK
    http_response_code(ResponseCode::NO_CONTENT);
}, 'delete');

    // Update or Edit category
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
            $category = Category::searchById(Util::$db, $id);

            /**
             * Return not found if activity does not exist
             */
            if (empty($category)){
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

            // Change category title
            if (isset($arguments->title)){
                $category->setTitle($arguments->title);
                $changed = true;
            }

            // Change category color
            if (isset($arguments->color)){
                $category->color = (int) $arguments->color;
                $changed = true;
            }

            // update the category in the database
            if ($changed) {
                $category->replaceDatabaseEntry();
            } else {
                http_response_code(ResponseCode::NOT_MODIFIED);
            }
            returnCategoryJson($category);
        }, 'put');

    // Create category
    Route::add('/', function()
    {
        if (!Idempotency::useKeyFromHttp()) {
            http_response_code(ResponseCode::NOT_MODIFIED);
            return;
        }

        // Prefer form data
        if (!empty($_POST)) {
            return categoryFromArray($_POST);
        }

        // if not, use JSON
        if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
            $arguments = json_decode(file_get_contents('php://input'), true);

            // return bad request on empty array
            if (empty($arguments)) {
                http_response_code(ResponseCode::BAD_REQUEST);
                return;
            }

            return categoryFromArray($arguments);
        }

        http_response_code(ResponseCode::BAD_REQUEST);
    }, 'post');

    // Execute API routes
    Route::run('/api/category');
?>
