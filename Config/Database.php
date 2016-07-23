<?php

class Config_Database {
    protected $dbHost;
    protected $dbName;
    protected $dbUser;
    protected $dbPassword;
    protected $tables = array();

    protected static $dbConnection = null;

    protected $tableGenerationStack;

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
                    $this->showConfig();
                    break;
                case 3:
                    $this->listTables();
                    break;
                case 4:
                    $this->configureTable();
                    break;
                case 5:
                    $this->fillTestData();
                    break;
                case 6:
                    break 2;
                default:
                    echo "Wrong answer. Select digit from 1 to 6";
            }
        }
    }

    /**
     * clear table info
     */
    public function clear() {
        while (count($this->tables)) {
            $table = array_shift($this->tables);
            unset($table);
        }
    }

    /**
     * reopen connection
     */
    public function reopenConnection() {
        $this->closeConnection();
        return $this->getConnection();
    }

    /**
     * close connection
     */
    public function closeConnection() {
        if (self::$dbConnection) {
            self::$dbConnection->close();
            self::$dbConnection = null;
        }
    }

    /**
     * get connection
     */
    public function getConnection() {
        if (!self::$dbConnection) {
            self::$dbConnection = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);;
        }

        return self::$dbConnection;
    }

    /**
     * read DB Structure
     */
    public function readDbStructure() {
        while (count($this->tables)) {
            $table = array_shift($this->tables);
            unset($table);
        }
        $this->tables = array();
        $link = $this->getConnection();

        if ($result = mysqli_query($link, "show tables")) {
            printf("Select returned %d rows.\n", mysqli_num_rows($result));
            while($row = $result->fetch_array())
            {
                $table = new Config_Table($this, $row[0]);
                $this->tables[] = $table;
            }

            mysqli_free_result($result);
        }
    }


    /**
     * get database name
     *
     * @return string
     */
    public function getDataBaseName() {
        return $this->dbName;
    }

    /**
     * print user menu
     */
    protected function printMenu() {
        echo <<<QQQ
1. Configure Database connection
2. Show Database connection
3. List Tables;
4. Configure Table
5. Fill Test data
6. Exit

QQQ;
    }

    /**
     * read database config parameters
     */
    protected function readConfig() {
        $this->dbHost = Reader_StdIn::readLine("DB Host Name: ");
        $this->dbName = Reader_StdIn::readLine("DB Name: ");
        $this->dbUser = Reader_StdIn::readLine("DB User: ");
        $this->dbPassword = Reader_StdIn::readLine("DB Password: ");

        $this->reopenConnection();
        $this->readDbStructure();
    }

    /**
     * show database connection parameters
     */
    protected function showConfig() {
        echo 'DB Host Name: '.$this->dbHost.PHP_EOL;
        echo 'DB Name: : '.$this->dbName.PHP_EOL;
        echo 'DB User: '.$this->dbUser.PHP_EOL;
        echo 'DB Password: '.$this->dbPassword.PHP_EOL;
    }

    /**
     * list tables
     */
    protected function listTables() {
        foreach ($this->tables as $index => $table) {
            echo ($index+1).'. '.$table.PHP_EOL;
        }
        echo PHP_EOL;
    }

    /**
     * configure paramaters for table
     */
    protected function configureTable() {
        $index = Reader_StdIn::readLine("select table ny index: ");
        if (($index >= 1) && ($index <= count($this->tables))) {
            $table = $this->tables[$index -1];
            $table->process();
        } else {
            echo 'Wrong answer. Select from 1 to '.count($this->tables).PHP_EOL;
        }
    }

    /**
     * fill tables
     * @throws Exception
     */
    protected function fillTestData() {
        foreach ($this->tables as $index => $table) {
            $this->tableGenerationStack = array($table->name);
            try {
                $table->generateTestValues();
            } catch (Exception $e) {
                echo "table name: ".$table->name.PHP_EOL;
                throw $e;
            }
        }
        echo PHP_EOL;
    }

    /**
     * fill data on required table
     *
     * @param string $schemaName
     * @param string $tableName
     * @throws Exception_Loop
     * @throws Exception
     */
    public function requireFillTestDataForTable($schemaName, $tableName) {
        if (in_array($tableName, $this->tableGenerationStack)) {
            throw new Exception_Loop('Loop in table references: '.implode(' -> ', $this->tableGenerationStack).' -> '.$tableName);
        } else {
            if ($schemaName != $this->dbName) {
                throw new Exception('Required filled data for table in another schema: '.$schemaName.'.'.$tableName);
            }
            $this->tableGenerationStack[] = $tableName;
            foreach ($this->tables as $table) {
                if ($table->name == $tableName) {
                    $table->generateTestValues();
                    break;
                }
            }
            array_pop($this->tableGenerationStack);
        }
    }
}