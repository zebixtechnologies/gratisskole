<?php
//======================================================================
// Song functions
//----------------------------------------------------------------------
// (C) 1995-2019 Steffen Estrup / Rikard Warnsdorf
//----------------------------------------------------------------------
// Function list:
//		make_rmenu()
//		make_updatelist($update_entry_count=8,$trunc_len=30)
//		display_songbook($id)
//		display_songbooksong($id)
//		display_song($id)
//		display_subjectlist()
//		display_subject($id)
//		display_authorlist()
//		display_author($id)
//		make_playlist($id)
//		display_frontpage()
//		main()
//----------------------------------------------------------------------
// Issues:
//		make_rmenu() - database won't recognize 'ignore' column
//		display_songbooksong($id)
//			- move songtext layout to external file
//			- move soundfile link layout to external file
//======================================================================

//
// Include files
//
include_once "main.php";
require $page->tpl_string('song');


//
// Make right menu
//
function make_rmenu()
{
	global $cfg,$page,$db;

	//
	// Songbooks list - check strange that ignore issue :-(
	// Display songbooks not marked with 'ignore' flag
	//
	$page->rmenu->add_start('[STR[SONGBOOKS]]');
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."sng_songbook
		ORDER BY
			seq
		 ";
	$db->open($sql);
	while($db->move_next()){
		if($db->field('ignore')==0){
			$page->rmenu->add_item(
				'song[EXT]?c=sb&amp;id='.$db->field('id'),
				$db->field('title'),
				$db->field('description'));
		}
	}
	$db->close();
	$page->rmenu->add_end();

	// Lookup section (subjects/composers)
	$page->rmenu->add_start('[STR[LOOKUP]]');
	$page->rmenu->add_item('song[EXT]?c=subs','[STR[SUBJECTS]]');
	$page->rmenu->add_item('song[EXT]?c=as','[STR[WRITER_COMPOSER]]');
	$page->rmenu->add_end();

	// Info section (MIDI/MP3)
	$page->rmenu->add_start('[STR[INFORMATION]]');
	$page->rmenu->add_item('info[EXT]?id=midi','MIDI');
	$page->rmenu->add_item('info[EXT]?id=mp3','MP3');
	$page->rmenu->add_end();

	// Playlist admin (SB1-MIDI/SB1-MP3)
	if(is_local()){
		$page->rmenu->add_start('[STR[MAKE_PLAYLISTS]]');
		$page->rmenu->add_item('song[EXT]?c=mpl&amp;id=gmidi','[STR[SB1_MIDI]]');
		$page->rmenu->add_item('song[EXT]?c=mpl&amp;id=gmp3','[STR[SB1_MP3]]');
		$page->rmenu->add_end();
	}
}


//
// Display songs in the specified songbook
//
function display_songbook($id)
{
	global $cfg,$page,$db;

	// Paranoid
	if(!is_numeric($id)||strlen((string)$id)>2) return;

	// Get songbook data
	$ignore=-1;
	$sql="
		SELECT
		  *
		FROM
		  ".$cfg['db']['prefix']."sng_songbook
		WHERE
		  id=$id
		";
	$db->open($sql);
	if($db->move_next()){
		$page->title=$db->field('title');
		$subtitle   =$db->field('subtitle');
		$description=$db->field('description');
		$ignore     =$db->field('ignore');
	}
	$db->close();
	if($ignore) return;

	// Load templates
	$tpl     =$page->read_template($cfg['song']['tpl']['songbook']['list']);
	$tpl_item=$page->read_template($cfg['song']['tpl']['songbook']['row']);

	// Lookup songs
	$slist='';
	$sql="
		SELECT
		  sbs.id AS sbsid,
		  sbs.seq,
		  sbs.seq2,
		  s.*
		FROM
		  ".$cfg['db']['prefix']."sng_songbooksong sbs,
		  ".$cfg['db']['prefix']."sng_song s
		WHERE
		  sbs.sbid=$id
		  AND sbs.songid=s.id
		ORDER BY
		  sbs.seq,
		  sbs.seq2
		";
	$db->open($sql);
	while($db->move_next()){
		// Get item template
		$slist.=$tpl_item;

		// Insert SBS id
		$slist=str_replace(
			'[SONG[SBSID]]',
			$db->field('sbsid'),
			$slist);

		// Insert songs number in the songbook
		$slist=str_replace(
			'[SONG[NUMBER]]',
			zpad($db->field('seq').$db->field('seq2'),3),
			$slist);

		// Insert song name
		$slist=str_replace(
			'[SONG[NAME]]',
			$db->field('name'),
			$slist);
	}
	$db->close();

	// Insert list into songbooksong template
	$tpl=str_replace('[SONG[LIST]]',$slist,$tpl);

	// Apply
	$page->content=$tpl;
}


