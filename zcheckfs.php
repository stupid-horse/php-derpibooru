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

$sql = "SELECT `expected_id_number`, `original_format`, `currentfilename` FROM `images` WHERE `duplicate_of` = '' AND `deletion_reason` = '' AND `currentfilename` != '';";
$result = mysql_query($sql, $con);

while($party = mysql_fetch_array($result))
{
	$expected_id_number = $party['expected_id_number'];
	$original_format = $party['original_format'];
	$currentfilename = $party['currentfilename'];

	$foldername = ceil(( $expected_id_number + 1 ) / 1000) * 1000;

	$dir = "./imagedata/" . $foldername . "/";

	$fn = $dir . $currentfilename;

	if(!file_exists($fn)) {
	    // Errors.
	    	$logfile = 'fscheckerr.log';
			$errormsg = $expected_id_number . "\r\n";
			echo "Missing: " . $errormsg;
			$handle = fopen($logfile, 'a');
			fwrite($handle, $errormsg);
			fclose($handle);
		/*    $sql = "DELETE FROM `spike_derpi`.`images` WHERE `images`.`expected_id_number` = " . $expected_id_number . ";";
			mysql_query($sql) or mysql_error();
   			Echo "Deleted sql for ".$expected_id_number."\r\n";*/
	}
} 
echo date('F j Y h:i:s A') .": ". "ok.";
notify("Derp FS Check done", "yah");
?>


