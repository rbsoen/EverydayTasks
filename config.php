<?php
    require_once 'library/Util.php';

    use EverydayTasks\Util;

    $DATABASE_SETTINGS = [
        "server" => "localhost",
        "db"     => "everydaytasks",
        "user"   => "everydaytasks",
        "pass"   => "everydaytasks"
    ];

    try {
        Util::$db = new PDO(
            'mysql:host='.$DATABASE_SETTINGS['server'].
            ';dbname='.$DATABASE_SETTINGS['db'].
            ';charset=utf8mb4',

            $DATABASE_SETTINGS['user'],

            $DATABASE_SETTINGS['pass'],

            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die("Error, PDO ".$e->getCode()." : ".$e->getMessage());
    }
?>