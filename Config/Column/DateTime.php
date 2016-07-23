<?php

class Config_Column_DateTime extends Config_Column {
    protected $dateTime;
    protected $usedResults = array();

    /**
     *
     * {@inheritDoc}
     * @see Config_Column::generateTestValue()
     */
    public function generateTestValue(Config_Database $database) {
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
            echo 'Error: Can not generate unique date time value'.PHP_EOL;
        } else {
            $this->getRandomDateTime();
            return $this->getStringRepresentation();
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

    /**
     * get random date
     */
    protected function getRandomDateTime() {
        $this->dateTime = new \DateTime(date('Y-m-d H:i:s', rand(0, time())));
    }

    /**
     * get string representation
     *
     * @return string
     */
    protected function getStringRepresentation() {
        return $this->dateTime->format('Y-m-d H:i:s');
    }
}