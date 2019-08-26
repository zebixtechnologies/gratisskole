<?php
//----------------------------------------------------------------------
// Statistical functions
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

// Include files
require 'main.php';
require 'class/func_stats.php';


// Main function
function main()
{
	global $page;

	// Setup page
	$page->add_css('stats');;
	$page->title=constant('STR_STATISTICS');

	// Read template
	$tpl=$page->read_template('stats');

	// Insert values into template
	$tpl=str_replace('[PAGE[STATS]]',make_stats(),$tpl);
	$tpl=str_replace('[PAGE[UPDATES]]',make_updates(),$tpl);

	// Apply template
	$page->content=$tpl;
}


// Entry point
main();
$page->send();
?>







