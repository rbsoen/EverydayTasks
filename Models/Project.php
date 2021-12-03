<?php

namespace EverydayTasks;

use DateTime;
use PDO;

class Project
{
    private PDO $db;
    private string $id;
    private string $subject;
    private string $description;
    public ?DateTime $due = null;
    public ?Category $category = null;
    private float $last_order_number = 0;
    /*
     * [
     *  ["task" => Task(), "order" => 262.5],
     *  ["task" => Task(), "order" => 300.2],
     *  ["task" => Task(), "order" => 900],
     *  ...
     * ]
     */
    public array $tasks = [];

    public function __construct(
        PDO $db,
        string $id,
        string $subject,
        string|null $description,
        DateTime|null $due,
        Category|null $category
    ){
        $this->db = $db;
        $this->id = $id;
        $this->subject = $subject;
        $this->description = $description;
        if ($due instanceof DateTime) $this->due = $due;
        if ($category instanceof Category) $this->category = $category;
    }

    // generic getter functions
    public function getSubject(): string { return $this->subject; }
    public function getDescription(): string { return $this->description; }
    public function getID(): string { return $this->id; }

    // setter functions with sanitizing
    public function setSubject(string $subject) { $this->subject = Util::sanitize($subject); }
    public function setDescription(string $description) { $this->description = Util::sanitize($description); }
    public function setLastOrderNumber(float $order) { $this->last_order_number = $order; }

    /**
     * Inserts the object to the actual database
     */
    public function addToDatabase(){
        $query = $this->db->prepare('
                insert into projects(id, subject, description, due, category)
                values (:id, :subject, :description, :due, :category);
            ');
        $query->execute($this->toArray());
    }

    /**
     * Replaces an existing entry in the database with one of the same ID
     * @return bool Whether or not replacement succeeded
     */
    public function replaceDatabaseEntry(): bool {
        $query = $this->db->prepare('
                update projects
                    set subject=:subject, description=:description, due=:due,
                        category=:category
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
                delete from projects
                    where id = :id
            ');
        $query->execute(['id'=>$this->id]);
        return (bool)$query->rowCount();
    }

    /**
     * Shortcut to fetch all projects available in the database matching an SQL criteria.
     * (Potentially insecure)
     * @param PDO $db Target database, must have the "tasks" table.
     * @param string $query SQL query to use (as a prepared statement)
     * @param array $params Query parameters
     * @return array All project objects
     */
    public static function getCustom(PDO $db, string $query, array $params): array {
        $activities = [];
        $query = $db->prepare('
                select * from projects where ' . $query . '
            ');
        $query->execute($params);

        while ($result = $query->fetch()) {
            array_push($activities, self::toProject($db, $result));
        }
        return $activities;
    }

    /**
     * Shortcut to search projects by an ID
     * @param PDO $db Target database, must have the "projects" table.
     * @param string $id The project ID
     * @return Task|null If there is one with the requested ID
     */
    public static function searchById(PDO $db, string $id): Project | null {
        $result = self::getCustom($db, "id=?", ["id"=>$id]);
        if (count($result) > 0)
            return $result[0];
        return null;
    }

    /**
     * Shortcut to fetch all projects available in the database
     * @param PDO $db Target database, must have the "projects" table.
     * @return array All project objects
     */
    public static function getAll(PDO $db): array {
        return self::getCustom($db, "1=1", []);
    }

    /**
     * Transforms an Project object into an array for use with
     * PDO::execute.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'due' => $this->due->format('Y-m-d H:i:s'),
            'category' =>
                ($this->category instanceof Category)
                    ? $this->category->getID()
                    : null
        ];
    }

    /**
     * Converts from Array result to a PHP Project object.
     * @param PDO $db
     * @param array $result Fetch result
     * @return Project
     */
    public static function toProject(PDO $db, array $result): Project
    {
        $category = is_null($result['category'])
            ? null
            : Category::searchById($db, $result['category']);
        $project = new Project(
            $db,
            $result['id'],
            $result['subject'],
            $result['description'],
            DateTime::createFromFormat('Y-m-d H:i:s', $result['due']),
            $category
        );
        // get subprojects
        $query = $db->prepare('
                select * from subprojects where project=:id order by card_order asc
            ');
        $query->execute($result['id']);

        while ($subproject = $query->fetch()) {
            array_push($project->tasks, [
                'project' => Task::toTask($db, $subproject),
                'order' => $subproject['card_order']
            ]);
            $project->setLastOrderNumber(
                $subproject['card_order']
            );
        }

        return $project;
    }
}