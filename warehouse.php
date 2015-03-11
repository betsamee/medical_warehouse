<?php

/**
 * This page manages the warehouse SoapService, listening for incoming SOAP requests coming from
 * http://localhost/medical_warehouse/warehouse.php
 * And using the SoapService class to manage the warehousing of the incoming file
 */

include_once 'config.php';
include_once 'format_classes.php';
include_once 'db_classes.php';
include_once 'SoapService_classes.php';

$db_handler = new MySQL_DBHandler($HOST,$PORT,$USER,$PASSWORD);
$logger = new Logger();

try{
    $db_handler->ConnectToDB($DB);
}catch (exception $e)
{
    $logger->log_error($e);
    exit;
}

$options = array('uri'=> $SERVER_URI);
$server = new SoapServer(NULL,$options);

$server->setClass('SoapService',$db_handler,$logger);
$server->handle();
?>