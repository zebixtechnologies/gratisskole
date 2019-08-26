<?php
//----------------------------------------------------------------------
// Menu object
//----------------------------------------------------------------------
// Class for left/right menu on page
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Niv&#65533;, Denmark
//----------------------------------------------------------------------

class webmenu
{
var $tpl_wrap;
var $tpl_header;
var $tpl_item;
var $tpl_xml_item;
var $buffer='';

// Constructor
function webmenu()
{
	$this->tpl_wrap='[MENU[LIST]]';
}

// Read menu definition
function read_menu($file)
{
	$in_menu=0;

	$fp=@fopen($file,'rt');
	if($fp){
		while(!feof($fp)){
			$line=trim(fgets($fp));
			if($line!='' && $line[0]!=';'){
				if(substr($line,0,1)=='#'){
					if($in_menu==1)
						$this->add_end();
					$this->add_start(substr($line,1));
					$in_menu=1;
				}else{
					$items=explode('|',$line);
					$this->add_item($items[0],$items[1],$items[2]);
				}
			}
		}
		if($in_menu==1) $this->add_end();
		fclose($fp);
	}
}


// Start new menu list
function add_start($title='')
{
	if($title!='') $this->add_header($title);
}

// Terminate current menu
function add_end()
{
}

// Add header to item list
function add_header($title)
{
	$tpl=$this->tpl_header;
	$tpl=str_replace('[MENU[HEADER]]',htmlentities($title),$tpl);
	$this->buffer.=$tpl;
}

// Add item to current menu list
function add_item($url,$title,$long_title='')
{
	$tpl=$this->tpl_item;
	$tpl=str_replace('[MENU[URL]]',$url,$tpl);
	$tpl=str_replace('[MENU[TITLE]]',htmlentities($title),$tpl);
	$tpl=str_replace('[MENU[LONG_TITLE]]',htmlentities($long_title),$tpl);
	$this->buffer.=$tpl;
}

// Add xml item to current menu list
function add_xml_item($xmlurl,$url,$title,$long_title='')
{
	$tpl=$this->tpl_xml_item;
	$tpl=str_replace('[MENU[XML_URL]]',$xmlurl,$tpl);
	$tpl=str_replace('[MENU[URL]]',$url,$tpl);
	$tpl=str_replace('[MENU[TITLE]]',htmlentities($title),$tpl);
	$tpl=str_replace('[MENU[LONG_TITLE]]',htmlentities($long_title),$tpl);
	$this->buffer.=$tpl;
}

// Add formatted data
function add_misc($data)
{
	$tpl=$this->tpl_misc_item;
	$tpl=str_replace('[MENU[DATA]]',$data,$tpl);
	$this->buffer.=$tpl;
}

// Compile and return menu
function get()
{
	// Wrap menu list
	$tpl=$this->tpl_wrap;
	$tpl=str_replace('[MENU[LIST]]',$this->buffer,$tpl);

	// Return menu
	return $tpl;
}

}

?>
