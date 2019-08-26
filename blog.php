<?php
//======================================================================
// Blog functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//
// Include files
//
require 'main.php';
require $page->tpl_string('blog');
require 'class/class_blog.php';
require 'class/class_date.php';


//
// Global blog object
//
$blog=new blog;
$blog->page=&$page;
$blog->db=&$db;


//
// Make default right menu
//
function make_rmenu()
{
	global $cfg,$db,$page,$blog;

	//
	// BLOG Frontpage
	//
	$page->rmenu->add_start('[STR[BLOG]]');
	$page->rmenu->add_xml_item('blog[EXT]?f=rss.xml','blog[EXT]','[STR[FRONTPAGE]]');
	$page->rmenu->add_end();

	//
	// Calandar
	//
	$page->rmenu->add_start('[STR[CALENDAR]]');
	$page->rmenu->add_item('blog[EXT]?c=cal',flcap(get_month_name((int)date('m'),1)));
	for($i=((int)date('m')-1);$i>=3;$i--)
		$page->rmenu->add_item('blog[EXT]?c=cal&amp;y=2006&amp;m='.$i,flcap(get_month_name($i)));
	$page->rmenu->add_end();

	//
	// Latest entry days
	//
	$page->rmenu->add_start(sprintf(constant('STR_THE_LATEST_N_DAYS'),5));
	$sql="
		SELECT TOP 5
			YEAR(datecreated) AS Y,
			MONTH(datecreated) AS M,
			DAY(datecreated) AS D
		FROM
			".$cfg['db']['prefix']."blog_entry
		WHERE
			id>1
		GROUP BY
			YEAR(datecreated),
			MONTH(datecreated),
			DAY(datecreated)
		ORDER BY
			YEAR(datecreated) DESC,
			MONTH(datecreated) DESC,
			DAY(datecreated) DESC
		";
	$db->open($sql);
	while($db->move_next()){
		$ndate=$db->field('Y').zpad($db->field('M'),2).zpad($db->field('D'),2);
		$url='blog[EXT]?d='.$ndate;
		$page->rmenu->add_item($url,make_display_date($ndate,0));
	}
	$db->close();
	$page->rmenu->add_end();

	//
	// Categories
	//
	$page->rmenu->add_start('[STR[CATEGORIES]]');
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."blog_subject
		ORDER BY
			id
		";
	$db->open($sql);
	while($db->move_next()){
		$url='blog[EXT]?s='.$db->field('id');
		$page->rmenu->add_xml_item($url.'&amp;f=rss.xml',$url,flcap($db->field('name')));
	}
	$db->close();
	$page->rmenu->add_end();

	//
	// Display local maintenance menu
	//
	if(is_local()){
		// Header + ping all
		$page->rmenu->add_start('[STR[BLOG_PING]]');
		$page->rmenu->add_item('blog[EXT]?c=ping','[STR[BLOG_PING_ALL_BOTS]]');

		// List available blog bots
		$sql="
			SELECT
				bot
			FROM
				".$cfg['db']['prefix']."blog_bot
			";
		$db->open($sql);
		while($db->move_next()){
			$page->rmenu->add_item(
				'blog[EXT]?c=ping&amp;id='.$db->field('bot'),
				'- '.$db->field('bot'));
		}
		$db->close();
		$page->rmenu->add_end();
	}
}


