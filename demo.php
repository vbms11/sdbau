<?php

include_once(__DIR__."/src/sdbau.php");

$host = "localhost";
$username = "root";
$password = "";
$schema = "sdbau";

try {
    
    // set directorys for mappings and generated table object classes
    sdbau\init(__DIR__."/database/mapping",__DIR__."/database/table",__DIR__."/database/custom");
    
    // get connection 
    $connection = sdbau\getDatabaseConnection($host,$username,$password,$schema);
    
    echo "connected to database: $schema<br/>";
    
    $connection->getObjectFactory()->generateClassesFromDatabase(__DIR__."/output/test/");
    /*
    // create the user table
    $connection->getTable(new UserTable())->create();
    $connection->getTable(new MessageTable())->create();
    
    echo "user table created<br/>";
    
    // create a user table object and save
    $user = $connection->getTable(new UserTable());
    $user->username = "vbms";
    $user->age = 33;
    $user->email = "silkyfx@gmail.com";
    $user->save();
    
    echo "created user id: ".$user->id."<br/>";
    
    $message = $connection->getTable(new MessageTable());
    $message->userid = $user->id;
    $message->message = "this is the message";
    $message->save();
    
    echo "created message id: ".$message->id."<br/>";
    
    $queryBuilder = $connection->getQueryBuilder(UserTable::getTableName(),"u");
    $queryBuilder->setFeilds("m.message as message, u.username as username");
    $queryBuilder->addJoin("left join",MessageTable::getTableName(),"m","m.userid=u.id");
    
    $query = $queryBuilder->getQuery();
    echo "<hr/>".$query->query."<hr/>";
    $result = $query->execute();
    
    $row = $result->get(0);
    
    echo "queryResult results: ".$result->size." username: ".$row["username"]." message: ".$row["message"];
    */
} catch (Exception $e) {
    echo "<hr/>".$e->getMessage()."<hr/>".$e->getTraceAsString();
    
}
/*
class TestTable {
    
    static function getTableName () {
        return "testTable";
    }
}

class Database {
    static function getTable ($type) {
        $tableName = $type::getTableName();
        return $tableName;
    }
}

echo Database::getTable(TestTable::getTableName());
*/


//$testTable = TestTable.getById(1);
//$testTable = TestTable.search(1);
//$testTable = TestTable.find(array("name"=>"vbms","active"=>"1"));







