<?php
//======================================================================
// Display info-text functions
//----------------------------------------------------------------------
// Displcy misc. information of the system
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//
// Include main application file and date class
//
require 'main.php';
require 'class/class_date.php';


//
// Display countries that have visited the pages
//
function display_country_list()
{
	global $page,$db;

	//
	// Read templates
	//
	$tpl=$page->read_template('book/country_list');
	$item=$page->read_template('book/country_list_item');
	$clist='';

	//
	// Open list file and read lines
	//
	$fp=fopen('log/country_list.txt','rt');
	while(!feof($fp)){
		//
		// Read line
		//
		$line=trim(fgets($fp));
		if($line!=''){
			//
			// Idetify field-values and prepend template
			//
			$cc=explode('|',$line);
			$clist=$item.$clist;

			//
			// Insert values into template
			//
			$clist=str_replace('[COUNTRY[CC]]',strtolower($cc[0]),$clist);
			$clist=str_replace('[COUNTRY[NAME]]',$cc[1],$clist);
			$clist=str_replace('[COUNTRY[COUNT]]',$cc[2],$clist);
		}
	}
	fclose($fp);

	//
	// Insert list into template
	//
	$tpl=str_replace('[COUNTRY[LIST]]',$clist,$tpl);

	//
	// Apply title and content
	//
	$page->title=sprintf(
		constant('STR_COUNTRY_LIST_TITLE'),
		constant('STR_SITE_LAUNCH_DATE'),
		make_short_date(date('Ymd',strtotime('-1 day'))));
	$page->content=$tpl;
}


//
//  Display the top most commen countries
//
function display_top_countries($top=15)
{
	global $cfg,$db,$page;

	//
	// Read templetes
	//
	$tpl=$page->read_template('book/book_top_popular');
	$item=$page->read_template('book/country_top_popular_item');
	$tlist='';
	$count=0;

	//
	// Lookup the most popular texts
	//
	$sql="
		SELECT TOP $top
			a.client_countrycode AS cc,
			c.country,
			COUNT(*) AS RCount
		FROM
			".$cfg['db']['prefix']."text_access a,
			".$cfg['db']['prefix']."dw_country c
		WHERE
			a.client_countrycode=c.country_code
		GROUP BY
			a.client_countrycode,
			c.country
		ORDER BY
			RCount DESC
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Get and format values from the database
		//
		$count++;
		$country=$db->field('country');
		$iname=$country;
		if(is_local()) $iname.=' - ('.$db->field('RCount').')';

		//
		// Insert data into template
		//
		$tlist.=$item;
		$tlist=str_replace('[COUNTRY[RANK]]',$count,$tlist);
		$tlist=str_replace('[COUNTRY[CODE]]',strtolower($db->field('cc')),$tlist);
		$tlist=str_replace('[COUNTRY[NAME]]',$db->field('country'),$tlist);
		$tlist=str_replace('[COUNTRY[INAME]]',$iname,$tlist);
	}
	$db->close();

	//
	// Insert list into template
	//
	$tpl=str_replace('[TOP[LIST]]',$tlist,$tpl);

	//
	// Apply to page
	//
	$page->title=sprintf(constant('STR_TOP_COUNTRY_TITLE'),$top);
	$page->content=$tpl;
}


//
//  Display most popular texts
//
function display_top_texts($top=20)
{
	global $cfg,$db,$page;

	//
	// Read templetes
	//
	$tpl=$page->read_template('book/book_top_popular');
	$item=$page->read_template('book/book_top_popular_item');
	$tlist='';
	$count=0;

	//
	// Lookup the most popular texts
	//
	$sql="
		SELECT TOP $top
			t.*,
			r.RCount
		FROM
			".$cfg['db']['prefix']."texts t,
			(
			SELECT
				tt.mid AS tid,
				COUNT(*) AS RCount
			FROM
				".$cfg['db']['prefix']."text_access a,
				".$cfg['db']['prefix']."texts tt
			WHERE
				a.mid=tt.mid
			GROUP BY
				tt.mid
			) r
		WHERE
			t.mid=r.tid
		ORDER BY
			r.RCount DESC,
			t.mid
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Increase recordcounter and add item-parenttext template
		//
		$count++;
		$tlist.=$item;

		//
		// Make parent text
		//
		$item_parenttext='';
		$last_mid=0;
		if($db->field('parenttext')!=''){
			$pt_list=explode('|',$db->field('parenttext'));
			for($i=0;$i<count($pt_list);$i++){
				$pt_item=explode('#',$pt_list[$i]);
				if($item_parenttext!='') $item_parenttext.=' / ';
				$item_parenttext.=$pt_item[1];
				$last_mid=$pt_item[0];
			}
		}

		$item_parenttext.=' / '.$db->field('title');
		if(substr($item_parenttext,0,3)==' / ')
			$item_parenttext=substr($item_parenttext,3);
		if($item_parenttext=='')
			$item_parenttext=$db->field('title');

		//
		// Make extended title
		//
		$atitle=$item_parenttext;		
		if(is_local()) $atitle.=' - ('.$db->field('RCount').')';

		//
		// Insert values into item template
		//
		$tlist=str_replace('[TOP[COUNT]]', $count,           $tlist);
		$tlist=str_replace('[TOP[MID]]',   $db->field('mid'),$tlist);
		$tlist=str_replace('[TOP[TITLE]]', $item_parenttext, $tlist);
		$tlist=str_replace('[TOP[ATITLE]]',$atitle,          $tlist);
	}
	$db->close();

	//
	// Insert list into template
	//
	$tpl=str_replace('[TOP[LIST]]',$tlist,$tpl);

	//
	// Apply to page
	//
	$page->title=sprintf(constant('STR_TOP_TITLE'),$top);
	$page->content=$tpl;
}


