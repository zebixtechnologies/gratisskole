<?php




//
// Display book info in box with image
//
function make_book_box($book)
{
	global $page,$db;

	// Read template
	$tpl=$page->read_template('book/book_box');

	// Select book
	$sql="
		SELECT
			*
		FROM
			jkk_systxt
		WHERE
			id='$book'
		";
	$db->open($sql);
	if($db->move_next()){
		$tpl=str_replace('[INFO[ID]]',$book,$tpl);
		$tpl=str_replace('[INFO[TITLE]]',htmlentities($db->field('name')),$tpl);
		$tpl=str_replace('[INFO[MID]]',$db->field('mid'),$tpl);
		$tpl=str_replace('[INFO[DESC]]',htmlentities($db->field('description')),$tpl);
	}
	$db->close();

	return $tpl;
}








//
// Make updates box
//
function make_updates($update_entry_count=10,$trunc_len=28)
{
	global $page,$db;

	// Initialize
	$items='';
	$count=0;

	// Get templates
	$tpl_wrap=$page->read_template('update_wrap');
	$tpl_items=$page->read_template('update_item');

	// Lookup update entries
	$sql="
		SELECT TOP $update_entry_count
			u.*,
			s.name,
			s.mid
		FROM
			jkk_updates u,
			jkk_systxt s
		WHERE
			u.textid=s.id
		ORDER BY
			id DESC
		";
	$db->open($sql);
	if($db->move_next()){
		do{
			$items.=$tpl_items;
			$items=str_replace('[UPDATE[DATE]]',make_date_time($db->field_date('upddate'),false),$items);
			$items=str_replace('[UPDATE[SBOOK]]',htmlentities(str_trunc($db->field('name'),$trunc_len)),$items);
			$items=str_replace('[UPDATE[BOOK]]',htmlentities($db->field('name')),$items);
			$items=str_replace('[UPDATE[MID]]',$db->field('mid'),$items);
			$items=str_replace('[UPDATE[DESC]]',htmlentities($db->field('description')),$items);
			$count++;
		}while($db->move_next());
	}
	$db->close();

	// Return update block
	return str_replace('[UPDATE[LIST]]',$items,$tpl_wrap);
}









//
// Return computed record count
//
function get_sql_count($sql,$add=0)
{
	global $db;
	$db->open($sql);
	$count=$db->move_next()?($db->field('RCount')+$add):0;
	$db->close();
	return $count;
}









//
// Make stats box
//
function make_stats()
{
	global $page,$db;

	// Initialize
	$text_count[2]=0;
	$book_count[2]=0;
	$chapter_count[2]=0;
	$items='';

	// Total number of text-fragments
	$text_count[1]=get_sql_count('SELECT COUNT(*) AS RCount FROM jkk_texts',-1);
	$text_count[2]=get_sql_count('SELECT COUNT(*) AS RCount FROM jkk_texts1',-1);

	// Number of books/texts
	$book_count[1]=get_sql_count('SELECT COUNT(*) AS RCount FROM jkk_texts WHERE id>1 AND parentid=1');
	$book_count[2]=get_sql_count('SELECT COUNT(*) AS RCount FROM jkk_texts1 WHERE id>1 AND parentid=1');

	// Number of chapters
	$chapter_count[1]=get_sql_count("SELECT COUNT(*) AS RCount FROM jkk_texts WHERE id>1 AND texttype='c'");
	$chapter_count[2]=get_sql_count("SELECT COUNT(*) AS RCount FROM jkk_texts1 WHERE id>1 AND texttype='c'");

	// Get templates
	$tpl_wrap=$page->read_template('stat_wrap');
	$tpl_item=$page->read_template('stat_item');;

	// Summarize
	$book_count[3]=$book_count[1]+$book_count[2];
	$chapter_count[3]=$chapter_count[1]+$chapter_count[2];
	$text_count[3]=$text_count[1]+$text_count[2];

	// Insert name/values
	$items=$tpl_item;
	$items=str_replace('[COUNT[NAME]]',htmlentities(constant('STR_BOOKS')),$items);
	for($i=1;$i<=3;$i++)
		$items=str_replace('[COUNT[VALUE'.$i.']]',number_dot($book_count[$i]),$items);
	$items.=$tpl_item;
	$items=str_replace('[COUNT[NAME]]',htmlentities(constant('STR_CHAPTERS')),$items);
	for($i=1;$i<=3;$i++)
		$items=str_replace('[COUNT[VALUE'.$i.']]',number_dot($chapter_count[$i]),$items);
	$items.=$tpl_item;
	$items=str_replace('[COUNT[NAME]]',htmlentities(constant('STR_TEXTS')),$items);
	for($i=1;$i<=3;$i++)
		$items=str_replace('[COUNT[VALUE'.$i.']]',number_dot($text_count[$i]),$items);

	// Return stat block
	return str_replace('[COUNT[LIST]]',$items,$tpl_wrap);
}









//
// Make daily history reference
//
function make_daily_history($month=0,$day=0)
{
	global $db,$page;

	// Get templates
	$tpl_wrap=$page->read_template('history_wrap');
	$tpl_event=$page->read_template('history_event');;

	// Get current month and day
	if($month==0){
		$month=(int)date('m');
		$day=(int)date('d');
	}
	$events='';

	// Get events of exact date
	for($i=1;$i<2;$i++){
		$sql='
			SELECT
				*
			FROM
				jkk_dailyhistory
			WHERE
				MONTH(thedate)='.$month.'
				AND DAYOFMONTH(thedate)='.($i==1?$day:1).'
				AND accurate='.$i;
		$db->open($sql);
		if($db->move_next()){
			do{
				$thedate=$db->field_date('thedate');
				if($i==0)
					$thedate=substr($thedate,4,2).'-'.substr($thedate,0,4);
				else
					$thedate=make_short_date($thedate);
				$events.=$tpl_event;
				$events=str_replace(
					'[HISTORY[EVENT]]',
					$db->field('event'),
					$events);
				$events=str_replace(
					'[HISTORY[DATE]]',
					$thedate,
					$events);
			}while($db->move_next());
		}
		$db->close();
	}

	if($events==''){
			$events=$tpl_event;
			$events=str_replace(
				'[HISTORY[EVENT]]',
				'-',
				$events);
			$events=str_replace(
				'[HISTORY[DATE]]',
				'[STR[NO_EVENTS_FOUND]]',
				$events);
	}

	// Wrap list
	return str_replace('[HISTORY[LIST]]',$events,$tpl_wrap);
}





?>
