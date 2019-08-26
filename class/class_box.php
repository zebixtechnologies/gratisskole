<?php
//======================================================================
// Classes for diffrent info boxes
//----------------------------------------------------------------------
// Standard
// Date List
// Navigation
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Nivå, Denmark
//======================================================================

//
// Standard
//
class box_std
{
var $trunc_len=120;
var $title='';
var $icondir='[DIR[DATA]]data/bookimg/';
var $icon='';
var $url='';
var $urltitle='';
var $text='';

function get()
{
	global $page;
	$t=$page->read_template('box_std');

	$title   =htmlentities($this->title);
	$urltitle=htmlentities($this->urltitle);
	$text    =htmlentities(str_trunc($this->text,$this->trunc_len));

	$t=str_replace('[BOX[TITLE]]',   $title,         $t);
	$t=str_replace('[BOX[ICONDIR]]', $this->icondir, $t);
	$t=str_replace('[BOX[ICON]]',    $this->icon,    $t);
	$t=str_replace('[BOX[URL]]',     $this->url,     $t);
	$t=str_replace('[BOX[URLTITLE]]',$urltitle,      $t);
	$t=str_replace('[BOX[TEXT]]',    $text,          $t);

	return $t;
}

};


//
// Date List
//
class box_datelist
{
var $title='';
var $trunc_len=30;
var $buffer='';
var $tpl_main='';
var $tpl_row='';

function box_datelist()
{
	global $page;
	$this->tpl_main=$page->read_template('box_datelist_main');
	$this->tpl_row =$page->read_template('box_datelist_row');
}

function set_title($title)
{
	$this->title=$title;
}

function add($date,$url,$title,$description='')
{
	$t=$this->tpl_row;

	if($description=='') $description=$title;
	$title=htmlentities(str_trunc($title,$this->trunc_len));
	$description=htmlentities($description);

	$t=str_replace('[ROW[DATE]]',       $date,       $t);
	$t=str_replace('[ROW[URL]]',        $url,        $t);
	$t=str_replace('[ROW[TITLE]]',      $title,      $t);
	$t=str_replace('[ROW[DESCRIPTION]]',$description,$t);

	$this->buffer.=$t;
}

function get()
{
	$t=$this->tpl_main;

	$t=str_replace('[BOX[TITLE]]',$this->title,$t);
	$t=str_replace('[BOX[ROWS]]', $this->buffer,$t);

	return $t;
}

};


//
// Number List
//
class box_numlist
{
var $title='';
var $trunc_len=30;
var $buffer='';
var $tpl_main='';
var $tpl_row='';


function box_numlist()
{
	global $page;
	$this->tpl_main=$page->read_template('box_numlist_main');
	$this->tpl_row =$page->read_template('box_numlist_row');
}

function set_title($title)
{
	$this->title=$title;
}

function add($title,$number)
{
	$t=$this->tpl_row;

	$title=htmlentities(str_trunc($title,$this->trunc_len));

	$t=str_replace('[ROW[TITLE]]', $title, $t);
	$t=str_replace('[ROW[NUMBER]]',$number,$t);

	$this->buffer.=$t;
}

function get()
{
	$t=$this->tpl_main;

	$t=str_replace('[BOX[TITLE]]',$this->title,$t);
	$t=str_replace('[BOX[ROWS]]', $this->buffer,$t);

	return $t;
}

};


//
// Navigation
//
class box_nav
{
var $title='';
var $row_title='';
var $in_row=false;
var $items;
var $rows='';
var $tpl_main='';
var $tpl_row='';
var $tpl_item='';
var $trunc_len=30;

function box_nav()
{
	global $page;
	$this->tpl_main=$page->read_template('box_nav_main');
	$this->tpl_row =$page->read_template('box_nav_row');
	$this->tpl_item=$page->read_template('box_nav_item');
}

function start_row($title)
{
	if($this->in_row==true) $this->end_row();
	$this->row_title=$title;
	$this->in_row=true;
}

function end_row()
{
	$t=$this->tpl_row;
	$t=str_replace('[ROW[TITLE]]',$this->row_title,$t);
	$t=str_replace('[ROW[ITEMS]]',$this->items,$t);
	$this->rows.=$t;

	$this->row_title='';
	$this->items='';
	$this->in_row=false;
}

function add_item($url,$title,$description='')
{
	if($description=='') $description=$title;
	$title=htmlentities(str_trunc($title,$this->trunc_len));
	$description=htmlentities($description);

	$t=$this->tpl_item;
	$t=str_replace('[ITEM[URL]]',        $url,$t);
	$t=str_replace('[ITEM[TITLE]]',      $title,$t);
	$t=str_replace('[ITEM[DESCRIPTION]]',$description,$t);
	$this->items.=$t;
}



function get()
{
	if($this->in_row==true) $this->end_row();
	$t=$this->tpl_main;
	$t=str_replace('[BOX[TITLE]]',$this->title,$t);
	$t=str_replace('[BOX[ROWS]]', $this->rows,$t);
	return utf8_encode($t);
}

};

?>
