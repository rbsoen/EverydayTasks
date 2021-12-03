<?php

namespace EverydayTasks\GUI\Authentication;

use EverydayTasks\Util;
use Steampixel\Route;
use CodeShack\Template;

Route::clearRoutes();

Route::add('/logout', function(){
    if (array_key_exists('username', $_COOKIE)) {
        if ($_COOKIE['username'] != '-') {
            setcookie('username', "", time()-3600, "/");
        }
    }

    header('Location: /');
    return;
}, 'get');

// Execute GUI route
Route::run('/');