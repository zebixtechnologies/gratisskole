<?php

class dbconn
{
// Local variables
public $_is_conn=0;
public $_cn;

public $_is_open;
public $_rs;
public $_row;
public $_move_count;
public $_row_status;

public $def_connect='';
public $def_database='';
public $def_user='';
public $def_pass='';

public $use_safe_count;
public $_row_count;

// Constructor
function dbconn()
{
	$this->_is_open=array(0,0,0,0,0,0,0,0,0,0);
	$this->_rs=array();
	$this->_row=array();
	$this->_move_count=array(0,0,0,0,0,0,0,0,0,0);
	$this->_row_status=array(0,0,0,0,0,0,0,0,0,0);
	$this->use_safe_count=array(0,0,0,0,0,0,0,0,0,0);
	$this->_row_count=array(0,0,0,0,0,0,0,0,0,0);
}



// Fix old mysql syntax
function fix_sql(&$sql,$roff=1)
{
	$sql=str_replace("\t",' ',$sql);
	$sql=str_replace("\n",' ',$sql);
	$sql=str_replace("\r",' ',$sql);
	$sql=str_replace('  ',' ',$sql);

	if(substr($sql,0,11)=='SELECT TOP '){
		$sql=trim(substr($sql,11));
		$p=strpos($sql,' ');
		$top=substr($sql,0,$p);
		$sql=trim(substr($sql,$p));
		$sql='SELECT '.$sql.' LIMIT 0,'.$top;
	}

	if(substr($sql,0,6)=='SELECT' && $this->use_safe_count[$roff]!=0){
		$sql='SELECT SQL_CALC_FOUND_ROWS '.substr($sql,6);
	}

	$sql=str_replace('DAY(','DAYOFMONTH(',$sql);
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



// Set default connection values
function set_default($name,$dbase,$user='',$pass='')
{
	if($name=='') $name='localhost';
	$this->def_connect=$name;
	$this->def_database=$dbase;
	$this->def_user=$user;
	$this->def_pass=$pass;
}

// Connect to database
function connect($name='',$dbase='',$user='',$pass='')
{
	if($this->_is_conn<>0) return;

	if(strlen($name)==''){
		$name=$this->def_connect;
		$dbase=$this->def_database;
		$user=$this->def_user;
		$pass=$this->def_pass;
	}

	$this->_cn=mysqli_connect('localhost','root','','denmark');
	if($this->_cn===false){
		echo 'Error connecting to database: '.$name."<p>\n";
	}else{
		$this->_is_conn=1;
	}
}

// Disconnect from database
function disconnect()
{
	if(!$this->_is_conn) return;
	mysqli_close($this->_cn);
	$this->_is_conn=0;
}


// Open result set
function open($sql,$roff=1,$row_start=0,$row_count=0)
{
	if($this->_is_open[$roff]==1) $this->close($roff);

	$sql=trim($sql);
	$this->fix_sql($sql,$roff);

	if($row_start!=0 || $row_count!=0)
		$sql.=' LIMIT '.$row_start.','.$row_count;

	$this->_rs[$roff]=mysqli_query($this->_cn,$sql);

	if($this->use_safe_count[$roff]!=0){
		$rs_nr=mysqli_query($this->_cn,"SELECT FOUND_ROWS() AS RowCount");
		$rows_nr=mysqli_fetch_array($rs_nr);
		$this->_row_count[$roff]=$rows_nr['RowCount'];
		mysqli_free_result($rs_nr);
	}

	if($this->_rs[$roff]===false){
		echo "Error querying database\n";
	}else{
		$this->_is_open[$roff]=1;

		$this->_move_count[$roff]=0;
		$n=mysqli_num_rows($this->_rs[$roff]);
		if($n>0){
			if($this->use_safe_count[$roff]==0)
				$this->_row_count[$roff]=$n;
			$this->_row[$roff]=mysqli_fetch_array($this->_rs[$roff]);
			$this->_row_status[$roff]=($this->_row===false)?0:1;
		}else{
			$this->_row_status[$roff]=0;
		}
	}
}

// Open limited resultset
function open_set($sql,$row_start,$row_count,$roff=1)
{
	$this->open($sql,$roff,$row_start,$row_count);
}

// Close result set
function close($roff=1)
{
	if($this->_is_open[$roff]!=1) return;
	mysqli_free_result($this->_rs[$roff]);
	$this->_is_open[$roff]=0;
}

// Move to next record
function move_next($roff=1)
{
	if($this->_is_open[$roff]!=1) return 0;
	if($this->_move_count[$roff]>0){
		$this->_row[$roff]=mysqli_fetch_array($this->_rs[$roff]);
		$this->_move_count[$roff]++;
		return ($this->_row[$roff]===null)?0:1;
	}else{
		$this->_move_count[$roff]++;
		return $this->_row_status[$roff];
	}
}

// Return column value
function field($name,$roff=1)
{
	if(!$this->_is_open[$roff]) return '';
	return $this->_row[$roff][$name];
}

// Return column date value
function field_date($name,$roff=1)
{
	if(!$this->_is_open[$roff]) return '';
	return $this->normalize_date($this->_row[$roff][$name]);
}

// Execute SQL command
function execute($sql)
{
	return mysqli_query($this->_cn,$sql)==false?false:true;
}

// Return number of selected rows
function num_rows($roff=1)
{
	$num=$this->_row_count[$roff];
	return $num;
}

// End
};

?>
