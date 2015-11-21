<?php
    /**
 * This class encapsulates the building of the analysis form
 *  
 * @package default
 * @author samuel levy  
  */
class build_filter_form_text2umls extends LogicException
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
        echo " <select  name=".$title."[] multiple=multiple size=3 >";
          
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
     * Displays select with option according to the filters received
     * receives also javascript code
     *
     * @return void
     * @author samuel levy  
     */
    function display_select_js($statement,$id,$title,$jscode)
    {
        $result = $this->_db_handle->SelectDB($statement);
        echo " <select id=".$id." ".$jscode." name=".$title." size=20 >";
          
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
        
        echo "<table border=0>";
        echo "<tr valign=top><td>";
        $this->display_dates($start_date, $end_date);
        
        
        $statement = "SELECT DISTINCT CLT_Id,CLT_ExternalId FROM Clients INNER JOIN Ingests on CLT_Id = ING_ClientId
        WHERE ING_FormatId = 4";
        
        echo "</td><td align=left>";
        $this->display_select($statement,"Client_Id");
        echo "</td><td align=left>";
        
        $statement = "SELECT DISTINCT ING_BatchId,ING_BatchId FROM Ingests
        WHERE ING_FormatId = 4";
        
        $this->display_select($statement,"Batch_Id");
        echo "</td></tr>";
        
        $statement = "SELECT UMC_Code,UMC_Translation FROM UMLS_Correspondance
        order by UMC_Translation ASC";
        
        echo "<tr valign=top>";
        echo "<td>";
        $this->display_select_js($statement,"umlsid","UMLS_Codes","onDblClick='document.getElementById(\"filters\").value += document.getElementById(\"umlsid\").value + \" , \" '");
        echo "</td>";
        echo "<td><textarea id=filters name=filters rows=10 cols=60></textarea><br/>
        <font size=1>UMLS Terms to analyze (separated by a ,) - for full analysis type NONE (long!)</font></td>";
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
class build_results_text2umls extends LogicException
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
        echo "<h2>Analyzis (first 50 results - for all results download the csv)</h2>";
        
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
          
             if(count($results) == 0)
            {
                echo "No results</br>";
                echo "</table>";
                return;
            }
            
            
            
            echo "<a href='tmp/".$this->_datasetId.".png' target=_blank>Association graph</a>&nbsp;&nbsp;<a href='tmp/".$this->_datasetId.".csv' target=_blank>Result csv file</a>&nbsp;&nbsp;";
        
            if(count($results) > 1)
            {
                echo "<a href='tmp/cloud_".$this->_datasetId.".png' target=_blank>Cloud of tags</a></br>";
            }
        
            echo "<br/><table border=1>";
            echo "<tr bgcolor=Tan><td><b>1st Term</b></td><td><b>2nd Term</b></td></td><td><b>Coocurrences</b></td><td><b>Percentage vs Total in Batch</b></td></tr>";
            $buffer = "1st Term,2nd Term,Coocurrences,Percentage vs Total in Batch\n";
            
            $i = 0;
            
            foreach($results as $result)
            {
                    
                if($i < 50)    
                    echo "<tr bgcolor=PapayaWhip><td>".$result[0]."</td><td>".$result[1]."</td><td>".$result[2]."</td><td>".($result[3]*100)."%</td></tr>";
                
                $buffer .= $result[0].",".$result[1].",".$result[2].",".$result[3]."\n";
                $i++;
            }
            
            echo "</table>";
            
            // saves the full results into csv file
            fwrite(fopen("tmp/".$this->_datasetId.".csv","w+"),$buffer);
            
           
            // launches the generation of a connected graph displaying the coocurrences
            exec('python create_graph.py '.$this->_datasetId.' > /dev/null');
           
            if($i > 1)
                exec('export TERM=xterm ; python create_cloud.py '.$this->_datasetId);
          
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
                    
                    $query_filters = str_replace(Array("&","|","!",","),Array(" OR "," OR "," NOT "," OR "),rtrim(rtrim($this->_filters," , "))) ;
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
  

/**
 * This class encapsulates the building of the analysis form
 *  
 * @package default
 * @author samuel levy  
  */
class build_filter_form_freetext extends LogicException
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
        echo " <select  name=".$title."[] multiple=multiple size=4 >";
          
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
        WHERE ING_FormatId in (1,2,3,5)";
        
        echo "</td><td align=left>";
        $this->display_select($statement,"Client_Id");
        echo "</td><td align=left>";
        
        $statement = "SELECT DISTINCT FRM_Id,FRM_Name FROM Formats INNER JOIN Ingests on FRM_Id = ING_FormatId
        WHERE ING_FormatId in (1,2,3,5)";
        
        echo "</td><td align=left>";
        $this->display_select($statement,"Format_Type");
    
        
        $statement = "SELECT DISTINCT ING_BatchId,ING_BatchId FROM Ingests
        WHERE ING_FormatId in (1,2,3,5)";
        
        $this->display_select($statement,"Batch_Id");
        echo "</td></tr>";
        
        echo "</tr></table>";
         echo "<td>Your Filters<br/>
         <textarea id=filters name=filters rows=10 cols=60></textarea><br/>
         <font size=1>authorized operators ; ( ) & | ! - please put a space between every term</font><br/>";
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
class build_results_freetext extends LogicException
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
     * Launches the analysis of the data
     *
     * @return void
     * @author samuel levy  
     */
    function analyze_data()
    {
        $filename = date("YmdHis").".csv";
        echo "<h2>Analyzis</h2>";
        
        $results = $this->_db_handle->SelectDB($this->_query);
        
             
             if(count($results) == 0)
            {
                echo "No results</br>";
                echo "</table>";
                return;
            }
        
            echo "<a href='tmp/".$filename.".csv' target=_blank>Result csv file</a><br/><br/>";
            echo "<table border=1>";
            echo "<tr bgcolor=Tan><td><b>Ocurrences</b></td><td><b>Percentage from batch</b></td></tr>";
            $buffer = "Ocurrences,Percentage\n";
            
            foreach($results as $result)
            {
                echo "<tr bgcolor=PapayaWhip><td>".$result[0]."</td><td>".$result[1]."%</td></tr>";
                $buffer .= $result[0].",".$result[1]."\n";
            }
            
            echo "</table>";
            
            // saves the full results into csv file
            fwrite(fopen("tmp/".$filename.".csv","w+"),$buffer);
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
                    $query_filters = explode(" ",rtrim($this->_filters));
                    
                    $query = "";
                    
                    foreach($query_filters as $filter)
                    {
                        switch($filter)
                        {
                            case '&':
                                $query .= " AND ";
                            break;
                            case '|':
                                $query .= " OR ";
                            break;
                            case '!':
                                $query .= " NOT ";
                            break;
                            case '(':
                                $query .= " ( ";
                            break;      
                            case ')':
                                $query .= " ) ";
                            break;      
                            default:
                                $query .= " ING_Payload like '%".$filter."%'";
                            break;
                            
                        }
                    }
                   
                    $this->_query = "SELECT count(*),count(*) / (SELECT count(*) FROM Ingests WHERE ING_FormatId in ($this->_formats) AND ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate' AND ING_ClientId = ".$this->_clientId." AND ING_BatchId in (".$this->_batchId."))* 100 as Percentage
                    FROM Ingests
                    WHERE ING_FormatId in ($this->_formats)
                    AND ING_IngestTime BETWEEN '$this->_startdate' and '$this->_enddate'
                    AND (".$query.")
                    AND ING_ClientId = ".$this->_clientId."
                    AND ING_BatchId in (".$this->_batchId.")";
                    
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
        echo "Start date <input name=start_date type=text value='".$start."'>  End Date <input name=end_date type=text value='".$end."'>";
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
    
    function display_select_unique($statement,$title)
    {
        $result = $this->_db_handle->SelectDB($statement);
        echo " <select name=".$title."[] size=4 >";
          
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
        echo "<form method=post>";
        echo "<table border=0><tr valign=top align=center><td align=left>";
        
        $this->display_dates($start_date, $end_date);
        
        $statement = "SELECT DISTINCT CLT_Id,CLT_ExternalId FROM Clients INNER JOIN Ingests on CLT_Id = ING_ClientId
        WHERE ING_FormatId = 1";
        
        echo "</td><td align=left>";
        $this->display_select_unique($statement,"Client_Id");
        echo "</td><td align=left>";
        
        $statement = "SELECT DISTINCT ING_BatchId,ING_BatchId FROM Ingests
        WHERE ING_FormatId = 1";
        
        $this->display_select_unique($statement,"Batch_Id");
        echo "</td></tr></table>";
        
        $statement = "SELECT DISTINCT FRM_Id,FRM_Name FROM Formats INNER JOIN Ingests on FRM_Id = ING_FormatId
        WHERE FRM_id = 1";
        
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
    private $_clientId;
    private $_batchId;
    
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
                AND ING_BatchId = $this->_batchId
                AND ING_ClientId = $this->_clientId
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
                AND ING_BatchId = $this->_batchId
                AND ING_ClientId = $this->_clientId
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
                AND ING_FormatId IN ($this->_formats)
                AND ING_BatchId = $this->_batchId
                AND ING_ClientId = $this->_clientId";
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
        
            if(count($results) == 0)
            {
                echo "No results</br>";
                echo "</table>";
                return;
            }
        
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
                    case 'Batch_Id':
                        $this->_batchId = $buffer;
                    break;
                    case 'Client_Id':
                        $this->_clientId = $buffer;
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

  
?>