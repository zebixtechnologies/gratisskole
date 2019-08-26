<?php
//----------------------------------------------------------------------
// Search object
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

class search_object
{
// Work
var $sql;
var $display_search='';
var $max_subtitle_len=240;
var $page_start=0;
var $page_rows=10;
var $row_count=0;
var $result;
var $tn;

// Form values
var $form_title='';
var $form_author='';
var $form_text='';
var $form_scr=0;
var $form_books=0;
var $form_pubs=0;
var $form_song=0;
var $form_lex=0;
var $form_da=0;
var $form_en=0;
var $form_se=0;
var $form_no=0;
var $form_de=0;

// External references
var $db;
var $page;


// Constructor
function search_object()
{
	$this->result=array();
	$this->tn=new thumbnails;
}

// Remove backslashes
function remove_backslash($text)
{
	return str_replace('\"','"',$text);
}

// Save vars to session
function save_session()
{
	$_SESSION['jkk_search_sql']=$this->sql;
	$_SESSION['jkk_search_rowcount']=$this->row_count;
	$_SESSION['jkk_search_display']=$this->display_search;

	// Save form values
	$_SESSION['jkk_search_form_title']=$this->form_title;
	$_SESSION['jkk_search_form_author']=$this->form_author;
	$_SESSION['jkk_search_form_text']=$this->form_text;
	$_SESSION['jkk_search_form_scr']=$this->form_scr;
	$_SESSION['jkk_search_form_books']=$this->form_books;
	$_SESSION['jkk_search_form_pubs']=$this->form_pubs;
	$_SESSION['jkk_search_form_song']=$this->form_song;
	$_SESSION['jkk_search_form_lex']=$this->form_lex;
	$_SESSION['jkk_search_form_da']=$this->form_da;
	$_SESSION['jkk_search_form_en']=$this->form_en;
	$_SESSION['jkk_search_form_se']=$this->form_se;
	$_SESSION['jkk_search_form_no']=$this->form_no;
	$_SESSION['jkk_search_form_de']=$this->form_de;
}

// Get session vars
function get_session()
{
	if($this->sql=='') $this->sql=session_string('jkk_search_sql');
	$this->row_count=session_int('jkk_search_rowcount');
	if($this->row_count<0) $this->row_count=0;
	$this->display_search=session_string('jkk_search_display');

	// Get form text values
	$this->form_title=$this->remove_backslash(session_string('jkk_search_form_title'));
	$this->form_author=$this->remove_backslash(session_string('jkk_search_form_author'));
	$this->form_text=$this->remove_backslash(session_string('jkk_search_form_text'));

	// Get form boolean values
	$this->form_scr=session_int('jkk_search_form_scr',0,0,0);
	$this->form_books=session_int('jkk_search_form_books',0,0,0);
	$this->form_pubs=session_int('jkk_search_form_pubs',0,0,0);
	$this->form_song=session_int('jkk_search_form_song',0,0,0);
	$this->form_lex=session_int('jkk_search_form_lex',0,0,0);
	$this->form_da=session_int('jkk_search_form_da',0,0,0);
	$this->form_en=session_int('jkk_search_form_en',0,0,0);
	$this->form_se=session_int('jkk_search_form_se',0,0,0);
	$this->form_no=session_int('jkk_search_form_no',0,0,0);
	$this->form_de=session_int('jkk_search_form_de',0,0,0);
}

// Check if form buffer is empty
function set_default_form()
{
	if(
		$this->form_title=='' &&
		$this->form_author=='' &&
		$this->form_text=='' &&
		$this->form_scr==0 &&
		$this->form_books==0 &&
		$this->form_pubs==0 &&
		$this->form_song==0 &&
		$this->form_lex==0 &&
		$this->form_da==0 &&
		$this->form_en==0 &&
		$this->form_se==0 &&
		$this->form_no==0 &&
		$this->form_de==0){

		$this->form_scr=1;
		$this->form_books=1;
		$this->form_pubs=1;
		$this->form_song=1;
		$this->form_lex=1;
		$this->form_da=1;
	}
}

// Display search form
function display_form($message='')
{
	$this->page->tpl_main='main_lmenu_rmenu';

	// Setup page
	$this->page->title='[STR[SEARCH_TITLE]]';

	// Read templates
	$tpl=$this->page->read_template('search/search_form');
	$tpl_form=$this->page->read_template('search/search_form_all');

	// Get saved data
	$this->get_session();
	$this->set_default_form();

	// Insert form text values
	$tpl_form=str_replace('[VALUE[TITLE]]',htmlspecialchars($this->form_title),$tpl_form);
	$tpl_form=str_replace('[VALUE[AUTHOR]]',htmlspecialchars($this->form_author),$tpl_form);
	$tpl_form=str_replace('[VALUE[TEXT]]',htmlspecialchars($this->form_text),$tpl_form);


	// Insert form checkbox values
	$tpl_form=str_replace('[CHECKED[SCR]]',$this->form_scr==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[BOOKS]]',$this->form_books==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[PUBS]]',$this->form_pubs==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[SONG]]',$this->form_song==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[LEX]]',$this->form_lex==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[DA]]',$this->form_da==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[EN]]',$this->form_en==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[SE]]',$this->form_se==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[NO]]',$this->form_no==1?' checked="checked"':'',$tpl_form);
	$tpl_form=str_replace('[CHECKED[DE]]',$this->form_de==1?' checked="checked"':'',$tpl_form);

	// Insert values
	$tpl=str_replace('[SEARCH[FORM]]',$tpl_form,$tpl);
	$tpl=str_replace('[FORM[MESSAGE]]',$message,$tpl);

	// Apply template
	$this->page->content.=$tpl;
}