//
// Use XML-RPC to ping blobot
//
function weblog_updates_ping($host,$port,$path,$method,$bname,$name,$url,$debug=false)
{
	$postdata='<?xml version="1.0" encoding="iso-8859-1"?>
	   <methodCall>
	     <methodName>'.htmlspecialchars($method).'</methodName>
	     <params>
	       <param><value><string>'.htmlspecialchars($name).'</string></value></param>
	       <param><value><string>'.htmlspecialchars($url).'</string></value></param>
	     </params>
 	   </methodCall>';

	$timeout=20;

	$fp=fsockopen($host,$port,$errno,$errstr,$timeout);
	if(!$fp) return array(-1, "Could not connect to $host:$port");
	socket_set_timeout($fp, $timeout);
	
	$request=
		"POST $path HTTP/1.0\r\n" .
		"Host: $host\r\n" .
		"Content-Type: text/xml\r\n" .
		"User-Agent: $bname XML-RPC client\r\n" .
		"Content-Length: ".strlen($postdata)."\r\n" .
		"\r\n" .
		$postdata;
	fputs($fp, $request);
	
	if($debug){
		print "<div style='color: blue; white-space: pre'>";
		print htmlspecialchars($request);
		print "</div>";
	}

	$response='';
	while(!feof($fp)){
		$response.=fgets($fp,1024);
		$status=socket_get_status($fp);
		if($status['timed_out']){
			fclose($fp);
			return array(-2, "Request timed out");
		}
	}
	fclose($fp);

	if($debug){
		print "<div style='color: green; white-space: pre'>";
		print htmlspecialchars($response);
		print "</div>";
	}

	if(preg_match('|<methodResponse>\s*<params>\s*<param>\s*<value>\s*<struct>\s*' .
		'<member>\s*<name>flerror</name>\s*<value>\s*<boolean>([^<])</boolean>\s*</value>\s*</member>\s*' .
		'<member>\s*<name>message</name>\s*<value>(\s*<string>)?([^<]*)(</string>\s*)?</value>\s*</member>\s*' .
		'</struct>\s*</value>\s*</param>\s*</params>\s*</methodResponse>' .
		'|s', $response, $reg)) {
		return array($reg[1], $reg[3]);
	}else{
		return array(-3,"Malformed reply:\n".$response);
	}
}


//
// Ping blogbot.dk
//
function blog_ping($bot='')
{
	global $cfg,$page,$db;

	// Initialize
	$tpl=$page->read_template('blog/blog_ping');
	$error=0;
	$message='';
	$p='';

	// Lookup blog bots
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."blog_bot
		".
		($bot==''?'':" WHERE bot='$bot'");
	$db->open($sql);
	while($db->move_next()){
		// Ping the current bot
		list($error,$message)=weblog_updates_ping(
			$db->field('bot'),
			$db->field('port'),
			$db->field('path'),
			$db->field('method'),
			$db->field('bname'),
			$db->field('name'),
			$db->field('url'));

		// Check for error
		if($error!=0){
			$p.='<p style="color:red">[STR[AN_ERROR_OCCURED]]: '.
				$db->field('bot').' - '.htmlspecialchars($message).'</p>';
		}else{
			$p.=sprintf(constant('STR_BLOG_PING_SUCCESSFULL'),$db->field('bot')).'</p>';
		}
	}
	$db->close();

	// Insert result into template
	$tpl=str_replace('[PING[CONTENT]]',$p,$tpl);

	// Make right menu
	make_rmenu();

	// Apply
	$page->title='[STR[BLOG_PING]]';
	$page->content=$tpl;
}


