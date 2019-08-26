<?php
require 'main.php';


function main()
{
	global $page;

	$number=request_string('number');
	if($number==404){
		$page->title='Fejlkode '.$number;
		$tpl=$page->read_template('error/'.$number);
		//$tpl=str_replace('','',$tpl);
		$page->content=$tpl;
	}
}


main();
$page->send();
?>
