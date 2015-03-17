<?php

/**
 * Abstract class for DB handling abstraction
 *
 * @package default
 * @author Sam Levy  
 */
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
}// END

/**
 * Extension of DBHandler for MySQL DB handling
 *
 * @package default
 * @author Sam Levy  
 */
Class MySQL_DBHandler extends DBHandler{
    
    /**
     * Connects to the DB
     *
     * @return 1 for success -1 for failure
     * @author samuel levy  
     */
    public function ConnectToDB($dbname){
       $this->_dbname = $dbname;
       
        if(!$this->_dblink = mysqli_connect($this->_host,$this->_user,$this->_password,$this->_dbname))
            {
                throw new Exception("DB Connection Error 1".mysqli_error());
                return -1;
            }
        return 1;
    }
    
    /**
     * Inserts statement to the DB
     *
     * @return number of affected rows
     * @author samuel levy  
     */
    public function InsertDB($statement)
    {   
        if(!$results = mysqli_query($this->_dblink,$statement))
            throw new Exception("Insert Query Error ".$statement.mysqli_error());
    
            return(mysqli_affected_rows($this->_dblink));
    
    }
    
    /**
     * Performs a count statement on the DB
     *
     * @return results of the count
     * @author samuel levy  
     */
    public function CountDB($statement)
    {   
        if(!$results = mysqli_query($this->_dblink,$statement))
            throw new Exception("Count Query Error ".$statement.mysqli_error());
    
            if(mysqli_num_rows($results) == 1)
            {
                $row = mysqli_fetch_array($results, MYSQLI_BOTH);
                return($row[0]);
            } 
    
    }
    
     /**
     * Performs a SELECT statement on the DB
     *
     * @return results SELECT in an associative array form
     * @author samuel levy  
     */
    public function SelectDB($statement,$return_num_fields=0)
    {   
        if(!$results = mysqli_query($this->_dblink,$statement))
            throw new Exception("Count Query Error ".$statement.mysqli_error());
   
        if($return_num_fields == 1)
            return(mysqli_num_fields($results));
   
        $rows = Array();
        
        while($row = mysqli_fetch_array($results,MYSQLI_NUM))
            $rows[] = $row;
        
        return($rows);
    }
    
      /**
     * Escape strings for SQL query to avoid illegal characters
     *
     * @return escaped query
     * @author samuel levy  
     */
    public function EscapeStrings($buffer)
    {
        return mysqli_real_escape_string($this->_dblink,$buffer);
    }
 
    /**
     * Gives the last inserted id in the DB
     *
     * @return last inserted row id
     * @author samuel levy  
     */
    public function LastInsertedId()
    {   
        if(!$lastid = mysqli_insert_id($this->_dblink))
            throw new Exception("Last Insert Id Error ".mysqli_error());
    
            return($lastid);
    
    }
       
} // END


?>