//
// Display the specified blog
//
function display_blog($show,$type,$format)
{
	global $cfg,$db,$page,$blog;

	// Save type
	$blog->display_type=$type;
	$top_count='';

	// Act on output specification
	switch($type){

	// Day list - Normalize date ( d=n )
	case 0:
		$show=normalize_date($show);
		$w=date('w',mktime(0,0,0,substr($show,4,2),substr($show,6,2),substr($show,0,4)));
		$w=$w==0?$w=7:$w--;
		$page->title=flcap(get_day_name($w)).' '.make_display_date($show,0);
		$entry_order=$blog->format=='xml'?'DESC':'DESC';
		//$has_comments=true;
		$has_comments=false;
		$from_where="
			FROM
				".$cfg['db']['prefix']."blog_entry e
			WHERE
				e.id>1 AND e.userid=$blog->id
				AND
				YEAR(e.datecreated)=".substr($show,0,4)."
				AND
				MONTH(e.datecreated)=".substr($show,4,2)."
				AND
				DAY(e.datecreated)=".substr($show,6,2)."
			";
		break;

	// Find subject name ( s=n )
	case 1:
		$db->open('SELECT name FROM jkk_blog_subject WHERE id='.$show);
		$page->title=flcap($db->field('name'));
		$db->close();
		$entry_order='';
		//$has_comments=true;
		$has_comments=false;
		$top_count='TOP 50';
		$from_where="
			FROM
				".$cfg['db']['prefix']."blog_entry_subject es,
				".$cfg['db']['prefix']."blog_entry e
			WHERE e.id>1
				AND es.entryid=e.id
				AND es.subjectid=$show
			";
		break;

	// Specific entry ( e=n )
	case 2:
		$page->title='';
		$entry_order='';
		//$has_comments=false;
		$has_comments=true;
		$blog->show_comments=is_local()?true:false;
		$blog->show_comments=false;
		$blog->entry_id=$show;
		if(request_string('c')=='i') $blog->insert_comment($db);
		$from_where="
			FROM
				".$cfg['db']['prefix']."blog_entry e
			WHERE
				e.id=$show
			";
		break;

	// Latest entries
	case 3:
		$w=date('w',mktime(0,0,0,substr($show,4,2),substr($show,6,2),substr($show,0,4)));
		$w=$w==0?$w=7:$w--;
		$page->title=flcap(get_day_name($w)).' '.make_display_date($show,0);
		$entry_order='';
		$has_comments=false;
		$top_count='TOP 10';
		$from_where="
			FROM
				".$cfg['db']['prefix']."blog_entry e
			";
		break;
	}

	// Create comments Select-SQL
	if($has_comments!=false){
		$sql="
			SELECT
				entryid,
				COUNT(datecreated) AS RCount
			FROM
				".$cfg['db']['prefix']."blog_comment
			WHERE
				entryid IN
					(SELECT id $from_where)
			GROUP BY
				entryid,
				datecreated
			ORDER BY
				datecreated
			";
		if($type!=0) $sql.=" DESC";

		// Open comments recordset
		$db->open($sql,3);
	}

	// Select entries
	$sql="
		SELECT $top_count
			e.*
		$from_where
		ORDER BY
			e.datecreated ".$entry_order;
	if($type!=0) $sql.=' DESC';
	$db->open($sql,1);

	while($db->move_next(1)){

		// Trackback
		$blog->trackback=$db->field('parentid',1);

		// Comments
		$comment_count=0;
		if($has_comments!=false){
			$stop=false;
			while($db->field('entryid',3)==$db->field('id',1)&&$stop==false){
				$comment_count+=$db->field('RCount',3);
				$stop=$db->move_next(3)?false:true;
			}
		}

		// Subjects
		$blog->clear_sub_list();
		$sql="
			SELECT
				s.*
			FROM
				".$cfg['db']['prefix']."blog_subject s,
				".$cfg['db']['prefix']."blog_entry_subject e
			WHERE
				e.entryid=".$db->field('id',1)."
			AND
				e.subjectid=s.id
			ORDER BY
				s.name
			";
		$db->open($sql,2);
		while($db->move_next(2))
			$blog->add_subject($db->field('id',2),$db->field('name',2));
		$db->close(2);

		// Add the current entry
		$blog->add_entry(
			$db->field('id',1),
			$db->field('title',1),
			normalize_date($db->field_date('datecreated',1)),
			$db->field('textdata',1),
			$comment_count);
			
		// Add comments
		if($type==2){
			$page->title=$db->field('title',1);

			// Lookup comments
			$sql="
				SELECT
					*
				FROM
					".$cfg['db']['prefix']."blog_comment
				WHERE
					entryid=".$db->field('id',1)."
				ORDER BY
					datecreated
				";
			$db->open($sql,4);

			if($db->move_next(4)){
				do{
					$blog->add_comment(
						$db->field('id',4),
						normalize_date($db->field_date('datecreated',4)),
						$db->field('name',4),
						$db->field('email',4),
						$db->field('url',4),
						$db->field('textdata',4),
						$db->field('sender_ip',4)
						);
				}while($db->move_next(4));
			}
			$db->close(4);
		}
	}

	// Close all recordsets
	$db->close(1);
	if($has_comments!=false) $db->close(3);

	//if($has-comments==false) $blog->show_comments=false;

	// Get entry list
	$p=$blog->get_list();

	// Discard comment section if not used
	$p=apply_section($p,'COMMENT',$has_comments?0:1);

	// Apply list
	$page->content.=$p;
}


