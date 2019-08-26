<?php
//======================================================================
// User functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2019 Steffen Estrup / Rikard Warnsdorf
//======================================================================

// Include files
include_once "main.php";
include_once "function/func_stats.php";
include_once "class/class_date.php";





//
// 1=tl docs
// 2=folders
// 3=tl docs/folders
// 4=latest docs
//
function make_my_folders_box($type=3)
{
	global $page,$db,$user;

	// Paranoid
	if(!$user->logged_in()) return;

	// Initialize lists
	$flist='';
	$dlist='';

	// Find folders
	if($type==2||$type==3){
		$sql="
			SELECT
				*
			FROM
				".$cfg['db']['prefix']."user
			WHERE
				parentid=$user->id AND type=2
			ORDER BY
				name
			";
		$db->open($sql);
		if($db->move_next()){
			$tpl_folder_item='<tr><td style="width:20px;text-align:center">[IMG[FOLDER]]</td><td style="width:244px"><a class="menu-link" href="user[EXT]?f=[FOLDER[ID]]">[FOLDER[NAME]]</a></td></tr>';
			$tpl_folder_wrap='<tr style="background-color:#eeeeee"><td><table border="0">[FOLDER[LIST]]</table></td></tr>';
			$img=$page->tn->make_img('folder');
			do{
				$flist.=$tpl_folder_item;
				$flist=str_replace('[IMG[FOLDER]]',$img,$flist);
				$flist=str_replace('[FOLDER[ID]]',$db->field('id'),$flist);
				$flist=str_replace('[FOLDER[NAME]]',$db->field('name'),$flist);
			}while($db->move_next());
			$flist=str_replace('[FOLDER[LIST]]',$flist,$tpl_folder_wrap);
		}
		$db->close();
	}

	// Toplevel documents
	if($type==1||$type==3){
		$dlist='';
		$sql="
			SELECT
				t.*
			FROM
				".$cfg['db']['prefix']."user_text ut,
				".$cfg['db']['prefix']."texts t
			WHERE
				ut.userid=$user->id
				AND
				ut.mid=t.mid
			ORDER BY
				ut.datecreated
			";
		$db->open($sql);
		if($db->move_next()){
			$tpl_doc_item='<tr><td style="width:20px;text-align:center">[IMG[DOC]]</td><td><a class="menu-link" href="text[EXT]?id=[DOC[MID]]">[DOC[NAME]]</a></td></tr>';
			$tpl_doc_wrap='<tr><td><table>[DOC[LIST]]</table></td></tr>';
			$img=$page->tn->make_img('doc');
			do{
				$dlist.=$tpl_doc_item;
				$dlist=str_replace('[IMG[DOC]]',$img,$dlist);
				$dlist=str_replace('[DOC[MID]]',$db->field('mid'),$dlist);
				$dlist=str_replace('[DOC[NAME]]',$db->field('title'),$dlist);
			}while($db->move_next());
			$dlist=str_replace('[DOC[LIST]]',$dlist,$tpl_doc_wrap);
		}
		$db->close();
	}

	//
	$tpl_list_wrap='<table border="0" cellspacing="0" cellpadding="0" class="info"><tr><th class="info-title">Mine foldere</th></tr>[LIST[LIST]]</table>';
	
	if($flist==''&&$dlist==''){
		$items='<tr><td style="text-align:center;font-size:11px;padding:2px 2px 2px 2px;color:#333333">Du har ingen mapper eller dokumenter</td></tr>';
	}else{
		$items=$flist;
		if($flist!=''&&$dlist!='')
			$items.='<tr><td style="height:1px;background-color:#333366"><img src="image/trans.gif" style="border:none;width:1px;height:1px" alt="" /></td></tr>';
		$items.=$dlist;
	}

	//
	$list=str_replace('[LIST[LIST]]',$items,$tpl_list_wrap);



	return $list;
}





// Display current users startpage
function display_start_page()
{
	global $page;

	// Paranoid
	if(!$page->user->logged_in()) return;

	$tpl=$page->read_template('start');
	$tpl_item='<div style="padding-top:6px">[PANE[ITEM]]</div>';

	$page->title=sprintf(constant('STR_USER_STARTPAGE'),$page->user->firstname);

	$mcal=   str_replace('[PANE[ITEM]]',make_month_calendar(2005,2,false,2),$tpl_item);
	$stats=  str_replace('[PANE[ITEM]]',make_stats(),$tpl_item);
	$updates=str_replace('[PANE[ITEM]]',make_updates(4),$tpl_item);
	$daybook=str_replace('[PANE[ITEM]]',make_daily_book_box(),$tpl_item);
	$dayhist=str_replace('[PANE[ITEM]]',make_daily_history(),$tpl_item);
	$mylist= str_replace('[PANE[ITEM]]',make_my_folders_box(),$tpl_item);

	$lpane=$mylist.$mcal;
	$rpane=$stats.$updates.$dayhist;

	// Apply
	$tpl=str_replace('[PAGE[LPANE]]',$lpane,$tpl);
	$tpl=str_replace('[PAGE[RPANE]]',$rpane,$tpl);
	$page->content=$tpl;
}


//
// Display login form
//
function display_login_form($msg='')
{
	global $page;

	$page->tpl_main='main_lmenu_rmenu';
	$tpl=$page->read_template('form_login');
	$tpl=str_replace('[FORM[MSG]]',$msg,$tpl);
	$page->title='Adgangskontrol';
	$page->content=$tpl;
}


//
// Try to admit user
//
function do_login()
{
	global $page;

	// User credetials
	$name=trim(make_db_string(request_string('name')));
	$pass=trim(make_db_string(request_string('pass')));

	// Lookup user in database
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."user
		WHERE
			name='$name'
			AND
			password='$pass'
		";
	$page->db->open($sql);
	$result=$page->db->move_next()?true:false;
	$page->db->close();

	// If user nor found
	if($result==false){
		display_login_form(constant('STR_ERROR_IN_USERNAME_PASSWORD'));
		return;
	}

	// User found - load user information
	$result=$page->user->load_user($name);

	// If user information not loaded
	if($result==false){
		display_login_form(constant('STR_AN_ERROR_OCCURED_CONTACT'));
		return;
	}

	// Jump to users startpage
	html_redirect('user'.$page->script_ext);
}


//
// Log out
//
function do_logout()
{
	global $page;
	
	$page->user->logout();
	html_redirect('index'.$page->script_ext);
}


//
// Main
//
function main()
{
	global $page;

	// Get command
	$cmd=request_string('c');
	if($cmd==''){
		if($page->user->logged_in())
			$cmd='start';
		else
			$cmd='login';
	}

	// ID
	$id=request_string('id');
	if($id=='') $id='';

	// Add styleshets
	$page->add_css('stats');
	$page->add_css('date');

	// Act on command
	switch($cmd){
	case 'start':   display_start_page(); break;
	case 'login':   display_login_form(); break;
	case 'logout':  do_logout();          break;
	case 'dologin': do_login();           break;
	default: break;
	}
}


//
// Entry point
//
main();
$page->send();
?>
