<?php

/**
 * This class allows to manage logging of the events occuring 
 *
 * @package default
 * @author  samuel levy
 */
class Logger{
    /**
     * Logs regular event to the log file
     *
     * @return void
     * @author  
     */
    public function log_event($message)
    {
        $file = fopen("logs/".date("Ymd").".log","a+");
        fwrite($file,"EVENT | ".date("Y-m-d H:i:s")." | ".$message."\n");
        fclose($file);
    }
    
    /**
     * Logs errors to the log file
     *
     * @return void
     * @author  samuel levy
     */
    public function log_error($message)
    {
        $file = fopen("logs/".date("Ymd").".log","a+");
        fwrite($file,"ERROR | ".date("Y-m-d H:i:s")." | ".$message."\n");
        fclose($file);
    }
}
// END

/**
 * This class manages the ingest Soap Service  
 *
 * @package default
 * @author  samuel levy
 */
class SoapService extends LogicException{
        
    private $_db_handle;
    private $_parsed_buffer ="";
    private $_logger;
    
    function __construct($dbhandle,$logger)
    {
        $this->_db_handle = $dbhandle;
        $this->_logger = $logger;
    }    
    
     /**
     * Basic authentication of the message sender
     *
     * @return 1 if client is authenticated
     * @author  
     */
    public function check_client($clientId,$md5)
    {
        $statement = "SELECT Count(*) FROM Clients
                        WHERE CLT_ExternalId = '".$this->_db_handle->EscapeStrings($clientId)."'
                        AND CLT_MD5 = '".$this->_db_handle->EscapeStrings($md5)."'";
           
        $count = $this->_db_handle->CountDB($statement);             
        
        if( $count == 1)
            return 1;
        else 
            throw new Exception("Unauthorized".$count);
    }
    
     /**
     * Ingests the incoming message to the warehouse
     *
     * @return ingest result
     * @author  
     */
    public function ingest_file($clientId,$md5,$format,$payload)
    {
        $this->_logger->log_event("New ".$format." message received from ".$clientId." ".$payload);
        try
        {
            $this->check_client($clientId,$md5);
        }
        catch(exception $e)
        {
            $this->_logger->log_error($e);
            return($e);
        }
        
      
        if($format == "HL7v2" || $format == "HL7v3" || $format == "DICOM" || $format == "TEXT2UMLS" || $format == "FREETEXT")
        {
            $parser = new FormatStrategy($payload,$format);
        }
        else {
            $this->_logger->log_error("Invalid Format");                
            return($format." file ingest KO");
        }
            
        $this->_parsed_buffer = $parser->parsePayload();

        
        try
        {
            
            $statement = "INSERT INTO Ingests(ING_ClientId,ING_FormatId,ING_Payload)
                                    SELECT CLT_id, FRM_Id , \"".$this->_db_handle->EscapeStrings($this->_parsed_buffer)."\"
                                    FROM Clients, Formats
                                    WHERE CLT_ExternalId = '".$this->_db_handle->EscapeStrings($clientId)."'
                                    AND FRM_Name = '".$this->_db_handle->EscapeStrings($format)."'
                                    LIMIT 1 ;";
                                    
            if($this->_db_handle->InsertDB($statement) != 1)
            {
                Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
            }
            
            $parser->IngestMsg($this->_db_handle->LastInsertedId(),$this->_db_handle);
            
        }
        catch(exception $e)
        {
               $this->_logger->log_error($e);
               return($format." file ingest KO");   
        }
    
        $this->_logger->log_event("File ingested OK");
        return($format." file ingest OK");
     }
}
// END
?>