<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<TITLE>Inbound Report</TITLE></HEAD>
<BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>

<?php
include("connection.php");

echo "<br/>";
echo "<br/>";
echo "---------- Inbound Calls Report:<br/>";
echo "<br/>";
echo "+-------------------------+-------------------+---------------------------+-------------+--------------------+------------+-----------------+-----------------------+<br/>";
echo "| Campaigns or Group                  | Received Calls   | Calls Abandoned 0-30 | Calls Drop | Answered Calls  | Hang ups | Service Level | Average talk Time |<br/>"; 
echo "+-------------------------+-------------------+---------------------------+-------------+--------------------+------------+-----------------+-----------------------+<br/>";

$campaign=$_GET['campaign'];
$country=$_GET['country'];

$query_date_BEGIN="2014-08-04 00:00:00";
$query_date_END="2014-08-04 23:29:59";

switch($country)
{
    case "uk":
        $sqlDIDPattern="select did_pattern,did_description,did_id from vicidial_inbound_dids where did_description like '%$campaign%' and (did_pattern like '44%' or  did_pattern like '20%')";
        break;
    case "us":
        $sqlDIDPattern="select did_pattern,did_description,did_id from vicidial_inbound_dids where did_description like '%$campaign%' and (did_pattern like '8%' or  did_pattern like '1%')";
        break;
}

$grandTotal=array();
$TOTALcalls=0;
$unid_SQL="";  
$did_SQL="";
$DIDunid_SQL='';

$rslt=mysql_query($sqlDIDPattern, $link);
$groupDIDId=array();
while($row=mysql_fetch_assoc($rslt)){
    $group[]=$row['did_pattern'];
    $groupDIDId[]=$row['did_id'];
    $groupDIDDescription[]=$row['did_description'];
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
    while($i < $group_ct)
    {
        
		$did_id[$i]='0';
		$DIDunid_SQL='';
		$did_id[$i] = $groupDIDId[$i];

		$stmt="select uniqueid from vicidial_did_log where did_id='$did_id[$i]';";
        
        $rslt=mysql_query($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$DIDunids_to_print = mysql_num_rows($rslt);
		$k=0;
		while ($k < $DIDunids_to_print)
			{
			$row=mysql_fetch_row($rslt);
			$DIDunid_SQL .= "'$row[0]',";
			$k++;
			}
		$DIDunid_SQL = eregi_replace(",$",'',$DIDunid_SQL);
		if (strlen($DIDunid_SQL)<3) {$DIDunid_SQL="''";}

		$stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";
		$rslt=mysql_query($stmt, $link);
	
		$row=mysql_fetch_row($rslt);

			$stmt="select count(*) from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";
		$rslt=mysql_query($stmt, $link);
        $rowx=mysql_fetch_row($rslt);

        
        $sql="select count(*) as total_drops  from vicidial_closer_log  where `status`='DROP' and length_in_sec>30 and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";
        $query_exec = mysql_query($sql) or die(mysql_error());
$rows = mysql_fetch_assoc($query_exec);
        $total_drops=$rows['total_drops'];


		$stmt="select count(*),sum(length_in_sec) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status IN('DROP','XDROP') and (length_in_sec <= 49999 or length_in_sec is null) and uniqueid IN($DIDunid_SQL);";
		$rslt=mysql_query($stmt, $link);
		$rowy=mysql_fetch_row($rslt);

        $stmt="select count(*),sum(queue_seconds) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND') and uniqueid IN($DIDunid_SQL);";
        
        $rslt=mysql_query($stmt, $link);
        $rowa=mysql_fetch_row($rslt);
    
        $groupDISPLAY =	sprintf("%20s", $group[$i]);
		$gTOTALcalls =	sprintf("%7s", $row[0]);
		$gIVRcalls =	sprintf("%7s", $rowx[0]);
		$gDROPcalls =	sprintf("%7s", $rowy[0]);
        $ANSWEREDcalls  =	sprintf("%10s", $rowa[0]);
        
        if ( ($gDROPcalls < 1) or ($gTOTALcalls < 1) )
		{
            $gDROPpercent = '0';
        }
		else
		{
		  $gDROPpercent = (($gDROPcalls / $gTOTALcalls) * 100);
		  $gDROPpercent = round($gDROPpercent, 2);
		}
		$gDROPpercent =	sprintf("%6s", $gDROPpercent);
        
        if ( ($rowa[0] < 1) or ($ANSWEREDcalls < 1) )
        {
            $average_answer_seconds = '         0';
        }
        else
   	    {
    	   $average_answer_seconds = ($rowa[1] / $rowa[0]);
       	   $average_answer_seconds = round($average_answer_seconds, 2);
    	   $average_answer_seconds =	sprintf("%10s", $average_answer_seconds);
        }        
        

		echo "| $groupDISPLAY - $groupDIDDescription[$i] | $gTOTALcalls | $gDROPcalls | $total_drops  | $ANSWEREDcalls | H | S | $rowa[1] seconds |<br/>";
		$i++;
		
    } 
}//// if ($group_ct > 1)
?>
</BODY>