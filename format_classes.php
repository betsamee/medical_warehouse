<?php

class FormatStrategy{
        public $_payload = NULL;
        public $_formatStrategy = NULL;
        
        public function __construct($payload,$format)
        {
            $this->_payload = $payload; 
            
            switch($format)
            {
                case "HL7v2":
                    $this->_formatStrategy = new HL7v2($this->_payload);
                break;
                case "HL7v3":
                    $this->_formatStrategy = new HL7v3($this->_payload);
                break;
                case "DICOM":
                    $this->_formatStrategy = new DICOM($this->_payload);
                break;
                case "TEXT2UMLS":
                    $this->_formatStrategy = new TEXT2UMLS($this->_payload);
                break;
                case "FREETEXT":
                    $this->_formatStrategy = new TEXT2UMLS($this->_payload);
                break;
              }
        }
        
        public function parsePayload()
        {
            return $this->_formatStrategy->parsePayload();        
        }
       
        public function IngestMsg($ingestId,$db_handle)
       {
           return $this->_formatStrategy->IngestMsg($ingestId, $db_handle);
       }
}

/**
 * Base class for all the ingestion formats supported
 *
 * @package default
 * @author  samuel levy
 */
class Format{
        public $_payload = NULL;
 
        public function __construct($payload,$format)
        {
            $this->_payload = $payload; 
        }
}

/**
 * Interface used for the ingestion strategies (strategy pattern)
 *
 * @package default
 * @author  samuel levy
 */
interface FormatStrategyInterface
{    
    public function parsePayload();
    public function IngestMsg($ingestId,$db_handle);
    public function getMessageType();
    public function IngestSegment($segment,$msg_id,$db_handle);
    public function IngestFields($fields,$seg_id,$db_handle);    
}

/**
 * This class implements HL7v2 ingestion algorithms 
 *
 * @package default
 * @author  samuel levy
 */
class HL7v2 extends Format implements FormatStrategyInterface {

    public $_exploded_payload;
    public $_ingest_id;
    public $_message_id;
    
     /**
     * parses original payload message
     *
     * @return void
     * @author  
     */
    public function parsePayload()
    { 
        return $this->_payload;
    }
    
    /**
     * returns HL7 message type
     *
     * @return string
     * @author  
     */
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
    
    /**
     * ingests HL7 message
     *
     * @return string
     * @author  
     */
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
    
    /**
     * ingests segments composing the Hl7 message
     *
     * @return string
     * @author  
     */
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
    
    /**
     * ingest fields compposing the HL7 segments
     *
     * @return string
     * @author  
     */
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

/**
 * This class implements HL7v3 ingestion algorithms 
 *
 * @package default
 * @author  samuel levy
 */
class HL7v3 extends Format implements FormatStrategyInterface{
        
    public function parsePayload()
    { 
        return strtolower($this->_payload);
    }
    
    public function IngestMsg($ingestId,$db_handle){}
    public function getMessageType(){}
    public function IngestSegment($segment,$msg_id,$db_handle) {}
    public function IngestFields($fields,$seg_id,$db_handle) {}
    
}


/**
 * This class implements DICOM (structured reports) ingestion algorithms 
 *
 * @package default
 * @author  samuel levy
 */
class DICOM extends Format implements FormatStrategyInterface {
             
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
    
    public function IngestMsg($ingestId,$db_handle){}
    public function getMessageType(){}
    public function IngestSegment($segment,$msg_id,$db_handle) {}
    public function IngestFields($fields,$seg_id,$db_handle) {}
    
}


/**
 * This class implements TETX2UMLS (free text o UMLS) ingestion algorithms 
 *
 * @package default
 * @author  samuel levy
 */
class TEXT2UMLS extends Format implements FormatStrategyInterface {
             
    public $_exploded_payload;
    public $_ingest_id;
    public $_message_id;
    public $_parsed_payload;
 
    
    /**
     * parses ingested text , uses MTI to translate it into weighted MeSH terms
     *
     * @return string
     * @author  
     */
    public function parsePayload()
    {
        $_MTI_url = 'http://ii.nlm.nih.gov/cgi-bin/II/Interactive/interactiveMTI.pl';    
        $dom = new DOMDocument;
        $fields = array('InputText' => urlencode($this->_payload));

        $curl_ressource = curl_init();
        
        curl_setopt($curl_ressource,CURLOPT_URL, $_MTI_url);
        curl_setopt($curl_ressource,CURLOPT_POST, count($fields));
        curl_setopt($curl_ressource,CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl_ressource, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl_ressource, CURLOPT_TIMEOUT, 40);
        
        // Parses result html to extract MTI semantical parser results
        $dom->loadHTML(curl_exec($curl_ressource));
        $pres = $dom->getElementsByTagName('pre');
        
        foreach($pres as $pre)
            if(strstr($pre->nodeValue,"Command: MTI"))
                {
                    $array = explode("\n", $pre->nodeValue);
                    array_shift($array);
                    array_shift($array);
                    array_shift($array);
                    array_shift($array);
                    $result = implode("\n", $array); 
                    $this->_parsed_payload = $result; 
               }
        
        curl_close($curl_ressource);
                 
        return $result;
       }
          
    public function getMessageType(){}
        
    /**
     * ingest the MeSH weighted terms into database
     *
     * @return void
     * @author  
     */
    public function IngestMsg($ingestId,$db_handle)
    {
            $this->_ingest_id = $ingestId;
            
            $array = explode("\n", $this->_parsed_payload);
           
	     
            foreach($array as $entry)
            {
		        $line = explode("|", $entry);
                
                if($line[1] == "")
                    continue;
                
                $statement = "INSERT INTO Textual_Rows(TXR_IngestId,TXR_Rank,TXR_OriginalText,TXR_Heading,TXR_Score,TXR_HeadingType,TXR_CorrespondingText,TXR_Field,TXR_Path)
                      VALUES (".$this->_ingest_id.",'".$line[0]."','".$line[1]."','".$line[2]."',".$line[3].",'".$line[4]."','".$line[5]."','".$line[6]."','".$line[7]."');";
                                    
                if($db_handle->InsertDB($statement) != 1)
                {
                    Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
                }
            }
                
                
     }
        
    public function IngestSegment($segment,$msg_id,$db_handle) {}
    public function IngestFields($fields,$seg_id,$db_handle) {}
    
}

/**
 * This class implements free text ingestion 
 *
 * @package default
 * @author  samuel levy
 */
class FREETEXT extends Format implements FormatStrategyInterface {
             
    public $_exploded_payload;
    public $_ingest_id;
    public $_message_id;
    public $_parsed_payload;
    
    /**
     * ingest the message as Free Text message, without any prior treatment
     *
     * @return void
     * @author  
     */
     public function parsePayload()
    {
        return($this->_payload);
    }
          
    public function getMessageType(){}
        
    
    public function IngestMsg($ingestId,$db_handle){}
        
    public function IngestSegment($segment,$msg_id,$db_handle) {}
    public function IngestFields($fields,$seg_id,$db_handle) {}
    
}


?>