// Perform search in all texts
function search_all()
{
	// Get text parameters
	$title=request_string('title');
	$author=request_string('author');
	$text=request_string('text');

	// Get section parameters
	$scr=request_int('scr');
	$books=request_int('books');
	$pubs=request_int('pubs');
	$song=request_int('song');
	$lex=request_int('lex');

	// Get languageparameters
	$da=request_int('da');
	$en=request_int('en');
	$se=request_int('se');
	$no=request_int('no');
	$de=request_int('de');

	// Adjust text values
	$title=substr(trim(str_replace("'",'"',$title)),0,200);
	$author=substr(trim(str_replace("'",'"',$author)),0,200);
	$text=substr(trim(str_replace("'",'"',$text)),0,200);

	// Add to buffer and session
	$this->form_title=$title;
	$this->form_author=$author;
	$this->form_text=$text;
	$this->form_scr=($scr==1?1:0);
	$this->form_books=($books==1?1:0);
	$this->form_pubs=($pubs==1?1:0);
	$this->form_song=($song==1?1:0);
	$this->form_lex=($lex==1?1:0);
	$this->form_da=($da==1?1:0);
	$this->form_en=($en==1?1:0);
	$this->form_se=($se==1?1:0);
	$this->form_no=($no==1?1:0);
	$this->form_de=($de==1?1:0);
	$this->save_session();

	// Check fields
	if($title==''&&$author==''&&$text=='')
		return $this->display_form('Der skal indtastes mindst et søgekriterie.');
	if($scr<1 && $books<1 && $pubs<1 && $song<1 && $lex<1)
		return $this->display_form('Der skal vælges nogle tekster af søge i.');
	if($da<1 && $en<1 && $se<1 && $no<1 && $de<1)
		return $this->display_form('Der skal vælges mindst ét sprog.');

	// Check for complete search
	$use_language=($da==1 && $en==1 && $se==1 && $no==1 && $de==1)?false:true;
	$use_sections=($scr==1 && $books==1 && $pubs==1 && $song==1)?false:true;

	// Use query expansion
	$qexp='';

	// Compile full-text index
	$match=''; $match_text=''; $match_title=''; $match_author='';
	if($text!='')
		$match_text=" MATCH (title,subtitle,textdata,author) AGAINST ('$text' IN BOOLEAN MODE $qexp) ";
	if($title!='')
		$match_title=" MATCH (title) AGAINST ('$title' IN BOOLEAN MODE $qexp) ";
	if($author!='')
		$match_author.=" MATCH (author) AGAINST ('$author' IN BOOLEAN MODE) $qexp";
	if($match_text!='') $match.='AND '.$match_text;
	if($match_title!='') $match.='AND '.$match_title;
	if($match_author!='') $match.='AND '.$match_author;

	// Compile Score selection
	$sql_score='';
	if($match_text!='') $sql_score.=$match_text;
	if($match_title!='') $sql_score.=($sql_score!=''?'+':'').$match_title;
	if($match_author!='') $sql_score.=($sql_score!=''?'+':'').$match_author;
	$sql_score.=' AS relevance';

	// Compile language query
	$sql_language='';
	if($use_language!=false){
		if($da==1) $sql_language.="'da',";
		if($en==1) $sql_language.="'en',";
		if($se==1) $sql_language.="'se',";
		if($no==1) $sql_language.="'no',";
		if($de==1) $sql_language.="'de',";
		$sql_language='AND s.language IN ('.substr($sql_language,0,strlen($sql_language)-1).') ';
	}

	// Compile section query
	$sql_sections='';
	if($use_sections!=false){
		if($scr==1) $sql_sections.="(s.dtd='skrift') OR ";
		if($books==1) $sql_sections.="(s.dtd='ztextx' AND s.type='Bog') OR ";
		if($pubs==1) $sql_sections.="(s.dtd='ztextx' AND s.type='Tidsskrift') OR ";
		if($song==1) $sql_sections.="(s.dtd='ztextx' AND s.type='Sang') OR ";
		if($lex==1) $sql_sections.="(s.dtd='zbdict') OR ";
		$sql_sections='AND ('.substr($sql_sections,0,strlen($sql_sections)-4).') ';
	}

	// Compile SQL
	$this->sql="
		SELECT
			SQL_CALC_FOUND_ROWS
			t.*,
			s.id AS sid,
			$sql_score
		FROM
			jkk_texts t,
			jkk_systxt s
		WHERE
			t.id BETWEEN s.startid AND s.endid
			$sql_language
			$sql_sections
			$match
		ORDER BY
			relevance DESC
		";
	//if(is_local()) echo htmlentities($this->sql).'<p>';

	// Compile display search
	$this->display_search=$title.' '.$author.' '.$text;

	// Save to session
	$this->save_session();

	// Display search result
	return $this->display_result(0);
}

