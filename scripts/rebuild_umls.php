<?php
 /* simple script to rebuild UMLS correspondance table
 * @package default
 * @author samuel levy  
  */
  
include_once '../config.php';
include_once '../db_classes.php';

$db_handler = new MySQL_DBHandler($HOST,$PORT,$USER,$PASSWORD);

$db_handler->ConnectToDB($DB);

$sql_empty_umls = "DELETE FROM UMLS_Correspondance";
    
$db_handler->InsertDB($sql_empty_umls);
    
$sql_rebuild_umls = "INSERT INTO UMLS_Correspondance(UMC_Code,UMC_Translation)
    SELECT DISTINCT TXR_Heading,TXR_OriginalText
    FROM Textual_Rows";
    
$db_handler->InsertDB($sql_rebuild_umls);

echo "UMLS correspondance table rebuilt!";

?>