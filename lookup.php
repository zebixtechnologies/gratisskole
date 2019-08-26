<?php
//======================================================================
// Lookup book functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2005 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//
// Include main application file
//
require 'main.php';
require 'function/func_stats.php';
require $page->tpl_string('ebook');


//
// Display lookup frontpage width all book sections
//
function display_frontpage()
{
	global $cfg,$db,$page;

	//
	// Read templates
	//
	$tpl=$page->read_template('book/books');
	$tpl_wrap=$page->read_template('book/book_pane_wrap');
	$tpl_item=$page->read_template('book/book_list_item');

	//
	// Initialize list vars
	//
	$lst=array('','','','');
	$pos=1;

	//
	// Lookup all books/texts
	//
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."textlist
		WHERE
			list=1
		ORDER BY
			id
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Insert database dato into database
		//
		$item=$tpl_item;
		$item=str_replace('[ITEM[ID]]',  $db->field('id'),  $item);
		$item=str_replace('[ITEM[NAME]]',$db->field('name'),$item);

		//
		// Add to current list
		//
		$lst[$pos++].=$item;
		if($pos>3) $pos=1;
	}
	$db->close();

	//
	// Wrap and apply all 3 lists
	//
	for($pos=1;$pos<=3;$pos++){
		$lst[$pos]=str_replace('[TEXT[LIST]]',$lst[$pos],$tpl_wrap);
		$tpl=str_replace('[BOOKLIST['.$pos.']]',$lst[$pos],$tpl);
	}

	//
	// Apply to page
	//
	$page->title=constant('STR_BOOKS');
	$page->content=$tpl;
}


//
// Display book/text section using the supplied information
//
function display_section($section,$title,$img,$text)
{
	global $cfg,$db,$page;

	//
	// Read templetes
	//
	$tpl=$page->read_template('book/section_frontpage');
	$tpl_wrap=$page->read_template('book/book_pane_wrap');
	$tpl_item=$page->read_template('book/section_list_item');

	//
	// Prepare list array
	//
	$lst=array('','');
	$pos=0;

	//
	// Query database
	//
	$sql="
		SELECT
			s.*
		FROM
			".$cfg['db']['prefix']."systxt s,
			".$cfg['db']['prefix']."textlisttext t
		WHERE
			s.id=t.txtid
			AND txtlst='$section'
		ORDER BY
			t.seq,
			name
		";
	$db->open($sql);
	if($db->move_next()){
		do{
			//
			// Insert data into template
			//
			$item=$tpl_item;
			$item=str_replace('[ITEM[ID]]',$db->field('mid'),$item);
			$item=str_replace('[ITEM[NAME]]',$db->field('name'),$item);
			
			//
			// Add to current list
			//
			$lst[$pos].=$item;
			$pos=($pos==0)?1:0;
		}while($db->move_next());
	}
	$db->close();

	//
	// Wrap and apply both lists
	//
	$lst[0]=str_replace('[TEXT[LIST]]',$lst[0],$tpl_wrap);
	$lst[1]=str_replace('[TEXT[LIST]]',$lst[1],$tpl_wrap);
	$tpl   =str_replace('[LIST[1]]',   $lst[0],$tpl);
	$tpl   =str_replace('[LIST[2]]',   $lst[1],$tpl);
	$tpl   =str_replace('[LIST[IMG]]', $img,   $tpl);
	$tpl   =str_replace('[LIST[TEXT]]',$text,  $tpl);

	//
	// Apply to page
	//
	$page->title=$title;
	$page->content=$tpl;
}


//
// Display a book/texts section
//
function display_db_section($section)
{
	global $cfg,$db,$page;

	//
	// Lookup books/texts in section
	//
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."textlist
		WHERE
			id='$section'
		";
	$db->open($sql);
	if(!$db->move_next()) return;

	//
	// Save list information
	//
	$title=$db->field('name');
	$description=nl2html($db->field('description'));
	$db->close();

	//
	// Display section width the accuired information
	//
	display_section($section,$title,$section,$description);
}


//
// Display list of non-church publications
//
function display_other_section()
{
	global $cfg,$db,$page;

	//
	// Read templetes
	//
	$tpl=$page->read_template('book/section_other_frontpage');
	$tpl_wrap=$page->read_template('book/book_pane_wrap');
	$tpl_item=$page->read_template('book/section_list_item');
	$tpl_head=$page->read_template('book/section_list_head_item');

	//
	// Prepare list array
	//
	$lst=array('','');
	$pos=0;
	$last_tname='';

	//
	// Query database (old style fo mysql...)
	//
	$sql="
		SELECT
			tl.list,
			tl.name AS tname,
			s.id,s.name AS sname,
			s.mid
		FROM
			".$cfg['db']['prefix']."systxt s,
			".$cfg['db']['prefix']."textlist tl,
			".$cfg['db']['prefix']."textlisttext t
		WHERE
			s.id=t.txtid
			AND t.txtlst=tl.id
			AND tl.list >1
		ORDER BY
			tl.list,
			t.seq,
			tl.name,
			s.name
		";
	//echo $sql;
	$db->open($sql);
	if($db->move_next()){
		do{
			//
			// New list
			//
			if($db->field('tname')!=$last_tname){
				$item=$tpl_head;
				$item=str_replace('[ITEM[NAME]]',$db->field('tname'),$item);
				$lst[$db->field('list')-2].=$item;
			}
			
			//
			// Add item
			//
			$item=$tpl_item;
			$item=str_replace('[ITEM[ID]]',$db->field('mid'),$item);
			$item=str_replace('[ITEM[NAME]]',$db->field('sname'),$item);
			$lst[$db->field('list')-2].=$item;

			//
			// Save list
			//
			$last_tname=$db->field('tname');
		}while($db->move_next());
	}
	$db->close();

	//
	// Wrap and apply both lists
	//
	$lst[0]=str_replace('[TEXT[LIST]]',$lst[0],$tpl_wrap);
	$lst[1]=str_replace('[TEXT[LIST]]',$lst[1],$tpl_wrap);
	$tpl   =str_replace('[LIST[1]]',   $lst[0],$tpl);
	$tpl   =str_replace('[LIST[2]]',   $lst[1],$tpl);
	//$tpl   =str_replace('[LIST[IMG]]', $img,   $tpl);
	//$tpl   =str_replace('[LIST[TEXT]]',$text,  $tpl);

	//
	// Apply to page
	//
	$page->title='[STR[OTHER_BOOKS]]';
	$page->content=$tpl;
}