//
// Display the contact form
//
function display_contact_form($msg='')
{
	global $page;

	//
	// Read template and prepare for display
	//
	$tpl=$page->read_template('contact_form');
	$tpl=str_replace('[CONTACT[MSG]]',$msg,$tpl);
	apply_section($tpl,'MESSAGE',$msg==''?1:0);

	//
	// Apply template and return success
	//
	$page->title='[STR[CONTACT]]';
	$page->content=$tpl;
	return true;
}


//
// Send comment mail
//
function send_contact_mail()
{
	//
	// Get string parameters
	//
	$subject=request_string('subject');
	$name   =request_string('name');
	$email  =request_string('email');
	$comment=request_string('comment');

	//
	// Validate parameters
	//
	if($comment=='')
		return display_contact_form('[STR[MUST_FILL_IN_COMMENT]]');

	//
	// Compose mail body;
	//
	$body=
		constant('STR_SUBJECT').": $subject\n".
		constant('STR_NAME').   ": $name\n".
		constant('STR_EMAIL').  ": $email\n".
		constant('STR_COMMENT').":\n$comment\n";

	//
	// Send mail
	//
	$from='system@kristus.dk';
	$xtra="From: $from\nReply-To: $from\nContent-Type: text/html\nContent-Transfer-Encoding: 8bit\n";
	$result=mail(
		constant('STR_CONTACT_MAILTO'),
		constant('STR_CONTACT_HEADER'),
		$body,
		$xtra);

	//
	// Return to contact form with status message
	//
	display_contact_form(
		$result==false?
		'[STR[AN_ERROR_OCCURED_CONTACT]]':
		'[STR[COMMENT_WAS_SENT]]');
}


//
// Display client information
//
function make_client_item($item,$name,$value)
{
	$item=str_replace('[ITEM[NAME]]',$name,$item);
	$item=str_replace('[ITEM[VALUE]]',$value,$item);
	return $item;
}


