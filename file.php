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

echo "<br/>";
echo "<br/>";
echo "---------- Inbound Calls Report:<br/>";
echo "<br/>";
echo "+-------------------------+-------------------+---------------------------+-------------+--------------------+------------+-----------------+-----------------------+<br/>";
echo "| Campaigns or Group                  | Received Calls   | Calls Abandoned 0-30 | Calls Drop | Answered Calls  | Hang ups | Service Level | Average talk Time |<br/>"; 
echo "+-------------------------+-------------------+---------------------------+-------------+--------------------+------------+-----------------+-----------------------+<br/>";



$grandTotal=array();

foreach($campaigns as $campaign_country){
    
    $TOTALcalls=0;
    $query_date_BEGIN="2014-08-04 00:00:00";
    $query_date_END="2014-08-04 23:29:59";
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
        
        
        $stmt="select count(*) as hungup from vicidial_closer_log where term_reason='AGENT' and  call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by term_reason;";
	    
        $rslt=mysql_query($stmt, $link);
        $rowy=mysql_fetch_assoc($rslt);
        $hungUp=$rowy['hungup'];
        
        if($hungUp==''){
            $hungUp=0;
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
           $average_answer_minutes =$average_call_seconds/60;
           $average_answer_minutes=round($average_answer_minutes,2);
        }
    
    
    
        $sql="select count(*) as total_drops  from vicidial_closer_log  where `status`='DROP' and length_in_sec>30 and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL);";
        $query_exec = mysql_query($sql) or die(mysql_error());
$rows = mysql_fetch_assoc($query_exec);
        $total_drops=$rows['total_drops'];
    
        
        $serviceLevel=(($ANSWEREDcalls-$total_drops)/$TOTALcalls)*100;
        $serviceLevel=round($serviceLevel,2);

        print "| <a href='detail.php?country=$countryCode&campaign=$campaign' target='_blank' > $campaign - $countryCode </a>                      |  $TOTALcalls |  $rowd[0]   |   $total_drops |  $ANSWEREDcalls   |   $hungUp  | $serviceLevel % | $average_answer_minutes minutes |<br/>";
        
        $grandTotal['total_calls']+=$TOTALcalls;
        $grandTotal['total_drops']+=$rowd[0];
        $grandTotal['total_drops_30']+=$total_drops;
        $grandTotal['hang_ups']+=0;
        $grandTotal['total_answer']+=$ANSWEREDcalls;
        $grandTotal['average_answer_minutes']+=$average_answer_minutes;
        $grandTotal['hung_up']+=$hungUp;
        
        unset($TOTALcalls,$totalDrops,$ANSWEREDcalls,$IVRcalls,$row,$rowx,$rowy,$stmt,$unid_SQL,$did_SQL,$group,$gDROPcalls);
       
    }////if ($group_ct > 1)
}/// end for each

$grandTotal['service_level']=round((($grandTotal['total_answer']-$grandTotal['total_drops_30'])/$grandTotal['total_calls'])*100)." %";

d($grandTotal);
?>
</BODY>