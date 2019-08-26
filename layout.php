<?php
//----------------------------------------------------------------------
// Change functions
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//----------------------------------------------------------------------

// Include main application file
include_once "main.php";


// Main function
function main()
{
	global $page;

	// Get parameter
	$cmd=trim(request_string('c'));

	switch($cmd){
	case 'lsize':
		// Toggle font size and get lastest URI
		$page->user->toggle_bigfont();
		$uri=session_string('jkk_last_request_uri');

		// Adjust URL
		$uri=$uri!=''?substr($uri,5):'index'.$page->script_ext;
		if($uri=='') $uri='index'.$page->script_ext;

		// Jump to latest page
		html_redirect($uri);
		exit;
		break;
	default: break;
	}
}


// Entrypoint
main();
$page->send();
?>
