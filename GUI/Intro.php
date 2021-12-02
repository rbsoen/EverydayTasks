<?php

namespace EverydayTasks\GUI\Intro;

use EverydayTasks\Util;
use Steampixel\Route;
use CodeShack\Template;

Route::clearRoutes();

Route::add('/', function(){
    if (array_key_exists('username', $_COOKIE)) {
        header('Location: /activity/');
        return;
    }

    Template::view('Templates/intro.html', [
    ]);
}, 'get');

Route::add('/', function(){
    if (empty($_POST['username'])) {
        Template::view('Templates/intro.html', [
            'notifications' => [
                ['type' => 'error',
                'message' => 'You must input a username']
            ]
        ]);
        return;
    }

    setcookie('username', Util::sanitize($_POST['username']), time() + (10 * 365 * 24 * 60 * 60), '/');

    header('Location: /activity/');
}, 'post');

// Execute GUI route
Route::run('/');