//
// Display an entry in a songbook
//
function display_songbooksong($id)
{
	global $cfg,$page,$db;

	//
	// Read song templates
	//
	$tpl             =$page->read_template($cfg['song']['tpl']['sbsong']['main']);
	$tpl_songbook    =$page->read_template($cfg['song']['tpl']['sbsong']['songbook']);
	$tpl_songbook_cur=$page->read_template($cfg['song']['tpl']['sbsong']['songbook_cur']);
	$tpl_subject     =$page->read_template($cfg['song']['tpl']['sbsong']['subject']);
	$tpl_scripture   =$page->read_template($cfg['song']['tpl']['sbsong']['scripture']);

	//
	// Lookup song
	//
	$sql="
		SELECT
		  sbs.*,
		  s.name AS sname,
		  s.other AS sother
		FROM
		  ".$cfg['db']['prefix']."sng_songbooksong sbs,
		  ".$cfg['db']['prefix']."sng_song s,
		  ".$cfg['db']['prefix']."sng_songbook sb
		WHERE
		  sbs.id=$id
		  AND sbs.songid=s.id
		  AND sbs.sbid=sb.id
		";
	$db->open($sql,1);
	if($db->move_next(1)){
		// Use song name as page title
		$page->title=$db->field('sname',1);

		// Insert song name
		$tpl=str_replace(
			'[SONG[TITLE]]',
			htmlentities($db->field('sname',1)),
			$tpl);

		// Songbooks
		$songbooks='';
		$songid=$db->field('songid',1);
		$sql="
			SELECT
			  sb.id,
			  sb.title,
			  sbs.id AS sbsid,
			  sbs.seq,
			  sbs.seq2
			FROM
			  ".$cfg['db']['prefix']."sng_songbooksong sbs,
			  ".$cfg['db']['prefix']."sng_songbook sb
			WHERE
			  sbs.songid=$songid
			  AND sbs.sbid=sb.id
			  AND sb.ignore=0
			ORDER BY
			  sb.seq
			";
		$db->open($sql,2);
		while($db->move_next(2)){
			// Extract title and number in songbook
			$sb_title=$db->field('title',2);
			$seq=$db->field('seq',2).$db->field('seq2',2);

			// Set delimiter and determine songbook template
			if($songbooks!='') $songbooks.=' / ';
			$songbooks.=
				($db->field('id',2)==$db->field('sbid',1))?
					$tpl_songbook_cur:
					$tpl_songbook;

			// Insert songbook id
			$songbooks=str_replace(
				'[SONGBOOK[SBID]]',
				$db->field('id',2),
				$songbooks);

			// Insert songbook title
			$songbooks=str_replace(
				'[SONGBOOK[TITLE]]',
				$sb_title,
				$songbooks);

			// Insert SBS id
			$songbooks=str_replace(
				'[SONGBOOK[SBSID]]',
				$db->field('sbsid',2),
				$songbooks);

			// Insert songs number in songbook
			$songbooks=str_replace(
				'[SONGBOOK[SEQ]]',
				$seq,
				$songbooks);
		}
		$db->close(2);

		// Insert songbooklist into template
		$tpl=str_replace('[SONG[SONGBOOKS]]',$songbooks,$tpl);

		// Instruction
		$instruction=trim
			($db->field('instruction',1).' | '.
			 $db->field('speed',1).' | '.
			 $db->field('key',1));
		if($instruction!='')
			$tpl=str_replace('[SONG[INSTRUCTION]]',$instruction,$tpl);
		apply_section($tpl,'INSTRUCTION',$instruction==''?1:0);

		// Authors - initialize role arrays
		$roles=array('COMPM','ARRM','REFR','COMPT',
			'ARRT','REARR','BSARR','TRANS');
		$authors=array('COMPM'=>'','ARRM'=>'','REFR'=>'','COMPT'=>'',
			'ARRT'=>'','REARR'=>'','BSARR'=>'','TRANS'=>'');

		// Lookup authors for this song
		$songid=$db->field('songid',1);
		$sql="
			SELECT
			  r.id AS rid,
			  a.*
			FROM
			  ".$cfg['db']['prefix']."sng_songauthor sa,
			  ".$cfg['db']['prefix']."sng_author a,
			  ".$cfg['db']['prefix']."sng_role r
			WHERE
			  sa.songid=$songid
			  AND sa.authid=a.id
			  AND sa.roleid=r.id
			ORDER BY
			  r.seq
			";
		$db->open($sql,2);
		if($db->move_next(2)){
			do{
				// Make author name
				$a=$db->field('surname',2);
				if($db->field('name',2)!='#') $a=$db->field('name',2).' '.$a;
				$a='<a class="rlink" href="song[EXT]?c=a&amp;id='.
					$db->field('id',2).'">'.$a.'</a>';

				// Add birth/death years if present
				if($db->field('birthyear',2)!='' || $db->field('deathyear',2)!=''){
					$a.=', ';
					if($db->field('birthyear',2)!='')
						$a.=$db->field('birthyear',2);
					if($db->field('deathyear',2)!=''){
						if($db->field('birthyear',2)!='') $a.='-';
						$a.=$db->field('deathyear',2);
					}
				}
				
				// Add to authors array
				if($authors[$db->field('rid',2)]!='')
					$authors[$db->field('rid',2)].='; ';
				$authors[$db->field('rid',2)].=$a;

			// Fetch nest author
			}while($db->move_next(2));
		}
		$db->close(2);

		// Insert authors into template
		for($i=0;$i<count($roles);$i++){
			// Insert into the correct 'role' section
			$tpl=str_replace(
				'[SONG['.$roles[$i].']]',
				$authors[$roles[$i]],
				$tpl);
			apply_section($tpl,$roles[$i],$authors[$roles[$i]]==''?1:0);
		}

		// Lookup subjects
		$subjects='';
		$songid=$db->field('songid',1);
		$sql="
			SELECT
			  s.*
			FROM
			  ".$cfg['db']['prefix']."sng_songsubjectlink ss,
			  ".$cfg['db']['prefix']."sng_songsubject s
			WHERE
			  ss.songid=$songid
			  AND ss.subjectid=s.id
			ORDER BY
			  s.name
			";
		$db->open($sql,2);
		if($db->move_next(2)){
			do{
				// Add subject separator if needed and get subject template
				if($subjects!='') $subjects.=' / ';
				$subjects.=$tpl_subject;

				// Insert subject values into template
				$subjects=str_replace('[SUBJECT[ID]]',$db->field('id',2),$subjects);
				$subjects=str_replace('[SUBJECT[NAME]]',$db->field('name',2),$subjects);

			// Fetch next subject
			}while($db->move_next(2));
		}
		$db->close(2);
		if($subjects!='') $tpl=str_replace('[SONG[SUBJECTS]]',$subjects,$tpl);
		apply_section($tpl,'SUBJECTS',$subjects==''?1:0);

		// Lookup scripture references
		$refs='';
		$s_id=$db->field('id',1);
		$sql="
			SELECT
				*
			FROM
				".$cfg['db']['prefix']."sng_scriptref
			WHERE
				sbsid=$s_id
			ORDER BY
				seq
			 ";
		$db->open($sql,2);
		while($db->move_next(2)){
			do{
				// Add reference separator if needed and get reference template
				if($refs!='') $refs.='; ';
				$refs.=$tpl_scripture;

				// Insert references into template
				$refs=str_replace('[SCRIPTURE[REFID]]',$db->field('refid',2),$refs);
				$refs=str_replace('[SCRIPTURE[REFTEXT]]',htmlentities($db->field('reftext',2)),$refs);

			// Fetch next reference
			}while($db->move_next(2));
		}
		$db->close(2);

		// Insert referencelist into template if any is present
		if($refs!='') $tpl=str_replace('[SONG[SCRIPTURE_REFS]]',$refs,$tpl);
		apply_section($tpl,'SCRIPTURE_REFS',$refs==''?1:0);

		// Lookup sound files
		$sound='';
		$s_id=$db->field('id',1);
		$sql="
			SELECT
			  f.id AS fid,
			  t.*
			FROM
			  ".$cfg['db']['prefix']."sng_sbsongfile s,
			  ".$cfg['db']['prefix']."sng_soundfile f,
			  ".$cfg['db']['prefix']."sng_soundtype t
			WHERE
			  s.sbsid=$s_id
			  AND s.fileid=f.id
			  AND f.type=t.id
			ORDER BY
			  t.seq
			";
		$db->open($sql,2);
		if($db->move_next(2)){
			do{
				// Add separator if needed
				if($sound!='') $sound.=' - ';

				// Add link to sound file
				$sound.='<a class="rlink" href="[DIR[DATA]]data/song/media/'.zpad($db->field('fid',2),4).'.'.$db->field('ext',2).'" title="'.$db->field('description',2).'">'.$db->field('id',2).'</a>';

			// Fetch next sound file
			}while($db->move_next(2));
		}
		$db->close(2);

		// Insert soundfile list into template if any is present
		if($sound!='') $tpl=str_replace('[SONG[SOUND_FILES]]',$sound,$tpl);
		apply_section($tpl,'SOUND_FILES',$sound==''?1:0);

		// Lookup song text, if a songtext link is present
		$text='';
		if($db->field('textid',1)>0){
			$s_id=$db->field('textid',1);
			$sql="
				SELECT
					*
				FROM
				  ".$cfg['db']['prefix']."texts
				WHERE
				  mid=$s_id
				";
			$db->open($sql,2);
			if($db->move_next(2)) $text=$db->field('textdata',2);
			$db->close(2);
		}

		// If text was found, adjust layout
		if($text!=''){
			$text=str_replace('<p>','<div style="padding-top:8px">',$text);
			$text=str_replace('</p>','</div>',$text);
		}
		if($text!='') $tpl=str_replace('[SONG[TEXT]]',$text,$tpl);
		apply_section($tpl,'SONG_TEXT',$text==''?1:0);
	}
	// Ups, no songbookentry found
	else $tpl='';

	// Some debug - display songid on local clients
	if(is_local())
		$tpl.='<div style="padding:12px;color:#999999">songid:'.$db->field('songid').'</div>';

	// Close main recordset
	$db->close(1);

	// Apply template
	$page->content=$tpl;
}


