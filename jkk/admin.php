<?php
//======================================================================
// Administration functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//
// Include file
//
include_once "main.php";
include_once "class/class_date.php";
require $page->tpl_string('blog');


//
// Access only granted from local computers
//
if(!is_local()) html_redirect('index'.$page->script_ext);


//
// Include blog functions
//
include_once "admin/admin_blog.php";


//
// Make right menu
//
function make_rmenu()
{
	global $page;

	//
	// Log section
	//
	$page->rmenu->add_start('Log');
	$page->rmenu->add_item('log/report.htm','Rapport');
	$page->rmenu->add_item('log/report.gif','Graf');
	$page->rmenu->add_end();

	//
	// Blog section
	//
	$page->rmenu->add_start('BLOG');
	$page->rmenu->add_item('admin[EXT]?c=bloglist','List poster');
	$page->rmenu->add_item('admin[EXT]?c=blognew','Opret');
	$page->rmenu->add_end();

	//
	// Text
	//
	$page->rmenu->add_start('Tekst');
	$page->rmenu->add_item('admin[EXT]?c=updtextaccess','Text_Access');
	$page->rmenu->add_end();
}


//
// Display admin frontpage
//
function display_frontpage()
{
}


//
// Link access log to country
//
function update_textaccess_country()
{
	global $db,$page;

	$p='';
	$count=0; $upd_count=0;
	$sql="
		SELECT
			DISTINCT
			client_ip
		FROM
			jkk_text_access
		WHERE
			client_countrycode='?'
		";
		//LIMIT 0,10
	$db->open($sql,1);
	while($db->move_next(1)){
		$ipc=ip2long($db->field('client_ip',1));

		$ipa=explode('.',$db->field('client_ip',1));

		$ipc2 = (16777216*$ipa[0]) + (65536*$ipa[1]) + (256*$ipa[2]) + $ipa[3];

		$p.=$db->field('client_ip',1).'-'.$ipc2.'<br />';
		$sql="
			SELECT
				country_code
			FROM
				jkk_dw_geoip
			WHERE
				$ipc2>=ip_num_start
				AND $ipc2<=ip_num_end
			";
		$db->open($sql,2);
		if($db->move_next(2)){
			$sql="
				UPDATE
					jkk_text_access
				SET
					client_countrycode='".$db->field('country_code',2)."'
				WHERE
					client_ip='".$db->field('client_ip',1)."'
				";
			$db->execute($sql);
			$upd_count++;
		}
		$db->close(2);
		$count++;
	}
	$db->close(1);

	$page->content=$p.'<br /><br />'.$count.' - '.$upd_count;
}


//
// Main
//
function main()
{
	global $page;

	//
	// Get and check parameters
	//
	$cmd=request_string('c');
	if($cmd=='') $cmd='frontpage';
	$id=request_string('id');
	if($id=='') $id='';
	$sub=request_string('s');

	//
	// Setup double menu page
	//
	$page->tpl_main='main_lmenu_rmenu';
	$page->add_css('blog');

	//
	// Act om command
	//
	switch($cmd){
	case 'frontpage': display_frontpage();  break;

	//
	// Blog commands
	//
	case 'bloglist':        blog_entry_list();                  break;
	case 'blogedit':        blog_entry_edit('upd',$id);         break;
	case 'blognew':         blog_entry_edit('add',$id);         break;
	case 'blogadd':         blog_entry_add();                   break;
	case 'blogrem':         blog_entry_remove($id);             break;
	case 'blogupdate':      blog_entry_update($id);             break;
	case 'blogeditremsub':  blog_entry_subject('rem',$id,$sub); break;
	case 'blogeditaddsub':  blog_entry_subject('add',$id,$sub); break;

	//
	//
	//
	case 'updtextaccess': update_textaccess_country(); break;

	//
	// Deafult: do nothing
	//
	default: break;
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
