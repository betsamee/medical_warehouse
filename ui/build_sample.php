<?php

include_once '../config.php';
include_once '../db_classes.php';
include_once '../SoapService_classes.php';

/**
 * This class allows to select the parameters for sample building from a dynamicly built form
 *  
 * @package default
 * @author samuel levy  
  */
class build_sample_form extends LogicException
{
    
    private $_db_handle;
    private $_logger;
    
    function __construct($dbhandle,$logger)
    {
        $this->_db_handle = $dbhandle;
        $this->_logger = $logger;
    } 
    
    /**
     * Displays date frame for the sample building
     *
     * @return void
     * @author samuel levy  
     */
    function display_dates($start,$end)
    {
        echo "Start date <input name=start_date type=text value='".$start."'>  End Date <input name=end_date type=text value='".$end."'><br/><br/>";
    }
    
    /**
     * Display select/option for the SQL statement received
     *
     * @return void
     * @author samuel levy  
     */
    function display_select($statement,$title)
    {
        $result = $this->_db_handle->SelectDB($statement);
        
        echo " <select name=".$title."[] multiple=multiple size=20>";
            
        echo "<optgroup label=".$title.">";      
        foreach($result as $res)
        {
            echo "<option value='".$res[0]."'>".$res[1]."</option><br/>";
        }
        echo "</select> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        
    }
    
    /**
     * Displays form according to the filters received
     *
     * @return void
     * @author samuel levy  
     */
    function display_form($format_type="*",$message_type="*",$segment_type="*",$field_type="*",$start_date="2015-01-01",$end_date="3000-01-01")
    {
        echo "<form method=post>";
        
        $this->display_dates($start_date, $end_date);
        
        $statement = "SELECT DISTINCT FRM_Id,FRM_Name FROM Formats INNER JOIN Ingests on FRM_Id = ING_FormatId";
        
        if($format_type != "*")
        {
            $statement .= " WHERE FRM_Id in (".$format_type.")";
        }
    
        $this->display_select($statement,"Format_Type");
        
        $statement = "SELECT DISTINCT MSG_Id,MSG_Type FROM HL7_Message_Types INNER JOIN HL7_Messages_Received on MSG_Id = HMR_MessageType";
        
        if($message_type != "*")
        {
            $statement .= " WHERE MSG_Id in (".$message_type.")";
        }
    
        $this->display_select($statement,"Message_Type");
    
        $statement = "SELECT DISTINCT SEG_Id,SEG_Type FROM HL7_Segment_Types INNER JOIN HL7_Segments_Received on SEG_Id = HSR_SegmentType";
        
        if($segment_type != "*")
        {
            $statement .= " WHERE MSG_Id in (".$segment_type.")";
        }
    
        $this->display_select($statement,"Segment_Type");
    
    
        $statement = "SELECT DISTINCT HSP_Name,HSP_Name FROM HL7_Segment_Parsing INNER JOIN HL7_Segments_Received on HSP_Segment_Id = HSR_SegmentType WHERE HSP_NotRelevant = 0 ";
        
        if($field_type != "*")
        {
            $statement .= " AND MSG_Id in (".$field_type.")";
        }
    
        $this->display_select($statement,"Field_Type");
    
        echo "<input type=submit value=submit name=submit>";
        echo "</form>";
    }
    
    
} // END

/**
 * This class allows to build samples according to the parameters chosen in the form
 *  
 * @package default
 * @author samuel levy  
 */
class build_sample extends LogicException
{
    
    private $_db_handle;
    private $_logger;
    private $_parameters;
    
    function __construct($dbhandle,$logger,$parameters)
    {
        $this->_db_handle = $dbhandle;
        $this->_logger = $logger;
        $this->_parameters = $parameters;
    } 

} // END

$db_handler = new MySQL_DBHandler($HOST,$PORT,$USER,$PASSWORD);
$logger = new Logger();

try{
    $db_handler->ConnectToDB($DB);
}catch (exception $e)
{
    $logger->log_error($e);
    exit;
}

echo "<html><head><title>Sample construction</title></head>";
echo "<body>";

if(! isset($_POST['submit']))
{
    $form = new build_sample_form($db_handler,$logger);
    $form->display_form();
}
else {
    $sample = new build_sample($db_handler,$logger,$_POST);
}
echo "</body>";
echo "<<html>";

?>