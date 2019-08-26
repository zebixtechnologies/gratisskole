<?php
//======================================================================
// Image functions
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
// (C) 1995-2006 Steffen Estrup, Niv&#65533;, Denmark
//======================================================================

//
// Include main application file
//
require 'main.php';


//
// Global error status
//
$image_error='';


//======================================================================

//
// Display B image
//
function display_image($id)
{
	global $cfg,$db,$page;

	// Setup page
	$page->tpl_main='main_lmenu_rmenu';

	// Lookup image
	$id=make_db_string($id);
	$sql="
		SELECT
			*
		FROM
			".$cfg['db']['prefix']."image
		WHERE
			id='$id'
		";
	$db->open($sql);
	if(!$db->move_next()) return;

	// Read template
	$tpl=$page->read_template('image/image');

	// Determine image dimensions
	$width=$height=$ewidth=$eheight=0;
	switch($db->field('otype')){
	case 'L1':
		$width=400;
		$height=300;
		$ewidth=1024;
		$eheight=768;
		break;
	case 'P1':
		$width=300;
		$height=400;
		$ewidth=768;
		$eheight=1024;
		break;
	case 'L2':
		$width=400;
		$height=400;
		$ewidth=1024;
		$eheight=1024;
		break;
	case 'L3':
		$width=400;
		$height=400;
		$ewidth=800;
		$eheight=800;
		break;
	default:
		break;
	}

	// Insert image data into template
	$tpl=str_replace('[IMAGE[ID]]',$db->field('id'),$tpl);
	$tpl=str_replace('[IMAGE[WIDTH]]',$width,$tpl);
	$tpl=str_replace('[IMAGE[HEIGHT]]',$height,$tpl);
	$tpl=str_replace('[IMAGE[TITLE]]',$db->field('title'),$tpl);

	// Right menu image
	$page->rmenu->add_start('[STR[IMAGE_DISPLAY_IMAGE]]');
	$page->rmenu->add_item(
		'[DIR[DATA]]data/image/'.$db->field('id').'b.jpg',
		$width.'x'.$height.' ('.trunc_int(filesize('data/image/'.$db->field('id').'b.jpg')).'kb)',
		'[STR[IMAGE_DISPLAY_IMAGE_ONLY]]');
	
	if($ewidth!=0){
		$page->rmenu->add_item(
			'[DIR[DATA]]data/image/'.$db->field('id').'a.jpg',
			$ewidth.'x'.$eheight.' ('.trunc_int(filesize('data/image/'.$db->field('id').'a.jpg')).'kb)',
			'[STR[IMAGE_DISPLAY_IMAGE_BIG]]');
	}
	$page->rmenu->add_end();

	$page->title=$db->field('title');
	$db->close();

	// Right menu folders
	$sql="
		SELECT
			f.*
		FROM
			".$cfg['db']['prefix']."img_folder f,
			".$cfg['db']['prefix']."img_folder_img i
		WHERE
			i.image_id='$id'
			AND
			i.folder_id=f.id
		ORDER BY
			i.seq,f.name
		";
	$db->open($sql);
	if($db->move_next()){
		$page->rmenu->add_start('[STR[IMAGE_FOLDERS]]');
		do{
			$page->rmenu->add_item(
				'image[EXT]?f='.$db->field('id'),
				$db->field('name'),
				'[STR[IMAGE_FOLDER_IMAGES]]: '.$db->field('name'));
		}while($db->move_next());
		$page->rmenu->add_end();
	}

	// Apply and close recordset
	$page->content=$tpl;
}


//================================================================================

//
//
//
function navigate_result($display_page)
{
	$query=session_string('image_list_query');
	$title=session_string('image_list_title');
	display_image_list($query,$display_page,$title);
}


