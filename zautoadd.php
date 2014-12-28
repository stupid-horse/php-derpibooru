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

// Get Local Highest image id
$highestsql = "SELECT `expected_id_number` FROM `images` WHERE `expected_id_number`=(SELECT max(`expected_id_number`) FROM `images`)";
$highestsql = mysql_query($highestsql);
$highestsql = mysql_fetch_array($highestsql);
$highestsql = $highestsql['expected_id_number'];

//Get Derpibooru highest sql
$url = "https://derpiboo.ru/images.json";

// Open the cURL session
$ch = curl_init();
curl_setopt ($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'User-Agent: Crond 1.0')
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT,30);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$rawjson = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if($httpCode != 200 || $rawjson == ' ' || $rawjson == '') {
    // Errors.
    notify("Derpibooru cron failed.","null");
	die("Could not get newest image.");
} else 
{
	$array = json_decode($rawjson, true);
	$highestderp = $array['images'][0]['id_number'];
}

if($highestsql != $highestderp)
{
	echo "New images found. " . ($highestderp - $highestsql) . " remaining.\r\n";
	$min = $highestsql + 1;
	$max = $highestderp;

	$arr = range($min,$max);

	$sqlgetlist = "SELECT `expected_id_number` FROM `images` WHERE `expected_id_number` BETWEEN '".$min."' AND '".$max."';";

	$result = mysql_query($sqlgetlist);
	$indb = [];
	while($row = mysql_fetch_array($result))
	{
		$id = $row['expected_id_number'];
	    $indb[] = $id;
	}

	for ($i = 0; $i < count($arr); ++$i) {

		$expected_id_number = $arr[$i];
		
		if(!in_array($expected_id_number, $indb)){

			$url = "https://derpiboo.ru/" . $expected_id_number . ".json";

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

				$id = sanitize($array['id']);
				$id_number = sanitize($array['id_number']);
				$created_at = sanitize($array['created_at']);
				$file_name = @sanitize($array['file_name']);

				$foldername = ceil(( $expected_id_number + 1 ) / 1000) * 1000;
				$timepolled = sanitize(time());		

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
						//Crap. Download it!
						$dir = "./imagedata/" . $foldername . "/";

						if (!file_exists($dir)) {
						   mkdir($dir);
						}

						if($original_format == 'svg'){
							//svg is bad.
							$logfile = 'svg.log';
							$errormsg = $expected_id_number . " is an svg. \r\n";
							echo date('F j Y h:i:s A') .": ". $errormsg;
							$handle = fopen($logfile, 'a');
							fwrite($handle, $errormsg);
							fclose($handle);
							//Derpibooru makes svg png's.
							$original_format = 'png';
						}

						//$fn = $dir . $expected_id_number . "." . $original_format;
						
						$tempbits = explode('/', $image);
						$cleanstring  = end($tempbits);


						$fn = $dir . $cleanstring;
						$cleanstring = mysql_real_escape_string($cleanstring);
						if(!file_exists($fn))
						{
							$fileData = file_get_contents( $fileurl);				// Attempt to grab file from site's CDN

							if( $writeResult = file_put_contents( $fn, $fileData ) ){		// Attempt to write result to file
								//Continue
								echo date('F j Y h:i:s A') .": ". $expected_id_number . " OK!\r\n";

								$insertsql = "INSERT INTO `images` (`id`, `id_number`, `expected_id_number`, `created_at`, `file_name`, `description`, `uploader`, `image`, `score`, `upvotes`, `downvotes`, `faves`, `comment_count`, `tags`, `original_format`, `sha512_hash`, `orig_sha512_hash`, `source_url`, `timepolled`, `duplicate_of`, `deletion_reason`, `currentfilename`) VALUES ('".$id."', '".$id_number."', '".$expected_id_number."', '".$created_at."', '".$file_name."', '".$description."', '".$uploader."', '".$image."', '".$score."', '".$upvotes."', '".$downvotes."', '".$faves."', '".$comment_count."', '".$tags."', '".$original_format."', '".$sha512_hash."', '".$orig_sha512_hash."', '".$source_url."', '".$timepolled."', '".$duplicate_of."', '".$deletion_reason."', '".$cleanstring ."');";
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
								echo date('F j Y h:i:s A') .": ". "ERROR (".$fileurl.")!\r\n";				
								$logfile = 'fserr.log';
								$errormsg = "ERROR (ID: ".$expected_id_number." )!\r\n";
								echo date('F j Y h:i:s A') .": ". $errormsg;
								$handle = fopen($logfile, 'a');
								fwrite($handle, $errormsg);
								fclose($handle);
							}
						} else {
							echo date('F j Y h:i:s A') .": ". $expected_id_number . "already exists in fs. Adding DB data.";

							$insertsql = "INSERT INTO `images` (`id`, `id_number`, `expected_id_number`, `created_at`, `file_name`, `description`, `uploader`, `image`, `score`, `upvotes`, `downvotes`, `faves`, `comment_count`, `tags`, `original_format`, `sha512_hash`, `orig_sha512_hash`, `source_url`, `timepolled`, `duplicate_of`, `deletion_reason`, `currentfilename`) VALUES ('".$id."', '".$id_number."', '".$expected_id_number."', '".$created_at."', '".$file_name."', '".$description."', '".$uploader."', '".$image."', '".$score."', '".$upvotes."', '".$downvotes."', '".$faves."', '".$comment_count."', '".$tags."', '".$original_format."', '".$sha512_hash."', '".$orig_sha512_hash."', '".$source_url."', '".$timepolled."', '".$duplicate_of."', '".$deletion_reason."', '".$cleanstring ."');";
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
						}
					} else {	
						// Only for real ones.
						$description = 'none';
						$uploader = 'none';
						$score = 0;
						$upvotes = 0;
						$downvotes = 0;
						$faves = 0;
						$comment_count = 0;
						$tags = '';
						$original_format = '';
						$sha512_hash = '';
						$orig_sha512_hash = '';
						$source_url = '';		
						$duplicate_of = sanitize($array['duplicate_of']);
						echo date('F j Y h:i:s A') .": ". $expected_id_number . " is a duplicate of " . $duplicate_of . "\r\n";
						$image = NULL;
						$deletion_reason = NULL;

						$insertsql = "INSERT INTO `images` (`id`, `id_number`, `expected_id_number`, `created_at`, `file_name`, `description`, `uploader`, `image`, `score`, `upvotes`, `downvotes`, `faves`, `comment_count`, `tags`, `original_format`, `sha512_hash`, `orig_sha512_hash`, `source_url`, `timepolled`, `duplicate_of`, `deletion_reason`, `currentfilename`) VALUES ('".$id."', '".$id_number."', '".$expected_id_number."', '".$created_at."', '".$file_name."', '".$description."', '".$uploader."', '".$image."', '".$score."', '".$upvotes."', '".$downvotes."', '".$faves."', '".$comment_count."', '".$tags."', '".$original_format."', '".$sha512_hash."', '".$orig_sha512_hash."', '".$source_url."', '".$timepolled."', '".$duplicate_of."', '".$deletion_reason."', '".$cleanstring ."');";
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
					}				
				} else {
						// Only for real ones.
						$description = 'none';
						$uploader = 'none';
						$score = 0;
						$upvotes = 0;
						$downvotes = 0;
						$faves = 0;
						$comment_count = 0;
						$tags = '';
						$original_format = '';
						$sha512_hash = '';
						$orig_sha512_hash = '';
						$source_url = '';		
						$deletion_reason = sanitize($array['deletion_reason']);;
						echo date('F j Y h:i:s A') .": ". $expected_id_number . " was deleted becuase of " . $deletion_reason . "\r\n";
						$image = NULL;
						$duplicate_of = NULL;

						$insertsql = "INSERT INTO `images` (`id`, `id_number`, `expected_id_number`, `created_at`, `file_name`, `description`, `uploader`, `image`, `score`, `upvotes`, `downvotes`, `faves`, `comment_count`, `tags`, `original_format`, `sha512_hash`, `orig_sha512_hash`, `source_url`, `timepolled`, `duplicate_of`, `deletion_reason`, `currentfilename`) VALUES ('".$id."', '".$id_number."', '".$expected_id_number."', '".$created_at."', '".$file_name."', '".$description."', '".$uploader."', '".$image."', '".$score."', '".$upvotes."', '".$downvotes."', '".$faves."', '".$comment_count."', '".$tags."', '".$original_format."', '".$sha512_hash."', '".$orig_sha512_hash."', '".$source_url."', '".$timepolled."', '".$duplicate_of."', '".$deletion_reason."', '".$cleanstring ."');";
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
				}



				
				// Check that a connection was made
				if (curl_error($ch)){
					// If it wasn't...
					echo date('F j Y h:i:s A') .": ". curl_error($ch);
				}
				curl_close ($ch);
			}
		} else { echo date('F j Y h:i:s A') .": ". $expected_id_number . " already exists!\n"; }
	}
}


echo "ok auto scrape.";
if(isset($_GET['ref']))
  {
  //Incase they called the url from the browser, send them back to their thread list.
  header("Location:thread.php");
}
?>