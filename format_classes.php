<?php

abstract class Format{
        public $_payload;
        
        public function __construct($payload)
        {
            $this->_payload = $payload; 
        }
        
        public function getPayload()
        {
            return $this->_payload;
        }
}

class HL7v2 extends Format{

    public $_exploded_payload;
    public $_ingest_id;
    public $_message_id;
    
    public function parsePayload()
    { 
        return $this->_payload;
    }
    
    public function getMessageType()
    {           
        $this->_exploded_payload = explode("\n",$this->_payload);
        
        foreach($this->_exploded_payload as $line)
        {
            $exploded = explode("|",$line);
            
            if($exploded[0] == 'MSH')
            {
                return($exploded[8]);
            }
        }
    }
    
    public function IngestMsg($ingestId,$db_handle)
    {
            $msgtype = $this->getMessageType();
            $this->_ingest_id = $ingestId;
                
            $msg_type_exploded = explode("^",$msgtype);
          
            $statement = "INSERT INTO HL7_Messages_Received(HMR_IngestId,HMR_MessageType,HMR_EventType)
                      VALUES (".$ingestId.",'".$msg_type_exploded[0]."','".$msg_type_exploded[1]."');";
        
                                    
            if($db_handle->InsertDB($statement) != 1)
            {
                Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
            }
            
            $this->_message_id = $db_handle->LastInsertedId();
          
           
            foreach($this->_exploded_payload as $segment)
            {
                $this->IngestSegment($segment,$this->_message_id,$db_handle);
            }
        }
    
    public function IngestSegment($segment,$msg_id,$db_handle)
    {
            $segment_exploded = explode("|",$segment);
            
            $statement = "INSERT INTO HL7_Segments_Received(HSR_MRId,HSR_SegmentType)
                      VALUES (".$msg_id.",'".$segment_exploded[0]."');";
        
            if($db_handle->InsertDB($statement) != 1)
            {
                Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
            }
            
            
            $segment_id = $db_handle->LastInsertedId();
        
            $this->IngestFields($segment_exploded,$segment_id,$db_handle);
            
    }
    
    public function IngestFields($fields,$seg_id,$db_handle)
    {
        $i = 0;
        $statement = "INSERT INTO HL7_Fields_Received(HFR_SRId,HFR_Position,HFR_Value)
                    VALUES ";
                    
        foreach($fields as $field)
        {
            if($i!=0)
                $statement .= ",";
            
            $statement .= "(".$seg_id.",".$i.",\"".$db_handle->EscapeStrings($field)."\")";
            $i++;
        }
        
        echo $statement;
        
        if($db_handle->InsertDB($statement) < 1)
        {
           Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
        }
        
    }
}

class HL7v3 extends Format{
        
    public function parsePayload()
    { 
        return strtolower($this->_payload);
    }
    
}

class DICOM extends Format {
        
    public function parsePayload()
    {
        $filename = 'dicom_sr_'.date('YmdHHis').'.tmp';
        $file = fopen('tmp/'.$filename,'w+');
        fwrite($file,base64_decode($this->_payload));
        fclose($file);
        
        //Uses the utility dsr2xml to translate binary DICOM_SR format to XML
        exec('dsr2xml tmp/'.$filename.' > tmp/'.$filename.'.translated'); 
        $filecontent = file_get_contents('tmp/'.$filename.'.translated');
        
        unlink('tmp/'.$filename.'.translated');
        unlink('tmp/'.$filename);
        
        return $filecontent;
    }
    
}
?>