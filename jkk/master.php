<?php
//======================================================================
// Master scripture functions
//----------------------------------------------------------------------
// List the scriptures selectable from the four books.
// Display a specific master scripture width accompanying information.
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//
// Include files
//
include_once "main.php";
include_once "function/func_stats.php";	// For make_master() function
										// Also used on the site frontpage

//
// Determine booknames containing the master scriptures
//
function bookname($id,$output=1)
{
	//
	// Danish scriptures and their names
	//
	$books=array('?',
		constant('STR_OLD_TESTAMENT_ID'),
		constant('STR_NEW_TESTAMENT_ID'),
		constant('STR_BOOK_OF_MORMON_ID'),
		constant('STR_DOCTRINE_AND_COVENANTS_ID')
	);
	$booknames=array('?',
		constant('STR_OLD_TESTAMENT'),
		constant('STR_NEW_TESTAMENT'),
		constant('STR_BOOK_OF_MORMON'),
		constant('STR_DOCTRINE_AND_COVENANTS')
	);

	//
	// Check for numeric id
	//
	if(is_numeric($id))
		return $output==1?$books[$id]:$booknames[$id];

	//
	// Check output 1
	//
	if($output==1)
		return $booknames[$id];

	//
	// Flip books
	//
	$books=array_flip($books);	

	//
	// Check output 2
	//
	if($output==2)
		return $books[$id];

	//
	// Return booknames array
	//
	return $booknames[$books[$id]];
}


//
// Make right menu
//
function make_rmenu()
{
	global $page;

	//
	// Add right menu title
	//
	$page->rmenu->add_start('[STR[BOOKS]]');

	//
	// Add link to all the master scripture books
	//
	for($i=1;$i<=4;$i++)
		$page->rmenu->add_item('master[EXT]?c=bl&amp;id='.$i,bookname($i,2));

	//
	// End the right menu
	//
	$page->rmenu->add_end();
}


//
// Display specific entry
//
function display_entry($id)
{
	global $cfg,$page,$db;

	//
	// Paranoid checks
	//
	if(!is_numeric($id)) $id=1;
	if($id<1 || $id>100) $id=1;

	//
	// Load template
	//
	$tpl=$page->read_template($cfg['master']['template']['entry']);

	//
	// Lookup master entry
	//
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
		//
		// Insert data into template
		//
		$tpl=str_replace('[MASTER[BOOKID]]',   bookname($db->field('book'),2),$tpl);
		$tpl=str_replace('[MASTER[BOOK]]',     bookname($db->field('book'),3),$tpl);
		$tpl=str_replace('[MASTER[REF]]',      $db->field('ref'),             $tpl);
		$tpl=str_replace('[MASTER[MID]]',      $db->field('tref'),            $tpl);
		$tpl=str_replace('[MASTER[TITLE]]',    $db->field('title'),           $tpl);
		$tpl=str_replace('[MASTER[TEXT]]',     $db->field('textdata'),        $tpl);
		$tpl=str_replace('[MASTER[HISTORY]]',  $db->field('history'),         $tpl);
		$tpl=str_replace('[MASTER[TEACHINGS]]',$db->field('teaching'),        $tpl);
		$tpl=str_replace('[MASTER[MISSION]]',  $db->field('use'),             $tpl);
		$tpl=str_replace('[MASTER[PERSONAL]]', $db->field('personal'),        $tpl);
	}
	$db->close();

	//
	// Apply template
	//
	$page->title='[STR[MASTERSCRIPTURE]]';
	$page->content=$tpl;
}


//
// Display entries in the specified book
//
function display_booklist($id)
{
	global $cfg,$page,$db;

	//
	// Paranoid
	//
	if(!is_numeric($id)) $id=1;
	if($id<1 || $id>4)   $id=1;

	//
	// Load templates
	//
	$tpl=$page->read_template($cfg['master']['template']['list']);
	$tpl_item=$page->read_template($cfg['master']['template']['list_item']);

	//
	// Lookup book entries
	//
	$mlist='';
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."master
		WHERE
			book='".bookname($id,1)."'
		ORDER BY
			id
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Get data from database
		//
		$ref=htmlentities($db->field('ref'));
		$msid=$db->field('id');
		$title=htmlentities(str_trunc($db->field('title'),50));

		//
		// Add database data to template
		//
		$mlist.=$tpl_item;
		$mlist=str_replace('[MASTER[REF]]',  $ref,  $mlist);
		$mlist=str_replace('[MASTER[ID]]',   $msid, $mlist);
		$mlist=str_replace('[MASTER[TITLE]]',$title,$mlist);
	}
	$db->close();

	//
	// Insert entry into template
	//
	$tpl=str_replace('[MASTER[LIST]]',$mlist,$tpl);

	//
	// Apply template
	//
	$page->title=bookname($id,2);
	$page->content=$tpl;
}


//
// Display master scripture frontpage
//
function display_frontpage()
{
	//
	// Make navigation box
	//
	$box=new box_nav;
	$box->title='[STR[SCRIPTURE_REFS]]';
	$box->start_row('[STR[BOOKS]]');
	for($i=1;$i<5;$i++)
		$box->add_item('master[EXT]?c=bl&amp;id='.$i,bookname($i,2));
	$box->end_row();
	$box->start_row('[STR[MASTER_IMAGE]]');
	$box->add_item('[STR[MASTER_IMAGE_URL_1]]','[STR[MASTER_IMAGE_LABEL_1]]');
	$box->add_item('[STR[MASTER_IMAGE_URL_2]]','[STR[MASTER_IMAGE_LABEL_2]]');
	$box->end_row();

	//
	// Compile section frontpage
	//
	make_frontpage(
		'[STR[MASTERSCRIPTURES]]',				// Page title
		array(
			'[STR[MASTER_FRONTPAGE_TEXT1]]',	// Page text 1
			'[STR[MASTER_FRONTPAGE_TEXT2]]'		// Page text 2
		),
		'master',								// Image name
		'',
		$box->get(),							// Navigation box
		make_master()							// Daily master scripture
	);
}


//
// Main
//
function main()
{
	global $page;

	//
	// Setup double menu
	//
	$page->tpl_main='main_lmenu_rmenu';
	$page->add_css('master');

	//
	// Get and adjust parameters
	//
	$cmd=request_string('c');
	$id=request_string('id');
	if($cmd=='' && $id=='') $cmd='f';
	if($cmd=='' && $id!='') $cmd='e';

	//
	// Act on command
	//
	switch($cmd){
	case 'e':  display_entry($id);    break;
	case 'bl': display_booklist($id); break;
	default:   display_frontpage();   break;
	}

	//
	// Make right menu
	//
	make_rmenu();
}


//
// Entry point
//
main();
$page->send();
?>