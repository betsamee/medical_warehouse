<?php

include_once '../config.php';
include_once '../db_classes.php';
include_once '../SoapService_classes.php';

/**
 * This class encapsulates the building of the analysis form
 *  
 * @package default
 * @author samuel levy  
  */
class build_filter_form extends LogicException
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
        echo "Start date <input name=start_date type=text value='".$start."'><br/>End Date <input name=end_date type=text value='".$end."'>&nbsp;&nbsp;&nbsp;";
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
        echo " <select  name=".$title." size=4 >";
          
        echo "<optgroup label=".$title.">";
        
        $i = 0;
              
        foreach($result as $res)
        {
            if($i == 0)
                $selected="selected";
            else
                $selected="";
            
            echo "<option value='".$res[0]."' ".$selected." >".$res[1]."</option><br/>";
            $i++;
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
        echo "<form id=frm method=post>";
        
        echo "<table border=0>";
        echo "<tr valign=top><td>";
        $this->display_dates($start_date, $end_date);
        
        
        $statement = "SELECT DISTINCT CLT_Id,CLT_ExternalId FROM Clients INNER JOIN Ingests on CLT_Id = ING_ClientId
        WHERE ING_FormatId in (2,3,5)";
        
        echo "</td><td align=left>";
        $this->display_select($statement,"Client_Id");
        echo "</td><td align=left>";
        
        $statement = "SELECT DISTINCT FRM_Id,FRM_Name FROM Formats INNER JOIN Ingests on FRM_Id = ING_FormatId
        WHERE ING_FormatId in (2,3,5)";
        
        echo "</td><td align=left>";
        $this->display_select($statement,"Format_Type");
    
        
        $statement = "SELECT DISTINCT ING_BatchId,ING_BatchId FROM Ingests
        WHERE ING_FormatId in (2,3,5)";
        
        $this->display_select($statement,"Batch_Id");
        echo "</td></tr>";
        
        echo "</tr></table>";
        echo "<input type=submit value=submit name=submit>";
        echo "</form>";
    }
    
    
} // END

/**
 * This class allows to analyze the set of data choosen in the form
 *  
 * @package default
 * @author samuel levy  
 */
class build_results extends LogicException
{
    
    private $_db_handle;
    private $_logger;
    private $_parameters;
    private $_startdate;
    private $_enddate;
    private $_query;
    private $_filters;
    private $_datasetId; 
    private $_clientId;
    private $_batchId; 
            
    function __construct($dbhandle,$logger,$parameters)
    {
        $this->_db_handle = $dbhandle;
        $this->_logger = $logger;
        $this->_parameters = $parameters;
    } 
    
     /**
     * Return coocurrences between UMLS terms
     *
     * @return array
     * @author samuel levy  
     */
    function analyze_coocurrences($single_entry,$coocurrences)
    {
      
      $array_length = count($single_entry);
      
      for($i=0;$i<$array_length;$i++)
      {
          for($j=$i+1;$j<$array_length;$j++)
          {
              if($single_entry[$i] != $single_entry[$j])
                    $coocurrences[$single_entry[$i]][$single_entry[$j]] = 1*($coocurrences[$single_entry[$i]][$single_entry[$j]]) + 1;
          }
      }
      
      return($coocurrences);
    }
    
