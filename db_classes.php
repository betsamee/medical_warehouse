<?php

abstract class DBHandler{
    public $_host;
    public $_port;
    public $_user;
    public $_password;
    
    public function __constructor($host,$port,$user,$password){
        $this->_host = $host;
        $this->_port = $port;
        $this->_user = $user;
        $this->_password = $password;
    }
}

Class MySQL_DBHandler extends DBHandler{
        
    public $_db;
    
    public function ConnectToDB($dbname){
       
        if(!$this->_db = mysql_connect($this->_host,$this->_user,$this_password))
            throw new Exception("DB Connection Error");
        
        if(!mysql_select_db($dbname,$db))
            throw new Exception("DB Connection Error");
        
        return 1;
    }
    
}

?>