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
        echo "Start date <input name=start_date type=text value='".$start."'>  End Date <input name=end_date type=text value='".$end."'>&nbsp;&nbsp;&nbsp;";
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
        echo " <select  name=".$title."[] multiple=multiple size=3 >";
          
        echo "<optgroup label=".$title.">";      
        foreach($result as $res)
        {
            echo "<option value='".$res[0]."'>".$res[1]."</option><br/>";
        }
        echo "</select> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";     
    }
    
    function display_select_js($statement,$id,$title,$jscode)
    {
        $result = $this->_db_handle->SelectDB($statement);
        echo " <select id=".$id." ".$jscode." name=".$title." size=25 >";
          
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
        echo "<form id=frm method=post>";
        
        $this->display_dates($start_date, $end_date);
        
        
        $statement = "SELECT DISTINCT CLT_Id,CLT_ExternalId FROM Clients INNER JOIN Ingests on CLT_Id = ING_ClientId
        WHERE ING_FormatId = 4";
        
        $this->display_select($statement,"Client_Id");
        
        $statement = "SELECT UMC_Code,UMC_Translation FROM UMLS_Correspondance
        order by UMC_Translation ASC";
        
        echo "<table>";
        echo "<tr valign=top>";
        echo "<td>";
        $this->display_select_js($statement,"umlsid","UMLS_Codes","onDblClick='document.getElementById(\"filters\").value += document.getElementById(\"umlsid\").value + \" \" '");
        echo "</td>";
        echo "<td><font size=1>Your Filtering expression based on UMLS Terms, authorized operators are ! ( ) & | , for full analysis type NONE (long!)</font><textarea id=filters name=filters rows=5 cols=40></textarea></td>";
        echo "</tr></table>";
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
    private $_startdate;
    private $_enddate;
    private $_query;
    private $_filters;
    private $_datasetId; 
            
    function __construct($dbhandle,$logger,$parameters)
    {
        $this->_db_handle = $dbhandle;
        $this->_logger = $logger;
        $this->_parameters = $parameters;
    } 
    
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
    
    function analyze_data()
    {
        echo "<h2>Analyzis</h2>";
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
                            $statement = "INSERT INTO Analysis_Results(ANR_DataSetId,ANR_TextHeadingId1,ANR_TextHeadingId2,ANR_Coocurrences) 
                            VALUES ($this->_datasetId,'".$index."','".$index2."',".$occurence.")";
                            
                            if($this->_db_handle->InsertDB($statement) != 1)
                            {
                                 Throw new Exception("INSERT ERROR : ".$statement." no record inserted");
                            }
                    
                       }
                } 
        
        }
        
            $sql_analysis = "SELECT concat(ANR_TextHeadingId1,\"-\",UMC_Translation),
            concat(ANR_TextHeadingId2,\"-\",A.UMC_Translation2),ANR_Coocurrences,
            ANR_Coocurrences / (SELECT count(Distinct TXR_IngestId) FROM Textual_Rows) as percentage_against_Total_Entries
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
            echo "<tr bgcolor=grey><td>1st Term</td><td>2nd Term</td></td><td>Coocurrences</td><td>Percentage vs Total</td></tr>";
            
            foreach($results as $result)
            {
                echo "<tr><td>".$result[0]."</td><td>".$result[1]."</td><td>".$result[2]."</td><td>".$result[3]."</td></tr>";   
            }
            
            echo "</table>";
            
         
        echo "<br/>";
     
    }
      
    function build_query()
    {
                //Full scan analysis
                if($this->_filters == 'NONE')
                {
                    $this->_query = "SELECT TXR_IngestId,TXR_Id,TXR_Heading,TXR_Score
                    FROM Ingests INNER JOIN Textual_Rows ON ING_Id = TXR_IngestId
                    WHERE ING_FormatId = 4
                    AND ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                    ORDER BY TXR_IngestId,TXR_Id ASC";
                }
                else{
                    
                    $query_filters = str_replace(Array("&","|","!"),Array("OR","OR","NOT"),$this->_filters) ;
                    $query_filters_ready =  preg_replace('/([A-Z][0-9]{7})/', ' TXR_Heading=\'\1\'' , $query_filters);
                
                   
                    $this->_query = "SELECT TXR_IngestId,TXR_Id,TXR_Heading,TXR_Score
                    FROM Ingests INNER JOIN Textual_Rows ON ING_Id = TXR_IngestId
                    WHERE ING_FormatId = 4
                    AND ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                    AND (".$query_filters_ready.")
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

echo "<html><head><title>Analysis of textual data</title></head>";
echo "<body>";

if(! isset($_POST['submit']))
{
    $form = new build_sample_form($db_handler,$logger);
    $form->display_form();
}
else {
    $sample = new build_sample($db_handler,$logger,$_POST);
    echo "<h3>Filters choosen</h3>";
    $sample->getParams();
    echo "<hr>";
    $sample->build_query();
    $sample->analyze_data();
    
}
echo "</body>";
echo "<html>";

?>