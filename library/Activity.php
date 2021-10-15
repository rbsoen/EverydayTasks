<?php

    namespace EverydayTasks;

    class Activity {
        private \PDO $db;
        private string $id;
        private string $subject;
        private string $description;
        public \DateTime $date_time;

        /**
         * @param \PDO $db Database used
         * @param string $id Unique ID
         * @param string $subject Activity name
         * @param string $description Activity description
         * @param \DateTime $date_time When the activity was committed
         */
        public function __construct(\PDO $db, string $id, string $subject, string $description, \DateTime $date_time)
        {
            $this->db = $db;
            $this->id = $id;
            $this->subject = $subject;
            $this->description = $description;
            $this->date_time = $date_time;
        }

        // generic getter functions
        public function getSubject(): string { return $this->subject; }
        public function getDescription(): string { return $this->description; }
        public function getID(): string { return $this->id; }

        // setter functions with sanitizing
        public function setSubject(string $subject) { $this->subject = Util::sanitize($subject); }
        public function setDescription(string $description) { $this->description = Util::sanitize($description); }

        /**
         * Inserts the Activity object to the actual database
         */
        public function addToDatabase(){
            $query = $this->db->prepare('
                insert into activities(id, subject, description, date_time)
                values (:id, :subject, :description, :date_time);
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
                    set subject=:subject, description=:description, date_time=:date_time
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
         * @param \PDO $db Target database, must have the "activities" table.
         * @param string $id The activity ID
         * @return Activity|null Activity, if there is one with the requested ID
         */
        public static function searchById(\PDO $db, string $id): Activity | null {
            $query = $db->prepare('
                select * from activities where id=?
            ');
            $query->execute([$id]);

            $result = $query->fetch();

            return $result
                ? self::toActivity($db, $result)   // if result is present
                : null;                             // if result not found
        }

        /**
         * Shortcut to fetch all activities available in the database
         * @param \PDO $db Target database, must have the "activities" table.
         * @return array All activity objects
         */
        public static function getAll(\PDO $db): array {
            $activities = [];
            $query = $db->prepare('
                select * from activities
            ');
            $query->execute();

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
                'date_time' => $this->date_time->format('Y-m-d H:i:s')
            ];
        }

        /**
         * Converts from Array or PDORow result to a PHP Activity object.
         * @param mixed $result Fetch result
         * @return Activity
         */
        public static function toActivity(\PDO $db, array|object $result): Activity
        {
            if (gettype($result) == 'array') {
                return new Activity(
                    $db,
                    $result['id'],
                    $result['subject'],
                    $result['description'],
                    \DateTime::createFromFormat('Y-m-d H:i:s', $result['date_time'])
                );
            } else {
                return new Activity(
                    $db,
                    $result->id,
                    $result->subject,
                    $result->description,
                    \DateTime::createFromFormat('Y-m-d H:i:s', $result->date_time)
                );
            }
        }
    }
?>