<?php
//======================================================================
// Display text functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2019 Steffen Estrup / Rikard Warnsdorf
//======================================================================

//
// Include files
//
include_once "main.php";
include_once "class/class_text.php";


//
// Setup global text object
//
$to=new text_object;
$to->db=&$db;
$to->page=&$page;


//
// Display audio
//
function display_audio_book($book)
{
	global $cfg,$db,$page;

	//
	// Get book title and start-mid
	//
	$book_title='';
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."systxt
		WHERE
			id='$book'
	";
	$db->open($sql);
	if($db->move_next()){
		$book_title=$db->field('name');
		$mid_start=$db->field('mid');
	}
	$db->close();
	if($book_title=='') return;

	//
	//
	//
	$page->title=$book_title;
	$page->content=$page->read_template('text/bookread_'.$book);
	return;

	//
	// Find end-mid
	//
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."systxt
		WHERE
			mid>$mid_start
		ORDER BY
			mid
		";
	$db->open_set($sql,0,1);
	if($db->move_next())
		$mid_end=$db->field('mid')-1;
	else
		$mid_end=1000000;
	$db->close();
	//
	//
	//
	$book_link   ='<a href="data/bookread/mp3/T[ALIST[MID]].mp3" style="font-weight:bold">[ALIST[TITLE]]</a>';
	$tpl=$page->read_template('bookread/bookread_book');
	$chapter_link=$page->read_template('bookread/bookread_book_chapter');

	$count=0;
	$item_count=0;
	$items='';

	$sql="
		SELECT
			t.title,
			t.texttype,
			a.*
		FROM
			".$cfg['db']['prefix']."textaudio a,
			".$cfg['db']['prefix']."texts t
		WHERE
			a.mid=t.mid
			AND a.mid BETWEEN $mid_start AND $mid_end
		ORDER BY
			t.id
		";

	$db->open($sql);
	if($db->move_next()){
		do{
			if($db->field('texttype')=='b'){
				$tpl_item=$count>0?'</table>':'';
				$tpl_item.='<p style="font-weight:bold">'.$db->field('title').'</p><table>';
			}else{
				$tpl_item=$chapter_link;
				$tpl_item=str_replace('[ALIST[MID]]',$db->field('mid'),$tpl_item);
				$tpl_item=str_replace('[ALIST[TITLE]]',$db->field('title'),$tpl_item);
			
			}

			$alist.=$tpl_item;
			$count++;
		}while($db->move_next());
		$alist.='</table>';
	}
	$db->close();


	$tpl=str_replace('[ALIST[LIST]]',$alist,$tpl);


	//$tpl=$mid_start.' '.$mid_end;

	//
	// Apply template
	//
	$page->title=$book_title;
	$page->content=$tpl;
}


// Display the text associated to the specified mid
function display_text($id)
{
	global $cfg,$db,$page,$to;

	// Parse ID
	$to->parse_id($id);

	// Some special case
	if($to->id==61600){
		display_audio_book('mb');
		return;
	}

	// Select text table
	if($to->id>999){
		$sql="
			SELECT TOP 1
				id,
				mid,
				db,
				dtd
			FROM
				".$cfg['db']['prefix']."systxt
			WHERE
				mid<=$to->id
			ORDER BY
				mid DESC
			";
echo $sql;
			
		$db->open($sql);
		if(!$db->move_next())
			return $to->error('[STR[SYSTXT_NOT_FOUND]]');
		$to->table=$db->field('db')==0?'jkk_texts':'jkk_texts1';
		$to->systxt_id=$db->field('id');
		$to->dtd=$db->field('dtd');
		$db->close();
	}else{
		$to->table=$cfg['db']['prefix']."jkk_texts";
		$to->systxt_id='sng';
	}

	// Lookup text data in database
	$sql="
		SELECT
			*
		FROM
			$to->table
		WHERE
			mid=$to->id
		";

					// echo $sql;
		
	$db->open($sql);
	if(!$db->move_next())
		return $to->error(constant('STR_TEXT_NOT_FOUND'));
	$to->get_record_data($db);
	$db->close();

	// Determine Lingo/Audio/Path
	$to->parse_extras();

	// Save text stats
	if(!is_local()){
		$sql="
			INSERT INTO
				".$cfg['db']['prefix']."text_access
			(systxtid,mid,startend,referer,client_ip,client_countrycode)
			VALUES(
				'".$to->systxt_id."',
				".$to->id.",
				'".$to->start.'-'.$to->end."',
				'".(''.@$_SERVER['HTTP_REFERER'])."',
				'".$_SERVER['REMOTE_ADDR']."',
				'?'
				)
			";
		$db->execute($sql);
	}

	// Parse text
	$to->parse_text();
	//if($to->text!='') $p=$to->make_text_section();

	// Subtexts
	$sql="
		SELECT
			*
		FROM
			$to->table
		WHERE
			parentid=$to->xid
		ORDER BY
			id
		";

	$db->open($sql);
	if($db->move_next()){

		// Scripture
		if($to->texttype=='c'&&$to->type=='Skrift'){
			// Initialize text references if requested
			if($to->use_trefs!=false){
				$q="
					SELECT
						tt.srcid,
						t.mid,
						t.title
					FROM
						".$cfg['db']['prefix']."txttxt tt,
						$to->table t,
						$to->table t2
					WHERE
						tt.destid=t.mid
						AND tt.srcid=t2.id
						AND t2.parentid=$to->xid
					ORDER BY
						tt.refseq
					";
				$db->open($q,2);
				if(!$db->move_next(2))
					$to->use_trefs=false;
			}

			// Iterate verses
			do{
				// Get verse data
				$verse_id=$db->field('id');
				$title=$db->field('title');
				$subtitle=$db->field('subtitle');
				$text=$db->field('textdata');

				// TRefs
				$trefs='';
				if($to->use_trefs!=false){
					$res=1;
					while($res==1 && $db->field('srcid',2)<$verse_id)
						$res=$db->move_next(2)?1:0;
					while($res==1 && $db->field('srcid',2)==$verse_id){
						$pos=count($trefs);
						$trefs.='@'.$db->field('mid',2).'~'.$db->field('title',2);
						$res=$db->move_next(2)?1:0;
					}
					if($trefs!='') $trefs=substr($trefs,1);
				}

				// Save verse
				$to->add_verse($title,$subtitle,$text,$trefs);
			}while($db->move_next());
			$to->make_verses();

			// Close tref cursor if nessesary
			if($to->use_trefs!=false) $db->close(2);

		}else{
			// Ordinary
			do{
				$mid=$db->field('mid');
				$title=$db->field('title');
				$subtitle=$db->field('subtitle');
				$to->add_list($mid,$title,$subtitle);
			}while($db->move_next());
			$to->make_list();
		}
	}
	$db->close();

	// Get page path
	$page->textpath=$to->pagepath;

	// Compile and make entire page content
	$page->content=$to->make_page();
}


// Main
function main()
{
	global $db;

	// Get parameter
	$id=request_string('id');
	if($id=='') $id='';

	// Display text
	display_text($id);
}


// Entry point
main();
$page->send();
?>