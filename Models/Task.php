<?php

namespace EverydayTasks;

use DateTime;
use PDO;

class Task {
    private PDO $db;
    private string $id;
    private string $subject;
    private string $description;
    public ?Category $category = null;
    public ?Activity $activity = null;
    private ?string $username = null;
    public ?DateTime $due = null;

    /**
     * @param PDO $db Database used
     * @param string $id Unique ID
     * @param string $subject Task name
     * @param string $description Task description
     * @param DateTime|null $due
     * @param Category|null $category The category to place the task in
     * @param Activity|null $activity The associated activity
     * @param string|null $username
     */
    public function __construct(
        PDO $db, string $id, string $subject, string $description,
        DateTime|null $due, Category|null $category=null, Activity|null $activity=null,
        string|null $username=null
    ){
        $this->db = $db;
        $this->id = $id;
        $this->subject = $subject;
        $this->description = $description;
        if ($due instanceof DateTime) $this->due = $due;
        if ($category instanceof Category) $this->category = $category;
        if ($activity instanceof Activity) $this->activity = $activity;
        $this->username = Util::sanitize($username);
    }

    // generic getter functions
    public function getSubject(): string { return $this->subject; }
    public function getDescription(): string { return $this->description; }
    public function getID(): string { return $this->id; }
    public function getUsername(): string|null { return $this->username; }

    // setter functions with sanitizing
    public function setSubject(string $subject) { $this->subject = Util::sanitize($subject); }
    public function setDescription(string $description) { $this->description = Util::sanitize($description); }

    /**
     * Inserts the object to the actual database
     */
    public function addToDatabase(){
        $query = $this->db->prepare('
                insert into tasks(id, subject, description, due, category, activity, username)
                values (:id, :subject, :description, :due, :category, :activity, :username);
            ');
        $query->execute($this->toArray());
    }

    /**
     * Replaces an existing entry in the database with one of the same ID
     * @return bool Whether or not replacement succeeded
     */
    public function replaceDatabaseEntry(): bool {
        $query = $this->db->prepare('
                update tasks
                    set subject=:subject, description=:description, due=:due,
                        category=:category, activity=:activity, username=:username
                where id = :id
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
                delete from tasks
                    where id = :id
            ');
        $query->execute(['id'=>$this->id]);
        return (bool)$query->rowCount();
    }

    /**
     * Shortcut to search task by an ID
     * @param PDO $db Target database, must have the "tasks" table.
     * @param string $id The task ID
     * @return Task|null If there is one with the requested ID
     */
    public static function searchById(PDO $db, string $id): Task | null {
        $query = $db->prepare('
                select * from tasks where id=?
            ');
        $query->execute([$id]);

        $result = $query->fetch();

        return $result
            ? self::toTask($db, $result)   // if result is present
            : null;                             // if result not found
    }

    /**
     * Shortcut to fetch all tasks available in the database
     * @param PDO $db Target database, must have the "tasks" table.
     * @return array All task objects
     */
    public static function getAll(PDO $db): array {
        $activities = [];
        $query = $db->prepare('
                select * from tasks
            ');
        $query->execute();

        while ($result = $query->fetch()) {
            array_push($activities, self::toTask($db, $result));
        }
        return $activities;
    }

    /**
     * Shortcut to fetch all tasks available in the database matching an SQL criteria.
     * (Potentially insecure)
     * @param PDO $db Target database, must have the "tasks" table.
     * @param string $query SQL query to use (as a prepared statement)
     * @param array $params Query parameters
     * @return array All task objects
     */
    public static function getCustom(PDO $db, string $query, array $params): array {
        $activities = [];
        $query = $db->prepare('
                select * from tasks where ' . $query . '
            ');
        $query->execute($params);

        while ($result = $query->fetch()) {
            array_push($activities, self::toTask($db, $result));
        }
        return $activities;
    }


    /**
     * Transforms an Task object into an array for use with
     * PDO::execute.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'due' => empty($this->due)? null : $this->due->format('Y-m-d H:i:s'),
            'category' =>
                ($this->category instanceof Category)
                    ? $this->category->getID()
                    : null,
            'activity' =>
                ($this->activity instanceof Activity)
                    ? $this->activity->getID()
                    : null,
            'username' => $this->username
        ];
    }

    /**
     * Converts from Array or PDORow result to a PHP Task object.
     * @param PDO $db
     * @param mixed $result Fetch result
     * @return Task
     */
    public static function toTask(PDO $db, array $result): Task
    {
        $category = is_null($result['category'])
            ? null
            : Category::searchById($db, $result['category']);
        $activity = is_null($result['activity'])
            ? null
            : Activity::searchById($db, $result['activity']);
        $due = empty($result['due'])? null : DateTime::createFromFormat('Y-m-d H:i:s', $result['due']);
        return new Task(
            $db,
            $result['id'],
            $result['subject'],
            $result['description'],
            $due,
            $category,
            $activity,
            $result['username']
        );
    }
}
