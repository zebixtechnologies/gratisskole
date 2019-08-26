<?php
//----------------------------------------------------------------------
// Thumbnail object
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

class thumbnails
{

function make_img($img,$alt='')
{
	switch($img){
	case 'dk':
	case 'da':
	case 'uk':
	case 'us':
	case 'se':
	case 'no':
	case 'fi':
	case 'de':
	case 'en':
	case 'gr':
	case 'la':
		$f=$img;
		$w=17;
		$h=13;
		break;
	case 'up':
	case 'dn':
	case 'rg':
	case 'lf':
	case 'lfx':
	case 'rgx':
		$f='arrow_'.$img;
		$w=13;
		$h=13;
		break;
	case 'speak':
	case 'pin':
	case 'save':
		$f=$img;
		$w=13;
		$h=13;
		break;
	case 'lsize':
		$f=$img;
		$w=26;
		$h=13;
		break;
	case 'folder':
		$f=$img;
		$w=15;
		$h=13;
		break;
	case 'doc':
		$f=$img;
		$w=11;
		$h=13;
		break;
	case 'letter':
		$f=$img;
		$w=15;
		$h=11;
		break;
	case 'form':
		$f=$img;
		$w=15;
		$h=13;
		break;
	}

	return '<img src="[DIR[DATA]]image/'.$f.'.gif" style="border:0;width:'.$w.'px;height:'.$h.'px" alt="'.$alt.'" />';
}

function make_link($link,$img,$alt='',$accesskey='')
{
	$link='<a href="'.$link.'"[ACCESS]>'.$this->make_img($img,$alt).'</a>';
	$link=str_replace('[ACCESS]',$accesskey==''?'':' accesskey="'.$accesskey.'"',$link);
	return $link;
}

};

?>