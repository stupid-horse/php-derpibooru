<?php
// Derpibooru Archiver
// @author: Anon the cuck 
// @copy: 2014
die("WARNING! NEED TO FIX FOR !1");
$isBackend = TRUE;
require_once("phpstart.php");
ob_end_clean();
ini_set('max_execution_time', 0);

//Debug
error_reporting( E_ALL );

//Support Async
ignore_user_abort(true);

//NOTES
// Check the filesystem (Make sure files in the DB (and are not duplicates or deleted) exist on the filesystem and make sure they are not 0 bytes.)

$sql = "SELECT `uniqid`, `expected_id_number`, `original_format`, `image`, `currentfilename` FROM `images` WHERE `duplicate_of` = '' AND `deletion_reason` = '';";
$result = mysql_query($sql, $con);

while($party = mysql_fetch_array($result))
{
	$expected_id_number = $party['expected_id_number'];
	$original_format = $party['original_format'];
    $uniqid = $party['uniqid'];
	$image = $party['image'];
	$currentfilename = $party['currentfilename'];

	$foldername = ceil(( $expected_id_number + 1 ) / 1000) * 1000;

	$dir = "./imagedata/" . $foldername . "/";


	$fn = $dir . $currentfilename;


	if(!file_exists($fn)) {
	    // Errors.
	}
/*
$pattern = '/:/';
$replace = '-colon-';
$tags = preg_replace($pattern, $replace, $tags);
$pattern = '/ /';
$replace = '+';
$tags = preg_replace($pattern, $replace, $tags);

$tags = explode(",+", $tags);
$tags = array_slice($tags, 0, 10);
var_dump($tags);

$sortingArr = array("safe");

$result = array(); // result array
foreach($sortingArr as $val){ // loop
    $result[array_search($val, $tags)] = $val; // adding values
}
print_r($result); // print results


$underscores = implode("_", $tags);
$string = $expected_id_number."__".$underscores;
$cleanstring = sanitizefile($string);


$fnnew = $dir . $expected_id_number . "." . $original_format;*/


$tempbits = explode('/', $image);
$cleanstring  = end($tempbits);


$fnnew = $dir . $cleanstring;
$cleanstring = mysql_real_escape_string($cleanstring);


if($fn != $fnnew){
	$try = rename($fn, $fnnew);
	if($try === TRUE) {	
		$updatesql = "UPDATE `images` SET `currentfilename` = '".$cleanstring."' WHERE `uniqid` = '".$uniqid."'; ";
		$updateresult = mysql_query($updatesql, $con);
		if($updateresult != TRUE){
            echo date('F j Y h:i:s A') .": ". "MySql failed @ (".$expected_id_number.")!\r\n";              
            $logfile = 'mysqlerr.log';
            $errormsg = "ERROR updateing query ( ".$updatesql." ) for ".$expected_id_number." !\r\n";
            echo date('F j Y h:i:s A') .": ". $errormsg;
            $handle = fopen($logfile, 'a');
            fwrite($handle, $errormsg);
            fclose($handle);
			echo date('F j Y h:i:s A') .": ". $expected_id_number . " Ok.\r\n";
		}
	} else { echo date('F j Y h:i:s A') .": ". $expected_id_number . " Error renaming file\r\n"; }
} else { echo date('F j Y h:i:s A') .": ". $expected_id_number . " File name already ok \r\n"; }

} 
notify("Derp FS Update done", "yah");
?>


