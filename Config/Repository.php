<?php

class Config_Repository {
    protected static $data = null;
    protected static $fileName = 'config.json';

    public static function readRepository() {
        if (file_exists(self::$fileName)) {
            $raw = file_get_contents(self::$fileName);
            self::$data = json_decode($raw, true);
        }
    }

    public static function writeRepository() {
        $raw = json_encode(self::$data);
        file_put_contents(self::$fileName, $raw);
    }

    public static function getData() {
        if (!self::$data) {
            self::readRepository();
        }
        return self::$data;
    }

    public static function setData($data) {
        self::$data = $data;
    }
}