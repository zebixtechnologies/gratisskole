<?php
//----------------------------------------------------------------------
// Webpage object
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

// Include required classes
require 'class_tn.php';
require 'class_pageref.php';
require 'class_webpath.php';
require 'class_webmenu.php';

// Class for entire page
class webpage
{
// Templates
var $content_type='';
var $tpl_overide='';
var $tpl_template='';
var $tpl_main='';
var $tpl_dir='';
var $tpl_language='';

// File extensions
var $tpl_ext='';
var $script_ext='';
var $txt_ext='';

// References to external classes
var $db;
var $user;

// Location of bandwidth-heavy external files
var $data_location='[URL[EXT]]';

// CSS / ATTR collections
var $css;
var $css_set='';
var $body_attrs;

// Main vars
var $title;
var $subtitle;
var $content;

// Objects
var $lmenu;
var $rmenu;
var $pagepath;
var $pagerefs;
var $tn;

// Constructor
function webpage()
{
	global $cfg;

	// Copy default page setup
	$this->tpl_template = $cfg['page']['template']['default'];
	$this->tpl_main     = $cfg['page']['template']['main'];
	$this->tpl_dir      = $cfg['page']['template']['dir'];
	$this->tpl_ext      = $cfg['page']['template']['ext'];
	$this->tpl_language = $cfg['page']['language']['default'];
	$this->script_ext   = $cfg['page']['script']['ext'];
	$this->txt_ext      = $cfg['page']['text']['ext'];

	// Create objects
	$this->lmenu=new webmenu;
	$this->rmenu=new webmenu;
	$this->pagepath=new webpath;
	$this->pagerefs=new pagerefs;
	$this->tn=new thumbnails;

	// Setup CSS and ATTRS collections
	$this->css=array();
	$this->css_set=($_SESSION['jkk_user_flag_bigfont'])?'_big':'';
	$this->body_attrs=array();

	// Load all menu templates
	$this->load_local_templates();

	// Read left menu
	$this->read_menu(0,'menu_main');
}

//
function tpl_string($name)
{
	return $this->tpl_dir.$this->tpl_language.'_string/str_'.$name.$this->script_ext;
}

function tpl_path()
{
	return $this->tpl_dir.$this->tpl_language.'/';
	//return $this->tpl_dir.$this->tpl_language.'_string/';
}

// Read menu file
function read_menu($lrmenu,$menu)
{
	if($lrmenu==0)
		$this->lmenu->read_menu($this->tpl_path().$menu.$this->txt_ext);
	else
		$this->rmenu->read_menu($this->tpl_path().$menu.$this->txt_ext);
}

// Load templates for the menues
function load_local_templates()
{
	// Load left menu templates
	$this->lmenu->tpl_header=$this->read_template('menu/lmenu_header');
	$this->lmenu->tpl_item=$this->read_template('menu/lmenu_item');
	$this->lmenu->tpl_xml_item=$this->read_template('menu/lmenu_xml_item');
	$this->lmenu->tpl_misc_item=$this->read_template('menu/lmenu_misc_item');

	// Load right menu templates
	$this->rmenu->tpl_header=$this->read_template('menu/rmenu_header');
	$this->rmenu->tpl_item=$this->read_template('menu/rmenu_item');
	$this->rmenu->tpl_xml_item=$this->read_template('menu/rmenu_xml_item');
	$this->rmenu->tpl_misc_item=$this->read_template('menu/rmenu_misc_item');
}

// Add attribute to body
function add_body_attr($name,$value)
{
	$this->body_attrs[count($this->body_attrs)]=$name.'@'.$value;
}

// Make all body attributes
function make_body_attrs()
{
	$set=array();
	$attrs='';
	for($i=0;$i<count($this->body_attrs);$i++){
		$set=explode('@',$this->body_attrs[$i]);
		$attrs.=' '.$set[0].'="'.$set[1].'"';
	}
	return $attrs;
}

// Add stylesheet to page
function add_css($name)
{
	$this->css[count($this->css)]=$name;
}

// Make all css links
function make_css_links()
{
	if(!count($this->css)) return;
	$tpl=$this->read_template('link_css');
	$link='';
	for($i=0;$i<count($this->css);$i++){
		$link.=$tpl;
		$link=str_replace('[CSS[NAME]]',$this->css[$i],$link);
	}
	return $link;
}

// Load text file contents - ?? USE
function read_text($name)
{
	$file=file_get_contents($this->tpl_dir.$name.$this->txt_ext);
	$file=str_replace('<p>','<div style="gen-text-para">',$file);
	$file=str_replace('</p>','</div>',$file);
	return $file;
}

// Load template file contents
function read_template($name)
{
	return file_get_contents($this->tpl_dir.$this->tpl_language.'/'.$name.$this->tpl_ext);
	//return file_get_contents($this->tpl_dir.$name.$this->tpl_ext);
}

// Apply webpage content and send to client
function send()
{
	global $cfg;

	// Load master templates
	if($this->tpl_overide==''){
		$tpl=$this->read_template($this->tpl_template);
		if($this->tpl_main!='')
			$tpl=str_replace('[PAGE[MAIN]]',$this->read_template($this->tpl_main),$tpl);
	}
	else $tpl=$this->tpl_overide;

	// Add icon to change page letter size on all pages
	$this->pagerefs->add(0,'layout[EXT]?c=lsize','lsize','[STR[CHANGE_LETTERSIZE]]','q');

	// Insert page content
	$tpl=str_replace('[PAGE[CSS]]',$this->make_css_links(),$tpl);
	$tpl=str_replace('[PAGE[CSS_SET]]',$this->css_set,$tpl);
	$tpl=str_replace('[PAGE[BODY_ATTRS]]',$this->make_body_attrs(),$tpl);

	// Insert menus/path/refs
	$tpl=str_replace('[PAGE[LMENU]]',$this->lmenu->get(),$tpl);
	$tpl=str_replace('[PAGE[RMENU]]',$this->rmenu->get(),$tpl);
	$tpl=str_replace('[PAGE[PATH]]',
		$this->pagepath->get($_SESSION['jkk_user_flag_bigfont']==0?65:55),$tpl);
	$tpl=str_replace('[PAGE[REFS]]',$this->pagerefs->get(),$tpl);

	// Insert main page contents
	$tpl=str_replace('[PAGE[CONTENT]]',$this->content,$tpl);
	$tpl=str_replace('[PAGE[TITLE]]',$this->title,$tpl);
	$tpl=str_replace('[PAGE[SUBTITLE]]',$this->subtitle,$tpl);
	$tpl=str_replace('[PAGE[USER]]',$this->user->get_username(),$tpl);

	// Apply external data link
	$tpl=str_replace('[DIR[DATA]]',$this->data_location,$tpl);
	$tpl=str_replace('[URL[ME]]', $cfg['site']['home'],$tpl);
	$tpl=str_replace('[URL[EXT]]',$cfg['site']['data'],$tpl);

	// Apply text-links
	$tpl=str_replace('[TREF]','text[EXT]?id=',$tpl);
	$tpl=str_replace('[CREF]','chain[EXT]?id=',$tpl);
	$tpl=str_replace('[SBS]','song[EXT]?c=sbs&amp;id=',$tpl);

	// Disconnect from database
	$this->db->disconnect();

	// Change content-type if custom type is supplied
	if($this->content_type!='')
		header('Content-Type: '.$this->content_type."\n\n");

	// Insert string constants
	$callback=function($matches){
	return local_chars(constant("STR_".$matches[1]));
};
$tpl=preg_replace_callback("/\[STR\[(\S[^\]]+)\]\]/",$callback,$tpl);

	// Insert php file extension
	$tpl=str_replace('[EXT]',$this->script_ext,$tpl);

	// Skip local content if true remote user
	apply_section($tpl,'IS_LOCAL',is_local()==false?1:0);
	apply_section($tpl,'IS_GLOBAL',is_local()?1:0);

	// Send entire page to client
	echo trim($tpl);
}

}

?>
