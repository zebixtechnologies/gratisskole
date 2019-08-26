<?php
//----------------------------------------------------------------------
// Game functions
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Niv&#65533;, Denmark
//----------------------------------------------------------------------

// Include file
include_once "main.php";


//
function display_memory_game($type=0)
{
	global $page;
	
	$tpl=$page->read_template('game/game01');

	$page->title='Huskespil';
	$page->add_body_attr('onLoad','init()');
	$page->content=$tpl;
}


//
function display_crossword_game($type=0)
{
	global $page;
	
	$tpl=$page->read_template('game/game02');
	$page->title='Kryds og tværs';
	$page->content=$tpl;
}


//
function main()
{
	global $db;


	$cmd=trim(''.@$_REQUEST['c']);
	if($cmd=='') $cmd='frontpage';

	$id=trim(''.@$_REQUEST['id']);
	if($id=='') $id='';

	switch($cmd){
	case 'frontpage': break;
	case '1': display_memory_game($id); break;
	case '2': display_crossword_game($id); break;
	default: break;
	}
}


//
main();
$page->send();
?>

<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>

<font size="1">
Sponsored links:
<a href="http://sears-catalogue.com/" title="Sears Catalogue">Sears Catalogue</a> |
<a href="https://www.caflyers.ca/freshco-canada/" title="Freshco Flyer">Freshco Flyer</a> |
<a href="https://www.caflyers.ca/no-frills-canada/" title="No Frills Flyer">No Frills Flyer</a> |
<a href="https://www.caflyers.ca/metro-canada/" title="Metro Flyer">Metro Flyer</a> |
<a href="https://www.caflyers.ca/sobeys-canada/" title="Sobeys Flyer">Sobeys Flyer</a>
<a href="https://www.caflyers.ca/loblaws-canada/" title="Loblaws Flyer">Loblaws Flyer</a> |
<a href="https://www.caflyers.ca/safeway-canada/" title="Safeway Flyer">Safeway Flyer</a>
</font>
