<?php

$hostname = "localhost";
$username = "root";
$password = "";
$schema = "vbmscms";

$dbLink = mysqli_connect($hostname,$username,$password);
if (false !== $dbLink && true === mysqli_select_db($dbLink,$schema)) {
    
} else {
    echo "could not connect to database!";
}

// tables
// SELECT DISTINCT TABLE_NAME FROM information_schema.columns WHERE table_schema='vbmscms'
/*
$result = mysqli_query($dbLink, "select table_name from information_schema.tables where table_schema = database()");
if ($result === false) {
    echo mysqli_error($dbLink);
    exit;
}

$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
print_r($rows);
exit;
if (count($rows) > 0) {
    $columns = array_keys($rows[0]);
    ?>
    <table><thead><tr>
    
       <td>TABLE_NAME</td>
                                     
    </tr></thead>
    <tbody>
    <?php
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($columns as $column) {
            echo "<td>".$row[$column]."</td>";
        }
        echo "</tr>";
    }
    ?>
    </tbody></table>
    <?php
} else {
    echo "no results!";
}

*/





//$result = mysqli_query($dbLink, "SELECT column_name, data_type, column_default, is_nullable, numeric_precision, character_maximum_length from information_schema.columns WHERE table_schema='vbmscms' and table_name='t_page'");
$result = mysqli_query($dbLink, "SELECT extra from information_schema.columns WHERE table_schema='vbmscms' and table_name='t_page'");

if ($result === false) {
    echo mysqli_error($dbLink);
    exit;
}

$rows = mysqli_fetch_all($result,MYSQLI_ASSOC);
if (count($rows) > 0) {
    $columns = array_keys($rows[0]);
    ?>
    <table><thead><tr>
    ´   <?php
        foreach (array_keys($rows[0]) as $name) {
            echo "<td>$name</td>";
        }
        ?>
    </tr></thead>
    <tbody>
    <?php
    foreach ($rows as $row) {
        echo "<tr>";
        foreach (array_keys($row) as $key) {
            echo "<td>".$row[$key]."</td>";
        }
        echo "</tr>";
    }
    ?>
    </tbody></table>
    <?php
} else {
    echo "no results!";
}
