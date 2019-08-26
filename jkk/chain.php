<?php
//======================================================================
// Scripture chain functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// Functions:
//		make_rmenu() - Create right menu
//		display_chain($id) - Display the specified chain
//		display_chain_list() - Display list of all chains
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//----------------------------------------------------------------------
// Include files
//
require "class/class_date.php";
require "main.php";


//----------------------------------------------------------------------
// Make right menu
//
function make_rmenu()
{
	global $cfg,$db,$page;

	//
	// Chains section
	//
	$page->rmenu->add_start('[STR[CHAINS]]');
	$page->rmenu->add_item('chain[EXT]','[STR[FRONTPAGE]]');
	$page->rmenu->add_item('chain[EXT]?c=x','[STR[ALL_CHAINS]]');	
	$page->rmenu->add_end();

	$page->rmenu->add_start('[STR[BOOKS]]');
	$sql="
		SELECT
			id,
			type,
			name
		FROM
			".$cfg['db']['prefix']."systxt
		WHERE
			id IN (
				SELECT DISTINCT
					bookid AS id
				FROM
					".$cfg['db']['prefix']."chain_scripture
			)
		ORDER BY
			name
		";
	$db->open($sql);
	while($db->move_next()){
		$page->rmenu->add_item(
			'chain[EXT]?c='.$db->field('id'),
			str_trunc($db->field('name'),20),
			$db->field('name'));
	}
	$db->close();

	$page->rmenu->add_end();
}


//----------------------------------------------------------------------
// Display the specified scripture chain
//
function display_chain($id)
{
	global $cfg,$db,$page;

	//
	// Paranoid checks
	//
	if($id==''||!is_numeric($id)||strlen($id)>5) return;

	//
	// Load templates
	//
	$tpl  =$page->read_template($cfg['chain']['tpl']['entry']);
	$verse=$page->read_template($cfg['chain']['tpl']['entry_verse']);
	$ref  =$page->read_template($cfg['chain']['tpl']['entry_ref']);

	//
	// Lookup chain
	//
	$chaintitle='';
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."chain_scripture
		WHERE
			id=$id
		";
	$db->open($sql);
	if($db->move_next()){
		$chaintitle=$db->field('title');
		$chain=$db->field('chain');
	}
	$db->close();
	if($chaintitle=='') return;

	//
	// Compile chain
	//
	$text='';
	$mid=explode('|',$chain);
	for($i=0;$i<count($mid);$i++){
		$ids=explode('-',$mid[$i]);

		//
		// Lookup the verses for current reference
		//
		$sql="
			SELECT
				t1.id AS id1,
				t1.mid,
				t1.parenttext,
				t1.title AS title1,
				t2.id AS id2,
				t2.title AS title2,
				t2.textdata
			FROM
				".$cfg['db']['prefix']."texts t1,
				".$cfg['db']['prefix']."texts t2
			WHERE
				t1.mid=".$ids[0]."
				AND t2.parentid=t1.id ";
			if(count($ids)==2)
				$sql.="AND t2.id=t1.id+".$ids[1];
			else
				$sql.="AND t2.id BETWEEN t1.id+".$ids[1]." AND t1.id+".$ids[2];
			$sql.=" ORDER BY t2.id";
		
		$count=0;
		$db->open($sql);
		while($db->move_next()){
			//
			// If this is the first in a series...
			//
			if($count==0){
				//
				// Prepare vars
				//
				$parenttext=decode_parenttext(
					$db->field('parenttext'),
					$db->field('title1'));
				$v=$ids[1];
				$aname='#'.$v;
				if(count($ids)==3) $v.='-'.$ids[2];
				$parenttext.=':'.$v;

				//
				// Add template and insert reference values
				//
				$text.=$ref;
				$text=str_replace('[CHAIN[REF]]',$mid[$i].$aname,$text);
				$text=str_replace('[CHAIN[REF_PATH]]',$parenttext,$text);
			}

			//
			// Add verse template and insert values
			//
			$text.=$verse;
			$text=str_replace('[CHAIN[VERSE_ID]]',$db->field('title2'),$text);
			$text=str_replace('[CHAIN[VERSE_TEXT]]',$db->field('textdata'),$text);
	
			//
			// Increase verse counter
			//
			$count++;
		}
		$db->close();
	}

	//
	// Insert into chain template
	//
	$tpl=str_replace('[CHAIN[TITLE]]',$chaintitle,$tpl);
	$tpl=str_replace('[CHAIN[TEXT]]',$text,$tpl);

	//
	// Apply
	//
	$page->title='[STR[SCRIPTURE_CHAIN]]';
	$page->content=$tpl;
}