// Navigation handler
function navigation()
{
	// Get navigation page
	$id=request_int('id');
	if($id<1) $id=1;

	// Rerun query
	$this->display_result(1,$id);
}

// Insert navigation in header/footer
function insert_navigation(&$tpl_header,$first,$prev,$next,$last,$result_page_start,$result_page_count)
{
	// Page info section
	$tpl_header=str_replace(
		'[RESULT[PAGE_START]]',
		$result_page_start,
		$tpl_header);
	$tpl_header=str_replace(
		'[RESULT[PAGES]]',
		$result_page_count,
		$tpl_header);
	$tpl_header=str_replace(
		'[RESULT[PAGES]]',
		sprintf(constant('STR_SEARCH_RESULT_PAGES'),
			$result_page_start,
			$result_page_count),
		$tpl_header);

	// Page navigation section
	$tpl_header=str_replace('[RESULT[FIRST]]',$first,$tpl_header);
	apply_section($tpl_header,'FIRST',$first==''?1:0);
	$tpl_header=str_replace('[RESULT[PREVIOUS]]',$prev,$tpl_header);
	apply_section($tpl_header,'PREV',$prev==''?1:0);
	$tpl_header=str_replace('[RESULT[NEXT]]',$next,$tpl_header);
	apply_section($tpl_header,'NEXT',$next==''?1:0);
	$tpl_header=str_replace('[RESULT[LAST]]',$last,$tpl_header);
	apply_section($tpl_header,'LAST',$last==''?1:0);
	apply_section($tpl_header,'DELIM',($first.$prev.$next.$last)==''?1:0);
}

