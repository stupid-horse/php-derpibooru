<?php
// Derpibooru Archiver
// @author: Anon the cuck 
// @copy: 2014
$isBackend = TRUE;
require_once("phpstart.php");
ob_end_clean();
ini_set('max_execution_time', 0);

//Image downloading timeout
ini_set('default_socket_timeout', 360); // 360 Seconds = 6 Mins
//Debug
error_reporting( E_ALL );

//Support Async
ignore_user_abort(true);

// NOTES
// Updates the image info and gets comments into the database

$min = 0;
$max = 400000;

$arr = range($min,$max);

$sqlgetlist = "SELECT `expected_id_number`,`uniqid` FROM `images` WHERE `expected_id_number` BETWEEN '".$min."' AND '".$max."';";

$result = mysql_query($sqlgetlist);

while($row = mysql_fetch_array($result))
{
	$expected_id_number = $row['expected_id_number'];
	$uniqid = $row['uniqid'];

	$url = "https://derpiboo.ru/" . $expected_id_number . ".json?comments=1";

	// Open the cURL session
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'User-Agent: Crond 1.0')
	);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT,15);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$rawjson = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if($httpCode != 200 || $rawjson == ' ' || $rawjson == '') {
	    // Errors.
	    	$logfile = 'httperr.log';
			$errormsg = "Error. HTTP: " . $httpCode . " Content: ".$rawjson." Url: " . $url . " ExpectId: " . $expected_id_number . "\r\n";
			echo date('F j Y h:i:s A') .": ". $errormsg;
			$handle = fopen($logfile, 'a');
			fwrite($handle, $errormsg);
			fclose($handle);
	} else 
	{
		$array = json_decode($rawjson, true);
		$comments = $array['comments'];

		for ($i = 0; $i < count($comments); ++$i) {
			$body = mysql_real_escape_string($comments[$i]['body']);
			$author = mysql_real_escape_string($comments[$i]['author']);
			$image_id = mysql_real_escape_string($comments[$i]['image_id']);
			$posted_at = mysql_real_escape_string($comments[$i]['posted_at']);
			$sqlCheckForDuplicate = "SELECT * FROM `comments` WHERE `body` = '".$body."' AND `author` = '".$author."' AND `image_id` = '".$image_id."' AND `posted_at` = '".$posted_at."' ;";
			if(mysql_num_rows(mysql_query($sqlCheckForDuplicate)) == 0 ){
				//Insert it
				$insertsql = "INSERT INTO `comments` (`body`, `author`, `image_id`, `posted_at`) VALUES ('".$body."', '".$author."', '".$image_id."', '".$posted_at."');";
				$insertresult = mysql_query($insertsql, $con);	
				if($insertresult != TRUE){
							echo date('F j Y h:i:s A') .": ". "MySql failed @ (".$expected_id_number.")!\r\n";				
							$logfile = 'mysqlerr.log';
							$errormsg = "ERROR inserting query ( ".$insertsql." ) for ".$expected_id_number." !\r\n";
							echo date('F j Y h:i:s A') .": ". $errormsg;
							$handle = fopen($logfile, 'a');
							fwrite($handle, $errormsg);
							fclose($handle);
				}
			} else {
				//Already there
				//echo "Comment Already exists.\r\n";
			}
		}

		// Supress the errors.
		$id = sanitize($array['id']);
		$id_number = sanitize($array['id_number']);
		$created_at = sanitize($array['created_at']);
		$file_name = @sanitize($array['file_name']);
		$foldername = ceil(( $expected_id_number + 1 ) / 1000) * 1000;
		$timecomments = sanitize(time());		

		if(!isset($array['deletion_reason'])){
			if(!isset($array['duplicate_of'])){
				$description = @sanitize($array['description']);
				$uploader = @sanitize($array['uploader']);
				$score = @sanitize($array['score']);
				$upvotes = @sanitize($array['upvotes']);
				$downvotes = @sanitize($array['downvotes']);
				$faves = @sanitize($array['faves']);
				$comment_count = @sanitize($array['comment_count']);
				$tags = @sanitize($array['tags']);
				$original_format = @sanitize($array['original_format']);
				$sha512_hash = @sanitize($array['sha512_hash']);
				$orig_sha512_hash = @sanitize($array['orig_sha512_hash']);
				$source_url = @sanitize($array['source_url']);

				$image = sanitize($array['image']);
				$fileurl = "https:" . $array['image'];
				$duplicate_of = NULL;					
				$deletion_reason = NULL;
				$updatesql = "UPDATE `images` SET `description` = '".$description."', `score` =  '".$score."', `upvotes` = '".$upvotes."', `downvotes` = '".$downvotes."', `faves` = '".$faves."', `comment_count` = '".$comment_count."', `tags` = '".$tags."', `timecomments` = '".$timecomments."' WHERE `uniqid` = '".$uniqid."'; ";
				$updateresult = mysql_query($updatesql, $con);
				if($updateresult != TRUE){
							echo date('F j Y h:i:s A') .": ". "MySql failed @ (".$expected_id_number.")!\r\n";				
							$logfile = 'mysqlerr.log';
							$errormsg = "ERROR updateing query ( ".$updatesql." ) for ".$expected_id_number." !\r\n";
							echo date('F j Y h:i:s A') .": ". $errormsg;
							$handle = fopen($logfile, 'a');
							fwrite($handle, $errormsg);
							fclose($handle);
				}
				echo date('F j Y h:i:s A') .": ". $expected_id_number . " ok.\r\n";
			} else {	
				// Duplicate. Don't update
			}				
		} else {
				// Deleted. Don't update
		}			
		// Check that a connection was made
		if (curl_error($ch)){
			// If it wasn't...
			echo date('F j Y h:i:s A') .": ". curl_error($ch);
		}
		curl_close ($ch);
	}
}
echo date('F j Y h:i:s A') .": ". "ok.";
notify("Derp update DB done", "yah");
?>