<?php
//======================================================================
// [Object] Webpath
//----------------------------------------------------------------------
// (C) 2006-2009, GratisSkole.dk, Steffen Estrup
//======================================================================

class webpath
{
var $delim=' / ';
var $plist;

// Constructor
function webpath()
{
	$this->plist=array();
}

// Add path item
function add($url,$title,$longtitle='')
{
	$this->plist[count($this->plist)]=$url.'#'.$title.'#'.$longtitle;
}

// Make and return path string
function get($maxlen=75)
{
	$tpl_path_item='<a class="menu-link" href="[PATH[URL]]" title="[PATH[LONGTITLE]]">[PATH[TITLE]]</a>';

	$p='';
	$len=0;
	$value=array();

	for($i=0;$i<count($this->plist);$i++){
		if($p!=''&&($len+strlen($this->delim))<=$maxlen){
			$p.=$this->delim;
			$len+=strlen($this->delim);
		}

		if($len<$maxlen){

			$value=explode('#',$this->plist[$i]);

			if(($len+strlen($value[1]))>$maxlen)
				$value[1]=substr($value[1],0,$maxlen-$len);

			$p.=$tpl_path_item;

			$p=str_replace('[PATH[URL]]',$value[0],$p);
			$p=str_replace('[PATH[TITLE]]',$value[1],$p);
			$p=str_replace('[PATH[LONGTITLE]]',$value[2],$p);

			$len+=strlen($value[1]);
		}
	}
	return utf8_encode($p);
}

}

//======================================================================
?>