//
// Display songbooks that includes the specified song
// If only one songbook has this song, then go to that song entry
//
function display_song($id)
{
	global $cfg,$page,$db;

	// Compile sql
	$sql="
		SELECT
			sbs.id AS id,
			sb.title AS title,
			sbs.seq,sbs.seq2,
			s.name AS name
		FROM
			".$cfg['db']['prefix']."sng_song s,
			".$cfg['db']['prefix']."sng_songbooksong sbs,
			".$cfg['db']['prefix']."sng_songbook sb
		WHERE
			s.id=$id
			AND s.id=sbs.songid
			AND sbs.sbid=sb.id
			AND sb.ignore=0
		ORDER BY
			sb.seq,
			sbs.seq
		";
	// Lookup the song representations in songbooks,
	// and save result in array
	$songs=array();
	$count=0;
	$db->open($sql);
	if($db->move_next()){
		$page->title=$db->field('name');
		do{
			$songs[]=array(
				$db->field('id'),
				$db->field('title'),
				$db->field('seq'),
				$db->field('seq2'));
			$count++;
		}while($db->move_next());
	}
	$db->close();

	// Song not found, return nothing
	if($count==0) return;

	// Song appears in only one songbook, display that specific song
	if($count==1){
		display_songbooksong($songs[0][0]);
		return;
	}

	// Read templates
	$tpl=$page->read_template('song/song');
	$tpl_item=$page->read_template('song/song_songitem');

	// Make songbook list
	$slist='';
	for($i=0;$i<count($songs);$i++){
		// Add song item template
		$slist.=$tpl_item;

		// Insert song values into template
		$slist=str_replace('[SONG[SBSID]]',   $songs[$i][0],$slist);
		$slist=str_replace('[SONG[SONGBOOK]]',$songs[$i][1],$slist);
		$slist=str_replace('[SONG[SEQ]]',     $songs[$i][2].$songs[$i][3],$slist);
	}

	// Insert list into template
	$tpl=str_replace('[SONG[SONGBOOKS]]',$slist,$tpl);

	// Apply template
	$page->content=$tpl;
}


