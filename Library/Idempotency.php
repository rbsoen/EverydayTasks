<?php

    namespace EverydayTasks;

    use DateTime;
    use DateTimeZone;
    use EverydayTasks\Util;

    class Idempotency {
        private const IDEMPOTENCY_FILE = "tmp/idempotency.json";

        private static array $keys;

        /**
         * Handle idempotency and expire old idempotency keys
         * @throws \Exception
         */
        private static function init(): void
        {
            // load array
            if (
                !file_exists(self::IDEMPOTENCY_FILE) ||
                !filesize(self::IDEMPOTENCY_FILE)
            ) {
                self::$keys = [];
                return;
            }
            self::$keys = json_decode(
                file_get_contents(self::IDEMPOTENCY_FILE),
            true
            );

            // check expiry date of idempotency token
            foreach (self::$keys as $key => $data) {
                $datetime = new DateTime($data['expires']['date']);
                $timezone = new DateTimeZone($data['expires']['timezone']);
                $datetime->setTimezone($timezone);

                if (new DateTime() > $datetime) {
                    unset(self::$keys[$key]);
                }
            }

            file_put_contents(self::IDEMPOTENCY_FILE, json_encode(self::$keys));
        }

        // Create idempotency token, default to 1 hour
        public static function useKey(string $key, int $ttl = 60*60): bool
        {
            self::init();
            if (!isset(self::$keys[$key])){
                $expiry_time = new DateTime('+'.$ttl.' seconds');
                self::$keys[$key] = ['expires' => $expiry_time];

                file_put_contents(self::IDEMPOTENCY_FILE, json_encode(self::$keys));
                return true;
            }
            return false;
        }
        
        public static function useKeyFromHttp(int $ttl = 60*60): bool
        {
            $headers = Util::getHttpHeaders();
            
            // Detect optional idempotency token
            $idempotency =
                key_exists('Idempotency-Token', $headers)
                    ? $headers['Idempotency-Token']         // key specified
                    : '';                                   // key unspecified
            
            if (!empty($idempotency)) {
                return self::useKey($idempotency, $ttl);
            }
            
            return true;
        }
    }