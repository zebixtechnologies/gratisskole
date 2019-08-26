<?php
//======================================================================
// JKK Config file
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//----------------------------------------------------------------------
// Generel site setup (class_webpage.php)
//
//$cfg['site']['home']     = 'http://www.kristus.dk/jkk/';
$cfg['site']['home']     = '';
//$cfg['site']['data']     = 'http://www.kristus.dk/jkk/';
$cfg['site']['data']     = '';
$cfg['site']['language'] = 'da';


//----------------------------------------------------------------------
// Database Setup (main.php)
//
$cfg['db']['vendor']          = 'mysql';
$cfg['db']['host']            = 'localhost';
$cfg['db']['database']        = 'denmark';
$cfg['db']['username']        = 'root';
$cfg['db']['password']        = '';
$cfg['db']['prefix']          = 'jkk_';
$cfg['db']['textsearch']      = true;

$cfg['db']['mdk']['host']     = '2.109.66.157';
$cfg['db']['mdk']['database'] = 'mormon_dk';
$cfg['db']['mdk']['username'] = 'root';
$cfg['db']['mdk']['password'] = 'VAzGfhL7';


//----------------------------------------------------------------------
// Mail setup (func_utils.php)
//
$cfg['mail']['sender'] = 'fra_kristus.dk@zenos.dk';


//----------------------------------------------------------------------
// Generel page setup (class_webpage.php)
//
$cfg['page']['template']['default'] = 'template';
$cfg['page']['template']['main']    = 'main_lmenu';
$cfg['page']['template']['dir']     = 'tpl/';
$cfg['page']['template']['ext']     = '.htm';
$cfg['page']['language']['default'] = $cfg['site']['language'];
$cfg['page']['script']['ext']       = '.php';
$cfg['page']['text']['ext']         = '.txt';


//----------------------------------------------------------------------
// Site frontpage (index.php)
//
$cfg['frontpage']['template']     = 'frontpage';
$cfg['frontpage']['mdk']['count'] = 5;
$cfg['frontpage']['mdk']['trunc'] = 28;
$cfg['frontpage']['box'][1]       = 'monthly_book';
$cfg['frontpage']['box'][2]       = 'daily_book';
$cfg['frontpage']['box'][3]       = 'master';
$cfg['frontpage']['box'][4]       = 'mdk_updates';


//----------------------------------------------------------------------
// Master-scriptures (master.php)
//
$cfg['master']['template']['entry']     = 'master/master';
$cfg['master']['template']['list']      = 'master/master_list';
$cfg['master']['template']['list_item'] = 'master/master_list_item';


//----------------------------------------------------------------------
// Scripture Chains (chain.php)
//
$cfg['chain']['tpl']['entry']       = 'chain/chain';
$cfg['chain']['tpl']['entry_verse'] = 'chain/chain_verse';
$cfg['chain']['tpl']['entry_ref']   = 'chain/chain_reference';
$cfg['chain']['tpl']['list']        = 'chain/chain_list';
$cfg['chain']['tpl']['list_item']   = 'chain/chain_list_item';


//----------------------------------------------------------------------
// Songs (song.php)
//
$cfg['song']['tpl']['songbook']['list']       = 'song/songbook';
$cfg['song']['tpl']['songbook']['row']        = 'song/songbook_item';
$cfg['song']['tpl']['sbsong']['main']         = 'song/songbooksong';
$cfg['song']['tpl']['sbsong']['songbook']     = 'song/songbooksong_songbook';
$cfg['song']['tpl']['sbsong']['songbook_cur'] = 'song/songbooksong_songbook_cur';
$cfg['song']['tpl']['sbsong']['subject']      = 'song/songbooksong_subject';
$cfg['song']['tpl']['sbsong']['scripture']    = 'song/songbooksong_scripture';

$cfg['song']['updatelist']['count']           = 7;



//----------------------------------------------------------------------
//
//


?>
