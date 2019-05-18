<?php

class db
{
    var $host;
    var $user;
    var $pass;
    var $database;

    var $connection;
    var $errno;
    var $error;

    function __construct($host, $user, $pass, $database)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->database = $database;
        $this->connect();
    }

    function connect()
    {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->database);
        if ($this->connection->connect_errno) {
            echo "Sorry, this website is experiencing problems.";
            echo "Error: Failed to make a MySQL connection, here is why: \n";
            echo "Errno: " . $this->connection->connect_errno . "\n";
            echo "Error: " . $this->connection->connect_error . "\n";
            exit;
        }
    }

    function query($qry)
    {
        if (!$result = $this->connection->query($qry))
        {
            $this->errno = $this->connection->errno;
            $this->error = $this->connection->error;
            return false;
        }

        if (substr($qry, 0, 6) == 'SELECT' || substr($qry, 0, 4) == 'SHOW' || substr($qry, 0, 8) == 'DESCRIBE')
        {
            $returnArray = Array();
            while ($row = $result->fetch_assoc()) { $returnArray[] = $row; }
            $result->free();
            return $returnArray;
        }
        else return $result;
    }

    function extractColumn($array, $column)
    {
        $returnArray = Array();
        foreach($array as $value) $returnArray[] = $value[$column];
        return $returnArray;
    }

    function checkForDropTable($table, $db1Tables, $db2Tables)
    {
        if (in_array($table, $db1Tables) && !in_array($table, $db2Tables))
        {
            return "DROP TABLE `{$table}`";
        }
        else return FALSE;
    }

    function checkForCreateTable($table, $db1Tables, $db2Tables)
    {
        if (!in_array($table, $db1Tables) && in_array($table, $db2Tables))
        {
            $result = $this->query("SHOW CREATE TABLE `{$table}`");
            return $result[0]['Create Table'];
        }
        else return FALSE;
    }

    function checkForAllColumns($db1TableInfo, $db2TableInfo)
    {
        foreach($db1TableInfo as $db1Field)
        {
            foreach($db2TableInfo as $db2Field)
            {
                if ($db1Field['Field'] == $db2Field['Field']) return FALSE;
            }
        }

        $result = $this->query("SHOW CREATE TABLE `{$table}`");

        $retStr = "DROP TABLE `{$table}`";
        $retStr .= "<br /><br />{$result[0]['Create Table']}";

        return $retStr;
    }

    function checkForColumnsToDropAndCreate($table, $db1TableInfo, $db2TableInfo)
    {
        $results = Array();

        foreach($db1TableInfo as $db1Index => $db1Field)
        {
            foreach($db2TableInfo as $db2Index => $db2Field)
            {
                if ($db1Field['Field'] == $db2Field['Field'])
                {
                    $db1TableInfo[$db1Index]['Match'] = TRUE;
                    $db2TableInfo[$db2Index]['Match'] = TRUE;
                }
            }
        }

        foreach($db1TableInfo as $db1Index => $db1Field)
        {
            if (!isset($db1TableInfo[$db1Index]['Match']))
            {
                $results[] = "ALTER TABLE `{$table}` DROP `{$db1TableInfo[$db1Index]['Field']}`";
            }
        }

        foreach($db2TableInfo as $db2Index => $db2Field)
        {
            if (!isset($db2TableInfo[$db2Index]['Match']))
            {
                if ($db2TableInfo[$db2Index]['Null'] == 'NO') $null = " NOT NULL";
                else $null = "";

                if (isset($db2TableInfo[$db2Index - 1]))
                {
                    $afterField = $db2TableInfo[$db2Index - 1];
                    $after = " AFTER `{$afterField['Field']}`";
                }
                else $after = " FIRST";

                $results[] = "ALTER TABLE `{$table}` ADD `{$db2TableInfo[$db2Index]['Field']}` {$db2TableInfo[$db2Index]['Type']}{$null}{$after}";
            }
        }

        if (!empty($results)) return implode("<br /><br />", $results);
        else return FALSE;
    }

    function close()
    {
        $this->connection->close();
    }
}
