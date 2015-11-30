<?php
// This script batches excels and generates xmls ingestable by the medical_warehouse SOAP interface 

$time = time();
require_once 'excel_reader2.php';
$data = new Spreadsheet_Excel_Reader("files/2012_2011xray.XLS");
$HEB =1;

    $numrows = $data->rowcount($sheet_index=0)."\n";
    
    for($i=2;$i<=$numrows;$i++)
       {
           $filename = "files/HEBxrays/FREETEXT_HEBXR_".$i.".xml";
           $payload_to_ingest = "";
            
        
            if($HEB == 1)
            {
                $payload = $data->val($i,'A');
                
                $payload_exploded = explode(" ",$payload);
                
                foreach($payload_exploded as $word)
                {
                    $word_inversed = strrev($word);
                    $payload_to_ingest = $payload_to_ingest." ".$word_inversed;
                }
                
            }
            else
                {
                    $payload_to_ingest = utf8_decode($data->val($i,'A'));
                }
                
           $file = fopen($filename,'w+');    
           fwrite($file,'<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
                  <soap:Body xmlns:m="http://192.168.163.129/medical_warehouse/warehouse.php">
                  <m:ingest_file>
                    <m:clientId>Sam_Test</m:clientId>
                    <m:md5>81dc9bdb52d04dc20036dbd8313ed055</m:md5>
                    <m:format>FREETEXT</m:format>
                    <m:payload>'.$payload_to_ingest.'</m:payload>
                    <m:batchId>HEBXRAYS11-12</m:batchId>
                  </m:ingest_file>
                </soap:Body>
                </soap:Envelope>');
           fclose($file);
       }
       
echo $numrows."files generated in ".(time() - $time)." seconds";
?>
