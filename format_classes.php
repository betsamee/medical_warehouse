<?php

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
?>