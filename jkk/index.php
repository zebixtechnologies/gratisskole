<?php
//======================================================================
// Homepage functions
//----------------------------------------------------------------------
// Create the main frontpage, with an asortment of informational
// options. More - not currently used - is found in_save_func.php.
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//
// Include files
//
require 'main.php';
require 'function/func_stats.php';
require $page->tpl_string('frontpage');


//
// Compile blog datetime
//
function make_blog_date($d)
{
	return '&nbsp;'.
		substr($d,6,2).'-'.
		substr($d,4,2).'-'.
		substr($d,0,4);
}


//
// Make BLOG navigation box
//
function make_blog_nav($top=5)
{
	global $db;

	$box=new box_nav;
	$box->title='Seneste Blog beskeder';
	$last_date='';
	$bcount=0;

	$sql="
		SELECT TOP $top
			*
		FROM
			jkk_blog_entry
		ORDER BY
			datecreated DESC
		";
	$db->open($sql);
	while($db->move_next()){
		$date=$db->field_date('datecreated');
		$bdate=substr($date,0,8);
		
		if($bdate!=$last_date){
			if($bcount>0)
				$box->end_row();
			$box->start_row(make_blog_date($date));
			$last_date=$bdate;
			$bcount++;
		}
		$box->add_item('blog[EXT]',$db->field('title'));
	}
	$db->close();

	if($bcount>0)
		$box->end_row();

	return $box->get();
}


//
// Load database statistics
//
function make_dbstat()
{
	$box=new box_numlist;

	$box->title='Database statistik';

	$num=array();

	$fp=fopen('dbstat.txt','rb');
	if($fp){
		while(!feof($fp)){
			$line=trim(fgets($fp));
			$t=explode('|',$line);
			$num[$t[0]]=$t[1];
		}
		fclose($fp);
	}

	$box->add('Bøger',            number_dot($num['BOOKS']));
	$box->add('Kapitler',         number_dot($num['CHAPTERS']));
	$box->add('Tekst-stykker',    number_dot($num['TEXTS']));
	$box->add('Vers fra skrifter',number_dot($num['VERSES']));
	$box->add('Sange',            number_dot($num['SONGS']));

	return $box->get();
}


//
// Main function
//
function main()
{
	global $cfg,$page;

	//
	// Page setup
	//
	$page->add_css('box');

	//
	// Load main template
	//
	$tpl=$page->read_template($cfg['frontpage']['template']);
	$count=$cfg['frontpage']['mdk']['count'];

	//
	// Insert the four bottom boxes
	//
	$tpl=str_replace('[PAGE[BOX1]]',make_monthly_book_box(),$tpl);
	$tpl=str_replace('[PAGE[BOX2]]',make_master(),           $tpl);
	//$tpl=str_replace('[PAGE[BOX3]]',make_mdk_updates($count),$tpl);
	$tpl=str_replace('[PAGE[BOX3]]',make_dbstat(),$tpl);
	$tpl=str_replace('[PAGE[BOX4]]',make_daily_book_box(),   $tpl);


	//$tpl=str_replace('[PAGE[BOX5]]',make_blog_nav(), $tpl);
	//$tpl=str_replace('[PAGE[BOX7]]',make_dbstat(),$tpl);
	$tpl=str_replace('[PAGE[BOX5]]','',$tpl);
	$tpl=str_replace('[PAGE[BOX7]]','',$tpl);

	$tpl=str_replace('[PAGE[BOX6]]','',$tpl);
	$tpl=str_replace('[PAGE[BOX8]]','',$tpl);

	//
	// Apply
	//
	$page->title='[STR[WELCOME]]';
	$page->content=$tpl;
}


//
// Entry point
//
main();
$page->send();
?>
