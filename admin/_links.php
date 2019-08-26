<?php
//----------------------------------------------------------------------
// Link functions
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

// Include main application file
require 'main.php';


// List links
function link_list($type=1)
{
	global $db,$page;

	// Read templates
	$tpl_wrap=$page->read_template('link');
	$tpl_item=$page->read_template('link_item');
	$list='';

	$sql=
		'SELECT l.* '.
		'FROM jkk_linkcat c,jkk_links l '.
		'WHERE c.catid='.$type.' '.
		'AND c.linkid=l.id '.
		'ORDER BY c.seq';
	$db->open($sql);
	if($db->move_next()){
		do{
			$list.=$tpl_item;
			$list=str_replace('[ITEM[ID]]',zpad($db->field('id'),4),$list);
		}while($db->move_next());
	}
	$db->close();

	// Apply
	$page->title='Links';
	$page->content=str_replace('[LINK[LIST]]',$list,$tpl_wrap);
}


// Main function
function main()
{
	// Get parameters
	$id=request_string('id');

	// Display list
	link_list();
}


// Entry point
main();
$page->send();
?>
