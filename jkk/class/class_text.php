<?php
//----------------------------------------------------------------------
// Text object
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

class text_object
{
// Record buffer
var $xid;
var $id;
var $start;
var $end;
var $parent;
var $next;
var $previous;
var $mid;
var $parenttext;
var $author;
var $texttype;
var $type;
var $dateupd;
var $title;
var $subtitle;
var $text;

// Work
var $dtd;
var $table='';
var $systxt_id;
var $top_mid;
var $text_font='';
var $pagepath;
var $add;
var $verse_normal='';
var $verse_high='-high';
var $lv_text;
var $use_trefs=true;
var $tn;

// Lists
var $link_list;
var $trefs_list;
var $verse_list;

// External references
var $page;
var $db;

// Constructor - initialize lists
function text_object()
{
	$this->link_list=array();
	$this->verse_list=array();
	$this->trefs_list=array();
	$this->tn=new thumbnails;
}

// Display error page
function error($text)
{
	$this->page->title='[STR[AN_ERROR_OCCURED]]';
	$this->page->content=$text;
	return -1;
}

// Ensure numeric value
function local_get_param($txt)
{
	return is_numeric(trim(''.$txt))?(int)trim($txt):0;
}

// Make subtitle
function make_subtitle($subtitle,$len=60)
{
	if($len<10) $len=10;
	$subtitle=str_replace('<br/>','. ',$subtitle);
	$subtitle=str_replace('<br />','. ',$subtitle);
	$subtitle=str_replace('<b>','',$subtitle);
	$subtitle=str_replace('</b>','',$subtitle);
	$subtitle=str_trunc(trim($subtitle),$len);
	return $subtitle;
}

// Normalize textid - original format: mid-startverse=-endverse?
function parse_id($id)
{
	$this->start=0;
	$this->end=0;
	$id_list=explode('-',$id);
	$this->id=$this->local_get_param($id_list[0]);
	$this->start=''.@$this->local_get_param($id_list[1]);
	$this->end=''.@$this->local_get_param($id_list[2]);
	if($this->end<=$this->start||($this->end>0&&$this->start==0)) $this->end=0;
	if($this->id==0) $this->id=1;
}

// Save text columns from database record to buffer
function get_record_data(&$db)
{
	$this->xid       =$db->field('id');
	$this->parent    =$db->field('parentid');
	$this->previous  =$db->field('previous');
	$this->next      =$db->field('next');
	$this->mid       =$db->field('mid');
	$this->parenttext=$db->field('parenttext');
	$this->author    =$db->field('author');
	$this->texttype  =''.$db->field('texttype');
	$this->type      =''.$db->field('type');
	$this->dateupd   =$db->field('dateupdated');
	$this->title     =$db->field('title');
	$this->subtitle  =$db->field('subtitle');
	$this->text      =''.$db->field('textdata');
}

// Normalize textblock
function parse_text()
{
	// No text
	if($this->text=='') return;

	// Make nice top p (works?)
	$this->text=str_replace('<p style="','<p style="margin-top:0;',$this->text);
	$this->text=str_replace('<p>','<p style="margin-top:0">',$this->text);

	// Make nice tables
	$this->text=str_replace('<td','<td class="default"',$this->text);
	$this->text=str_replace('<table','<table style="width:540px"',$this->text);

	// Cleanup text presentation
	$this->text=str_replace("</blockquote>\n<br /><br />","</blockquote>\n",$this->text);
	$this->text=str_replace("</ol>\n<br /><br />","</ol>\n",$this->text);
	$this->text=str_replace("</ul>\n<br /><br />","</ul>\n",$this->text);
	$this->text=str_replace("</div><br /><br />","</div><br />",$this->text);
	$this->text=str_replace('<cite>','',$this->text);
	$this->text=str_replace('</cite>','',$this->text);

	$this->text=str_replace('<q>','<span class="q">',$this->text);
	$this->text=str_replace('</q>','</span>',$this->text);


	// Konvert XML link to HTML link
	$this->text=str_replace('<olink locinfo="[TREF]','<a class="tref" href="[TREF]',$this->text);
	$this->text=str_replace('<olink locinfo="[SBS]','<a class="tref" href="[SBS]',$this->text);
	$this->text=str_replace('<olink locinfo=','<a class="tlink" href=',$this->text);
	$this->text=str_replace('[TREF]','text[EXT]?id=',$this->text);
	$this->text=str_replace('</olink>','</a>',$this->text);
	$this->text=trim($this->text);

	// Fix missing images
	$this->text=str_replace('textimg/[EMPTY]','textimg/empty.gif',$this->text);
	$this->text=str_replace('[TIREF]','data/textimg/',$this->text);

	// Remove trailing NL's
	if(substr($this->text,strlen($this->text)-12)=='<br /><br />')
		$this->text=substr($this->text,0,strlen($this->text)-12);

	// If this is a song text
	if($this->id<1000){
		// Lookup songbooksong that links to this text
		$sql='
			SELECT
				sbs.id AS id
			FROM
				jkk_sng_songbooksong sbs,
				jkk_sng_songbook sb
			WHERE
				sbs.textid='.$this->id.'
				AND sbs.sbid=sb.id
			ORDER BY
				sb.seq
			';
		$this->db->open($sql);
		if($this->db->move_next()){
			// Songbooksong found, append songlink to text
			$tpl=$this->page->read_template('text/text_song_link');
			$tpl=str_replace('[TEXT[SBSID]]',$this->db->field('id'),$tpl);
			$this->text.=$tpl;
		}
		$this->db->close();
	}
}

// Make text section?
function make_text_section()
{
	$tpl='';
	$txt=$this->text;
	return $txt;
}

// Parse additional text info
function parse_extras()
{
	// Audio
	$sql="
		SELECT
		  *
		FROM
		  jkk_textaudio
		WHERE
		  mid=$this->mid
		";
	$this->db->open($sql);

	if($this->db->move_next()){
		if($this->db->field('type')==0){
			$this->page->pagerefs->add(
				0,
				'[DIR[DATA]]data/bookread/mp3/'.
					strtolower($this->db->field('ref')),
				'speak',
				'[STR[SPEAK_TEXT]]',
				'a');
		}else{
			$mref=$this->db->field('alt');
			if($mref=='') $mref=$this->db->field('ref');
			$this->page->pagerefs->add(
				0,
				$mref,
				'speak',
				'[STR[SPEAK_TEXT]]');
		}
	}
	$this->db->close();

	// Pagepath
	$top_mid=$this->mid;
	if($this->parenttext!=''){
		$lst=explode('|',$this->parenttext);
		for($i=0;$i<count($lst);$i++){
			$lnk=explode('#',$lst[$i]);
			$this->page->pagepath->add('text[EXT]?id='.$lnk[0],$lnk[1]);
			if($i==0) $top_mid=$lnk[0];
		}
	}
	$this->top_mid=$top_mid;

	// Font face
	$sql="
		SELECT
			*
		FROM
			jkk_systxt
		WHERE
			mid=$top_mid
		";
	$this->db->open($sql);
	if($this->db->move_next())
		$this->text_font=$this->db->field('font');
	$this->db->close();

	// Lingo
	$sql="
		SELECT
		  v.*,
		  m.mid AS mmid
		FROM
		  jkk_systxt s,
		  jkk_systxt m,
		  jkk_txtversion v
		WHERE
		  s.mid=$top_mid
		AND
		  s.id=v.srcid
		  AND v.dstid=m.id
		";

	//echo $sql;

	$this->db->open($sql);
	while($this->db->move_next()){
		$this->page->pagerefs->add(
			0,
			'text[EXT]?id='.($this->db->field('mmid')+($this->mid-$top_mid)),
			$this->db->field('dstlanguage'),
			'[STR[READ_TEXT_IN]]'.strtolower(constant('STR_LANGUAGE_'.strtoupper($this->db->field('dstlanguage')))));
	}
	$this->db->close();
}

// Add item to text list
function add_list($mid,$title,$subtitle='')
{
	if($subtitle=='') $subtitle='&nbsp;';
	$count=count($this->link_list);
	$this->link_list[$count]=$mid.'|'.$title.'|'.$subtitle;
}

// Make text list
function make_list()
{
	// Determine max length og title
	$max_len=0;
	$count=count($this->link_list);
	for($i=0;$i<$count;$i++){
		$arr=explode('|',$this->link_list[$i]);
		if(strlen($arr[1])>$max_len) $max_len=strlen($arr[1]);
	}

	// Read list item template
	$tpl_item=$this->page->read_template('text/text_list_item');

	$xlist='';
	$count=count($this->link_list);
	for($i=0;$i<$count;$i++){
		$t=$tpl_item;
		$arr=explode('|',$this->link_list[$i]);
		$url=$arr[0];
		$title=$arr[1];

		// Insert into item template		
		$t=str_replace('[LIST[URL]]',$url,$t);
		$t=str_replace('[LIST[TITLE]]',str_trunc($title,80),$t);

		// Subtitles
		$stitle=$this->make_subtitle($arr[2],78-$max_len);
		if($max_len>30) $stitle="";
		$t=str_replace('[LIST[STITLE]]',$stitle,$t);

		// Save item
		$xlist.=$t;
	}

	$this->lv_text=$xlist;
}

// Add verse til verse list
function add_verse($title,$subtitle,$text,$trefs)
{
	$count=count($this->verse_list);
	$this->verse_list[$count]=$title.'|'.$subtitle.'|'.$text.'|'.$trefs;
}

// Make verse list
function make_verses()
{
	global $page;

	if($this->text_font!='') $this->text_font='-'.$this->text_font;

	// Read template
	$tpl_item=$this->page->read_template('text/verse_list_item');
	$tpl_tref_item=$this->page->read_template('text/verse_ref');

	$xlist='';
	$count=count($this->verse_list);
	for($i=0;$i<$count;$i++){
		$t=$tpl_item;

		$arr=explode('|',$this->verse_list[$i]);
		$title=$arr[0];
		$etitle=htmlentities($title);
		$subtitle=htmlentities($arr[1]);
		$text=$arr[2];

		$trefs='';
		if($arr[3]!=''){
			$lines=explode('@',$arr[3]);
			for($j=0;$j<count($lines);$j++){
				$ref=explode('~',$lines[$j]);
				$r=$tpl_tref_item;
				$r=str_replace('[TREF[MID]]',$ref[0],$r);	//substr($ref[0],1)
				$r=str_replace('[TREF[TITLE]]',$ref[1],$r);
				$trefs.=$r;
			}
		}

		$etitlea=is_numeric($etitle)?$etitle:$i;

		$t=str_replace('[LIST[TITLE]]',$arr[0],$t);
		$t=str_replace('[LIST[ETITLE]]',$etitle,$t);
		$t=str_replace('[LIST[ETITLEA]]',$etitlea,$t);
		$t=str_replace('[LIST[TEXT]]',$text,$t);
		$t=str_replace('[LIST[TREFS]]',$trefs,$t);

		$bgc=$this->verse_normal;
		if($this->start==($i+1)){
			$bgc=$this->verse_high;
		}elseif($this->end>0){
			if($this->end>=($i+1) && $this->start<($i+1))
				$bgc=$this->verse_high;
		}
		$t=str_replace('[LIST[BGC]]',$bgc,$t);



		$item_language='';
		//if($this->text_font=='-greek')
		//	$item_language=' xml:lang="grc" lang="grc"';


		$t=str_replace('[LIST[FONTFACE]]',$this->text_font,$t);
		$t=str_replace('[ITEM[LANGUAGE]]',$item_language,$t);
		
		$xlist.=$t;
	}

	// Save verse list
	$this->lv_text=$xlist;
}

// Generate navigation links
function make_navigation(&$tpl)
{
	$this->add='';
	if($this->previous!=0)
		$this->add.=' <a href="text[EXT]?id='.$this->previous.'">'.$this->tn->make_img('lf','[STR[DISPLAY_PREVIOUS_PAGE]]').'</a>';
	if($this->next!=0)
		$this->add.=' <a href="text[EXT]?id='.$this->next.'">'.$this->tn->make_img('rg','[STR[DISPLAY_NEXT_PAGE]]').'</a>';
	$tpl=str_replace('[TEXT[NAV]]',$this->add,$tpl);

	if($this->next!=0)
		$this->page->pagerefs->add(1,'text[EXT]?id='.$this->next,'rg','[STR[DISPLAY_NEXT_PAGE]]','3');
	if($this->previous!=0)
		$this->page->pagerefs->add(1,'text[EXT]?id='.$this->previous,'lf','[STR[DISPLAY_PREVIOUS_PAGE]]','2');
}

// Create the text page
function make_page()
{
	global $page;

	// Read template
	$tpl=$page->read_template('text/text_list');

	// Title
	$page->title=$this->title;
	$page->path=$this->pagepath;

	// User section
	if($this->page->user->logged_in()){



		apply_section($tpl,'USER',0);
	}
	else apply_section($tpl,'USER',1);

	// Subtitle
	$page->subtitle=$this->subtitle;
	apply_section($tpl,'SUBTITLE',$this->subtitle==''?1:0);

	// Add save icon, if relevant
	if($page->user->logged_in()){
		$this->page->pagerefs->add(
			1,
			'user[EXT]?c=a&amp;id='.$this->mid,
			'save','[STR[SAVE_DOCUMENT]]');
	}

	// Navigation
	$this->make_navigation($tpl);

	// Insert author
	$tpl=str_replace('[TEXT[AUTHOR]]',$this->author,$tpl);
	apply_section($tpl,'AUTHOR',$this->author==''?1:0);

	// Insert text
	$tpl=str_replace('[TEXT[TEXT]]',$this->text,$tpl);
	apply_section($tpl,'TEXT',$this->text==''?1:0);

	// Insert list
	$tpl=str_replace('[TEXT[LIST]]',$this->lv_text,$tpl);
	apply_section($tpl,'LIST',$this->lv_text==''?1:0);

	// Insert system information
	$tpl=str_replace('[TEXT[SYS]]','',$tpl);

	// Return page
	return $tpl;
}

};

?>
