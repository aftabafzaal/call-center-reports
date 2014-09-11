<?php
include_once("connection.php");

$campaingns=array('equinox_us','equinox_uk');

$campaingnsTitle=
array(
    'equinox'=>'Equinox',
);




echo "<br/>";
echo "<br/>";
echo "---------- Inbound Calls Report:<br/>";
echo "<br/>";
echo "+----------------------+---------+---------+---------+---------+<br/>";
echo "| Campaigns or Group                  | Received Calls   | Calls Abandoned 0-30 | Calls Drop | Answered Calls  | DROP %  | IVR     |<br/>"; 
echo "+----------------------+---------+---------+---------+---------+----------------+----------------+-------------------+------------<br/>";



foreach($campaingns as $campaingn_country){
$TOTALcalls=0;
    $query_date_BEGIN="2014-08-04 00:00:00";
$query_date_END="2014-08-04 23:29:59";
$unid_SQL="";  
    $did_SQL="";
    	$DIDunid_SQL='';
    list($campaingn,$countryCode)=explode("_",$campaingn_country);
    $query="";
    switch($countryCode){
        
        case "uk":
             $sqlDIDPattern="select did_pattern,did_description from vicidial_inbound_dids where did_description like '%equinox%' and (did_pattern like '44%' or  did_pattern like '20%')";
            break;
        case "us":
             $sqlDIDPattern="select did_pattern,did_description from vicidial_inbound_dids where did_description like '%equinox%' and (did_pattern like '8%' or  did_pattern like '1%')";
            break;
    }
 


   
    $rslt=mysql_query($sqlDIDPattern, $link);
    
    while($row=mysql_fetch_assoc($rslt)){
        $group[]=$row['did_pattern'];
    }
    $group_string="";
    $group_SQL ="";
    $groupQS ="";
    //d($group);
    $group_ct = count($group);
    $i=0;
    while($i < $group_ct)
    {
        $group_string .= "$group[$i]|";
    	$group_SQL .= "'$group[$i]',";
    	$groupQS .= "&group[]=$group[$i]";
    	$i++;
    }

    $group_SQL = str_replace(",$",'',$group_SQL);// eregi_replace(",$",'',$group_SQL);
    $group_SQL= rtrim($group_SQL,',');
    $stmt="select did_id from vicidial_inbound_dids where did_pattern IN($group_SQL);";
    
    $rslt=mysql_query($stmt, $link);
    $dids_to_print = mysql_num_rows($rslt);
    $i=0;

    while ($i < $dids_to_print)
    {
        $row=mysql_fetch_row($rslt);
    	$did_id[$i] = $row[0];
    	$did_SQL .= "'$row[0]',";
    	$i++;
    }
    $did_SQL = str_replace(",$",'',$did_SQL);
    $did_SQL= rtrim($did_SQL,',');
    
    
    $stmt="select uniqueid from vicidial_did_log where did_id IN($did_SQL);";
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
        $totalDrops=0;
        $totalIVRcalls=0;
        while($i < $group_ct)
    	{
        
            $did_id[$i]='0';
     
    		$stmt="select did_id from vicidial_inbound_dids where did_pattern='$group[$i]';";
    		$rslt=mysql_query($stmt, $link);
    		
    		$Sdids_to_print = mysql_num_rows($rslt);
    		if ($Sdids_to_print > 0)
    		{
    		  $row=mysql_fetch_row($rslt);
    		  $did_id[$i] = $row[0];
            }
    
    		$stmt="select uniqueid from vicidial_did_log where did_id='$did_id[$i]';";
    		$rslt=mysql_query($stmt, $link);
    		$DIDunids_to_print = mysql_num_rows($rslt);
    		$k=0;
    		while ($k < $DIDunids_to_print)
    		{
    		  $row=mysql_fetch_row($rslt);
    		  $DIDunid_SQL .= "'$row[0]',";
    		  $k++;
    		}
            
            $DIDunid_SQL = str_replace(",$",'',$DIDunid_SQL);
            $DIDunid_SQL= rtrim($DIDunid_SQL,',');
            if (strlen($DIDunid_SQL)<3) {$DIDunid_SQL="''";}
                
            $stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";			 /// 472 line
    		$rslt=mysql_query($stmt, $link);
    		$row=mysql_fetch_row($rslt);	
            
            $stmt="select count(*) from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";  //481
    		
            $rslt=mysql_query($stmt, $link);
    		$rowx=mysql_fetch_row($rslt);
            
            $stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status IN('DROP','XDROP') and (length_in_sec <= 49999 or length_in_sec is null) and uniqueid IN($DIDunid_SQL);"; //490 line
    		
    		$rslt=mysql_query($stmt, $link);
    	
    		$rowy=mysql_fetch_row($rslt);
    
    		$groupDISPLAY =	sprintf("%20s", $group[$i]);
    		$gTOTALcalls =	sprintf("%7s", $row[0]);
    		$gIVRcalls =	sprintf("%7s", $rowx[0]);
    		$gDROPcalls =	sprintf("%7s", $rowy[0]);
    		if ( ($gDROPcalls < 1) or ($gTOTALcalls < 1) )
    		{$gDROPpercent = '0';}
    		else
    		{
    		$gDROPpercent = (($gDROPcalls / $gTOTALcalls) * 100);
    		$gDROPpercent = round($gDROPpercent, 2);
    		}
    		$gDROPpercent =	sprintf("%6s", $gDROPpercent);
    
    		
            
            $stmt="select did_description from vicidial_inbound_dids where did_pattern='$group[$i]';"; // 
            $rslt=mysql_query($stmt, $link);
    		$rowdid=mysql_fetch_assoc($rslt);
            $didDescription=$rowdid['did_description'];
            
            //echo "| $groupDISPLAY - $didDescription | $gTOTALcalls | $gDROPcalls | $gDROPpercent% | $gIVRcalls |<br/>";
    		
            $totalCalls+=$gTOTALcalls;
            $totalDrops+=$gDROPcalls;
            $totalIVRcalls+=$gIVRcalls;
            	
            $i++;
        }
        
        if ( ($totalDrops < 1) or ($totalDrops < 1) )
    	{
            $dropPercent = '0';
        }
    	else
    	{
    	   $dropPercent = (($totalDrops / $totalCalls) * 100);
    	   $dropPercent = round($dropPercent, 2);
        }
        
        
        
        echo "| Equinox - UK | $totalCalls | $totalDrops | $dropPercent% | $totalIVRcalls |<br/>";
        
      
    $stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL);";
    $rslt=mysql_query($stmt, $link);
    $row=mysql_fetch_row($rslt);
    
    d($row);
    $stmt="select count(*),sum(queue_seconds) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND') and uniqueid IN($unid_SQL);";
    $rslt=mysql_query($stmt, $link);
    $rowy=mysql_fetch_row($rslt);
    
    $stmt="select count(*) from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and uniqueid IN($unid_SQL);";
    

    $rslt=mysql_query($stmt, $link);
    $rowx=mysql_fetch_row($rslt);
    //$TOTALcalls =	sprintf("%10s", $row[0]);
    echo $TOTALcalls=$row[0];
    $IVRcalls =	sprintf("%10s", $rowx[0]);
    $TOTALsec =		$row[1];
    
    if ( ($row[0] < 1) or ($TOTALsec < 1) )
    	{$average_call_seconds = '         0';}
    else
    	{
    	$average_call_seconds = ($TOTALsec / $row[0]);
    	$average_call_seconds = round($average_call_seconds, 0);
    	$average_call_seconds =	sprintf("%10s", $average_call_seconds);
    	}
    $ANSWEREDcalls  =	sprintf("%10s", $rowy[0]);
    if ( ($ANSWEREDcalls < 1) or ($TOTALcalls < 1) )
    	{$ANSWEREDpercent = '0';}
    else
    	{
    	$ANSWEREDpercent = (($ANSWEREDcalls / $TOTALcalls) * 100);
    	$ANSWEREDpercent = round($ANSWEREDpercent, 0);
    	}
    if ( ($rowy[0] < 1) or ($ANSWEREDcalls < 1) )
    	{$average_answer_seconds = '         0';}
    else
    	{
    	$average_answer_seconds = ($rowy[1] / $rowy[0]);
    	$average_answer_seconds = round($average_answer_seconds, 2);
    	$average_answer_seconds =	sprintf("%10s", $average_answer_seconds);
    	}
    
    
    //echo "Total calls taken in to this In-Group:        $TOTALcalls\n";
    //echo "Average Call Length for all Calls:            $average_call_seconds seconds\n";
    //echo "Answered Calls:                               $ANSWEREDcalls  $ANSWEREDpercent%\n";
    //echo "Average queue time for Answered Calls:        $average_answer_seconds seconds\n";
    //echo "Calls taken into the IVR for this In-Group:   $IVRcalls\n";
    //echo "| $campaingn - $countryCode   |  $TOTALcalls |   0  |  $totalDrops |  $ANSWEREDcalls   | $IVRcalls | <br/>";
    unset($TOTALcalls,$totalDrops,$ANSWEREDcalls,$IVRcalls,$row,$rowx,$rowy,$stmt,$unid_SQL);
}////if ($group_ct > 1)

    
}/// end for each











/*


$sql="select GROUP_CONCAT(uniqueid) as uniqueids from vicidial_did_log where did_id IN 
(select did_id from vicidial_inbound_dids where did_description like '%equinox%' and (did_pattern like '1%' or  did_pattern like '8%'));";
$rslt=mysqli_query($link,$sql);


$i=0;
$row=mysqli_fetch_assoc($rslt);
$uniqueids=$row['uniqueids'];
echo $stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($uniqueids);";
exit;*/
//echo $unid_SQL = preg_replace(",$/i", '',$unid_SQL);
//echo $unid_SQL = eregi_replace(",$",'',$unid_SQL);
?>