//
function save_query($rows)
{
	// Don't lof local queries
	if(is_local()) return;

	// Fix qoutes
	$title=str_replace('"','\"',$this->form_title);
	$author=str_replace('"','\"',$this->form_author);
	$text=str_replace('"','\"',$this->form_text);

	// Compile insert statement
	$sql="
		INSERT INTO
			jkk_search_access
		(
			title,
			author,
			text,
			scr,
			books,
			pubs,
			song,
			lex,
			da,
			en,
			se,
			no,
			result_rows,
			sql
		)
		VALUES
		(
			\"$title\",
			\"$author\",
			\"$text\",
			$this->form_scr,
			$this->form_books,
			$this->form_pubs,
			$this->form_song,
			$this->form_lex,
			$this->form_da,
			$this->form_en,
			$this->form_se,
			$this->form_no,
			$rows,
			\"$this->sql\"
		)
		";

		// Insert
		$this->db->execute($sql);
}

// Execute SQL search and display result
function display_result($source,$page_start=1,$page_rows=0)
{
	// Check that sql-statement is present
	$this->get_session();
	if($this->sql=='') return false;

	// Setup page selecters
	if($page_rows==0) $page_rows=$this->page_rows;

	// Execute
	$count=0;
	$this->row_count=0;
	$this->db->open_set($this->sql,($page_start-1)*$page_rows,$page_rows,1);

	// Get entire rowcount
	$this->db->open('SELECT FOUND_ROWS() AS RowCount',2);
	$this->row_count=$this->db->field('RowCount',2);
	$this->db->close(2);

	// If any records was found...
	if($this->db->move_next(1) && $this->row_count>=1){
		// Save query
		$this->save_query($this->row_count);

		// Read templates
		$tpl=$this->page->read_template('search/search_result');
		$tpl_header=$this->page->read_template('search/search_result_header');
		$tpl_footer=$this->page->read_template('search/search_result_footer');
		$tpl_listitem=$this->page->read_template('search/search_result_listitem');

		// Calculate result vars
		$result_rows=$this->row_count;
		$result_page_start=$page_start;
		$result_page_count=(floor($result_rows/$this->page_rows))+(($result_rows % $this->page_rows)>0?1:0);
		$result_row_start=($result_rows>$this->page_rows)?(($this->page_rows*($result_page_start-1))+1):1;
		$result_row_end=($result_rows<=$this->page_rows)?$result_rows:$result_row_start+($this->page_rows-1);

		// Get rows
		$list='';
		do{
			// Number
			$item_number=$result_row_start+$count;

			// Parent text
			$item_parenttext='';
			$last_mid=0;
			if($this->db->field('parenttext',1)!=''){
				$pt_list=explode('|',$this->db->field('parenttext',1));
				for($i=0;$i<count($pt_list);$i++){
					$pt_item=explode('#',$pt_list[$i]);
					if($item_parenttext!='') $item_parenttext.=' / ';
					$item_parenttext.=$pt_item[1];
					$last_mid=$pt_item[0];
				}
			}
			$item_parenttext.=' / '.$this->db->field('title',1);
			if(substr($item_parenttext,0,3)==' / ') $item_parenttext=substr($item_parenttext,3);

			// MID
			$mid=$this->db->field('mid',1);
			if($mid==0)
				$mid=$last_mid.'-'.$this->db->field('title',1).'#'.$this->db->field('title',1);
			
			// Text
			$text=strip_tags($this->db->field('subtitle',1));
			if($text==''){
				$text=substr($this->db->field('textdata',1),0,$this->max_subtitle_len*2);
				$text=str_trunc(strip_tags($text),$this->max_subtitle_len);
			}else
				$text=str_trunc($text,$this->max_subtitle_len);

			// Make item
			$list.=$tpl_listitem;
			$list=str_replace('[RESULT[ROW_NUMBER]]',$item_number,$list);
			$list=str_replace('[RESULT[PARENTTEXT]]',$item_parenttext,$list);
			$list=str_replace('[RESULT[MID]]',$mid,$list);
			$list=str_replace('[RESULT[TEXT]]',$text,$list);

			// Increaase counter
			$count++;
		}while($this->db->move_next(1));

	}else{
		// Save query
		$this->save_query(0);
	}
	$this->db->close(1);

	// Did we find any matching rows?
	if($this->row_count==0)
		return $this->display_form('[STR[SEARCH_NO_RESULT]]');

	// Navigation
	$first='';
	if($result_page_start>1){
		$first=$this->tn->make_link(
			'search[EXT]?c=nav&amp;id=1',
			'lfx','[STR[DISPLAY_FIRST_PAGE]]','1');
	}
	$prev='';
	if($result_page_start>1){
		$prev=$this->tn->make_link(
			'search[EXT]?c=nav&amp;id='.($result_page_start-1),
			'lf','[STR[DISPLAY_PREVIOUS_PAGE]]','2');
	}
	$next='';
	if($result_page_start<$result_page_count){
		$next=$this->tn->make_link(
			'search[EXT]?c=nav&amp;id='.($result_page_start+1),
			'rg','[STR[DISPLAY_NEXT_PAGE]]','3');
	}
	$last='';
	if($result_page_start<$result_page_count){
		$last=$this->tn->make_link(
			'search[EXT]?c=nav&amp;id='.$result_page_count,
			'rgx','[STR[DISPLAY_LAST_PAGE]]','4');
	}

	// Insert static header
	$tpl_header=str_replace('[RESULT[DISPLAY_SEARCH]]',$this->remove_backslash($this->display_search),$tpl_header);

	// Insert single itemt into template
	$tpl_header=str_replace('[RESULT[ROW_START]]',$result_row_start,$tpl_header);
	$tpl_header=str_replace('[RESULT[ROW_END]]',$result_row_end,$tpl_header);
	$tpl_header=str_replace('[RESULT[ROWS]]',$result_rows,$tpl_header);

	// Insert entire info string into template
	$tpl_header=str_replace(
		'[RESULT[INFO]]',
		sprintf(constant('STR_SEARCH_RESULT_INFO'),
			$result_row_start,
			$result_row_end,
			$result_rows),
		$tpl_header);

	// Insert navigation in header and footer
	$this->insert_navigation($tpl_header,$first,$prev,$next,$last,$result_page_start,$result_page_count);
	$this->insert_navigation($tpl_footer,$first,$prev,$next,$last,$result_page_start,$result_page_count);

	// Calculate colspan
	$colspan=4;
	if($first!='') $colspan++;
	if($prev!='') $colspan++;
	if($next!='') $colspan++;
	if($last!='') $colspan++;
	$tpl_header=str_replace('[RESULT[COLSPAN]]',$colspan,$tpl_header);

	// Insert into result page
	$tpl=str_replace('[RESULT[HEADER]]',$tpl_header,$tpl);
	$tpl=str_replace('[RESULT[LIST]]',$list,$tpl);
	$tpl=str_replace('[RESULT[FOOTER]]',$tpl_footer,$tpl);

	// Apply template
	$this->page->title='[STR[SEARCH_RESULT]]';
	$this->page->content.=$tpl;

	// Save to session
	$this->save_session();
}

// End: class search_object
};

?>