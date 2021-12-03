<?php
    namespace EverydayTasks;

    /**
     * Various functions to assist in other functions.
     */
    class Util
    {
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
        public static function sanitize($string): string
        {
            return trim(htmlspecialchars(strip_tags($string)));
        }

        /**
         * Because PHP does not reliably return $_GET as expected,
         * this function is needed to extract HTTP GET parameters from
         * the URL.
         *
         * @return array GET params as an array
         */
        public static function getParams(): array
        {
            /* A hack to read GET params manually
            */
            preg_match_all('/[?&]([^=]+)=([^&]+)/', $_SERVER['REQUEST_URI'], $co, PREG_SET_ORDER);
            $get = [];
            foreach ($co as $param) {
                $param_name = self::sanitize($param[1]);
                $param_value = self::sanitize($param[2]);
                $get[$param_name] = $param_value;
            }
            return $get;
        }

        /**
         * Shortcut to print out a JSON response
         * @param array $response PHP array
         */
        public static function jsonResponse(array $response)
        {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
        }

        /**
         * Extract HTTP headers
         * @return array
         */
        public static function getHttpHeaders(): array
        {
            $formatted_headers = [];
            foreach ($_SERVER as $header => $value){
                if (substr($header, 0, 5) == 'HTTP_') {
                    $header = substr($header, 5);

                    $header_words = explode('_', $header);
                    for ($i = 0; $i < count($header_words); $i++) {
                        $header_words[$i] = ucfirst(strtolower($header_words[$i]));
                    }

                    $header = implode('-', $header_words);

                    $formatted_headers[$header] = $value;
                }
            }
            return $formatted_headers;
        }

        /**
         * Return a representation of an object.
         * @param string $self The object's URL
         * @param array $object An array representation of the object
         * @param string $id The object's ID
         * @param string $object_name Object's class name
         */
        public static function convertIntoApiArray(string $self, array $object, string $id, string $object_name): array
        {
            http_response_code(ResponseCode::OK);
            $output = [
                'id' => $id,
                'status' => $object_name
            ];
            $output = array_merge($output, $object);

            // append links
            return array_merge($output, [
                'links' => [
                    'self' => [
                        'href' => $self,
                        'method' => 'GET'
                    ],
                    'edit' => [
                        'href' => $self,
                        'method' => 'PUT'
                    ],
                    'delete' => [
                        'href' => $self,
                        'method' => 'DELETE'
                    ]
                ],
            ]);
        }
    }
?>