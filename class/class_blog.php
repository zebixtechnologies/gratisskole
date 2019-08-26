<?php
//======================================================================
// Blog object
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Nivå, Denmark
//======================================================================

class blog
{
// System
var $id=2;
var $format='web';
var $entry_id=0;
var $trackback=1;
var $display_type=0;

// BLOG lists
var $blog_list='';
var $sub_list='';

// Templates
var $tpl_wrap='';
var $tpl_entry='';
var $tpl_comment='';
var $tpl_comment_wrap='';
var $tpl_comment_form='';

// Comment vars
var $comment_count=0;
var $comment_list='';
var $show_comments=false;

// References to external classes
var $page;
var $db;


// Constructor
function blog()
{
	if(is_local()) $this->show_comments=true;
}

// Read all blog template files
function read_blog_templates()
{
	$this->tpl_entry=$this->page->read_template('blog/blog_entry'.($this->format=='xml'?'_xml':'_web'));
	if($this->format!='xml'){
		$this->tpl_wrap=$this->page->read_template('blog/blog_wrap');
		$this->tpl_comment=$this->page->read_template('blog/blog_comment');
		$this->tpl_comment_wrap=$this->page->read_template('blog/blog_comment_wrap');
		$this->tpl_comment_form=$this->page->read_template('blog/blog_comment_form');
	}
}

// Strip tags from xml files
function fix_entities($t)
{
	if($this->format!='xml') return $t;
	$t=str_replace("\n"," ",$t);
	return strip_tags($t);

	//return html2txt($t);
	
	return $t;
}

// Insert smilies
function insert_smilies($t)
{
	if($this->format!='web') return $t;
	return insert_smilies($t);
}

// Clear entire subject list
function clear_sub_list()
{
	$this->sub_list='';
}

// Add subject to current entry's subject list
function add_subject($id,$name)
{
	if($this->sub_list!='') $this->sub_list.='#';
	$this->sub_list.=$id.'|'.$name;
}

// Add entry to entry list
function add_entry($id,$title,$date_time,$text_data,$comment_count)
{

	// Get entry template
	$tpl=$this->tpl_entry;

	// Make appropiate data conversion
	$date_time2=make_display_date($date_time);
	$text_data=nl2html($text_data);
	$text_data=$this->insert_smilies($text_data);

	// Create subject link list
	$slist='';
	if($this->sub_list!=''){
		$subs=explode('#',$this->sub_list);
		for($i=0;$i<count($subs);$i++){
			$lnk=explode('|',$subs[$i]);
			if($slist!='') $slist.=' - ';
			$slist.='<a class="rlink" href="blog[EXT]?s='.$lnk[0].'">'.$lnk[1].'</a>';
		}
	}

	// Insert blog data into entry template
	$tpl=str_replace('[ENTRY[TITLE]]',$this->fix_entities($title),$tpl);
	$tpl=str_replace('[ENTRY[TEXT_DATA]]',$this->fix_entities($text_data),$tpl);
	$tpl=str_replace('[ENTRY[DATE_TIME_LINK]]',substr($date_time,0,8),$tpl);
	$tpl=str_replace('[ENTRY[DATE_TIME]]',$date_time2,$tpl);
	$tpl=str_replace('[ENTRY[PUB_DATE]]',std_date($date_time),$tpl);
	$tpl=str_replace('[ENTRY[SUBJECT_LIST]]',$slist,$tpl);
	$tpl=str_replace('[ENTRY[COMMENT_COUNT]]',$comment_count,$tpl);
	$tpl=str_replace('[ENTRY[COMMENT_LINK]]','blog[EXT]?e='.$id,$tpl);
	$tpl=str_replace('[ENTRY[LINK]]','[URL[ME]]blog[EXT]?e='.$id,$tpl);
	$tpl=str_replace('[ENTRY[TRACKBACK]]',$this->trackback,$tpl);
	$tpl=str_replace('[ENTRY[ID]]',$id,$tpl);

	// Discard unused sections
	$tpl=apply_section($tpl,'TRACKBACK',$this->trackback>1?false:true);

	// Save entry to blog entry list
	$this->entry_id=$id;
	$this->blog_list.=$tpl;
	$this->comment_count=0;
	$this->comment_list='';
}

// Add new comment to list
function add_comment($id,$date_time,$name,$email,$url,$textdata,$sender_ip)
{
	// Nothing to do here...
	if($this->format=='xml') return;

	// Read template and increment comment counter
	$tpl=$this->read_template('blog/comment');
	$this->comment_count++;

	// Format values
	$date_time2=make_display_date($date_time);
	$textdata=nl2html($textdata);

	// Insert data into template
	$tpl=str_replace('[COMMENT[NUMBER]]',$this->comment_count,$tpl);
	$tpl=str_replace('[COMMENT[DATE_TIME]]',$date_time2,$tpl);
	$tpl=str_replace('[COMMENT[NAME]]',$name,$tpl);
	$tpl=str_replace('[COMMENT[EMAIL]]',$email,$tpl);
	$tpl=str_replace('[COMMENT[UEMAIL]]',umail($email),$tpl);
	$tpl=str_replace('[COMMENT[URL]]',$url,$tpl);
	$tpl=str_replace('[COMMENT[SENDER_IP]]',$sender_ip,$tpl);
	$tpl=str_replace('[COMMENT[TEXTDATA]]',$textdata,$tpl);

	// Discard unused sections
	$tpl=apply_section($tpl,'URL',$url!=''?false:true);
	$tpl=apply_section($tpl,'EMAIL',$email!=''?false:true);

	// Save comment to blog entry list
	$this->comment_list.=$tpl;
}

// Insert comment into database
function insert_comment()
{
	// Get parameters
	$name=request_string('name');
	$email=request_string('email');
	$url=request_string('url');
	$comment=request_string('comment');
	$e=request_string('e');
	$c=request_string('c');
	$v=request_string('v');
	$sender_ip=session_string('REMOTE_ADDR');

	// Validate parameters
	if($name=='' || strlen($name)>25) return false;
	if($email=='' || strlen($email)>50) return false;
	if($comment=='' || strlen($comment)>1024) return false;
	if($url=='') $url='-';

	// Normalize parameters
	$name=make_db_string($name);
	$email=str_replace("'",'',$email);
	$url=str_replace("'",'',$url);
	$comment=make_db_string($comment);

	// Insert into database
	$sql="
		INSERT INTO
			jkk_blog_comment
		(
			entryid,
			name,
			email,
			url,
			textdata,
			sender_ip
		)
		VALUES
		(
			$e,
			'$name',
			'$email',
			'$url',
			'$comment',
			'$sender_ip'
		);";
	$this->db->execute($sql);

	// Go to default entry file
	url_redirect('blog[EXT]?e='.$e);
}

// Compile and return entire entry list
function get_list()
{
	// Get entry list
	$list=$this->blog_list;

	// Comments
	if($this->show_comments){
	
		// Compile comment list
		$comments='';
		if($this->comment_count>0){
			$comments=$this->tpl_comment_wrap;
			$comments=str_replace('[COMMENT[LIST]]',$this->comment_list,$comments);
			$list.=$comments;
		}

		// If this is a single entry then display form
		if($this->display_type==2){
			// Get commentform
			$tpl=$this->tpl_comment_form;

			// Hm... not
			$_SESSION['blog_entry_id']=$this->entry_id;
			$_SESSION['blog_magic']=$magic=rand(0,65535);
			$code=md5($magic.zpad($this->entry_id,5).date('Ymd'));

			// Insert values in comment
			$tpl=str_replace('[ENTRY[ID]]',$this->entry_id,$tpl);
			$tpl=str_replace('[INSERT[CODE]]',$code,$tpl);

			// Add to list
			$list.=$tpl;
		}

	}

	// Wrap list
	$tpl=$this->tpl_wrap;
	if($tpl=='') $tpl='[BLOG[DATA]]';
	$tpl=str_replace('[BLOG[DATA]]',$list,$tpl);
	
	// Return the compiled list
	return utf8_encode($tpl);
}

};

?>