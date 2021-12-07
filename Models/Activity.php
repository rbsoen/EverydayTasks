<?php

    namespace EverydayTasks;

    use DateTime;
    use PDO;

    class Activity {
        private PDO $db;
        private string $id;
        private string $subject;
        private string $description;
        private ?Category $category = null;
        private ?string $username = null;
        public DateTime $date_time;

        /**
         * @param PDO $db Database used
         * @param string $id Unique ID
         * @param string $subject Activity name
         * @param string $description Activity description
         * @param DateTime $date_time When the activity was committed
         * @param Category|null $category The category to place the activity in
         */
        public function __construct(
            PDO $db, string $id, string $subject, string $description, DateTime $date_time,
            Category|null $category=null, string|null $username=null
        ){
            $this->db = $db;
            $this->id = $id;
            $this->subject = $subject;
            $this->description = $description;
            $this->date_time = $date_time;
            $this->username = Util::sanitize($username);
            if ($category instanceof Category) $this->category = $category;
        }

        // generic getter functions
        public function getSubject(): string { return $this->subject; }
        public function getDescription(): string { return $this->description; }
        public function getID(): string { return $this->id; }
        public function getCategory(): Category|null { return $this->category; }
        public function getUsername(): string|null { return $this->username; }

        // setter functions with sanitizing
        public function setSubject(string $subject) { $this->subject = Util::sanitize($subject); }
        public function setDescription(string $description) { $this->description = Util::sanitize($description); }
        public function setCategory(Category|null $category) { $this->category = $category; }
        /**
         * Inserts the Activity object to the actual database
         */
        public function addToDatabase(){
            $query = $this->db->prepare('
                insert into activities(id, subject, description, date_time, category, username)
                values (:id, :subject, :description, :date_time, :category, :username);
            ');
            $query->execute($this->toArray());
        }

        /**
         * Replaces an existing entry in the database with one of the same ID
         * @return bool Whether or not replacement succeeded
         */
        public function replaceDatabaseEntry(): bool {
            $query = $this->db->prepare('
                update activities
                    set subject=:subject, description=:description, date_time=:date_time, category=:category,
                        username=:username
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
                delete from activities
                    where id = :id
            ');
            $query->execute(['id'=>$this->id]);
            return (bool)$query->rowCount();
        }

        /**
         * Shortcut to search an activity by an ID
         * @param PDO $db Target database, must have the "activities" table.
         * @param string $id The activity ID
         * @return Activity|null Activity, if there is one with the requested ID
         */
        public static function searchById(PDO $db, string $id): Activity | null {
            $result = self::getCustom($db, "id=:id", ["id"=>$id]);
            if (count($result) > 0)
                return $result[0];
            return null;
        }

        /**
         * Shortcut to fetch all activities available in the database
         * @param PDO $db Target database, must have the "activities" table.
         * @return array All activity objects
         */
        public static function getAll(PDO $db): array {
            return self::getCustom($db, "1=1", []);
        }

        /**
         * Shortcut to fetch all activities available in the database matching an SQL criteria.
         * (Potentially insecure)
         * @param PDO $db Target database, must have the "activities" table.
         * @param string $query SQL query to use (as a prepared statement)
         * @param array $params Query parameters
         * @return array All activity objects
         */
        public static function getCustom(PDO $db, string $query, array $params): array {
            $activities = [];
            $query = $db->prepare('
                select * from activities where ' . $query . '
            ');
            $query->execute($params);

            while ($result = $query->fetch()) {
                array_push($activities, self::toActivity($db, $result));
            }
            return $activities;
        }


        /**
         * Transforms an Activity object into an array for use with
         * PDO::execute.
         * @return array
         */
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'subject' => $this->subject,
                'description' => $this->description,
                'date_time' => $this->date_time->format('Y-m-d H:i:s'),
                'category' =>
                    ($this->category instanceof Category)
                        ? $this->category->getID()
                        : null,
                'username' => $this->username
            ];
        }

        /**
         * Converts from Array or PDORow result to a PHP Activity object.
         * @param array $result Fetch result
         * @return Activity
         */
        public static function toActivity(PDO $db, array $result): Activity
        {
            $category = is_null($result['category'])
                ? null
                : Category::searchById($db, $result['category']);
            return new Activity(
                $db,
                $result['id'],
                $result['subject'],
                $result['description'],
                DateTime::createFromFormat('Y-m-d H:i:s', $result['date_time']),
                $category,
                $result['username']
            );
        }
    }
?>