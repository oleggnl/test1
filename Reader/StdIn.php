<?php
class Reader_StdIn {
    protected static $stdInHandler = null;

    public static function open() {
        self::$stdInHandler = fopen("php://stdin","r");
    }

    public static function close() {
        if (self::$stdInHandler) {
            fclose(self::$stdInHandler);
        }
    }

    /**
     * read line from user
     *
     * @param string $message
     *
     * @return string
     */
    public static function readLine($message = null) {
        if (!self::$stdInHandler) {
            self::open();
        }
        if ($message) {
            echo $message;
        }
        $line = fgets(self::$stdInHandler);
        $line = trim($line);

        return $line;
    }
}
