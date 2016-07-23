<?php

class Config_Column_Set extends Config_Column {
    protected $usedResults = array();

    public function __construct($type, $length, $config) {
        parent::__construct($type, $length, $config);

        $this->length = explode(',', $this->length);
    }


    /**
     *
     * {@inheritDoc}
     * @see Config_Column::generateTestValue()
     */
    public function generateTestValue(Config_Database $database) {
        if (!empty($this->length)) {
            if ($this->isInUniqIndex()) {
                $n = 0;
                while ($n < self::MAX_UNIQ_ITERATIONS) {
                    $this->getRandomDateTime();
                    $s = $this->getStringRepresentation();
                    if (!isset($this->usedResults[$s])) {
                        $this->usedResults[$s] = 1;
                        return $s;
                    }
                    $n++;
                }
                echo 'Error: Can not generate unique set value'.PHP_EOL;
            } else {
                return $this->getStringRepresentation();
            }
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see Config_Column::getBindParameterPrefix()
     */
    public function getBindParameterPrefix() {
        return 's';
    }

    /**
     *
     * {@inheritDoc}
     * @see Config_Column::resetUniques()
     */
    public function resetUniques() {
        $this->usedResults = array();
    }

    protected function getStringRepresentation() {
        $result = array();
        for ($i=0; $i < rand(1, count($this->length)-1); $i++) {
            $s = $this->length[rand(0, count($this->length)-1)];
            $result[$s] = 1;
        }
        return implode(', ', array_keys($result));
    }
}