//
// List all song subjects
//
function display_subjectlist()
{
	global $cfg,$page,$db;

	// Load templates
	$tpl=$page->read_template('song/subjectlist');
	$tpl_item=$page->read_template('song/subjectlist_item');
	$tpl_litem=$page->read_template('song/subjectlist_litem');

	// Lookup subjects
	$slist='';
	$count=0;
	$last='';
	$sql="
		SELECT *
		FROM
			".$cfg['db']['prefix']."sng_songsubject
		WHERE
			id>1
		ORDER BY
			name
		";
	$db->open($sql);
	while($db->move_next()){
		$name=$db->field('name');

		//
		// Add Letter header
		//
		if(strtoupper($name[0])!=$last){
			$last=strtoupper($name[0]);
			$slist.=$tpl_litem;
			$slist=str_replace('[ROW[LETTER]]',$last,$slist);
			$count=0;
		}

		// Add subject template
		$slist.=$tpl_item;

		// Insert subject values into template
		$slist=str_replace('[SUBJECT[ID]]',  $db->field('id'),$slist);
		$slist=str_replace('[SUBJECT[NAME]]',$name,$slist);
		$slist=str_replace('[ROW[BGCOL]]',   $count%2?'#ffffff':'#eeeeee',$slist);
		$count++;

	}
	$db->close();

	// Insert list into template
	$tpl=str_replace('[SUBJECT[LIST]]',$slist,$tpl);

	// Apply template
	$page->title='[STR[SUBJECTS]]';
	$page->content=$tpl;
}


