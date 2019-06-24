<?php

namespace sdbau {
    
    include_once(__DIR__."/queryResult.php");
    include_once(__DIR__."/query.php");
    include_once(__DIR__."/queryBuilder.php");
    include_once(__DIR__."/abstractTable.php");
    include_once(__DIR__."/abstractTranslatableTable.php");
    include_once(__DIR__."/databaseConnection.php");
    include_once(__DIR__."/driver.php");
    include_once(__DIR__."/mysqliDriver.php");
    
    function getDatabaseConnection ($hostname,$username,$password,$schema) {
        $driver = new \MysqliDriver();
        $connection = new \DatabaseConnection($hostname, $username, $password, $schema, $driver);
        $connection->connect();
        return $connection;
    }
    
    function init ($mappingDir,$objectDir,$customDir) {
        
        includeDirectory($objectDir);
        includeDirectory($customDir);
        
    }
    
    function includeDirectory ($directory) {
        if (($handle = opendir($directory)) !== false) {
            while (($entry = readdir($handle)) !== false) {
                if ($entry != "." && $entry != ".." && endsWith($entry, ".php")) {
                    include_once($directory."/".$entry);
                }
            }
        }
    }
    
    function endsWith ($string, $test) {
        $strlen = strlen($string);
        $testlen = strlen($test);
        if ($testlen > $strlen) {
            return false;
        }
        if ($testlen == 0) {
            return true;
        }
        return (substr($string, -$testlen) === $test);
    }
}