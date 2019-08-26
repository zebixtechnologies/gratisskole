<?php
//======================================================================
// Main functions - General application include file
//----------------------------------------------------------------------
// Globals:
//		$chf  = array()
//		$db   = new dbconn
//		$page = new webpage
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//if($_SERVER['REMOTE_ADDR']=='212.242.178.71'){ url_redirect('stop.txt'); exit; }

//
// Activate session buffer
//
session_start();

//
// Load master configuration file
//
require 'config.php';

//
// Include generic database object
// currently: MySql 4.1 PHP mysql(i) API
//
switch($cfg['db']['vendor']){
case 'mysqli':
	require 'class/z_db_mysqli.php';
	break;
case 'mysql':
	require 'class/z_db_mysql.php';
	break;
case 'odbc':
	require 'class/z_db_odbc.php';
	break;
default:
	die('Unknown database vendor: '.$cfg['db']['vendor']);
	break;
}

//
// Include mandatory class files
//
require 'function/func_utils.php';
require 'class/class_webpage.php';
require 'class/class_user.php';

//
// Create database object
//
$db=new dbconn;
/*$
db->connect(
	$cfg['db']['host'],
	$cfg['db']['database'],
	$cfg['db']['username'],
	$cfg['db']['password']
);
*/
$db->connect('2.109.66.157','kristus','root','VAzGfhL7');

//
// Create user object
//
$user=new user_object;
$user->db=&$db;

//
// Create webpage object
//
$page=new webpage;
$page->db=&$db;
$page->user=&$user;

require 'class/class_box.php';

//
// Save current requst uri, for layout.php to jump to
//
$uri=$_SERVER['REQUEST_URI'];
if(substr($uri,0,11)!='/jkk/layout')
	$_SESSION['jkk_last_request_uri']=$uri;

//
// String constants
//
require_once $page->tpl_string('strings');

//
// General functions
//
require 'function/func_general.php';

?>

