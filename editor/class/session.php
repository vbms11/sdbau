<?php

class Session {
    
    const sessionKey = "session.key";
    const sessionIdName = "s";
    const sessionKeyName = "k";
    
    static function getSessionKeysString () {
        return isset($_SESSION[self::sessionKey]) ? $_SESSION[self::sessionKey]."-".session_id() : "";
    }

    static function getSessionKeysArray () {
        return isset($_SESSION[self::sessionKey]) ? array("k"=>$_SESSION[self::sessionKey],session_name()=>session_id()) : array();
    }
    
    static function start () {
        
        $noError = true;
        $keysValid = true;
        $sessionValid = false;
        
        // get php session key param
        $sessionId = null;
        if (isset($_COOKIE[self::sessionIdName])) {
            $sessionId = $_COOKIE[self::sessionIdName];
        } else if (isset($_GET[self::sessionIdName])) {
            $sessionId = $_GET[self::sessionIdName];
        } else if (isset($_POST[self::sessionIdName])) {
            $sessionId = $_POST[self::sessionIdName];
        }
        
        // get session key param
        $sessionKey = null;
        if (isset($_COOKIE["k"])) {
            $sessionKey = $_COOKIE["k"];
        } else if (isset($_GET["k"])) {
            $sessionKey = $_GET["k"];
        } else if (isset($_POST["k"])) {
            $sessionKey = $_POST["k"];
        }
        
        // validate keys
        if (empty($sessionId)  || strlen($sessionId)  != 40 || 
            empty($sessionKey) || strlen($sessionKey) != 40) {
            
            // session keys are invalid
            $keysValid = false;
            $sessionValid = false;
            
        } else {

            // try starting session
            $sessionValid = self::startSession("s",$sessionId);
            
            if ($sessionValid == true) {
                // check if session valid
                $sessionValid = self::checkSessionKey($sessionKey);
            }
            
            // if session invalid destroy session
            if ($sessionValid == false) {
                
                self::endSession($sessionId);
            }
        }
        
        // if session is invalid create session
        if ($keysValid == false || $sessionValid == false) {
            
            // create session keys
            $sessionId = self::generateSessionId();
            $sessionKey = Session::generateSessionKey();
            
            // start session
            self::startSession(self::sessionIdName, $sessionId);
            $_SESSION["session.started"] = $sessionKey;
	    
            // set session key
            setcookie(self::sessionKeyName,$sessionKey, 0, "/");
        }
        
        // if user authcode log user in
        if (isset($_GET["userAuthKey"])) {
            
            UsersModel::loginWithKey($_GET["userAuthKey"]);
        }
    }
    
    static function startSession ($sessionName,$sessionId) {
        // try starting the session
        session_name($sessionName);
        session_id($sessionId);
        return session_start();
    }

    static function endSession ($sessionId) {
        session_unset();
        session_destroy();
        session_write_close();
        //session_regenerate_id();
    }
    
    static function checkSessionKey ($sessionKey) {
        return isset($_SESSION[self::sessionKey]) && $_SESSION[self::sessionKey] == $sessionKey;
    }
    
    static function clear () {
        
    }
    
    static function generateSessionId () {
        return sha1(md5(rand()).md5(time));
    }
    
    static function generateSessionKey () {
        return sha1(md5(rand()).md5(time));
    }
}

?>