//
// Display list of ebboks for PocketPC and Palm's
//
function display_ebook_list()
{
	global $cfg,$page,$db;

	//
	// Setup page
	//
	$page->tpl_main='main_lmenu_rmenu';
	$page->add_css('ebook');

	//
	// Read templates
	//
	$tpl=$page->read_template('book/ebook_list');
	$item=$page->read_template('book/ebook_list_item');

	//
	// Lookup e-books - twise - one for each format
	//
	$blist=array('','','');
	for($i=1;$i<=2;$i++){
		$sql="
			SELECT
				b.*,
				s.name
			FROM
				".$cfg['db']['prefix']."ebooks b,
				".$cfg['db']['prefix']."systxt s
			WHERE
				b.type='".($i==1?'msr':'pmr')."'
				AND b.mid=s.mid
			ORDER BY
				b.id
		";
		$db->open($sql);
		while($db->move_next()){
			//
			// Insert data info template
			//
			$t=$item;
			$t=str_replace('[EBOOK[MID]]',zpad($db->field('mid'),6),$t);
			$t=str_replace('[EBOOK[EXT]]',$i==1?'lit':'pdb',$t);
			$t=str_replace('[EBOOK[NAME]]',str_trunc($db->field('name'),30),$t);
			$t=str_replace('[EBOOK[LONGNAME]]',$db->field('name'),$t);
			$blist[$i].=$t;
		}
		$db->close();
		$tpl=str_replace('[EBOOK[LIST'.$i.']]',$blist[$i],$tpl);
	}

	//
	// Apply to page
	//
	$page->title='[STR[EBOOKS]]';
	$page->content=$tpl;
}


//
// List all books
//
function display_all_books($id=0)
{
	global $cfg,$page,$db;

	//
	// Initialize
	//
	$tn=new thumbnails;
	$page->tpl_main='main_lmenu_rmenu';

	//
	// Select database
	//
	switch($id){
	case '1': $where='WHERE db=0'; break;
	case '2': $where='WHERE db=1'; break;
	default:
		if(strlen($id)==2)
			$where="WHERE language='$id'";
		else
			$where='';
		break;
	}

	//
	// Read templetes
	//
	$tpl=$page->read_template('book\all_books_wrap');
	$item=$page->read_template('book\all_books_item');

	//
	// Lookup books
	//
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."systxt
		$where
		ORDER BY
			name
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Insert data into template
		//
		$blist.=$item;
		$blist=str_replace('[BOOK[LANGUAGE]]',$tn->make_img($db->field('language')),$blist);
		$blist=str_replace('[BOOK[ID]]',$db->field('mid'),$blist);
		$blist=str_replace('[BOOK[NAME]]',$db->field('name'),$blist);
	}

	//
	// Insert list
	//
	$tpl=str_replace('[BOOK[LIST]]',$blist,$tpl);

	//
	// Apply to page
	//
	$page->title='[STR[ALL_BOOKS]]';
	$page->content=$tpl;

	//
	// Create Right menu
	//
	$page->rmenu->add_start('Vælg database');
	$page->rmenu->add_item('lookup[EXT]?c=all','DB1 + DB2');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=1','DB1');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=2','DB2');
	$page->rmenu->add_item('info[EXT]?id=db','Beskrivelse','Kort beskrivelse af de to databaser som teksterne er lagret i');
	$page->rmenu->add_end();

	$page->rmenu->add_start('Vælg sprog');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=da','Dansk');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=en','Engelsk');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=se','Svensk');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=no','Norsk');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=gr','Græsk');
	$page->rmenu->add_item('lookup[EXT]?c=all&amp;id=la','Latin');
	$page->rmenu->add_end();
}


//
//
//
function display_intro()
{
	global $cfg,$db,$page;

	$gap='<br />';

	$box1=make_book_box_header('hkr','[STR[TEXT]]');
	$box2=make_book_box_header('mor','[STR[TEXT]]');
	$box3=make_book_box_header('int','[STR[TEXT]]');
	$box4=make_book_box_header('jsv','[STR[TEXT]]');

	make_frontpage(
		'Introduktion',		// Page title
		array(
			'bla 1',		// Page text 1
			'bla 2',		// Page text 2
			'bla 3'			// Page text 3
		),
		'choir',			// Image name
		'',
		$box1.$gap.$box3,	// Navigation box
		$box2.$gap.$box4	// Latest updates
	);

}


//
// Main function
//
function main()
{
	//
	// Get parameters
	//
	$cmd=request_string('c');
	$id=request_string('id');

	//
	// Act on command
	//
	switch($cmd){
	case 'intro': display_intro();         break;
	case 'ebook': display_ebook_list($id); break;
	case 'blst':  display_db_section($id); break;
	case 'oth':   display_other_section(); break;
	case 'all':   display_all_books($id);  break;
	default:      display_frontpage();     break;
	}
}


//
// Entry point
//
main();
$page->send();
?>







