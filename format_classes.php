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

    public function parsePayload()
    { 
        return strtolower($this->_payload);
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