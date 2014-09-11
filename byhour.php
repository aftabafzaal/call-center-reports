<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<TITLE>Inbound Report</TITLE></HEAD>
<BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<?php
include("connection.php");
$campaigns=array('equinox_uk','equinox_us','rvtl_us','rvtl_uk','cleanse_uk','cleanse_us','ketone_uk','ketone_us','yacon_uk','yacon_us');

$campaingnsTitle=
array(
    'equinox'=>'Equinox',
);
$query_date_BEGIN="2014-08-04";
$query_date_END="2014-08-04";

$beginHour=" 00:00:00";
$endHour=" 00:59:59";

echo "<br/>";
echo "<br/>";
echo "---------- Inbound Calls Report( $query_date_BEGIN ):<br/>";
echo "<br/>";
echo "+-------------------------+-------------------+---------------------------+-------------+--------------------+------------+-----------------+-----------------------+<br/>";
echo "| Campaigns or Group                  | Received Calls   | Calls Abandoned 0-30 | Calls Drop | Answered Calls  | Hang ups | Service Level | Average talk Time |<br/>"; 
echo "+-------------------------+-------------------+---------------------------+-------------+--------------------+------------+-----------------+-----------------------+<br/>";



$grandTotal=array();




$query_date_BEGIN= $query_date_BEGIN.$beginHour;
$query_date_END = $query_date_END.$endHour;


foreach($campaigns as $campaign_country){
$TOTALcalls=0;

$unid_SQL="";  
$did_SQL="";
$DIDunid_SQL='';

list($campaign,$countryCode)=explode("_",$campaign_country);
    $query="";
    switch($countryCode)
    {
        case "uk":
            $sqlDIDPattern="select did_pattern,did_description,did_id from vicidial_inbound_dids where did_description like '%$campaign%' and (did_pattern like '44%' or  did_pattern like '20%')";
            break;
        case "us":
            $sqlDIDPattern="select did_pattern,did_description,did_id from vicidial_inbound_dids where did_description like '%$campaign%' and (did_pattern like '8%' or  did_pattern like '1%')";
            break;
    }
 
    $rslt=mysql_query($sqlDIDPattern, $link);
    $groupDIDId=array();
    
    while($row=mysql_fetch_assoc($rslt)){
        $group[]=$row['did_pattern'];
        $groupDIDId[]=$row['did_id'];
    }
    
    $sqlGroupDIDId=implode(',',$groupDIDId);
    
    $group_ct = count($group);
    
    $stmt="select uniqueid from vicidial_did_log where did_id IN($sqlGroupDIDId);";
    $rslt=mysql_query($stmt, $link);
    $unids_to_print = mysql_num_rows($rslt);
    $i=0;
    
    while ($i < $unids_to_print)
    {
        $row=mysql_fetch_row($rslt);
        $unid_SQL .= "'$row[0]',";
        $i++;
    }
    
    $unid_SQL = str_replace(",$",'',$unid_SQL);
    $unid_SQL= rtrim($unid_SQL,',');
    $totalDrops=0;
    
    
    
    for($count=1;$count<=24;$count++)
    {
        
        if ($group_ct > 1)
        {
        	$i=0;
            $totalCalls=0;
            
            $stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status IN('DROP','XDROP') and (length_in_sec <= 49999 or length_in_sec is null) and uniqueid IN($unid_SQL);"; //490 line
        	$rslt=mysql_query($stmt, $link);
        	$rowd=mysql_fetch_row($rslt);
            $gDROPcalls =	sprintf("%7s", $rowd[0]);
            
            $stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL);";
            $rslt=mysql_query($stmt, $link);
            $row=mysql_fetch_row($rslt);
        
        
        
            $stmt="select count(*) from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and uniqueid IN($unid_SQL);";
            $rslt=mysql_query($stmt, $link);
            $rowx=mysql_fetch_row($rslt);
            $TOTALcalls =	sprintf("%10s", $row[0]);
            $IVRcalls =	sprintf("%10s", $rowx[0]);
            $TOTALsec =		$row[1];
        
            if ( ($row[0] < 1) or ($TOTALsec < 1) )
       	    {
                $average_call_seconds = '         0';
            }
            else
            {
               $average_call_seconds = ($TOTALsec / $row[0]);
        	   $average_call_seconds = round($average_call_seconds, 0);
        	   $average_call_seconds =	sprintf("%10s", $average_call_seconds);
       	    }
        
        
        
            $stmt="select count(*),sum(queue_seconds) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND') and uniqueid IN($unid_SQL);";
            $rslt=mysql_query($stmt, $link);
            $rowy=mysql_fetch_row($rslt);
        
            $ANSWEREDcalls  =	sprintf("%10s", $rowy[0]);
            if ( ($ANSWEREDcalls < 1) or ($TOTALcalls < 1) )
            {   
                $ANSWEREDpercent = '0';
            }
            else
            {
                $ANSWEREDpercent = (($ANSWEREDcalls / $TOTALcalls) * 100);
                $ANSWEREDpercent = round($ANSWEREDpercent, 0);
       	    }
            if ( ($rowy[0] < 1) or ($ANSWEREDcalls < 1) )
            {
                $average_answer_seconds = '         0';
            }
            else
       	    {
        	   $average_answer_seconds = ($rowy[1] / $rowy[0]);
           	   $average_answer_seconds = round($average_answer_seconds, 2);
        	   $average_answer_seconds =	sprintf("%10s", $average_answer_seconds);
            }
        
        
        
            $sql="select count(*) as total_drops  from vicidial_closer_log  where `status`='DROP' and length_in_sec>30 and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL);";
            $query_exec = mysql_query($sql) or die(mysql_error());
            $rows = mysql_fetch_assoc($query_exec);
            $total_drops=$rows['total_drops'];
        
        
        //echo "Total calls taken in to this In-Group:        $TOTALcalls\n";
        //echo "Average Call Length for all Calls:            $average_call_seconds seconds\n";
        //echo "Answered Calls:                               $ANSWEREDcalls  $ANSWEREDpercent%\n";
        //echo "Average queue time for Answered Calls:        $average_answer_seconds seconds\n";
        //echo "Calls taken into the IVR for this In-Group:   $IVRcalls\n";
        $beginHour=date("H:i:s",strtotime($query_date_BEGIN));
        $endHour=date("H:i:s",strtotime($query_date_END));;
            print "|$count+1 $campaign - $countryCode ($beginHour to $endHour )                    |  $TOTALcalls |  $total_drops  |  $rowd[0] |  $ANSWEREDcalls   |   Hang ups  | Service level | $average_call_seconds seconds |<br/>";
            
            $grandTotal['total_calls']+=$TOTALcalls;
            $grandTotal['total_drops']+=$rowd[0];
            $grandTotal['total_drops_30']+=$total_drops;
            $grandTotal['hang_ups']+=0;
            $grandTotal['service_level']+=0;
            $grandTotal['average_call_seconds']+=$average_call_seconds;
           
            
           
        }////if ($group_ct > 1)
        
        $query_date_BEGIN = date("Y-m-d H:i:s",strtotime($query_date_BEGIN)+3600);
        $query_date_END = date("Y-m-d H:i:s",strtotime($query_date_END)+3600);
    }/// for count 
    
    //exit;
}/// end for each
//echo "<br/>";
//d($grandTotal);
?>
</BODY>