//
// Display image list
//   pg=0 = alle billeder
//
function display_image_list($query='',$pg=0,$search_title='')
{
	global $cfg,$page,$db,$image_error;

	$tn=new thumbnails;

	// Chack for default query
	if($query==''){
		$query="SELECT * FROM ".$cfg['db']['prefix']."image ORDER BY id";
		$search_title='Alle billeder';
	}

	// Activate count mecanism
	if(strpos($query,'SQL_CALC_FOUND_ROWS')===false)
		$query='SELECT SQL_CALC_FOUND_ROWS '.substr(trim($query),7);

	// Save search
	$_SESSION['image_list_query']=$query;
	$_SESSION['image_list_title']=$search_title;

	// Initialize list vars
	$stop=false;
	$rows_per_page=4;
	$cells_per_row=5;
	$img_per_page=$cells_per_row*$rows_per_page;
	$cell_pos=0;
	$cur_row='';
	$img_count=0;
	$rows=array();
	$page_start=$pg==0?1:$pg;


	//echo '<p>'.$query.'<p>';

	// Lookup images in database
	$db->open_set($query,($page_start-1)*$img_per_page,$img_per_page);
	if(!$db->move_next()){
		$image_error=$query;
		return;
	}

	// Get entire rowcount
	$db->open('SELECT FOUND_ROWS() AS RowCount',2);
	$row_count=$db->field('RowCount',2);
	$db->close(2);

	// Setup page according to the number of found records
	$page_end=(int)($row_count/$img_per_page);
	if($row_count % $img_per_page) $page_end++;
	$page_count=$page_end;

	// Read templates
	$tpl=$page->read_template('image/image_list_wrap');
	$tpl_row=$page->read_template('image/image_list_row');
	$tpl_cell=$page->read_template('image/image_list_cell');


	// Iterate image rows
	do{
		// If new row required
		if(($cell_pos%$cells_per_row)==0 && $cell_pos!=0){
			$row=$tpl_row;
			$row=str_replace('[ROW[CELLS]]',$cur_row,$row);
			$rows[]=$row;
			$cur_row='';
			$cell_pos=0;
			if(count($rows)==$rows_per_page) $stop=true;
		}

		if(!$stop){
			// Save current row
			$cell=$tpl_cell;
			$cell=str_replace('[IMAGE[ID]]',$db->field('id'),$cell);
			$cell=str_replace('[IMAGE[TITLE]]',$db->field('id').' '.$db->field('title'),$cell);
			$cur_row.=$cell;

			// Increase cell counter
			$cell_pos++;
			$img_count++;
		}
	}while($db->move_next() && !$stop);
	$db->close();

	// Add needed rest cells
	while($cell_pos<=4){
		$cell=$tpl_cell;
		$cell=str_replace('[IMAGE[ID]]','0',$cell);
		$cell=str_replace('[IMAGE[TITLE]]','',$cell);
		$cur_row.=$cell;
		$cell_pos++;
	}
	//$rows[]=$cur_row;
	$rows[]=str_replace('[ROW[CELLS]]',$cur_row,$tpl_row);

	// Make navigation strings
	$first='';
	if($page_start>1){
		$first=$tn->make_link(
			'image[EXT]?c=nav&amp;id=1',
			'lfx','[STR[DISPLAY_FIRST_PAGE]]','1');
	}
	$prev='';
	if($page_start>1){
		$prev=$tn->make_link(
			'image[EXT]?c=nav&amp;id='.($page_start-1),
			'lf','[STR[DISPLAY_PREVIOUS_PAGE]]','2');
	}
	$next='';
	if($page_start<$page_count){
		$next=$tn->make_link(
			'image[EXT]?c=nav&amp;id='.($page_start+1),
			'rg','[STR[DISPLAY_NEXT_PAGE]]','3');
	}
	$last='';
	if($page_start<$page_count){
		$last=$tn->make_link(
			'image[EXT]?c=nav&amp;id='.$page_count,
			'rgx','[STR[DISPLAY_LAST_PAGE]]','4');
	}

	// Page info section
	$tpl=str_replace('[RESULT[DISPLAY_SEARCH]]',$search_title,$tpl);
	$tpl=str_replace('[RESULT[PAGE_START]]',$page_start,$tpl);
	$tpl=str_replace('[RESULT[PAGES]]',$page_count,$tpl);
	$tpl=str_replace('[RESULT[PAGES]]',sprintf(constant('STR_IMAGE_RESULT_PAGES'),$page_start,$page_count),$tpl);

	// Page navigation section
	$colspan=3;
	$tpl=str_replace('[RESULT[FIRST]]',$first,$tpl);
	apply_section($tpl,'FIRST',$first==''?1:0);
	if($first!='') $colspan++;
	
	$tpl=str_replace('[RESULT[PREVIOUS]]',$prev,$tpl);
	apply_section($tpl,'PREV',$prev==''?1:0);
	if($prev!='') $colspan++;

	$tpl=str_replace('[RESULT[NEXT]]',$next,$tpl);
	apply_section($tpl,'NEXT',$next==''?1:0);
	if($next!='') $colspan++;

	$tpl=str_replace('[RESULT[LAST]]',$last,$tpl);
	apply_section($tpl,'LAST',$last==''?1:0);
	if($last!='') $colspan++;

	apply_section($tpl,'DELIM',($first.$prev.$next.$last)==''?1:0);
	if($colspan>3) $colspan++;
	
	$tpl=str_replace('[RESULT[COLSPAN]]',8,$tpl);


	// Save last row and insert into main template

	$tpl=str_replace('[RESULT[ROW_START]]',(($page_start-1)*$img_per_page)+1,$tpl);
	$tpl=str_replace('[RESULT[ROW_END]]',(($page_start-1)*$img_per_page)+$img_count,$tpl);
	$tpl=str_replace('[RESULT[ROWS]]',$row_count,$tpl);
	$tpl=str_replace('[RESULT[PAGE_START]]',$page_start,$tpl);
	$tpl=str_replace('[RESULT[PAGES]]',$page_end,$tpl);

	// Add rows
	$tpl=str_replace('[LIST[ROWS]]',implode("\n",$rows),$tpl);

	// Apply to page
	$page->title='Billeder';
	$page->content=$tpl;
}