//
// Display information on the specified subject
//
function display_subject($id)
{
	global $cfg,$page,$db;

	// Get song information
	$has_song=42;	// You know why :-)
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."sng_songsubject
		WHERE
			id=$id
		";
	$db->open($sql);
	if($db->move_next()){
		$page->title=$db->field('name');
		$has_song=$db->field('hassong');
	}
	$db->close();
	if($has_song==42) return;

	// Read templates
	$tpl=$page->read_template('song/song_subject');
	$tpl_song_item=$page->read_template('song/song_subject_songitem');
	$tpl_ref_item=$page->read_template('song/song_subject_refitem');

	// Songlist
	$songs='';
	if($has_song!=0){
		$sql="
			SELECT
			  s.*
			FROM
			  ".$cfg['db']['prefix']."sng_songsubjectlink ss,
			  ".$cfg['db']['prefix']."sng_song s
			WHERE
			  ss.subjectid=$id
			  AND ss.songid=s.id
			ORDER BY
			  s.name
			";
		$db->open($sql);
		if($db->move_next()){
			do{
				// Add item template
				$songs.=$tpl_song_item;

				// Insert song values into template
				$songs=str_replace('[SONGITEM[ID]]]',$db->field('id'),$songs);
				$songs=str_replace('[SONGITEM[NAME]]',$db->field('name'),$songs);

			// Fetch next song
			}while($db->move_next());
		}
		$db->close();
	}
	$tpl=str_replace('[SUBJECT[SONGLIST]]',$songs,$tpl);
	apply_section($tpl,'SONGLIST',$songs==''?1:0);

	// Lookup references
	$refs='';
	$sql="
		SELECT
		  s.*
		FROM
		  ".$cfg['db']['prefix']."sng_songsubjectxref sx,
		  ".$cfg['db']['prefix']."sng_songsubject s
		WHERE
		  sx.subjectid=$id
		  AND sx.subjectxid=s.id
		ORDER BY
		  s.name
		";
	$db->open($sql);
	if($db->move_next()){
		do{
			// Add reference template
			$refs.=$tpl_ref_item;

			// Insert subject references
			$refs=str_replace('[REFITEM[ID]]',$db->field('id'),$refs);
			$refs=str_replace('[REFITEM[NAME]]',$db->field('name'),$refs);

		// Fetch next subject reference
		}while($db->move_next());
	}
	$db->close();

	// Insert reference list into template if any is present
	$tpl=str_replace('[SUBJECT[REFLIST]]',$refs,$tpl);
	apply_section($tpl,'REFLIST',$refs==''?1:0);

	// Apply template
	$page->content=$tpl;
}


