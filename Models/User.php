<?php

namespace EverydayTasks;

use DateTime;
use PDO;

class User {
    private PDO $db;
    private string $username;
    private string $password;

    public function __construct(
        PDO $db, string $username, string $password
    ){
        $this->db = $db;
        $this->username = $username;
        $this->password = $password;
    }

    // generic getter functions
    public function getUsername(): string { return $this->username; }
    public function checkPassword($password): bool { return password_verify($password, $this->password); }

    // setter functions with sanitizing
    public function setUsername(string $username) { $this->username = Util::sanitize(trim($username)); }
    public function setPassword(string $password) { $this->password = password_hash($password, PASSWORD_DEFAULT); }

    /**
     * Inserts the object to the actual database
     */
    public function addToDatabase(){
        $query = $this->db->prepare('
                insert into users(name, password)
                values (:username, :password);
            ');
        $query->execute($this->toArray());
    }

    /**
     * Replaces an existing entry in the database with one of the same ID
     * @return bool Whether or not replacement succeeded
     */
    public function replaceDatabaseEntry(): bool {
        $query = $this->db->prepare('
                update users
                    set password=:password
                where name = :username
            ');
        $query->execute($this->toArray());
        return (bool)$query->rowCount();
    }

    /**
     * Deletes the entry from the database.
     * @return bool Whether or not deletion succeeded
     */
    public function deleteFromDatabase(): bool {
        $query = $this->db->prepare('
                delete from users
                    where name = :name
            ');
        $query->execute(['name'=>$this->username]);
        return (bool)$query->rowCount();
    }

    public static function searchById(PDO $db, string $username): User|null {
        $result = self::getCustom($db, "name=?", [$username]);
        if (count($result) > 0)
            return $result[0];
        return null;
    }

    public static function getAll(PDO $db): array {
        return self::getCustom($db, "1=1", []);
    }

    public static function getCustom(PDO $db, string $query, array $params): array {
        $activities = [];
        $query = $db->prepare('
                select * from users where ' . $query . '
            ');
        $query->execute($params);

        while ($result = $query->fetch()) {
            array_push($activities, self::toUser($db, $result));
        }
        return $activities;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password
        ];
    }

    public static function toUser(PDO $db, array $result): User
    {
        return new User(
            $db,
            $result['name'],
            $result['password']
        );
    }
}