//
// Display images in the specified folder
//
function display_folder($f)
{
	global $cfg,$db;

	//
	// Lookup folder name
	//
	$sql="
		SELECT
			name
		FROM
			".$cfg['db']['prefix']."img_folder
		WHERE id=$f
		";
	$db->open($sql);
	if(!$db->move_next()) return;
	$title=$db->field('name');
	$db->close();

	//
	// Lookup images in folder
	//
	$sql="
		SELECT
			i.*
		FROM
			".$cfg['db']['prefix']."image i,
			".$cfg['db']['prefix']."img_folder_img f
		WHERE
			f.folder_id=$f
			AND
			f.image_id=i.id
		ORDER BY
			f.seq,i.id
	";
	display_image_list($sql,0,$title);
}


//================================================================================

//
// Make updates box
//
function make_updatelist()
{
	global $cfg,$db;

	//
	// Setup box
	//
	$row_count=6;
	$box=new box_datelist;
	$box->title="[STR[RECENT_UPDATES]]";

	//
	// Lookup songs
	//
	$sql="
		SELECT TOP $row_count
			*
		FROM
			".$cfg['db']['prefix']."image
		ORDER BY
			datecreated DESC
		";
	$db->open($sql);
	while($db->move_next()){
		//
		// Insert row into box
		//
		$box->add(
			make_date_time($db->field_date('datecreated'),false),
			'image[EXT]?id='.$db->field('id'),
			$db->field('title'),
			$db->field('title')
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
function make_image_nav()
{
	global $cfg,$db;

	//
	// Determine image count
	//
	$rcount='?';
	$sql="
		SELECT
			COUNT(*) AS RCount
		FROM
			".$cfg['db']['prefix']."image
		";
	$db->open($sql);
	if($db->move_next()) $rcount=$db->field('RCount');
	$db->close();

	//
	// Create box
	//
	$box=new box_nav;
	$box->title='[STR[IMAGES]]';

	$box->start_row('[STR[IMAGE_ARCHIVE_STATUS]]');
	$box->add_item('image[EXT]',$rcount);
	$box->end_row();

	$box->start_row('[STR[IMAGE_SERIES]]');
	$box->add_item('image[EXT]?f=2','[STR[IMAGE_SERIES_STD1]]');
	$box->end_row();

	$box->start_row('[STR[IMAGE_FOLDERS]]');
	$box->add_item('image[EXT]?f=3','[STR[IMAGE_CHRIST]]');
	$box->add_item('image[EXT]?f=4','[STR[IMAGE_FAMILY]]');
	$box->add_item('image[EXT]?f=5','[STR[IMAGE_NATURE]]');
	$box->add_item('image[EXT]?f=7','[STR[IMAGE_TEMPLES]]');
	$box->end_row();

	$box->start_row('[STR[IMAGE_CLIPART]]');
	$box->add_item('image[EXT]?f=6','[STR[IMAGE_PRIMARY]]');
	$box->end_row();

	return $box->get();
}


//
// Display image frontpage
//
function display_frontpage()
{
	make_frontpage(
		'[STR[IMAGE_ARCHIVE]]',					// Page title
		array(
			'[STR[IMAGE_FRONTPAGE_TEXT1]]',		// Page text 1
			'[STR[IMAGE_FRONTPAGE_TEXT2]]',		// Page text 2
			'[STR[IMAGE_FRONTPAGE_TEXT3]]'		// Page text 3
		),
		'image',								// Image name
		'',
		make_image_nav(),						// Navigation box
		make_updatelist()						// Latest updates
	);
}


//================================================================================

//
// Main function
//
function main()
{
	global $page,$image_error;

	//
	// Set stylesheet information
	//
	$page->add_css('image');

	//
	// Get parameters
	//
	$id=request_string('id');
	$folder=request_string('f');
	$cmd=request_string('c');

	//
	// Display list
	//
	if($cmd=='nav' && $id!='')
		navigate_result($id);
	elseif($id!='')
		display_image($id);
	elseif($folder!='')
		display_folder($folder);
	else
		display_frontpage();

	//
	// Error handling, or whatever...
	//
	if($image_error!='') $page->content.="[$image_error]";
}


//
// Entry point
//
main();
$page->send();
?>