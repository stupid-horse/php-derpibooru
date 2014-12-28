<?php
// Derpibooru Archiver
// @author: Anon the cuck 
// @copy: 2014
ob_start();
session_start();

// CONFIG
define("URL_ROOT","https://LocalhosT/");
define("MYSQL_HOST","127.0.0.1");
define("MYSQL_USERNAME","spike_derpi");
define("MYSQL_PASSWORD","YrRGLwN2xfmXKBVU");
define("MYSQL_DATABASE","spike_derpi");

//Change these two values and rename the files if you want to keep others from triggering the cron.
define("URL_TO_CRON","https://Localhost/zcheckdb.php");

// Use this to switch between WebCron calling of the downloader or the shell method. (Shell is faster, but is not supported by many hosts.)
define("USEWEBCRON", FALSE);

// Use this to disable authenticaiton. 
define("ISPUBLIC", TRUE);

// TODO: Have a silence setting
// If $silence exists, then the notifications will not be sent.
//$silence = TRUE;

function notify($message,$link)
{

}

// Everything Else is code. Edit if you know what you are doing.

$con = mysql_connect(MYSQL_HOST,MYSQL_USERNAME,MYSQL_PASSWORD);
if (!$con)
{
die('Check your MySQL settings in phpstart.php Error: ' . mysql_error());
}

mysql_select_db(MYSQL_DATABASE);

mysql_set_charset('utf8mb4');

if(@$isBackend)
{
  //Carry on.
}
else
{
  if(ISPUBLIC === TRUE){
    $_SESSION['derpiusername'] = "Anon";
    $_SESSION['derpiid'] = 0;
  } 
    if(@$islogin) // Supress those annoying PHP notices.... I know, it is undefined for a reason.
    {
      if(isset($_SESSION['derpiusername']) && isset($_SESSION['derpiid']))
      {
        // User logged in. At login Page.
        header("location:index.php");
        exit();
      }
    }else{
      if(!isset($_SESSION['derpiusername']) || !isset($_SESSION['derpiid']))
      {
        // Not logged in. Redirect to login.
        header("location:login.php");
        exit();
      }
      if((isset($_SESSION['derpiusername']) && !isset($_SESSION['derpiid'])) || (!isset($_SESSION['derpiusername']) && isset($_SESSION['derpiid'])))
      {
        // Corrupted Session...
        session_unset();
        session_destroy();
        header("location:login.php");
        exit();
      }
    }
}

function asynccron()
{
    if(USEWEBCRON === TRUE){
      $curlvars = array(
          'url' => $url);

      $curlvars_string = "";

      foreach($curlvars as $key=>$value) { $curlvars_string .= $key.'='.$value.'&'; }
      rtrim($curlvars_string, '&');

      //What's the url?
      $url = URL_TO_CRON;

      // Open the cURL session
      $ch = curl_init();

      curl_setopt ($ch, CURLOPT_URL, $url);

      curl_setopt ($ch, CURLOPT_POST, 1);                                                                     
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $curlvars_string);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'User-Agent: Client 1.0',
        'Content-Type: application/x-www-form-urlencoded')                                                                       
      );
      curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

      $wub = curl_exec($ch);

      //get Curl info
      $info = curl_getinfo($ch);

      // Check that a connection was made
      if (curl_error($ch)){
              // If it wasn't...
      }

      curl_close ($ch);
  } else {
// This setup will try to call cronrun.php in a background shell. Keeping it from tying up Apache.
    $command = 'php zcheckdb.php';
    launchBackgroundProcess($command);
  }
}


function update_query($dvar, $add_dvar, $remove_dvar, $change_dvar){
  $sub_sql = '';
  foreach($dvar as $k => $v){
    if(!in_array($k, $remove_dvar)){ // if not in remove array
      if(array_key_exists($k, $change_dvar)){$v = $change_dvar[$k];}
      $v = mysql_real_escape_string($v);
      $sub_sql[] = " $k='$v'";
    }
  }
  foreach($add_dvar as $k => $v){
    $v = mysql_real_escape_string($v);
    $sub_sql[] = " $k='$v'";
  }
  return implode(", ", $sub_sql);
}