     /**
     * Launches the analysis of the data
     *
     * @return void
     * @author samuel levy  
     */
    function analyze_data()
    {
        echo "<h2>Analyzis</h2>";
        echo "<a href='tmp/".$this->_datasetId.".png' target=_blank>Association graph</a>&nbsp;&nbsp;<a href='tmp/".$this->_datasetId.".csv' target=_blank>Result csv file</a><br/><br/>";
        
        $MIN_COOCURENCE = 0;
        
        $results = $this->_db_handle->SelectDB($this->_query);
        
        $coocurrences = Array();
        $previous_ingest_id = -1;
        
        $single_entry = Array();
        $i=0;
        
        foreach($results as $res)
        {
                
               if($previous_ingest_id == $res[0] || $i == 0)
                {
                    array_push($single_entry,$res[2]);
                    $previous_ingest_id = $res[0];
                }
                else {
                    
                    $coocurrences = $this->analyze_coocurrences($single_entry,$coocurrences);
                    $single_entry = Array();
                    array_push($single_entry,$res[2]);
                    $previous_ingest_id = $res[0];
                }
        $i++;
        
        }
        
        foreach($coocurrences as $index => $coocurrence)
        {
                
                foreach($coocurrence as $index2 => $occurence)
                {
                       if($occurence > $MIN_COOCURENCE)
                       {
                            // insertion of the results of the coocurrence analysis to the database
                            $statement = "INSERT INTO Analysis_Results(ANR_DataSetId,ANR_BatchId,ANR_TextHeadingId1,ANR_TextHeadingId2,ANR_Coocurrences) 
                            VALUES ($this->_datasetId,".$this->_batchId.",'".$index."','".$index2."',".$occurence.")";
                
                            
                            if($this->_db_handle->InsertDB($statement) != 1)
                            {
                                 Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
                            }
                    
                       }
                } 
        
        }
        
            // this query agregates coocurence analyzis into a comprehensive formatted table
            $sql_analysis = "SELECT concat(ANR_TextHeadingId1,\"-\",UMC_Translation),
            concat(ANR_TextHeadingId2,\"-\",A.UMC_Translation2),ANR_Coocurrences,
            ANR_Coocurrences / (SELECT count(Distinct TXR_IngestId) FROM Textual_Rows INNER JOIN Ingests on TXR_IngestId = ING_Id WHERE ING_BatchId = ".$this->_batchId.") as percentage_against_Total_Entries
            FROM Analysis_Results INNER JOIN UMLS_Correspondance on ANR_TextHeadingId1 = UMC_Code
            INNER JOIN 
            (
            SELECT UMC_Code as UMC_Code2,UMC_Translation as UMC_Translation2
            FROM UMLS_Correspondance
            )A ON A.UMC_Code2 = ANR_TextHeadingId2
            WHERE ANR_DataSetId = $this->_datasetId
            ORDER BY ANR_Coocurrences DESC";
  
        
            $results = $this->_db_handle->SelectDB($sql_analysis);
            
            echo "<table border=1>";
            echo "<tr bgcolor=Tan><td><b>1st Term</b></td><td><b>2nd Term</b></td></td><td><b>Coocurrences</b></td><td><b>Percentage vs Total in Batch</b></td></tr>";
            $buffer = "1st Term,2nd Term,Coocurrences,Percentage vs Total in Batch\n";
            
            foreach($results as $result)
            {
                echo "<tr bgcolor=PapayaWhip><td>".$result[0]."</td><td>".$result[1]."</td><td>".$result[2]."</td><td>".($result[3]*100)."%</td></tr>";
                $buffer .= $result[0].",".$result[1].",".$result[2].",".$result[3]."\n";
            }
            
            echo "</table>";
            
            // saves the full results into csv file
            fwrite(fopen("tmp/".$this->_datasetId.".csv","w+"),$buffer);
            
            // launches the generation of a connected graph displaying the coocurrences
            exec('python create_graph.py '.$this->_datasetId);
         
        echo "<br/>";
     
    }
    
    /**
     * Building of the query that will allow the analysis (based on the selected filters)
     *
     * @return void
     * @author samuel levy  
     */  
    function build_query()
    {
                //Full scan analysis
                if($this->_filters == 'NONE')
                {
                    $this->_query = "SELECT TXR_IngestId,TXR_Id,TXR_Heading,TXR_Score
                    FROM Ingests INNER JOIN Textual_Rows ON ING_Id = TXR_IngestId
                    WHERE ING_FormatId = 4
                    AND ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                    AND ING_ClientId = ".$this->_clientId."
                    AND ING_BatchId = ".$this->_batchId."
                    ORDER BY TXR_IngestId,TXR_Id ASC";
                }
                else{
                    
                    $query_filters = str_replace(Array("&","|","!",","),Array(" OR "," OR "," NOT "," OR "),$this->_filters) ;
                    $query_filters_ready =  preg_replace('/([A-Z][0-9]{7})/', ' TXR_Heading=\'\1\'' , $query_filters);
                
                   
                    $this->_query = "SELECT TXR_IngestId,TXR_Id,TXR_Heading,TXR_Score
                    FROM Ingests INNER JOIN Textual_Rows ON ING_Id = TXR_IngestId
                    WHERE ING_FormatId = 4
                    AND ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                    AND (".$query_filters_ready.")
                    AND ING_ClientId = ".$this->_clientId."
                    AND ING_BatchId = ".$this->_batchId."
                    ORDER BY TXR_IngestId,TXR_Id ASC";
                }
                
        $statement = "INSERT INTO DataSet_Definition(DSD_SQLStatement) VALUES (\"$this->_query\")";
        
        if($this->_db_handle->InsertDB($statement) != 1)
        {
             Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
        }
        
        $this->_datasetId = $this->_db_handle->LastInsertedId();  
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
                    case 'Client_Id':
                        $this->_clientId = $buffer;
                    break;
                    case 'Batch_Id':
                        $this->_batchId = $buffer;
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
                    case 'filters':
                        $this->_filters = $param;
                    
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
<title>Analysis of free textual data</title></head>";
echo "<body>";


if(! isset($_POST['submit']))
{
    echo "<h2>Prepare you Dataset</h2>";    
    $form = new build_filter_form($db_handler,$logger);
    $form->display_form();
}
else {
    echo "<h2>Results</h2>";    
    $time = time();
    $sample = new build_results($db_handler,$logger,$_POST);
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