<?php

namespace EverydayTasks\GUI\Authentication;

use EverydayTasks\User;
use EverydayTasks\Util;
use Steampixel\Route;
use CodeShack\Template;

Route::clearRoutes();

/***************************** Default  **********************************************************/

Route::add('/', function(){
    if (isset($_SESSION["user"])) {
        header('Location: /activity/');
        return;
    }

    header('Location: /login/');
    return;
}, 'get');


/***************************** Logout **********************************************************/

Route::add('/logout', function(){
    if (isset($_SESSION["user"])) {
        unset($_SESSION["user"]);
        setcookie('username', "", time()-3600, "/");
        session_destroy();
    }

    header('Location: /');
    return;
}, 'get');

/***************************** Registration ****************************************************/
Route::add('/register', function(){
    Template::view('Templates/intro.html', [
        "disable_bar" => true,
        "pagetitle" => "Register User",
        "register_prompt" => false
    ]);
}, 'get');

Route::add('/register', function(){
    $username = Util::sanitize(trim($_POST['un']));
    $password = $_POST['pw'];

    // validate username

    preg_match("/^[A-Za-z 0-9]+$/", $username, $output_array);

    if (empty($output_array)) {
        Template::view('Templates/intro.html', [
            "disable_bar" => true,
            "pagetitle" => "Register User",
            "register_prompt" => false,
            "notifications" => [
                ["type"=>"error", "message"=>"Username must only consist of letters A-Z, a-z, 0-9 and spaces"]
            ]
        ]);
        return;
    }

    // check user exists

    if (!empty(User::searchById(Util::$db, $username))) {
        Template::view('Templates/intro.html', [
            "disable_bar" => true,
            "pagetitle" => "Register User",
            "register_prompt" => false,
            "notifications" => [
                ["type"=>"error", "message"=>"Username exists"]
            ]
        ]);
        return;
    }

    $user = new User(
        Util::$db,
        $username, ""
    );
    $user->setPassword($password);

    $user->addToDatabase();
    header('Location: /login/');
}, 'post');

/****************************************** Login ******************************************/

Route::add('/login', function(){
    // skip login if user logged in
    if (isset($_SESSION["user"])) {
        if (!empty(User::searchById(Util::$db, $_SESSION["user"]))) {
            header('Location: /activity/');
        }
    }

    Template::view('Templates/intro.html', [
        "disable_bar" => true,
        "pagetitle" => "Login User",
        "register_prompt" => true
    ]);
}, 'get');

Route::add('/login', function(){
    // skip login if user logged in
    if (isset($_SESSION["user"])) {
        if (!empty(User::searchById(Util::$db, $_SESSION["user"]))) {
            header('Location: /activity/');
        }
    }

    $username = Util::sanitize(trim($_POST['un']));
    $password = $_POST['pw'];

    if (
        empty($username) ||
        empty($password)
    ) {
        Template::view('Templates/intro.html', [
            'notifications' => [
                ['type' => 'error',
                    'message' => 'Username or password empty.']
            ],
            "disable_bar" => true,
            "pagetitle" => "Login User",
            "register_prompt" => true
        ]);
        return;
    }

    $valid = false;
    $user = User::searchById(Util::$db, $username);

    if (!empty($user)) {
        if ($user->checkPassword($password)) {
            $valid = true;
        }
    }

    if (!$valid) {
        Template::view('Templates/intro.html', [
            'notifications' => [
                ['type' => 'error',
                    'message' => 'Username or password not valid.']
            ],
            "disable_bar" => true,
            "pagetitle" => "Login User",
            "register_prompt" => true
        ]);
        return;
    }

    // login
    $_SESSION["user"] = $user->getUsername();
    setcookie('username', $user->getUsername(), 0, '/');
    header('Location: /activity/');
}, 'post');
// Execute GUI route
Route::run('/');