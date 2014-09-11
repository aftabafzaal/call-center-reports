<?php
error_reporting(0);
function d($array,$die=false){
    echo "<pre>";
    print_r($array);
    echo "</pre>";        
    if($die){
     exit;         
    }      
}

ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
$link=mysql_connect("localhost:3306", "root", "");
if (!$link) 
{
    die('MySQL connect ERROR: ' . mysql_error());
}
mysql_select_db("asterisk");
?>