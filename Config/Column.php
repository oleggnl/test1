<?php

abstract class Config_Column {
    const KEY_PRIMARY = 1;
    const KEY_UNIQUE = 2;
    const KEY_MUL = 3;

    const MAX_UNIQ_ITERATIONS=10000;

    public $name;
    public $type;
    public $length;
    public $collation;
    public $nullable;
    public $key;
    public $default;
    public $extra;
    public $privileges;
    public $comment;
    public $usedInIndexes;

    public $referencedTableSchema;
    public $referencedTableName;
    public $referencedColumnName;

    protected $refColumnValues = null;

    public function __construct($type, $length, array $config) {
        $this->usedInIndexes = array();

        $this->name = $config['Field'];
        $this->type = $type;
        $this->length = $length;
        $this->name = $config['Field'];
        $this->collation = (isset($config['Collation'])) ? $config['Collation']:null;
        $this->nullable = ($config['Null'] == 'YES');
        switch ($config['Key']) {
            case 'PRI':
                $this->key = self::KEY_PRIMARY;
                break;
            case 'UNI':
                $this->key = self::KEY_UNIQUE;
                break;
            case 'MUL':
                $this->key = self::KEY_MUL;
                break;
            default:
                $this->key = null;
        }
        $this->default = $config['Default'];
        $this->extra = ($config['Extra']) ? explode(',', $config['Extra']):array();
        $this->privileges = (isset($config['Privileges'])) ? explode(',', $config['Privileges']):array();
        $this->comment = (isset($config['Comment'])) ? $config['Comment']:null;

        $this->referencedTableSchema = null;
        $this->referencedTableName = null;
        $this->referencedColumnName = null;
    }

    /**
     * get class name for column by type and length
     *
     * @param string $type
     * @param mixed $length
     *
     * @return string|NULL
     */
    public static function getClassForType($type, $length) {
        $types = array(
            'BIT'       => 'Integer',
            'TINYINT'   => 'Integer',
            'SMALLINT'  => 'Integer',
            'MEDIUMINT' => 'Integer',
            'INT'       => 'Integer',
            'INTEGER'   => 'Integer',
            'BIGINT'    => 'Integer',
            'REAL'      => 'Float',
            'DECIMAL'   => 'Float',
            'NUMERIC'   => 'Float',
            'DOUBLE'    => 'Float',
            'DATE'      => 'Date',
            'TIME'      => 'Time',
            'TIMESTAMP' => 'DateTime',
            'DATETIME'  => 'DateTime',
            'YEAR'      => 'Integer',
            'CHAR'      => 'String',
            'VARCHAR'   => 'String',
            'VARBINARY' => 'String',
            'TINYBLOB'  => 'String',
            'MEDIUMBLOB'=> 'String',
            'LONGBLOB'  => 'String',
            'TINYTEXT'  => 'String',
            'TEXT'      => 'String',
            'MEDIUMTEXT'=> 'String',
            'LONGTEXT'=> 'String',
            'ENUM'      => 'Enum',
            'SET'       => 'Set',
            'JSON'      => 'String',
        );

        $typeParams = explode(' ', $type);
        $type = strtoupper(array_shift($typeParams));

        if (isset($types[$type])) {
            return 'Config_Column_'.$types[$type];
        } else {
            return null;
        }
    }

    public function __destruct() {
        $this->usedInIndexes = null;
    }

    /**
     * add index info for column
     *
     * @param Config_Index $index
     */
    public function addUsedInIndexes(Config_Index $index) {
        $this->usedInIndexes[] = $index;
    }

    /**
     * return indexes for column
     *
     * @return array|null
     */
    public function getUsedInIndexes() {
        return $this->usedInIndexes;
    }

    /**
     * check is column used in unique index
     *
     * @return boolean
     */
    public function isInUniqIndex() {
        if ($this->key == self::KEY_PRIMARY) {
            return true;
        } else {
            $fresult = false;
            foreach ($this->usedInIndexes as $index) {
                $fresult = $fresult || (!$index->nonUnique);
            }

            return $fresult;
        }
    }

    /**
     * check is auto_increment column
     *
     * @return boolean
     */
    public function isAutoIncrement() {
        return (in_array('auto_increment', $this->extra));
    }

    /**
     * add references info for column (now can process only single reference)
     *
     * @param unknown $referencedTableSchema
     * @param unknown $referencedTableName
     * @param unknown $referencedColumnName
     */
    public function addReferenceInfo($referencedTableSchema, $referencedTableName, $referencedColumnName) {
        $this->referencedTableSchema = $referencedTableSchema;
        $this->referencedTableName = $referencedTableName;
        $this->referencedColumnName = $referencedColumnName;
    }

    /**
     * load reference table
     *
     * @param Config_Database $database
     * @throws Exception_Loop
     * @throws Exception
     */
    protected function loadRefColumnValues(Config_Database $database) {
        $link = $database->getConnection();
        $count = 0;
        if ($result = $link->query('select count(*) as amount from '.$this->referencedTableName)) {
            if ($row = $result->fetch_assoc()) {
                $count = $row['amount'];
            }
            $result->close();
        }
        if ($count == 0) {
            //try load test data
            try {
                $database->requireFillTestDataForTable($this->referencedTableSchema, $this->referencedTableName);
            } catch (Exception_Loop $e) {
                if (!$this->nullable) {
                    throw $e;
                } else {
                    echo 'Non block loop detected: '.$e->getMessage().PHP_EOL;
                }
            }
        }

        $this->refColumnValues = array();

        $query = sprintf(
                'select distinct %s from %s.%s limit %d',
                $this->referencedColumnName,
                $this->referencedTableSchema,
                $this->referencedTableName,
                self::MAX_UNIQ_ITERATIONS
                );
        $stmt = $link->prepare($query);
        if (!$stmt) {
            throw new Exception("Error on statement preparation");
        } else {
            $stmt->execute();

            $stmt->bind_result($id);

            while ($stmt->fetch()) {
                $this->refColumnValues[] = $id;
            }

            $stmt->close();
        }
    }


    /**
     * get test value for column
     *
     * @param Config_Database $database
     */
    abstract public function generateTestValue(Config_Database $database);

    /**
     * return bind type
     */
    abstract public function getBindParameterPrefix();

    /**
     * reset unique sequencer
     */
    abstract public function resetUniques();
}