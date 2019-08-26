<?php
//----------------------------------------------------------------------
// System functions
//----------------------------------------------------------------------
// (C) 2005-2019 Steffen Estrup / Rikard Warnsdorf
//----------------------------------------------------------------------

// Include file
require 'main.php';

// No outside connections allowed
if(!is_local()) html_redirect('index.php');


// Make right menu
function make_rmenu()
{
	global $page;
}


// Main function
function main()
{
	global $page;

	// Setup page
	$page->tpl_main='main_lmenu_rmenu';
}


// Entry point
main();
$page->send();
?>







