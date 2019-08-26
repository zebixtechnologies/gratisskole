<?php
//======================================================================
// Search functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2019 Steffen Estrup / Rikard Warnsdorf
//======================================================================

// Include files
include_once "main.php";
require $page->tpl_string('search');
include_once 'class/class_search.php';

// Make global search object
$search=new search_object;
$search->db=&$db;
$search->page=&$page;


// Make right menu
function make_rmenu()
{
	global $page;

	// Find menu
	$page->rmenu->add_start('[STR[FIND]]');
	$page->rmenu->add_item('search[EXT]','[STR[SEARCH_FORM]]');
	$page->rmenu->add_end();

	// Help menu
	$page->rmenu->add_start('[STR[HELP]]');
	$page->rmenu->add_item('search[EXT]?c=i&amp;id=search',   '[STR[GENERAL]]');
	$page->rmenu->add_item('search[EXT]?c=i&amp;id=fulltext', '[STR[FULL_TEXT_SEARCH]]');
	$page->rmenu->add_item('search[EXT]?c=i&amp;id=shortcuts','[STR[SHORTCUTS]]');
	$page->rmenu->add_end();
}


// Main
function main()
{
	global $page,$search;

	$page->add_css('search');
	$page->add_css('info');

	// Get parameter
	$cmd=request_string('c');
	switch($cmd){
	case 'nav':
		$search->navigation();
		break;
	case 'sall':
		$search->search_all();
		break;
	case 'i':
		$page->tpl_main='main_lmenu_rmenu';
		display_info_text(request_string('id'));
		break;
	default:
		$search->display_form();
		break;
	}

	make_rmenu();
}


// Entry point
main();
$page->send();
?>
