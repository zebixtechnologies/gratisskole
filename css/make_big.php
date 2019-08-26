<?php
// Load FTP functions
require '../../../../../library/z_ftp.php';

// Create FTP object
set_time_limit(300);
$ftp=new ftp_conn;
require '../../../ftp_login.php';
$css_files=array();


// Change font sizes
function change_font_size($src,$from,$to)
{
	return str_replace('font-size:'.$from.'px','font-size:'.$to.'px',$src);
}

// Change font sizes and font types
function change_font_size_type($src,$from,$to)
{
	return str_replace('font-size:'.$from.'px','font-size:'.$to,$src);
}

// Change background colors
function change_background_color($src,$from,$to)
{
	return str_replace('background-color:#'.$from,'background-color:#'.$to,$src);
}

// Change colors
function change_color($src,$from,$to)
{
	return str_replace('#'.$from,'#'.$to,$src);
}

// Change urls
function change_url($src,$from,$to)
{
	return str_replace('url('.$from.')','url('.$to.')'.$to,$src);
}


// Change entire stylesheet
function make_big($style)
{
	global $css_files;

	echo "- $style -> $style"."_big\n";

	$data=file_get_contents($style.'.css');

	$data=change_font_size($data,16,19);
	$data=change_font_size($data,15,18);
	$data=change_font_size($data,14,17);
	$data=change_font_size($data,13,16);
	//$data=change_font_size_type($data,12,'1em');
	$data=change_font_size($data,12,15);
	//$data=change_font_size($data,11,'1em');
	$data=change_font_size($data,11,13);
	$data=change_font_size($data,10,12);
	$data=change_font_size($data,9,11);
	$data=change_font_size($data,8,11);

	$data=change_background_color($data,'DDE3E3','#ffffff');
	$data=change_background_color($data,'333333','#ffffff');

	$data=change_color($data,'eeeeee','ffffff');
	$data=change_color($data,'000033','000000');
	$data=change_color($data,'333366','000000');

	//$data=change_url($data,'../image/bg.gif','../image/white.gif');

	// Save new file
	$fp=fopen($style.'_big.css','wt');
	fwrite($fp,$data);
	fclose($fp);

	// Save filenames
	$css_files[]=$style.'.css';
	$css_files[]=$style.'_big.css';
}


// Change each stylesheet
function main()
{
	global $ftp,$css_files;

	echo "Converting:\n";

	// Make second set of the stylesheet files
	make_big('style');
	make_big('style_blog');
	make_big('style_date');
	make_big('style_stats');
	make_big('style_song');
	make_big('style_master');
	make_big('style_info');
	make_big('style_search');
	make_big('style_chain');
	make_big('style_ebook');
	make_big('style_image');
	make_big('style_box');

	// Upload all stylesheet files
	echo "\nUploader:\n";
	$css_path='g:/website/kristus.dk/www/webroot/jkk/css/';
	$ftp->connect();
	$ftp->chdir('jkk2/css');
	for($i=0;$i<count($css_files);$i++){
		echo "- ".$css_files[$i]."\n";
		$ftp->put($css_path.$css_files[$i],$css_files[$i]);
	}
	$ftp->disconnect();

	echo "\nFinished!\n";
}


// Entry point
main();

?>
