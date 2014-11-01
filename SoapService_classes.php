<?php

class Logger{
    public function log_event($message)
    {
        $file = fopen("logs/".date("Ymd").".log","a+");
        fwrite($file,"EVENT | ".date("Y-m-d H:i:s")." | ".$message."\n");
        fclose($file);
    }
    
    public function log_error($message)
    {
        $file = fopen("logs/".date("Ymd").".log","a+");
        fwrite($file,"ERROR | ".date("Y-m-d H:i:s")." | ".$message."\n");
        fclose($file);
    }
}

class SoapService extends LogicException{
        
    private $_db_handle;
    private $_parsed_buffer ="";
    private $_logger;
    
    function __construct($dbhandle,$logger)
    {
        $this->_db_handle = $dbhandle;
        $this->_logger = $logger;
    }    
    
    public function check_client($clientId,$md5)
    {
        return 1;
    }
    
    public function ingest_file($clientId,$md5,$format,$payload)
    {
        $this->_logger->log_event("New ".$format." message received from ".$clientId);
        try
        {
            $this->check_client($clientId,$md5);
        }
        catch(exception $e)
        {
            $this->_logger->log_error($e);
            return($e);
        }
        
        switch($format)
        {
            case "HL7v2":
                $parser = new HL7v2($payload);
            break;
            case "HL7v3":
                $parser = new HL7v3($payload);
            break;
            case "DICOM":
                $parser = new DICOM($payload);
            break; 
            default:
                $this->_logger->log_error("Invalid Format");                
                return($format." file ingest KO");
            break;
        }
        
            
        $this->_parsed_buffer = $parser->parsePayload();
        
        try
        {
            
            $statement = "INSERT INTO Ingests(ING_ClientId,ING_FormatId,ING_Payload)
                                    SELECT CLT_id, FRM_Id , \"".$this->_db_handle->EscapeStrings($this->_parsed_buffer)."\"
                                    FROM Clients, Formats
                                    WHERE CLT_ExternalId = '".$clientId."'
                                    AND FRM_Name = '".$format."'
                                    LIMIT 1 ;";
                                    
            if($this->_db_handle->InsertDB($statement) != 1)
            {
                Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
             }
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
?>