//
// Display list of authors - writers and composers
//
function display_authorlist()
{
	global $cfg,$page,$db;

	//
	// Load templates
	//
	$tpl      =$page->read_template('song/song_author_list');
	$tpl_item =$page->read_template('song/song_author_list_item');
	$tpl_litem=$page->read_template('song/song_author_list_litem');

	//
	// Lookup authors
	//
	$alist='';
	$last='';
	$count=0;
	$sql="
		SELECT DISTINCT
			a.*
		FROM
			".$cfg['db']['prefix']."sng_author a,
			".$cfg['db']['prefix']."sng_sbsongauthor sbsa,
			".$cfg['db']['prefix']."sng_songbooksong sbs
		WHERE
			a.id=sbsa.authid
			AND sbsa.sbsid=sbs.id
			AND sbs.sbid IN (1,2,3)
		ORDER BY
			a.surname,
			a.name
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Make name
		//
		$aname=$db->field('surname');
		if($db->field('name')!='#') $aname.=', '.$db->field('name');
	
		//
		// Compile birth/death dates if present
		//
		$adates=$db->field('birthyear');
		if($db->field('deathyear')!=''){
			if($adates!='') $adates.='-';
			$adates.=$db->field('deathyear');
		}

		//
		// Add item template
		//
		if(strtoupper($aname[0])!=$last){
			$last=strtoupper($aname[0]);
			$alist.=$tpl_litem;
			$alist=str_replace('[ROW[LETTER]]',$last,$alist);
			$count=0;
		}

		//
		// Insert author values
		//
		$alist.=$tpl_item;
		$alist=str_replace('[AUTHOR[ID]]',   $db->field('id'),            $alist);
		$alist=str_replace('[AUTHOR[NAME]]', $aname,                      $alist);
		$alist=str_replace('[AUTHOR[DATES]]',$adates,                     $alist);
		$alist=str_replace('[ROW[BGCOL]]',   $count%2?'#ffffff':'#eeeeee',$alist);

		//
		// Increment counter - only used for tpl switching on rows
		//
		$count++;
	}
	$db->close();

	//
	// Insert list into template
	//
	$tpl=str_replace('[AUTHOR[LIST]]',$alist,$tpl);

	//
	// Apply to page
	//
	$page->title='[STR[WRITERS_COMPOSERS]]';
	$page->content=$tpl;
}