//
// Display calendar to choose blog entry date
//
function display_blog_calandar()
{
	global $page;

	//
	// Setup page
	//
	$page->add_css('date');

	//
	// Get parameters
	//
	$year=request_string('y');
	$month=request_string('m');

	//
	// Get current year and month if none specified
	//
	if($year=='') $year=(int)date('Y');
	if($month=='') $month=(int)date('m');

	//
	// Read template and insert calendar
	//
	$tpl=$page->read_template('blog/blog_calendar');
	$cal=make_month_calendar($year,$month,true,0);
	$tpl=str_replace('[BLOG[CALENDAR]]',$cal,$tpl);

	//
	// Make right menu
	//
	make_rmenu();

	//
	// Apply
	//
	$page->title='BLOG [STR[CALENDAR]]';
	$page->content=$tpl;
}


//
// Main handler - determine blog type and display blog
//
function main()
{
	global $cfg,$db,$page,$blog;

	//
	// Setup page
	//
	$page->tpl_main='main_lmenu_rmenu';
	$page->add_css('blog');

	//
	// Check for ping right away
	//
	switch(request_string('c')){
	case 'ping': return blog_ping(request_string('id')); break;
	case 'cal':  return display_blog_calandar();         break;
	}

	//
	// Get parameters
	//
	$d=request_string('d'); // Date
	$s=request_string('s'); // Subject
	$e=request_string('e'); // Entry
	$f=request_string('f'); // Format
	$c=request_string('c'); // Format

	//
	// Determine output-format
	//
	if($f!='rss.xml') $f='web';
	$blog->format=(($f=='rss.xml')?'xml':$f);
	$blog->read_blog_templates();

	//
	// Determine date
	//
	if($d!='' || ($d=='' && $s=='' && $e=='')){
		$show_date='';
		if($d!='') $show_date=$d;
		if($d==''){
			// Find latest entry date
			$sql="
				SELECT
					MAX(datecreated) AS MaxDate
				FROM
					".$cfg['db']['prefix']."blog_entry
				";
			$db->open($sql);
			if($db->move_next())
				$show_date=$db->field_date('MaxDate');
			$db->close();
			if($show_date=='')
				$show_date=date('Ymd');
		}

		// Display the relevant blog
		display_blog($show_date,$d==''?3:0,$f);
	}

	//
	// Subject list
	//
	elseif($s!='' && is_numeric($s)){
		display_blog($s,1,$f);
	}

	//
	// Single entry, with expanded comments
	//
	elseif($e!='' && is_numeric($e)){
		//$page->add_css('comment');
		display_blog($e,2,$f);
	}

	//
	// Error in combination of parameters
	//
	else{
		$page->title='[STR[AN_ERROR_OCCURED]]';
		$page->content='';
		return;
	};

	//
	// Make RSS if in XML mode
	//
	if($blog->format=='xml'){
		// Read master XML RSS template
		$tpl=$page->read_template('blog/template_xml');

		// Insert master values
		$tpl=str_replace('[BLOG[NAME]]',       '[STR[BLOG_NAME]]',$tpl);
		$tpl=str_replace('[BLOG[URL]]',        '[STR[BLOG_URL]]',$tpl);
		$tpl=str_replace('[BLOG[DESCRIPTION]]',constant('STR_BLOG_DESCRIPTION'),$tpl);
		$tpl=str_replace('[BLOG[LANGUAGE]]',   '[STR[BLOG_LANGUAGE]]',$tpl);
		$tpl=str_replace('[BLOG[BUILD_DATE]]', date('r'),$tpl);
		$tpl=str_replace('[BLOG[EDITOR]]',     '[STR[BLOG_EDITOR]]',$tpl);
		$tpl=str_replace('[BLOG[WEBMASTER]]',  '[STR[BLOG_WEBMASTER]]',$tpl);
		$tpl=str_replace('[BLOG[GENERATOR]]',  '[STR[BLOG_GENERATOR]]',$tpl);

		// Override existing template system
		$page->tpl_overide=$tpl;

		//$page->content_type='application/rss+xml';
		$page->content_type='text/xml';
	}else{
		//
		// Make right menu
		//
		make_rmenu();
	}
}


//
// Entry point
//
main();
$page->send();
?>
