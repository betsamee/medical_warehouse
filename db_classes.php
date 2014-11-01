<?php

Abstract Class DBHandler{
    public $_host;
    public $_port;
    public $_user;
    public $_password;
    public $_dbname;
    public $_dblink;
      
    function __construct($host,$port,$user,$password){
        $this->_host = $host;
        $this->_port = $port;
        $this->_user = $user;
        $this->_password = $password;
    }
}

Class MySQL_DBHandler extends DBHandler{

    public function ConnectToDB($dbname){
       $this->_dbname = $dbname;
       
        if(!$this->_dblink = mysqli_connect($this->_host,$this->_user,$this->_password,$this->_dbname))
            {
                throw new Exception("DB Connection Error 1".mysqli_error());
                return -1;
            }
        return 1;
    }
    
    public function InsertDB($statement)
    {   
        if(!$results = mysqli_query($this->_dblink,$statement))
            throw new Exception("Insert Query Error ".$statement.mysqli_error());
    
            return(mysqli_affected_rows($this->_dblink));
    
    }
    
    public function CountDB($statement)
    {   
        if(!$results = mysqli_query($this->_dblink,$statement))
            throw new Exception("Count Query Error ".$statement.mysqli_error());
    
            if(mysqli_num_rows($results) == 1)
                return(mysqli_result($results));
            else {
                throw new Exception("Count Query Error ".$statement.mysqli_error());
            } 
    
    }
    
    public function EscapeStrings($buffer)
    {
        return mysqli_real_escape_string($this->_dblink,$buffer);
    }
    
}

?>