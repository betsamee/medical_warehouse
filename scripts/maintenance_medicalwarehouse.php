<?php
 /* script performing some maintenance operations on the system
 * @package default
 * @author samuel levy  
  */
  
include_once '../config.php';
include_once '../db_classes.php';

$db_handler = new MySQL_DBHandler($HOST,$PORT,$USER,$PASSWORD);

$db_handler->ConnectToDB($DB);

$tables_to_optimize = Array("Analysis_Results","Dataset_Definition","HL7_Fields_Received","HL7_Segments_Received","HL7_Messages_Received", "Ingests","Textual_Rows","UMLS_Correspondance");

foreach($tables_to_optimize as $table)
{
    $sql_optimize = "OPTIMIZE TABLE ".$table;
    
    $db_handler->InsertDB($sql_optimize);
}

exec('rm -rf ../ui/tmp/*.*');
echo "Maintenance completed!";


?>