<?php

class dbconn
{
// Local variables
var $_is_conn=0;
var $_cn;

var $_is_open;
var $_rs;
var $_row;
var $_move_count;
var $_row_status;

var $def_connect='';
var $def_database='';
var $def_user='';
var $def_pass='';

var $use_safe_count=0;

// Constructor
function dbconn()
{
	$this->_is_open=array(0,0,0,0,0);
	$this->_rs=array();
	$this->_row=array();
	$this->_move_count=array(0,0,0,0,0);
	$this->_row_status=array(0,0,0,0,0);
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



//
function set_default($name,$dbase,$user='',$pass='')
{
	//
	$this->def_connect=$name;
	$this->def_database=$dbase;
	$this->def_user=$user;
	$this->def_pass=$pass;
}

// Connect to database
function connect($name='',$dbase='',$user='',$pass='')
{
	if($this->_is_conn<>0) return;

	if(strlen($name)==''&&strlen($dbase)==''){
		$name=$this->def_connect;
		$dbase=$this->def_database;
		$user=$this->def_user;
		$pass=$this->def_pass;
	}

	$this->_cn=odbc_connect($dbase,$user,$pass);
	if($this->_cn<=0){
		echo 'Error connecting to database: '.$dbase."<p>\n";
	}else{
		$this->_is_conn=1;
	}
}

// Disconnect from database
function disconnect()
{
	if(!$this->_is_conn) return;
	$this->close();
	odbc_close($this->_cn);
	$this->_is_conn=0;
}

// Open result set
function open($sql,$roff=1,$row_start=0,$row_count=0)
{
	if($this->_is_open[$roff]==1) $this->close();

	if($row_start!=0 || $row_count!=0){
	}

	$this->_rs[$roff]=odbc_exec($this->_cn,$sql);
	$this->_is_open[$roff]=1;
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
	odbc_free_result($this->_rs[$roff]);
	$this->_is_open[$roff]=0;
}

// Move to next record
function move_next($roff=1)
{
	if($this->_is_open[$roff]!=1) return 0;
	return odbc_fetch_row($this->_rs[$roff]);
}

// Return column value
function field($name,$roff=1)
{
	if(!$this->_is_open[$roff]) return '';
	odbc_binmode($this->_rs[$roff],ODBC_BINMODE_PASSTHRU);
	odbc_longreadlen($this->_rs[$roff],65536);
	return odbc_result($this->_rs[$roff],$name);
}

// Return column value
function field_date($name,$roff=1)
{
	return $this->normalize_date($this->field($name,$roff));
}


// Execute SQL statement
function execute($sql)
{
	return odbc_exec($this->_cn,$sql)==false?false:true;
}

// Return number of selected rows
function num_rows($roff=1)
{
	return odbc_num_rows($this->_rs[$roff]);
}

// End
};

?>
