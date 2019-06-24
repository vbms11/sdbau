<?php 
include (__DIR__."/include.php");
include (__DIR__.'/template/templateTop.php'); 

function renderConnectView () {
    $username = isset($_POST["username"]) ? $_POST["username"] : "";
    $password = "";
    $hostname = isset($_POST["hostname"]) ? $_POST["hostname"] : "";
    $schema = isset($_POST["schema"]) ? $_POST["schema"] : "";
    ?>
    <h3>Import database</h3>
    <p>
        Login to a database to generate the mappings and objects for the data abstraction layer.
    </p>
    <?php
    if (isset($_SESSION["message"])) {
        echo "<span class='validateTips'>".$_SESSION["message"]."</span>";
    }
    ?>
    <form method="post" action="?action=connect">
        <div class="divTable">
            <div>
                <label>Hostname</label>
                <div><input type="text" name="hostname" value="<?php echo $hostname; ?>"></div>
            </div>
            <div>
                <label>Username</label>
                <div><input type="text" name="username" value="<?php echo $username; ?>"></div>
            </div>
            <div>
                <label>Password</label>
                <div><input type="password" name="password" value="<?php echo $password; ?>"></div>
            </div>
            <div>
                <label>Schema</label>
                <div><input type="text" name="schema" value="<?php echo $schema; ?>"></div>
            </div>
        </div>
        <hr/>
        <input type="submit">Connect</a>
    </form>
    <?php
}

function renderShowDatabaseInfo () {
    
    $tables = array();
    
    try {
        $connection = sdbau\getDatabaseConnection($_SESSION["hostname"],$_SESSION["username"],$_SESSION["password"],$_SESSION["schema"]);
        $tables = $connection->driver->getTableNames();
        
    } catch (Exception $ex) {
        $_SESSION["message"] = "Failed to connect to the database";
        renderConnectView();
        return;
    }
    if (count($tables) > 0) {
        renderSetDirectoryView($tables);
    } else {
        renderDatabaseEmptyView();
    }
}

function renderDatabaseEmptyView () {
    ?>
    <h3>Database Empty</h3>
    <p>The database seems to be empty!</p>
    <?php
}

function renderSetDirectoryView ($tables) {
    ?>
    <h3>Tables Found</h3>
    <p>The following tables were found int the database.</p>
    <p><?php echo implode(", ", $tables); ?></p>
    <p>Please set a name for the folder to generate the files.</p>
    <?php
    if (isset($_SESSION["message"])) {
        echo "<span class='validateTips'>".$_SESSION["message"]."</span>";
    }
    ?>
    <form method="post" action="?action=create">
        <div class="divTable">
            <div>
                <label>Folder Name</label>
                <div><input type="text" name="foldername" value=""></div>
            </div>
        </div>
        <hr/>
        <input type="submit">Create Files</a>
    </form>
    <?php
}


$action = isset($_GET["action"]) ? $_GET["action"] : null;

switch ($action) {
    
    case "connect";
        $username = isset($_POST["username"]) ? $_POST["username"] : "";
        $password = isset($_POST["password"]) ? $_POST["password"] : "";
        $hostname = isset($_POST["hostname"]) ? $_POST["hostname"] : "";
        $schema = isset($_POST["schema"]) ? $_POST["schema"] : "";
        $connection = null;
        try {
            $connection = sdbau\getDatabaseConnection($host,$username,$password,$schema);
            unset($_SESSION["message"]);
        } catch (Exception $ex) {
            $_SESSION["message"] = "Failed to connect to the database";
            renderConnectView();
        }
        $_SESSION["username"] = $username;
        $_SESSION["password"] = $password;
        $_SESSION["database"] = $database;
        $_SESSION["schema"] = $schema;
        renderShowDatabaseInfo();
        break;
    case "create":
        
        $folderName = isset($_POST["foldername"]) ? $_POST["foldername"] : "";
        
        if (is_dir(__DIR__."/../output/".$folderName)) {
            $_SESSION["message"] = "that output folder already exists!";
            renderShowDatabaseInfo();
            break;
        }
        
        mkdir(__DIR__."/../output/".$folderName);
        
        // create mappings and files
        
        break;
}
     
include (__DIR__.'../template/templateBottom.php'); ?>