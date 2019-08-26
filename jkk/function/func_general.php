<?php
//======================================================================
// func_general.php
//----------------------------------------------------------------------
// Funktions:
//		insert_smilies($t)
//		display_info_text($file)
//		make_default_lmenu()
//		[cleanup]
//----------------------------------------------------------------------
// (C) 1995-2006, Steffen Estrup, Niv&#65533;, Denmark
//======================================================================


//
// Insert smilie faces in text
//
function insert_smilies($t)
{
	//
	// Setup smily array list
	//
	$smily=array(
		array(':-)','smiler','smiler'),
		array(':-(','sur','sur'),
		array(':-|','skuffet','skuffet'),
		array(':-D','griner','griner'),
		array(':-o','ani_overrasket','overrasket'),
		array('8-|','ani_briller','n&oslash;rd'),
		array('*-)','ani_hmmm','t&aelig;nker'),
		array('?-)','ani_ide','ide')
		);

	//
	// Insert smilies
	//
	for($i=0;$i<count($smily);$i++){
		$t=str_replace(
			$smily[$i][0],
			'<img src="[DIR[DATA]]image/smily/'.$smily[$i][1].'.gif" '.
				'style="vertical-align:middle" '.
				'alt="Smily: '.$smily[$i][2].'" />',
			$t);
	}

	//
	// Return the modified text
	//
	return $t;
}


//
// Get text and display it
//
function display_info_text($file)
{
	global $db,$page;

	//
	// Read template
	//
	$tpl=$page->read_template('text');

	//
	// Lookup text page
	//
	$title=$text='';
	$sql="
		SELECT
			*
		FROM
			jkk_infotext
		WHERE
			id='$file'
		";
	$db->open($sql);
	if($db->move_next()){
		$text=$db->field('textdata');
		$title=$db->field('title');
	}
	$db->close();
	if($title=='')
		$title=($title!='')?constant('STR_AN_ERROR_OCCURED'):'';

	//
	// Insert smilies
	//
	$text=insert_smilies($text);

	//
	// Insert values in template and apply
	//
	$tpl=str_replace('[TEXT[TEXT]]',$text,$tpl);
	$page->title=$title;
	$page->content=$tpl;
}


//
// Convert parenttext from database til readable text string
//
function decode_parenttext($parenttext,$title)
{
	//
	// Initialize
	//
	$item_parenttext='';
	$last_mid=0;

	//
	// If parenttext is present
	//
	if($parenttext!=''){
		$pt_list=explode('|',$parenttext);
		for($i=0;$i<count($pt_list);$i++){
			$pt_item=explode('#',$pt_list[$i]);
			if($item_parenttext!='') $item_parenttext.=' / ';
			$item_parenttext.=$pt_item[1];
			$last_mid=$pt_item[0];
		}
	}

	//
	// Add current text
	//
	$item_parenttext.=' / '.$title;

	//
	// Remove first deliliter
	//
	if(substr($item_parenttext,0,3)==' / ')
		$item_parenttext=substr($item_parenttext,3);

	//
	// Return convertet text
	//
	return $item_parenttext;
}


//
//
//
function make_frontpage($title,$text,$image,$image_text,$sub_left,$sub_right)
{
	global $page;

	$page->tpl_main='main_lmenu';
	$page->add_css('box');

	$tpl=$page->read_template('general_frontpage');

	$tpl=str_replace('[FRONT[IMAGE]]',    $image,$tpl);
	$tpl=str_replace('[FRONT[SUB_LEFT]]', $sub_left,$tpl);
	$tpl=str_replace('[FRONT[SUB_RIGHT]]',$sub_right,$tpl);

	if(is_array($text)){
		for($i=1;$i<4;$i++){
			if(isset($text[$i-1]))
				$tpl=str_replace('[FRONT[MASTER_FRONTPAGE_TEXT'.$i.']]',$text[$i-1],$tpl);
			apply_section($tpl,'TEXT'.$i,isset($text[$i-1])?0:1);
		}
	}else{
		$tpl=str_replace('[FRONT[MASTER_FRONTPAGE_TEXT1]]',$text,$tpl);
		apply_section($tpl,'TEXT2',1);
		apply_section($tpl,'TEXT3',1);
	}

	$page->title=$title;
	$page->content=$tpl;
}


//
//
//
function html2txt($text)
{
	$search=array(
		'@<script[^>]*?>.*?</script>@si',     // Strip out javascript
	    '@<[\\/\\!]*?[^<>]*?>@si',            // Strip out HTML tags
	    '@<style[^>]*?>.*?</style>@siU',      // Strip style tags properly
	    '@<![\\s\\S]*?--[ \\t\\n\\r]*>@'      // Strip multi-line comments including CDATA 
	);
	return preg_replace($search,'',$text);
}

?>
