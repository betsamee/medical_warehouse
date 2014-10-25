<?php
include_once 'config.php';
include_once 'format_classes.php';
class Format{
        private $Formatpayload;
        
        public function __construct($payload)
        {
            $this->Formatpayload = $payload; 
        }
        
        public function getPayload()
        {
            return $this->Formatpayload;
        }
}

class HL7v2 extends Format{

    public function parsePayload()
    { 
        return strtolower($this->getPayload());
    }
    
}

class HL7v3 extends Format{
        
    public function parsePayload()
    { 
        return strtolower($this->getPayload());
    }
    
}

class DICOM extends Format {
        
    public function parsePayload()
    { 
        return strtolower($this->getPayload());
    }
    
}

class SoapService extends LogicException{
	public function ingest_file($clientId,$format,$payload)
	{
        $fd = fopen('files/'.$clientId."_".$format,'w');
        
        if(!$fd)
            throw new Exception("File processing error");
        
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
                throw new Exception("Invalid Format");
            break;
        }
        
        if (!fwrite($fd,$parser->parsePayload()))
            throw new Exception("File processing error");
            
        fclose($fd);
        
        return($format." file ingested OK");
     }
}

$options = array('uri'=> $SERVER_URI);
$server = new SoapServer(NULL,$options);

$server->setClass('SoapService');
$server->handle();
?>