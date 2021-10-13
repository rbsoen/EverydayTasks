<?php
    namespace EverydayTasks;

    /**
     * Various functions to assist in other functions.
     */
    class Util{
        /**
         * @var \PDO database
         */
        public static \PDO $db;

        /**
         * Alias to quickly sanitize strings, helps prevent cross-site scripting.
         *
         * Example: "<hello>>hello" -> "&gt;hello"
         * @param $string String to sanitize
         * @return string Sanitized string
         */
        public static function sanitize($string)
        {
            return htmlspecialchars(strip_tags($string));
        }

        /**
         * Because PHP does not reliably return $_GET as expected,
         * this function is needed to extract HTTP GET parameters from
         * the URL.
         *
         * @return array GET params as an array
         */
        public static function getParams()
        {
            /* A hack to read GET params manually
            */
            preg_match_all('/[?&]([^=]+)=([^&]+)/', $_SERVER['REQUEST_URI'], $co, PREG_SET_ORDER);
            $get = [];
            foreach($co as $param){
                $param_name = self::sanitize($param[1]);
                $param_value = self::sanitize($param[2]);
                $get[$param_name] = $param_value;
            }
            return $get;
        }

        public static function jsonResponse(array $response)
        {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
        }
    }
?>