//----------------------------------------------------------------------
// Display list of chains
//
function display_chain_list($bookid='')
{
	global $cfg,$db,$page;

	//
	// Read templates
	//
	$tpl =$page->read_template($cfg['chain']['tpl']['list']);
	$item=$page->read_template($cfg['chain']['tpl']['list_item']);
	$clist='';

	//
	// Lookup book title
	//
	if($bookid=='x') $bookid='';
	if($bookid!=''){
		$sql="
			SELECT
				name
			FROM
				".$cfg['db']['prefix']."systxt
			WHERE
				id='$bookid'
			";
		$db->open($sql);
		if(!$db->move_next()) return;
		$title=$db->field('name');
		$db->close();

		$where=" WHERE bookid='$bookid' ";
	}else{
		$where='';
		$title='Alle kæder';
	}

	//
	// Lookup all chains
	//
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."chain_scripture
		$where
		ORDER BY
			title
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Add item template and insert item values
		//
		$clist.=$item;
		$clist=str_replace('[CHAIN[ID]]',$db->field('id'),$clist);
		$clist=str_replace('[CHAIN[TITLE]]',$db->field('title'),$clist);
	}
	$db->close();

	//
	// Insert list into chainlist template
	//
	$tpl=str_replace('[CHAIN[TITLE]]',$title,$tpl);
	$tpl=str_replace('[CHAIN[LIST]]',$clist,$tpl);

	//
	// Apply
	//
	$page->title='[STR[SCRIPTURE_CHAINS]]';
	$page->content=$tpl;
}


//
// Display chain frontpage
//
function display_frontpage()
{
	global $db,$cfg;

	//
	// Make book list
	//
	$box1=new box_datelist;
	$box1->title='[STR[BOOKS]]';
	$box1->trunc_len=28;
	$sql="
		SELECT
			id,
			type,
			name
		FROM
			".$cfg['db']['prefix']."systxt
		WHERE
			id IN (
				SELECT DISTINCT
					bookid AS id
				FROM
					".$cfg['db']['prefix']."chain_scripture
			)
		ORDER BY
			name
		";
	$db->open($sql);
	while($db->move_next()){
		$box1->add(
			$db->field('type'),
			'chain[EXT]?c='.$db->field('id'),
			$db->field('name'));
	}
	$db->close();
	$box1->add('[STR[LIST]]',	'chain[EXT]?c=x','[STR[ALL_CHAINS]]');

	//
	// Make latest chains list
	//
	$box2=new box_datelist;
	$box2->title='[STR[LATEST_CHAINS]]';
	$box2->trunc_len=28;
	$sql="
		SELECT TOP 5
			*
		FROM
			".$cfg['db']['prefix']."chain_scripture
		ORDER BY
			id DESC
		";
	$db->open($sql);
	while($db->move_next()){
		$box2->add(
			make_short_date($db->field_date('datecreated')),
			'chain[EXT]?id='.$db->field('id'),
			$db->field('title'));
	}
	$db->close();

	//
	// Make section frontpage
	//
	make_frontpage(
		'[STR[SCRIPTURE_CHAINS]]',			// Page title
		array(
			'[STR[CHAINS_FRONTPAGE_TEXT1]]',
			'[STR[CHAINS_FRONTPAGE_TEXT2]]'
		),
		'chains',							// Image name
		'',
		$box1->get(),						// Navigation box
		$box2->get()						// Latest updates
	);
}


//----------------------------------------------------------------------
// Main
//
function main()
{
	global $page;

	//
	// Setup double menu
	//
	$page->tpl_main='main_lmenu_rmenu';
	$page->add_css('chain');

	//
	// Get and adjust parameters
	//
	$cmd=request_string('c');
	$id=request_string('id');
	if($id!='' && $cmd=='') $cmd='show';
	if($id=='' && $cmd=='') $cmd='front';

	//
	// Act on command
	//
	switch($cmd){
	case 'front': display_frontpage();      break;
	case 'show':  display_chain($id);       break;
	default:      display_chain_list($cmd); break;
	}

	//
	// Make right menu
	//
	make_rmenu();
}


//----------------------------------------------------------------------
// Entry point
//
main();
$page->send();


//----------------------------------------------------------------------
?>



