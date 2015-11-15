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
        echo " <select name=".$title."[] multiple=multiple size=20 >";
          
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
    function display_form($format_type="*",$message_type="*",$segment_type="*",$field_type="*",$start_date="2015-01-01",$end_date="3000-01-01",$level_type="*")
    {
        echo "<form method=post>";
        
        $this->display_dates($start_date, $end_date);
        
        $statement = "SELECT DISTINCT FRM_Id,FRM_Name FROM Formats INNER JOIN Ingests on FRM_Id = ING_FormatId
        WHERE ING_id = 1";
        
        if($format_type != "*")
        {
            $statement .= " WHERE FRM_Id in (".$format_type.")";
        }
    
        $this->display_select($statement,"Format_Type");
    
    
        $statement = "SELECT LOA_Id,LOA_Name FROM LevelsOfAnalysis";
        
        if($level_type != "*")
        {
            $statement .= " WHERE LOA_Id in (".$level_type.")";
        }
    
        $this->display_select($statement,"Level_Type");
    
        $statement = "SELECT DISTINCT concat_ws( '-', MSG_Id, EVE_Id ) , substr( concat_ws( '-', MSG_Type, EVE_Name ) , 1, 40 )
                        FROM HL7_Message_Types
                        INNER JOIN HL7_Messages_Received ON MSG_Id = HMR_MessageType
                        INNER JOIN HL7_Event_Types ON HMR_EventType = EVE_Id";
        
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
    private $_formats;
    private $_startdate;
    private $_enddate;
    private $_message_event_types;
    private $_segment_types;
    private $_field_types;
    private $_query;
    private $_level_type;
    private $_datasetId;
    
    function __construct($dbhandle,$logger,$parameters)
    {
        $this->_db_handle = $dbhandle;
        $this->_logger = $logger;
        $this->_parameters = $parameters;
    } 
  
    function countFeatures()
    {
        return($this->_db_handle->SelectDB($this->_query,1));
    }
    
    function countSamples()
    {
        $statement = "SELECT count( * )
        FROM (".$this->_query.") A";
        
        return ($this->_db_handle->CountDB($statement));
    }
    
    function pivot_result($line_pivot=0,$title_field=1,$data_field=2,$title_field_name="Observation Identifier",$data_field_name="Observation Value")
    {
        $line_number = -1;
        
        $pivoted_table = Array("");
        $rows = $this->_db_handle->SelectDB($this->_query);
    
        foreach($rows as $row)
        {
           if(trim($row[$title_field]) == trim($title_field_name))
                $current_field = $row[$data_field];
           
           if(trim($row[$title_field]) == trim($data_field_name))
                $pivoted_table[$row[$line_pivot]][$current_field] = $row[$data_field];
                
        }
        foreach($pivoted_table as $pivoted_row)
        {
            foreach($pivoted_row as $field=>$value)
                echo $field."-".$value."<br/>";
            
            echo "<br/>";
        }
    }
    
    function build_query()
    {
   
        switch($this->_level_type)
        {
            
            //Level = Field
            case "'3'":
                $this->_query = "SELECT DISTINCT ING_Id,HSP_Name,HFR_Value FROM
                Ingests INNER JOIN HL7_Messages_Received ON ING_Id = HMR_IngestId
                INNER JOIN HL7_Segments_Received ON HMR_Id = HSR_MRId
                INNER JOIN HL7_Fields_Received on HFR_SRId = HSR_Id
                INNER JOIN HL7_Segment_Parsing on HSP_Segment_Id = HSR_SegmentType
                WHERE ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                AND ING_FormatId IN ($this->_formats)
                AND concat_ws('-',HMR_MessageType,HMR_EventType) IN ($this->_message_event_types)
                AND HSR_SegmentType in ($this->_segment_types)
                AND HSP_Name IN ($this->_field_types)
                AND HFR_Position = HSP_Position
                AND HSP_NotRelevant = 0
                ORDER BY HFR_Id";
            break;
            //Level = Segment
            case "'2'":
                $this->_query = "SELECT DISTINCT ING_IngestTime,HL7_Segments_Received.*,HL7_Segment_Types.* FROM
                Ingests INNER JOIN HL7_Messages_Received ON ING_Id = HMR_IngestId
                INNER JOIN HL7_Segments_Received ON HMR_Id = HSR_MRId
                INNER JOIN HL7_Event_Types ON HMR_EventType = EVE_Id
                INNER JOIN HL7_Segment_Types ON SEG_Id =  HSR_SegmentType
                WHERE ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                AND ING_FormatId IN ($this->_formats)
                AND concat_ws('-',HMR_MessageType,HMR_EventType) IN ($this->_message_event_types)
                AND HSR_SegmentType in ($this->_segment_types)";
            break;
            //Level = Message     
            case "'1'":
                
                $this->_query = "SELECT DISTINCT DISTINCT ING_Id,ING_IngestTime,HL7_Messages_Received.*,HL7_Event_Types.* FROM
                Ingests INNER JOIN HL7_Messages_Received ON ING_Id = HMR_IngestId
                LEFT JOIN HL7_Message_Types ON HMR_MessageType = MSG_Id
                LEFT JOIN HL7_Event_Types ON HMR_EventType = EVE_Id
                WHERE ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                AND ING_FormatId IN ($this->_formats)";
            break;     
        }
        $statement = "INSERT INTO DataSet_Definition(DSD_SQLStatement) VALUES (\"$this->_query\")";
        
        if($this->_db_handle->InsertDB($statement) != 1)
        {
             Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
        }
            
        $this->_datasetId = $this->_db_handle->LastInsertedId();
        echo "<h3>Data Set ".$this->_datasetId." created</h3>";
        
       // echo "<b> SQL Statement : </b>".$this->_query;
       // echo "<br><br> <b> Number of samples : </b>".$this->countSamples();
       // echo "<br><br> <b> Number of features : </b>".$this->countFeatures();
        
    }

    function display_results()
    {
            $results = $this->_db_handle->SelectDB($this->_query);
        
            echo "<a href='tmp/data-set-".$this->_datasetId.".csv' target=_blank>Result csv file</a><br/><br/>";
            echo "<table border=1>";
            //echo "<tr bgcolor=Tan><td><b>1st Term</b></td><td><b>2nd Term</b></td></td><td><b>Coocurrences</b></td><td><b>Percentage vs Total in Batch</b></td></tr>";
            //$buffer = "1st Term,2nd Term,Coocurrences,Percentage vs Total in Batch\n";
            $buffer = "";
            
            foreach($results as $result)
            {
                echo "<tr bgcolor=PapayaWhip>";
                for($i=0;$i<count($result);$i++)
                {    
                    echo "<td>".$result[$i]."</td>";
                    $buffer .= $result[$i].",";
                }
                
                $buffer .= "\n";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // saves the full results into csv file
            fwrite(fopen("tmp/data-set-".$this->_datasetId.".csv","w+"),$buffer);
    }
    
    function getParams()
    {  
        foreach($this->_parameters as $key => $param)
        {
            if($key=="submit")
                continue;
                
            echo "<b>".$key." : </b>";
            
            if(gettype($param) == "array")
            {
                $i=0;
                $buffer = "";
                
                foreach($param as $par)
                {
                        if($i>0)
                        {
                            $buffer .= ",'".$par."'";
                            $i++;
                        }
                        else 
                        {
                                $buffer .= "'".$par."'";
                                $i++;
                        }
                }
                echo $buffer;
                              
                echo "<br/>";
                
                switch($key)
                {
                    case 'Format_Type':
                        $this->_formats = $buffer;
                    break;
                    case 'Message_Type':
                        $this->_message_event_types = $buffer;
                    break;
                    case 'Segment_Type':
                        $this->_segment_types = $buffer;
                    break;
                    case 'Field_Type':
                        $this->_field_types = $buffer;
                    break;
                    case 'Level_Type':
                        $this->_level_type = $buffer;
                    break;
                }
            }
            else 
            {
                echo $param;
                echo "<br/>";
                
                switch($key)
                {
                    case 'start_date':
                        $this->_startdate = $param;
                    break;
                    
                    case 'end_date':
                        $this->_enddate = $param;
                    break;
                    
                }
            }
        }
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