//
// Display information on the specified author
//
function display_author($id)
{
	global $cfg,$page,$db;

	//
	// Paranoid checks
	//
	if(!is_numeric($id) || strlen((string)$id)>3) return;

	//
	// Lookup author
	//
	$surname='';
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."sng_author
		WHERE
			id=$id
		 ";
	$db->open($sql);
	if($db->move_next()){
		do{
			$surname    =$db->field('surname');
			$fname      =$db->field('name');
			$birthyear  =$db->field('birthyear');
			$deathyear  =$db->field('deathyear');
			$description=$db->field('description');
		}while($db->move_next());
	}
	$db->close();
	if($surname=='') return;

	//
	// Load templates
	//
	$tpl=$page->read_template('song/song_author');
	$tpl_song_item=$page->read_template('song/song_author_songlist_item');

	//
	// Lookup songs attributed to this author
	//
	$slist='';
	$sql="
		SELECT
			s.id,
			s.name,
			r.seq,
			r.work
		FROM
			".$cfg['db']['prefix']."sng_song s,
			".$cfg['db']['prefix']."sng_songauthor sa,
			".$cfg['db']['prefix']."sng_role r
		WHERE
			sa.authid=$id
			AND
			sa.songid=s.id
			AND
			sa.roleid=r.id
		GROUP BY
			s.id,
			s.name,
			r.seq,
			r.work
		ORDER BY
			s.name,
			r.seq
		";
	$db->open($sql);
	if($db->move_next()){
		do{
			$slist.=$tpl_song_item;
			$slist=str_replace('[SONG[ID]]',  $db->field('id'),$slist);
			$slist=str_replace('[SONG[NAME]]',$db->field('name'),$slist);
			$slist=str_replace('[SONG[ROLE]]',$db->field('work'),$slist);
		}while($db->move_next());
	}
	$db->close();

	//
	// Adjust author name
	//
	if($fname=='#') $fname='';
	$name=$surname;
	if($fname!='') $name.=', '.$fname;

	//
	// Insert author values
	//
	$tpl=str_replace('[AUTHOR[SURNAME]]',$surname,$tpl);

	if($fname!='') $tpl=str_replace('[AUTHOR[FNAME]]',$fname,$tpl);
	apply_section($tpl,'FNAME',$fname==''?1:0);

	if($birthyear!='') $tpl=str_replace('[AUTHOR[BIRTHYEAR]]',$birthyear,$tpl);
	apply_section($tpl,'BIRTHYEAR',$birthyear==''?1:0);

	if($deathyear!='') $tpl=str_replace('[AUTHOR[DEATHYEAR]]',$deathyear,$tpl);
	apply_section($tpl,'DEATHYEAR',$deathyear==''?1:0);

	if($description!='') $tpl=str_replace('[AUTHOR[DESCRIPTION]]',$description,$tpl);
	apply_section($tpl,'DESCRIPTION',$description==''?1:0);

	//
	// Insert songlist into template
	//
	$tpl=str_replace('[AUTHOR[SONGLIST]]',$slist,$tpl);

	//
	// Apply page
	//
	$page->title=$name;
	$page->content=$tpl;
}


//
// Create playlist files in m3u format
//
function make_playlist($id)
{
	global $cfg,$page,$db;

	//
	// Paranoid check
	//
	if(!is_local()) return;

	//
	// Prepare list specific vars
	//
	$slist=array();
	$media_root=$cfg['site']['data'].'data/song/media/';
	$ftype=($id=='gmp3')?'mp3':'midi';
	$fext=($id=='gmp3')?'mp3':'mid';
	$count=0;

	//
	// Lookup files
	//
	$sql="
		SELECT
			sf.id AS id
		FROM
			".$cfg['db']['prefix']."sng_songbooksong sbs,
			".$cfg['db']['prefix']."sng_sbsongfile sbsf,
			".$cfg['db']['prefix']."sng_soundfile sf
		WHERE
			sbs.sbid=1
			AND sbs.id=sbsf.sbsid
			AND sbsf.fileid=sf.id
			AND sf.type IN ".($id=='gmp3'?"('mp3c','mp3d')":"('midi1','midi2')")."
		ORDER BY
			sbs.seq,
			sbs.seq2
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Save references i array
		//
		$slist[]=$db->field('id');
		$count++;
	}
	$db->close();

	//
	// Open playlist-file and save references
	//
	$fp=fopen(
		$_SERVER['DOCUMENT_ROOT'].
			"/jkk/data/song/playlist_01_$ftype.m3u",
		'wt');
	for($i=0;$i<count($slist);$i++)
		fwrite($fp,$media_root.zpad($slist[$i],4).".$fext\n");
	fclose($fp);

	//
	// Display success message width songfile count
	//
	$page->content='OK-'.$count;
}


