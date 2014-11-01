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
        return strtolower($this->_payload);
    }
    
}
?>