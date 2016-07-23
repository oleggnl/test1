<?php

class Config_Table {
    public $name;

    protected $database;

    protected $columns = array();
    protected $indexes = array();
    protected $generateRowsCount = 0;
    protected static $maxTableNameLength = 0;


    public function __construct(Config_Database $database, $tableName) {
        $this->database = $database;
        $this->name = $tableName;

        if (($len = strlen($tableName)) > self::$maxTableNameLength) {
            self::$maxTableNameLength = $len;
        }

        $this->readTableInfo();
        $q = null;
        $q = array('classification__category', 'classification__category_audit');
        if (!in_array($tableName, $q)) {
            $this->generateRowsCount = 3;
        }
    }

    public function __destruct() {
        $this->clear();
    }

    /**
     * user menu loop
     */
    public function process() {
        while (true) {
            $this->printMenu();

            $answer = Reader_StdIn::readLine("Select ");

            switch ($answer) {
                case 1:
                    $this->readConfig();
                    break;
                case 2:
                    break 2;
                default:
                    echo 'Wrong answer. Select digit from 1 to 2'.PHP_EOL;
            }
        }
    }

    /**
     * print menu
     */
    protected function printMenu() {
        echo <<<QQQ
1. Set rows count for generation
2. Exit

QQQ;
    }

    /**
     * clear
     */
    public function clear() {
        while (count($this->columns)) {
            $item = array_shift($this->columns);
            unset($item);
        }
        while (count($this->indexes)) {
            $item = array_shift($this->indexes);
            unset($item);
        }
    }

    /**
     * generate test values
     */
    public function generateTestValues() {
        $generatedRows = array();
        if ($this->generateRowsCount > 0) {
            echo "------ test records for table ".$this->name.PHP_EOL;

            $link = $this->database->getConnection();

            $columns = array();
            $valuesNames = array();
            $bindPrefix = '';
            foreach ($this->columns as $column) {
                if (!$column->isAutoIncrement()) {
                    $columns[$column->name] = $column;
                    $valuesNames[] = '?';
                    $bindPrefix .= $column->getBindParameterPrefix();
                    $column->resetUniques();
                }
            }
            $fieldsNames = array_keys($columns);
            $query = 'INSERT INTO '.$this->name. ' ('.implode(', ', $fieldsNames). ') VALUES ('.implode(', ', $valuesNames).')';
            for ($i = 0; $i < $this->generateRowsCount; $i++) {
                $stmt = $link->prepare($query);
                /** @var mysqli_stmt $stmt */
                $values = array();
                $refValues = array($bindPrefix);
                foreach ($columns as $column) {
                    $values[$column->name] = $column->generateTestValue($this->database);
                    $refValues[] = &$values[$column->name];
                }
                $generatedRows[] = $values;
                call_user_func_array(array($stmt, 'bind_param'), $refValues);
                if (!$stmt->execute()) {
                    foreach ($stmt->error_list as $err) {
                        printf('SQL ERROR: errno: %d sqlstate: %s message: %s'.PHP_EOL, $err['errno'], $err['sqlstate'], $err['error']);
                    }
                }
                $stmt->close();
            }

            //out inserted rows
            foreach ($generatedRows as $row) {
                foreach ($row as $name => $value) {
                    echo " $name: $value";
                }
                echo PHP_EOL;
            }
            echo PHP_EOL;
        }
    }

    /**
     * get object as string
     * @return string
     */
    public function __toString() {
        $count = 0;
        $link = $this->database->getConnection();

        if ($result = $link->query('select count(*) as amount from '.$this->name)) {
            if ($row = $result->fetch_assoc()) {
                $count = $row['amount'];
            }
            $result->close();
        }
        return str_pad($this->name, self::$maxTableNameLength).' records count('.$count.') test values rows count: '.$this->generateRowsCount;
    }

    /**
     * read table config from user
     */
    protected function readConfig() {
        $this->generateRowsCount = Reader_StdIn::readLine("How many test records will generate: ");
        if ($this->generateRowsCount < 0) {
            echo 'Wrong value.';
            $this->generateRowsCount = 0;
        }
    }

    /**
     * read table info
     */
    public function readTableInfo() {
        $this->clear();
        $this->readColumns();
        $this->readIndexes();
        $this->readKeyColumnsUsage();
    }

    /**
     * read columns info
     *
     * @throws Exception
     */
    protected function readColumns() {
        $link = $this->database->getConnection();

        if (!($result = $link->query('show columns from '.$this->name))) {
            throw new Exception("Error on statement preparation");
        } else {
            while ($row = $result->fetch_assoc()) {
                if (preg_match('/(\w+)\((\d+)\)/', $row['Type'], $matches)) {
                    //normal type and size
                    $type = $matches[1];
                    $length = $matches[2];
                } elseif (preg_match('/^(.+)\((.+)\)$/', $row['Type'], $matches)) {
                    //enum like syntax
                    $type = $matches[1];
                    $length = $matches[2];
                } else {
                    $type = $row['Type'];
                    $length = null;
                }
                //used type and length for skip call preg_match with same parameters
                $className = Config_Column::getClassForType($type, $length);
                if ($className) {
                    $column = new $className($type, $length, $row);
                    $this->columns[$column->name] = $column;
                } else {
                    echo "ERROR: unknown type {$row['Type']}\n";
                    continue;
                }
            }
            $result->close();
        }
    }

    /**
     * read indexes
     *
     * @throws Exception
     */
    protected function readIndexes() {
        $link = $this->database->getConnection();

        if (!($result = $link->query('show index from '.$this->name))) {
            throw new Exception("Error on statement preparation");
        } else {
            while ($row = $result->fetch_assoc()) {
                $index = new Config_Index($row);
                $this->indexes[] = $index;

                if (isset($this->columns[$index->columnName])) {
                    $column = $this->columns[$index->columnName];
                    $column->addUsedInIndexes($index);
                }
            }
            $result->close();
        }
    }


    /**
     * read foreign keys info
     *
     * @throws Exception
     */
    protected function readKeyColumnsUsage() {
        $link = $this->database->getConnection();

        $query = 'select column_name, referenced_table_schema, referenced_table_name, referenced_column_name from '
                .'information_schema.key_column_usage where constraint_schema=? and table_name=?';
        $stmt = $link->prepare($query);
        if (!$stmt) {
            throw new Exception("Error on statement preparation");
        } else {
            $stmt->bind_param("ss", $this->database->getDataBaseName(), $this->name);

            $stmt->execute();

            $stmt->bind_result($columnName, $referencedTableSchema, $referencedTableName, $referencedColumnName);

            while ($stmt->fetch()) {
                if (isset($this->columns[$columnName])) {
                    $column = $this->columns[$columnName];
                    if ($referencedTableSchema && $referencedTableName && $referencedColumnName) {
                        $column->addReferenceInfo($referencedTableSchema, $referencedTableName, $referencedColumnName);
                    }
                }
            }

            $stmt->close();
        }
    }
}