function validateURL($str)
{
    return preg_match('/(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&:\/~\+#]*[\w\-\@?^=%&\/~\+#])?/i',$str);
}

function sanitize($str)
{
    if(ini_get('magic_quotes_gpc'))
        $str = stripslashes($str);

    $str = strip_tags($str);
    $str = trim($str);
    $str = htmlspecialchars($str);
    $str = mysql_real_escape_string($str);

    return $str;
}

function relativeTime($dt,$precision=2)
{
    if(is_string($dt)) $dt = strtotime($dt);

    $times=array( 365*24*60*60  => "year",
                    30*24*60*60   => "month",
                    7*24*60*60    => "week",
                    24*60*60    => "day",
                    60*60     => "hour",
                    60        => "minute",
                    1       => "second");

    $passed=time()-$dt;

    if($passed<5)
    {
        $output='less than 5 seconds ago';
    }
    else
    {
        $output=array();
        $exit=0;

        foreach($times as $period=>$name)
        {
            if($exit>=$precision || ($exit>0 && $period<60)) break;

            $result = floor($passed/$period);
            if($result>0)
            {
                $output[]=$result.' '.$name.($result==1?'':'s');
                $passed-=$result*$period;
                $exit++;
            }
            else if($exit>0) $exit++;
        }

        $output=implode(' and ',$output).' ago';
    }

    return $output;
}


/**
* Launch Background Process
*
* Launches a background process (note, provides no security itself, $call must be sanitized prior to use)
* @param string $call the system call to make
* @author raccettura
*/
function launchBackgroundProcess($call) {
 
    // Windows
    if(is_windows()){
        pclose(popen('start /b '.$call, 'r'));
    }
 
    // Some sort of UNIX
    else {
        pclose(popen($call.' /dev/null &', 'r'));
    }
    return true;
}
 
 
/**
* Is Windows
*
* Tells if we are running on Windows Platform
* @author raccettura
*/
function is_windows(){
    if(PHP_OS == 'WINNT' || PHP_OS == 'WIN32'){
        return true;
    }
    return false;
}


/**
 * Function: sanitizefile
 * Returns a sanitized string, typically for URLs.
 *
 * Parameters:
 *     $string - The string to sanitize.
 *     $force_lowercase - Force the string to lowercase?
 *     $anal - If set to *true*, will remove all non-alphanumeric characters.
 */
function sanitizefile($string, $force_lowercase = true, $anal = false) {
    $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "[", "{", "]",
                   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                   "â€”", "â€“", ",", "<", ".", ">", "/", "?");
    $clean = trim(str_replace($strip, "", strip_tags($string)));
    $clean = preg_replace('/\s+/', "-", $clean);
    $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
    return ($force_lowercase) ?
        (function_exists('mb_strtolower')) ?
            mb_strtolower($clean, 'UTF-8') :
            strtolower($clean) :
        $clean;
}

/**
 * Move array element by index.  Only works with zero-based,
 * contiguously-indexed arrays
 *
 * @param array $array
 * @param integer $from Use NULL when you want to move the last element
 * @param integer $to   New index for moved element. Use NULL to push
 * 
 * @throws Exception
 * 
 * @return array Newly re-ordered array
 */
function moveValueByIndex( array $array, $from=null, $to=null )
{
  if ( null === $from )
  {
    $from = count( $array ) - 1;
  }

  if ( !isset( $array[$from] ) )
  {
    throw new Exception( "Offset $from does not exist" );
  }

  if ( array_keys( $array ) != range( 0, count( $array ) - 1 ) )
  {
    throw new Exception( "Invalid array keys" );
  }

  $value = $array[$from];
  unset( $array[$from] );

  if ( null === $to )
  {
    array_push( $array, $value );
  } else {
    $tail = array_splice( $array, $to );
    array_push( $array, $value );
    $array = array_merge( $array, $tail );
  }

  return $array;
}
?>