<?php 

    namespace EverydayTasks;
    
    use PDO;
    use DateTime;
    
    class Category
    {
        private PDO $db;
        private string $id;
        private string $title;
        public int $color;
        
        /**
         * @param \PDO $db Database used
         * @param string $id Unique ID
         * @param string $title
         * @param string $description Activity description
         * @param \DateTime $date_time When the activity was committed
         */
        public function __construct(PDO $db, string $id, string $title, int $color)
        {
            $this->db = $db;
            $this->id = $id;
            $this->title = $title;
            $this->color = $color;
        }

        // generic getter functions
        public function getTitle(): string { return $this->title; }
        public function getID(): string { return $this->id; }

        // setter functions with sanitizing
        public function setTitle(string $title) { $this->title = Util::sanitize($title); }
        
        /**
         * Inserts the Category object to the actual database
         */
        public function addToDatabase()
        {
            $query = $this->db->prepare('
                insert into categories(id, title, color)
                values (:id, :title, :color);
            ');
            $query->execute($this->toArray());
        }

        /**
        * Replaces an existing entry in the database with one of the same ID
        * @return bool Whether or not replacement succeeded
        */
        public function replaceDatabaseEntry(): bool {
            $query = $this->db->prepare('
                update categories
                    set title=:title, color=:color
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
                delete from categories
                    where id = :id
            ');
            $query->execute(['id'=>$this->id]);
            return (bool)$query->rowCount();
        }

        /**
         * Transforms an Category object into an array for use with
         * PDO::execute.
         * @return array
         */
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'title' => $this->title,
                'color' => $this->color
            ];
        }
        
        /**
         * Converts from Array or PDORow result to a PHP Category object.
         * @param mixed $result Fetch result
         * @return Category
         */
        public static function toCategory(PDO $db, array|object $result): Category
        {
            if (gettype($result) == 'array') {
                return new Category(
                    $db,
                    $result['id'],
                    $result['title'],
                    $result['color']
                    );
            } else {
                return new Category(
                    $db,
                    $result->id,
                    $result->title,
                    $result->color
                    );
            }
        }

        /**
         * Shortcut to search a category by an ID
         * @param PDO $db Target database, must have the "activities" table.
         * @param string $id The category ID
         * @return Category|null Category, if there is one with the requested ID
         */
        public static function searchById(PDO $db, string $id): Category | null
        {
            $query = $db->prepare('
                select * from categories where id=?
            ');
            $query->execute([$id]);

            $result = $query->fetch();

            return $result
                ? self::toCategory($db, $result)   // if result is present
                : null;                             // if result not found
        }

        /**
         * Shortcut to fetch all categories available in the database
         * @param PDO $db Target database, must have the "categories" table.
         * @return array All categories objects
         */
        public static function getAll(PDO $db): array
        {
            $categories = [];
            $query = $db->prepare('
                select * from categories
            ');
            $query->execute();

            while ($result = $query->fetch()) {
                array_push($categories, self::toCategory($db, $result));
            }
            return $categories;
        }
    }

?>