<?php

//
// List top 20 blog entries
//
function blog_entry_list()
{
	global $page,$db;

	//
	// Load templates
	//
	$tpl=$page->read_template('blog/blog_entry_list');
	$item=$page->read_template('blog/blog_entry_list_item');

	//
	// Lookup blog entries
	//
	$elist='';
	$sql="
		SELECT
			*
		FROM
			jkk_blog_entry
		WHERE
			id>1
		ORDER BY
			datecreated DESC
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Imsert into template
		//
		$elist.=$item;
		$elist=str_replace('[BITEM[ID]]',   $db->field('id'),         $elist);
		$elist=str_replace('[BITEM[DATE]] ',$db->field('datecreated'),$elist);
		$elist=str_replace('[BITEM[TITLE]]',$db->field('title'),      $elist);
	}
	$db->close();

	//
	// Insert list into template
	//
	$tpl=str_replace('[BLOG[LIST]]',$elist,$tpl);

	//
	// Apply
	//
	$page->title='BLOG liste';
	$page->content=$tpl;
}


//
// Edit blog entry
//
function blog_entry_edit($type,$id)
{
	global $page,$db;

	//
	// If update mode
	//
	if($type=='upd'){
		//
		// Read blog entry columns
		//
		$parentid=0;
		$db->open('SELECT * FROM jkk_blog_entry WHERE id='.$id);
		if($db->move_next()){
			//
			// Sabe database values
			//
			$parentid=$db->field('parentid');
			$datecreated=$db->field('datecreated');
			$userid=$db->field('userid');
			$title=$db->field('title');
			$textdata=$db->field('textdata');
		}
		$db->close();

		//
		// Return if entry not found
		//
		if($parentid==0) return;

	//
	// Insertmode
	//
	}else{
		//
		// Set default value
		//
		$parentid=1;
		$datecreated=date('Y-m-d H:i:s');
		$userid=2;
		$title='';
		$textdata='';
	}

	//
	// Read template
	//
	$tpl=$page->read_template('blog/blog_entry_form');

	//
	// Insert entry values
	//
	$tpl=str_replace('[BITEM[ID]]',$id,$tpl);
	$tpl=str_replace('[BITEM[PARENTID]]',$parentid,$tpl);
	$tpl=str_replace('[BITEM[DATECREATED]]',$datecreated,$tpl);
	$tpl=str_replace('[BITEM[USERID]]',$userid,$tpl);
	$tpl=str_replace('[BITEM[TITLE]]',$title,$tpl);
	$tpl=str_replace('[BITEM[TEXTDATA]]',$textdata,$tpl);
	$tpl=str_replace('[BLOG[SUBMIT]]',($type=='upd'?'Opdater':'Opret'),$tpl);
	$tpl=str_replace('[BLOG[CMD]]',($type=='upd'?'blogupdate':'blogadd'),$tpl);

	//
	// Initialize subject lists
	//
	$csub='';
	$asub='';

	//
	// If update mode
	//
	if($type=='upd'){
		//
		// Current subjects
		//
		$csub='';
		$sql="
			SELECT
				s.*
			FROM
				jkk_blog_subject s,
				jkk_blog_entry_subject es
			WHERE
				es.entryid=$id
				AND
				es.subjectid=s.id
			ORDER BY
				s.name
			";
		$db->open($sql);
		while($db->move_next()){
			$csub.='<a href="admin[EXT]?c=blogeditremsub&amp;id='.$id.'&amp;s='.$db->field('id').'">'.$db->field('name').'</a><br />';
		}
		$db->close();

		//
		// Available subjects
		//
		$asub='';
		$sql=
			'SELECT * '.
			'FROM jkk_blog_subject '.
			'WHERE id NOT IN (SELECT DISTINCT subjectid FROM jkk_blog_entry_subject WHERE entryid='.$id.' ) '.
			'ORDER BY name ';
		$db->open($sql);
		while($db->move_next()){
			$asub.='<a href="admin[EXT]?c=blogeditaddsub&amp;id='.$id.'&amp;s='.$db->field('id').'">'.$db->field('name').'</a><br />';
		}
		$db->close();
	}

	//
	// Insert subject lists
	//
	$tpl=str_replace('[BLOG[CURRENT_SUBJECTS]]',$csub,$tpl);
	$tpl=str_replace('[BLOG[AVAIL_SUBJECTS]]',$asub,$tpl);

	//
	// Apply
	//
	$page->title='Ret BLOG';
	$page->content=$tpl;
}


//
// Update blog entry record
//
function blog_entry_update($eid)
{
	global $db,$page;

	//
	// Get parameters
	//
	$id=request_string('id');
	$parentid=request_int('parent');
	$datecreated=request_string('date');
	$title=request_string('title');
	$text=request_string('text');
	$dateupdate=request_string('dateupdate');

	//
	// Validate parameters
	//
	if($id!=$eid) return;
	if($datecreated=='') return;
	if($title=='') return;
	if($text=='') return;

	//
	// Prepare values
	//
	$datecreated=normalize_date($datecreated);
	if($dateupdate=='1') $datecreated=date('YmdHis');

	//
	// Update dabase
	//
	$sql="
		UPDATE
			jkk_blog_entry
		SET
			parentid=$parentid,
			datecreated=$datecreated,
			title='$title',
			textdata='$text'
		WHERE
			id=$eid
		";

	$db->execute($sql);

	//
	// Goto blog edit page
	//
	blog_entry_edit('upd',$eid);
}


//
// Add new blog entry
//
function blog_entry_add()
{
	global $db,$page;

	//
	// Get parameters
	//
	$parentid=request_int('parent');
	$datecreated=request_string('date');
	$title=request_string('title');
	$textdata=request_string('text');
	$dateupdate=request_string('dateupdate');

	//
	// Validate parameters
	//
	if($title=='') return;
	if($textdata=='') return;

	//
	// Prepare values
	//
	if($datecreated==''||$dateupdate=='1')
		$datecreated=date('YmdHis');
	$datecreated=normalize_date($datecreated);
	$userid=2;

	//
	// Insert into database
	//
	$sql="
		INSERT INTO
			jkk_blog_entry
		(
			parentid,
			datecreated,
			userid,
			title,
			textdata
		)
		VALUES (
			$parentid,
			$datecreated,
			$userid,
			'$title',
			'$textdata')
		";

	//
	// Update database
	//
	$db->execute($sql);

	//
	// Get new entry id
	//
	$sql="
		SELECT
			MAX(id) AS mid
		FROM
			jkk_blog_entry
		";
	$db->open($sql);
	$eid=$db->move_next()?$db->field('mid'):0;
	$db->close();

	//
	// Goto blog edit page
	//
	blog_entry_edit('upd',$eid);
}


//
// Remove blog entry
//
function blog_entry_remove($id)
{
	global $db;

	//
	// Remove subjects
	//
	$db->execute('DELETE FROM jkk_blog_entry_subject WHERE entryid='.$id);

	//
	// Remove entry
	//
	$db->execute('DELETE FROM jkk_blog_entry WHERE id='.$id);

	//
	// Display blog list
	//
	blog_entry_list();
}


//
// Add or Remove subject on blog entry
//
function blog_entry_subject($type,$id,$sub)
{
	global $db;

	//
	// Make changes
	//
	if($type=='rem')
		$db->execute('DELETE FROM jkk_blog_entry_subject WHERE entryid='.$id.' AND subjectid='.$sub);
	else
		$db->execute('INSERT INTO jkk_blog_entry_subject VALUES('.$id.','.$sub.')');

	//
	// Goto blog edit page
	//
	blog_entry_edit('upd',$id);
}


?>
