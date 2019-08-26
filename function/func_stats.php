<?php
//======================================================================
// Statistical functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2007 Steffen Estrup, Nivå, Denmark
//======================================================================

//----------------------------------------------------------------------
// Display book info in box with image
//
function make_book_box_header($book,$header)
{
	global $cfg,$db;

	$box=new box_std;
	$box->title=$header;
	$box->icon=$book;
	
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."systxt
		WHERE
			id='$book'
		";
	$db->open($sql);
	if($db->move_next()){
		$box->url='text[EXT]?id='.$db->field('mid');
		$box->urltitle=$db->field('name');
		$box->text=$db->field('description');
	}
	$db->close();

	return $box->get();
}


//----------------------------------------------------------------------
// Monthly book box
//
function make_monthly_book_box()
{
	$books=array('hh','evl','jsl');
	return make_book_box_header(
		$books[date('Ym')%count($books)],
		'[STR[BOOK_OF_THE_MONTH]]');
}


//----------------------------------------------------------------------
// Make a daily link to a folder
//
function make_daily_book_box()
{
	$books=array(
		'hkr','shg','frp','gop','mkr',
		'gen','jsv','bmf','dab','fml',
		'hjm','hkr','jsb','kjk','ogt');
	return make_book_box_header(
		$books[date('Ymd')%count($books)],
		'[STR[FOLDER_OF_THE_DAY]]');
}


//----------------------------------------------------------------------
// Create box with the daily master scripture
//
function make_master()
{
	global $cfg,$db;

	$id=(date('Ymd')%100)+1;

	$box=new box_std;
	$box->title='Dagens mesterskriftsted';
	$box->icon='bd';
	
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."master
		WHERE
			id=$id
		";
	$db->open($sql);
	if($db->move_next()){
		$box->url='master[EXT]?id='.$id;
		$box->urltitle=$db->field('ref');
		$box->text=$db->field('teaching');
	}
	$db->close();

	return $box->get();
}






function make_mdk_updates($count=5)
{
	global $cfg,$page;

	$box=new box_datelist;
	$box->title='[STR[MDK_NEWS]]';
	$box->trunc_len=$cfg['frontpage']['mdk']['trunc'];

	$news_file=file_get_contents("http://www.mormon.dk/script/news.php");
	$nlist=explode("#",$news_file);

	foreach($nlist as $n){
		$news=explode("|",$n);
		if(isset($news[0]) && trim($news[0])!=''){
			$n=strip_tags($news[2]);
			$n=str_replace('&nbsp;',' ',$n);
			$box->add(
				make_date_time($news[0],false),
				'http://www.mormon.dk/?mod=artikel&amp;id='.$news[1],
				$n,
				$n
			);

		}
	}

	//
	// Compile and return box
	//
	return $box->get();
}








//----------------------------------------------------------------------
// Display latest news from mormon.dk
//
function _make_mdk_updates($update_entry_count=10,$trunc_len=28)
{
	global $cfg,$page;

	//
	// Setup box
	//
	$box=new box_datelist;
	$box->title='[STR[MDK_NEWS]]';
	$box->trunc_len=$cfg['frontpage']['mdk']['trunc'];

	//
	// Connect to mormon.dk database
	//
	$dbm=new dbconn;
	$dbm->connect(
		$cfg['db']['mdk']['host'],
		$cfg['db']['mdk']['database'],
		$cfg['db']['mdk']['username'],
		$cfg['db']['mdk']['password']
	);

	//
	// Lookup latest news
	//
	$sql="
		SELECT TOP $update_entry_count
			*
		FROM
			mdk_text
		ORDER BY
			date_created DESC
		";
	$dbm->open($sql);
	if($dbm->move_next()){
		do{
			$box->add(
				make_date_time($dbm->field_date('date_created'),false),
				'http://www.mormon.dk/mdk/text.php?id='.$dbm->field('id'),
				$dbm->field('short_title'),
				$title=$dbm->field('title')
			);
		}while($dbm->move_next());
	}

	//
	// Close connection to database
	//
	$dbm->close();
	$dbm->disconnect();

	//
	// Compile and return box
	//
	return $box->get();
}


//----------------------------------------------------------------------
?>
