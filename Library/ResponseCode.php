<?php

    namespace EverydayTasks;

    /**
     * Constants for HTTP response codes
     */
    class ResponseCode {
        public const OK = 200;
        public const CREATED = 201;
        public const NO_CONTENT = 204;

        public const MOVED_PERMANENTLY = 301;
        public const NOT_MODIFIED = 304;

        public const BAD_REQUEST = 400;
        public const UNAUTHORIZED = 401;
        public const NOT_FOUND = 404;
        public const METHOD_NOT_ALLOWED = 405;

        public const SERVER_ERROR = 500;
    }
?>
