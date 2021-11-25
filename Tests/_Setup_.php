<?php
require_once 'Library/Util.php';

use EverydayTasks\Util;

$DATABASE_SETTINGS = [
    "server" => "localhost",
    "db"     => "everyday_tests",
    "user"   => "everyday_tests",
    "pass"   => "everyday_tests"
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

// Clear all tables first
$table_names = Util::$db->query('
    select table_name from information_schema.tables
    where table_schema = "everyday_tests";
');

while ($name = $table_names->fetch()) {
    Util::$db->exec(
        "set foreign_key_checks = 0; " .
        "drop table " . $name["table_name"] . "; " .
        "set foreign_key_checks = 1;"
    );
}

// Then reconstruct all of the tables
Util::$db->exec(
    file_get_contents("init.sql")
);