<?php

class Config_Column_Integer extends Config_Column {
    protected $usedResults = array();

    /**
     *
     * {@inheritDoc}
     * @see Config_Column::generateTestValue()
     */
    public function generateTestValue(Config_Database $database) {
        if ($this->referencedColumnName && $this->referencedTableName && $this->referencedTableSchema) {
            if (!$this->refColumnValues) {
                $this->loadRefColumnValues($database);
            }
            if (empty($this->refColumnValues)) {
                if (!$this->nullable) {
                    throw new Exception(
                        sprintf(
                            'Empty table: %s.%s referenced by column %s from %s',
                            $this->referencedTableSchema, $this->referencedTableName, $this->referencedColumnName, $this->name
                        )
                    );
                } else {
                    return null;
                }
            }
            if ($this->isInUniqIndex()) {
                $n = 0;
                while ($n < self::MAX_UNIQ_ITERATIONS) {
                    $s = $this->refColumnValues[rand(0, count($this->refColumnValues)-1)];
                    if (!isset($this->usedResults[$s])) {
                        $this->usedResults[$s] = 1;
                        return $s;
                    }
                    $n++;
                }
                echo 'Error: Can not generate unique integer value'.PHP_EOL;
            } else {
                return $this->refColumnValues[rand(0, count($this->refColumnValues)-1)];
            }
        } else {
            if ($this->isInUniqIndex()) {
                $n = 0;
                while ($n < self::MAX_UNIQ_ITERATIONS) {
                    $s = rand(0, pow(2, $this->length));
                    if (!isset($this->usedResults[$s])) {
                        $this->usedResults[$s] = 1;
                        return $s;
                    }
                    $n++;
                }
                echo 'Error: Can not generate unique integer value'.PHP_EOL;
            } else {
                return rand(0, pow(2, $this->length));
            }
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see Config_Column::getBindParameterPrefix()
     */
    public function getBindParameterPrefix() {
        return 'i';
    }

    /**
     *
     * {@inheritDoc}
     * @see Config_Column::resetUniques()
     */
    public function resetUniques() {
        $this->usedResults = array();
    }
}