//
// Make updates box
//
function make_song_updatelist()
{
	global $cfg,$db;

	//
	// Setup box
	//
	$row_count=$cfg['song']['updatelist']['count'];
	$box=new box_datelist;
	$box->title="[STR[RECENT_UPDATES]]";

	//
	// Lookup songs
	//
	$sql="
		SELECT TOP $row_count
			u.*,
			s.name,
			s.id AS sid
		FROM
			".$cfg['db']['prefix']."sng_update u,
			".$cfg['db']['prefix']."sng_song s
		WHERE
			u.songid=s.id
		ORDER BY
			u.datecreated DESC
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Insert row into box
		//
		$box->add(
			make_date_time($db->field_date('datecreated'),false),
			'song[EXT]?c=s&amp;id='.$db->field('sid'),
			$db->field('name'),
			$db->field('name')
		);
	}
	$db->close();

	//
	// Compile and return box
	//
	return $box->get();
}


//
// Make navigation box
//
function make_song_nav()
{
	$box=new box_nav;
	$box->title='[STR[SONGS]]';

	$box->start_row('[STR[SONGBOOKS]]');
	for($i=1;$i<4;$i++)
		$box->add_item('song[EXT]?c=sb&amp;id='.$i,"[STR[SONGBOOK_$i]]");
	$box->end_row();

	$box->start_row('[STR[SUBJECTS]]');
	$box->add_item('song[EXT]?c=subs','[STR[SUBJECT_LIST]]');
	$box->end_row();

	$box->start_row('[STR[PERSONS]]');
	$box->add_item('song[EXT]?c=as','[STR[WRITER_COMPOSER_MORE]]');

	$box->end_row();
	$box->start_row('[STR[PLAYLISTS]]');
	$box->add_item('data/song/playlist_01_mp3.m3u', '[STR[SONGBOOK_1]] / MP3');
	$box->add_item('data/song/playlist_01_midi.m3u','[STR[SONGBOOK_1]] / MIDI');

	$box->end_row();
	return $box->get();
}


//
// Display song frontpage
//
function display_frontpage()
{
	make_frontpage(
		'[STR[SONGS_MELODIES]]',				// Page title
		array(
			'[STR[SONG_FRONTPAGE_TEXT1]]',		// Page text 1
			'[STR[SONG_FRONTPAGE_TEXT2]]',		// Page text 2
			'[STR[SONG_FRONTPAGE_TEXT3]]'		// Page text 3
		),
		'choir',								// Image name
		'',
		make_song_nav(),						// Navigation box
		make_song_updatelist()					// Latest updates
	);
}


//
// Main
//
function main()
{
	global $page;

	//
	// Setup page
	//
	$page->tpl_main='main_lmenu_rmenu';
	$page->add_css('song');

	//
	// Get parameters
	//
	$cmd=request_string('c');
	$id=request_string('id');

	//
	// Adjust command if needed
	//
	if($cmd=='' && $id!='' && is_numeric($id)) $cmd='s';
	if($cmd=='' && $id=='') $cmd='front';

	//
	// Act on command
	//
	switch($cmd){
	case 's':    display_song($id);         break;
	case 'sbs':  display_songbooksong($id); break;
	case 'sub':  display_subject($id);      break;
	case 'subs': display_subjectlist();     break;
	case 'sb':   display_songbook($id);     break;
	case 'as':   display_authorlist();      break;
	case 'a':    display_author($id);       break;
	case 'mpl':  make_playlist($id);        break;
	default:     display_frontpage();		break;
	}

	//
	// Make right menu
	//
	make_rmenu();
}


//
// Entry point
//
main();
$page->send();
?>