//
// Display client onfo
//
function display_client_info()
{
	global $cfg,$page,$db;

	//
	// Read templates
	//
	$tpl  =$page->read_template('misc/client_info');
	$item =$page->read_template('misc/client_info_item');
	$delim=$page->read_template('misc/client_info_delim');

	//
	// Find user country
	//
	$country='?';
	$iplong=make_ip2long($_SERVER['REMOTE_ADDR']);
	$sql="
		SELECT
			country
		FROM
			".$cfg['db']['prefix']."dw_geoip
		WHERE
			$iplong>=ip_num_start
			AND $iplong<=ip_num_end
		";
	$db->open($sql);
	if($db->move_next()) $country=$db->field('country');
	$db->close();

	//
	// Get cookie parameters
	//
	$param=session_get_cookie_params();

	//
	// Get all cookies
	//
	$cook='';
	foreach($_COOKIE as $key => $value)
		$cook.='['.$key.'|'.$value.']<br />';

	//
	// Get all server vars
	//
	$serv='';
	foreach($_SERVER as $key => $value)
		$serv.='['.$key.','.$value.']<br />';

	//
	// Make table
	//
	$p='';
	$p.=make_client_item($item,'Timestamp',  date('r'));
	$p.=make_client_item($item,'Client IP',  $_SERVER['REMOTE_ADDR']);
	$p.=make_client_item($item,'Client Name',gethostbyaddr($_SERVER['REMOTE_ADDR']));
	$p.=make_client_item($item,'Country',    $country);
	$p.=make_client_item($item,'Client Port',$_SERVER['REMOTE_PORT']);
	$p.=make_client_item($item,'Connection', $_SERVER['HTTP_CONNECTION']);
	$p.=$delim;

	$p.=make_client_item($item,'Session name',    session_name());
	$p.=make_client_item($item,'Session id',      session_id());
	$p.=make_client_item($item,'Session lifetime',$param['lifetime']);
	$p.=$delim;

	$p.=make_client_item($item,'User-Agent',     $_SERVER['HTTP_USER_AGENT']);
	$p.=make_client_item($item,'Accept',         str_replace(',',', ',$_SERVER['HTTP_ACCEPT']));
	$p.=make_client_item($item,'Accept language',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
	$p.=make_client_item($item,'Accept eccoding',$_SERVER['HTTP_ACCEPT_ENCODING']);
	$p.=$delim;

	$p.=make_client_item($item,'Referer',        $_SERVER['HTTP_REFERER']);
	$p.=make_client_item($item,'Cookies',        $cook);

	//
	// Insert list into main template
	//
	$tpl=str_replace('[INFO[LIST]]',$p,$tpl);

	//
	// Apply
	//
	$page->title="Hvad jeg ved om dig";
	$page->content=$tpl;
}


//
//
//
function display_bom()
{
	$box1=new box_nav;
	$box1->title='[STR[BOOKS]]';
	$box1->start_row("Skriften");
	$box1->add_item('[TREF]60000','Mormons Bog - p&#65533; dansk');
	$box1->add_item('[TREF]160000','Book of Mormon');
	$box1->add_item('[TREF]260000','Mormons Bok');
	$box1->end_row();
	$box1->start_row("Studie");
	$box1->add_item('[TREF]33700','Studievejledning');
	$box1->add_item('[TREF]62000','Mormons Bog - Elevens hæfte');
	$box1->end_row();
	$box1->start_row("Andet");
	$box1->add_item('[TREF]28000','En ny bog udfordrer verden');
	$box1->add_item('master.php?c=bl&amp;id=3','Mesterskriftsteder');
	$box1->end_row();

	$box2=new box_nav;
	$box2->title='Lyt til Mormons Bog';
	$box2->start_row("Lydbog");
	$box2->add_item('[TREF]61600','Mormons Bog - Lydbog');
	$box2->end_row();
	$box2->start_row("Download MP3-filer");
	$box2->add_item('[DIR[DATA]]data/bulk/mb_mp3.zip','Nummereret med indeksfil');
	$box2->add_item('[DIR[DATA]]data/bulk/mb_name_mp3.zip','Navngivet efter bøgerne');
	$box2->end_row();

	make_frontpage(
		"[STR[BOOK_OF_MORMON]]",
		array(
			"Sammen med Bibelen er Mormons Bog de to vigtigste bøger som mennesker kan have gavn af at læse.",
			"Udover at du her kan læse Mormons Bog p&#65533; flere sprog, kan du ogs&#65533; finde studiemateriale om Mormons Bog.",
			"Bemærk at 'Mormons Bog - Elevens hæfte' er et større værk, som langt fra er færdigt."
		),
		'section_bom',
		'',
		$box1->get(),
		$box2->get()
	);
}




//
// Main function
//
function main()
{
	global $page;

	//
	// Setup double menu page
	//
	$page->tpl_main='main_lmenu_rmenu';
	$page->add_css('info');

	//
	// Get parameters
	//
	$id=request_string('id');
	if($id=='') $id='faq';
	$cmd=request_string('c');
	if($cmd=='') $cmd='info';

	switch($cmd){
	case 'mb':   display_bom();           break;
	case 'user': display_client_info();   break;
//	case 'top':  display_top_texts();     break;
	case 'topc': display_top_countries(); break;
	case 'cl':   display_country_list();  break;
	case 'info':

		//
		// Act on command
		//
		switch($id){
		case 'contact': display_contact_form(); break;
		case 'send':    send_contact_mail();    break;
		default:        display_info_text($id); break;
		}

		break;
	}

	//
	// Make right menu
	//
	$page->read_menu(1,'menu_info');
	if(is_local()){
		$page->rmenu->add_start("User INFO");
		$page->rmenu->add_item('info[EXT]?c=user','Client INFO');
		$page->rmenu->add_end();
	}
}


//
// Entry point
//
main();
$page->send();
?>
