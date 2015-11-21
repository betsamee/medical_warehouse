<?php

include_once '../config.php';
include_once '../db_classes.php';
include_once '../SoapService_classes.php';
include_once 'analyzis_classes.php';

$db_handler = new MySQL_DBHandler($HOST,$PORT,$USER,$PASSWORD);
$logger = new Logger();

try{
    $db_handler->ConnectToDB($DB);
}catch (exception $e)
{
    $logger->log_error($e);
    exit;
}

echo "<html><head>
<link rel='stylesheet' type='text/css' href='style.css'>
<title>Data Set construction</title></head>";
echo "<body>";

if(! isset($_POST['submit']))
{
    echo "<h2>Prepare you Dataset</h2>";
    $form = new build_sample_form($db_handler,$logger);
    $form->display_form();
}
else {
    echo "<h2>Results</h2>";    
    $sample = new build_sample($db_handler,$logger,$_POST);
    echo "<h3>Filters choosen</h3>";
    $sample->getParams();
    echo "<hr>";
    $sample->build_query();
    $sample->display_results();
 }
echo "</body>";
echo "<html>";

?>