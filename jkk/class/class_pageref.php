<?php
//----------------------------------------------------------------------
// Pageref object
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

class pagerefs
{
var $reflist;
var $tn;

// Constructor
function pagerefs()
{
	$this->reflist=array();
	$this->tn=new thumbnails;
}

// Add reference to list
function add($pos,$url,$img,$title,$accesskey='')
{
	if($pos==0)
		$this->reflist[]=array($url,$img,$title,$accesskey);
	else
		array_unshift($this->reflist,array($url,$img,$title,$accesskey));
}

// Get formatted reference list
function get()
{
	$p=''; $ak='';

	for($i=0;$i<count($this->reflist);$i++){
		$img=$this->tn->make_img($this->reflist[$i][1],$this->reflist[$i][2]);
		if($this->reflist[$i][3]!='')
			$ak=' accesskey="'.$this->reflist[$i][3].'"';
		if($this->reflist[$i][0]!='')
			$img='<a href="'.$this->reflist[$i][0].'"'.$ak.'>'.$img.'</a>';
		$p.='<td style="padding-left:5px;padding-bottom:1px">'.$img.'</td>';
	}

	return $p;
}

}

?>
