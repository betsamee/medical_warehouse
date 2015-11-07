<?php
// This script batches excels and generates xmls ingestable by the medical_warehouse SOAP interface 

$time = time();
require_once 'excel_reader2.php';
$data = new Spreadsheet_Excel_Reader("files/mri_english.XLS");

    $numrows = $data->rowcount($sheet_index=0)."\n";
    
    for($i=2;$i<=$numrows;$i++)
       {
           $filename = "files/MRIs/TEXTUAL_MRI_".$i.".xml";
           
           $file = fopen($filename,'w+');    
           fwrite($file,'<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
                  <soap:Body xmlns:m="http://192.168.163.129/medical_warehouse/warehouse.php">
                  <m:ingest_file>
                    <m:clientId>Sam_Test</m:clientId>
                    <m:md5>81dc9bdb52d04dc20036dbd8313ed055</m:md5>
                    <m:format>TEXTUAL</m:format>
                    <m:payload>'.$data->val($i,'A').'</m:payload>
                  </m:ingest_file>
                </soap:Body>
                </soap:Envelope>');
           fclose($file);
       }
       
echo $numrows."files generated in ".(time() - $time)." seconds";
?>
