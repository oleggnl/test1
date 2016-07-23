<?php

class Config_Column_Enum extends Config_Column {
    protected $usedResults = array();

    public function __construct($type, $length, $config) {
        parent::__construct($type, $length, $config);

        $this->length = explode(',', $this->length);
    }

    public function generateTestValue(Config_Database $database) {
        if (!empty($this->length)) {
            if ($this->isInUniqIndex()) {
                $n = 0;
                while ($n < self::MAX_UNIQ_ITERATIONS) {
                    $s = $this->length[rand(0, count($this->length)-1)];

                    if (!isset($this->usedResults[$s])) {
                        $this->usedResults[$s] = 1;
                        return $s;
                    }
                    $n++;
                }
                echo 'Error: Can not generate unique enum value'.PHP_EOL;
            } else {
                return 'Test String '.$this->name;
            }
        }
    }

    public function getBindParameterPrefix() {
        return 's';
    }

    public function resetUniques() {
        $this->usedResults = array();
    }
}