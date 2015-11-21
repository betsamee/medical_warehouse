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
<title>Analysis of textual data</title></head>";
echo "<body>";


if(! isset($_POST['submit']))
{
    echo "<h2>Prepare you Dataset - For analysis of UMLS terms</h2>";    
    $form = new build_filter_form_text2umls($db_handler,$logger);
    $form->display_form();
}
else {
    echo "<h2>Results</h2>";    
    $time = time();
    $sample = new build_results_text2umls($db_handler,$logger,$_POST);
    echo "<h3>Filters choosen</h3>";
    $sample->getParams();
    echo "<hr>";
    $sample->build_query();
    $sample->analyze_data();
    echo "<br/>Processed in ".(time()-$time)." secs";
}

echo "</body>";
echo "<html>";

?>