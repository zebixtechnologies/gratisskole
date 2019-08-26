<?php
require 'main.php';





function display_book_audio($book)
{
	global $db,$page;

	//
	// Get book title and start-mid
	//
	$book_title='';
	$sql="
		SELECT
			*
		FROM
			jkk_systxt
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
	// Find end-mid
	//
	$sql="
		SELECT
			*
		FROM
			jkk_systxt
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
			jkk_textaudio a,
			jkk_texts t
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
				$tpl_item.='<p style="font-weight:bold">'.$db->field('title').'</p><table border="0">';
			}else{
				$tpl_item=$chapter_link;
				$tpl_item=str_replace('[ITEM[MID]]',$db->field('mid'),$tpl_item);
				$tpl_item=str_replace('[ITEM[DOC]]',$page->tn->make_img('doc'),$tpl_item);
				$tpl_item=str_replace('[ITEM[AUDIO]]',$page->tn->make_img('speak'),$tpl_item);
				$tpl_item=str_replace('[ITEM[TITLE]]',$db->field('title'),$tpl_item);
				$tpl_item=str_replace('[ITEM[SIZE]]',number_dot(floor(filesize('data/bookread/mp3/t'.$db->field('mid').'.mp3')/1024)).' kb',$tpl_item);
			
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


function main()
{
	display_book_audio('mb');
}



main();
$page->send();
?>
