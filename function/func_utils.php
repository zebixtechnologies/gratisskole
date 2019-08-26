<?php
//----------------------------------------------------------------------
// Utility functions
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Niv&#65533;, Denmark
//----------------------------------------------------------------------

//jkk.kristus.dk/
// Is this an intranet connection =
function is_intranet()
{
	
	return substr($_SERVER['REMOTE_ADDR'],0,3)=='10.'?true:false;
}

// Is this a user from local computer
function is_local_user()
{
	return $_SERVER['REMOTE_ADDR']=='217.157.229.122'?true:false;
}

// Is this a local connection
function is_local()
{
	if(is_intranet()) return true;
	if(is_local_user()) return true;
	return false;
}




// Convert ip-address to long value ( ip2long() acts strange )
function make_ip2long($ip)
{
	$ipa=explode('.',$ip);
	return (16777216*$ipa[0]) + (65536*$ipa[1]) + (256*$ipa[2]) + $ipa[3];
}

// Get session string
function session_string($name)
{
	return (string)(''.@$_SESSION[$name]);
}

// Get session integer
function session_int($name,$min=0,$max=0,$default_value=-1)
{
	$value=session_string($name);
	if(!is_numeric($value))
		return $default_value;
	if(($min!=0||$min!=0)&&($value<$min||$value>$max))
		return $default_value;
	return (int)$value;
}

// Get string parameter
function request_string($name)
{
	return (string)(''.@$_REQUEST[$name]);
}

// Get integer parameter
function request_int($name,$min=0,$max=0)
{
	$value=request_string($name);
	if(!is_numeric($value)) return -1;
	if(($min!=0||$min!=0)&&($value<$min||$value>$max)) return -1;
	return (int)$value;
}


// Remove the specified characters from string
function str_rem_chr($text,$chrs)
{
	for($i=0;$i<strlen($chrs);$i++)
		$text=str_replace($chrs[$i],'',$text);
	return $text;
}


// Truncate string to specified length
function str_trunc($text,$max_length)
{
	if(!$max_length) return trim($text);
	if(strlen($text)<=$max_length) return $text;
	return trim(substr($text,0,$max_length-3)).'..';
}


// Zero(or whatever) pad string
function zpad($text,$length=2,$c='0')
{
	while(strlen($text)<$length) $text=$c.$text;
	return $text;
}


// Make first letter uppercase
function flcap($text)
{
	return strtoupper(substr($text,0,1)).substr($text,1);
}

//
function trunc_int($value)
{
	return round($value/1024,1);
}

// Add . to long numbers
function number_dot($number)
{
	if(strlen($number)>3) $number=substr($number,0,strlen($number)-3).'.'.substr($number,strlen($number)-3);
	if(strlen($number)>7) $number=substr($number,0,strlen($number)-7).'.'.substr($number,strlen($number)-7);
	return $number;
}


// Apply or remove section
function apply_section(&$text,$name,$type)
{
	if($type==0){
		$text=str_replace('[TAG['.$name.']]','',$text);
		$text=str_replace('[TAG[/'.$name.']]','',$text);
	}else{
		$pos=strpos($text,'[TAG['.$name.']]');
		$end='[TAG[/'.$name.']]';
		if($pos===false){
		}else{
			$text=substr($text,0,$pos).substr($text,strpos($text,$end)+strlen($end));
		}
	}
	return $text;
}


// Convert normalized timestamp into display date
function make_date_time($d,$time=true)
{
	$dt=substr($d,6,2).'-'.substr($d,4,2).'-'.substr($d,0,4);
	if($time) $dt.=' '.substr($d,8,2).':'.substr($d,10,2).':'.substr($d,12,2);
	return $dt;
}


// Normalize string for safe db text field
function make_db_string($text)
{
	$text=str_rem_chr($text,"'\t\n\r".' "@#&#65533;$%&/()[]{}');
	return $text;
}


// Send mail
$mail_sender=$cfg['mail']['sender'];
function send_mail($mail_dest,$title,$body,$attach='')
{
	global $mail_sender;
	return mail($mail_dest,$title,$body,'From: '.$mail_sender."\n")?'':'ERR';
	//return mail($mail_dest,$title,$body)?'':'ERR';
}







// Redirect user browser
function url_redirect($url,$rel=0)
{
	if($rel){
		header("Location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$url);
	}else{
		header("Location: ".$url);
	}
	exit;
}


// Redirect user browser using HTML
function html_redirect($url)
{
	echo "<html><head><title></title>";
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0;URL=http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$url.'")>';
	echo "</head><body></body></html>\n";
	exit;
}









// Convert new-lines to HTML
function nl2html($text)
{
	return str_replace("\n",'<br />',$text);
}


// Convert local chars
function local_chars($text)
{
	$text=str_replace('&#65533;','&aelig;',$text);
	$text=str_replace('&#65533;','&oslash;',$text);
	$text=str_replace('&#65533;','&aring;',$text);
	$text=str_replace('&#65533;','&eacute;',$text);
	$text=str_replace('&#65533;','&AElig;',$text);
	$text=str_replace('&#65533;','&Oslash;',$text);
	$text=str_replace('&#65533;','&Aring;',$text);
	$text=str_replace('&#65533;','&Eacute;',$text);
	return $text;
}

?>
