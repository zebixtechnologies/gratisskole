<?php
//----------------------------------------------------------------------
// Date functions
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

// Return the specified dayname - 4 formats available
function get_day_name($d,$l=1)
{
	global $day_names;

	// Paranoid
	if(!is_numeric($d)) $d=0;
	if($d<1||$d>7) $d=0;
	if(!is_numeric($l)) $l=1;
	if($l<1||$l>4) $l=1;

	// Return name
	return $day_names[$l-1][$d];
}

// Return the spacified month name - 2 formats available
function get_month_name($m,$l=1)
{
	global $month_names;

	// Paranoid
	if(!is_numeric($m)) $m=0;
	if($m<1||$m>12) $m=0;
	if(!is_numeric($l)) $l=1;
	if($l<1||$l>2) $l=1;

	// Return name
	return $month_names[$l-1][$m];
}

// Normalize date into the format: yyyymmddhhmmss
function normalize_date($d)
{
	$d=str_replace(' ','',$d);
	$d=str_replace('-','',$d);
	$d=str_replace(':','',$d);
	$d=str_replace('/','',$d);
	return $d;
}

// Make standard display date. Format: d. mmm yyyy
function make_display_date($d,$time=1)
{
	$m=get_month_name((int)substr($d,4,2));
	$dd=(int)substr($d,6,2).'. '.$m.' '.substr($d,0,4);
	if($time==1) $dd.=' '.substr($d,8,2).':'.substr($d,10,2).':'.substr($d,12,4);
	return $dd;
}

// Make short date Format: dd-mm-yyyy
function make_short_date($d)
{
	return substr($d,6,2).'-'.substr($d,4,2).'-'.substr($d,0,4);
}

// Make standard RFC? date - source must be yyyymmddhhmmss
function std_date($d)
{
	return date('r',mktime(
		substr($d,8,2),
		substr($d,10,2),
		substr($d,12,2),
		substr($d,4,2),
		substr($d,6,2),
		substr($d,0,4)
		));
}

// Make calendar
function make_month_calendar($year,$month,$use_db=false,$style=1)
{
	global $db,$page;

	// Check for current month
	if($year==date('Y') && $month==(int)date('m')){
		$cday=(int)date('d');
	}else{
		$cday=-1;
	}

	// Find days with entries
	$d=$year.'-'.zpad($month,2);
	for($m=0;$m<=50;$m++) $mlist[$m]='';
	if($use_db){
		$sql="
			SELECT
				DAY(datecreated) AS MDay
			FROM
				jkk_blog_entry
			WHERE
				YEAR(datecreated)=$year
				AND MONTH(datecreated)=$month
			GROUP BY
				DAY(datecreated)
			ORDER BY
				DAY(datecreated)
			";
		$db->open($sql);
		while($db->move_next()){
			$day=$year.zpad($month,2).zpad($db->field('MDay'),2);
			$mlist[(int)$db->field('MDay')]=
				'<a title="'.make_display_date($day,0).'" class="day-link'.$style.'" href="blog[EXT]?d='.$day.'">[DAY]</a>';
		}
		$db->close();
	}

	// Initialize
	$y=$year;
	$m=$month;
	$start=date("w", mktime(0, 0, 0, $m, 1, $y));
	if($start==0) $start=7;
	$m++;
	if($m==13){
		$m=12;
		$y++;
	}
	$end=date("d", mktime(0, 0, 0, $m, 0, $y));
	$y=$year;
	$m=$month;
	$d=0;

	// Table start
	$p='<div class="calendar'.$style.'">';
	$p.='<table border="0" cellpadding="0" cellspacing="0" class="calendar'.$style.'">';

	// Current / previous / next
	$p.='<tr>';
	$p.='<td colspan="7" class="month-year'.$style.'">'.flcap(get_month_name($month,1)).' '.$year.'</td>';
	$p.='</tr>';

	// Day names at top
	$day_name_len=$style==0?1:($style==1?3:2);
	$p.='<tr>';
	for($i=1;$i<=7;$i++) $p.='<td class="dayname'.$style.'">'.get_day_name($i,$day_name_len).'</td>';
	$p.='</tr>';

	// weeks
	for($l=0;$l<6;$l++){
		//if($d>=($end-1)) break;
		if((($d-$start)+1)>$end) break;

		$p.='<tr>';
		for($c=0;$c<7;$c++){
			$d++;
			$bgcol='#ffffff';
			$tcol='#000000';
			if($d<$start || (($d-$start)+1)>$end){
				$bgcol='#eeeeee';
				$t='&nbsp;';
			}else{
				$t=($d-$start)+1;
				if($mlist[$t]==''){
					$bgcol='#ffffff';
					if($t==$cday) $t='<div style="font-weight:bold;color:#000000">'.$t.'</div>';
				}else{
					$bgcol='#ddeeee';
					$tpl=$mlist[$t];
					if($t==$cday) $t='<b>'.$t.'</b>';
					$tpl=str_replace('[DAY]',$t,$tpl);
					$t=$tpl;
				}
			}
			$p.='<td class="day'.$style.'" style="background-color:'.$bgcol.'">'.$t.'</td>';
		}
		$p.='</tr>';
	}
	$p.='</table></div>';

	// Return calendar
	return utf8_encode($p);
}

?>
