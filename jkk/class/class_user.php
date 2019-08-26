<?php
//----------------------------------------------------------------------
// User object
//----------------------------------------------------------------------
// (C) 2005 Steffen Estrup, Nivå, Denmark
//----------------------------------------------------------------------

class user_object
{
var $id;
var $access;
var $name;
var $firstname;
var $surname;

var $flag_bigfont;
var $flag_logged_in=false;
var $db;

// Constructor
function user_object()
{
	$this->flag_bigfont=0;
	$this->flag_logged_in=0;

	$this->clear();
	if((''.@$_SESSION['jkk_user_id'])!=''){
		$this->read_session();
	}else{
		$this->id='';
		$this->flag_logged_in=0;
		if((''.@$_SESSION['jkk_user_flag_bigfont'])=='')
			$_SESSION['jkk_user_flag_bigfont']=$this->flag_bigfont;
		else
			$this->flag_bigfont=$_SESSION['jkk_user_flag_bigfont'];
	}
}

// Clear all user data
function clear()
{
	$this->id='';
	$this->access='';
	$this->name='';
	$this->firstname='';
	$this->surname='';
	$this->email='';
	$this->flag_bigfont=0;
	$flag_logged_in=0;
}

// Clear user data and session
function logout()
{
	$this->clear();
	$this->write_session();
}

// Toggle stylesheet. Save to user profile if user is logged in
function toggle_bigfont()
{
	$this->flag_bigfont=$this->flag_bigfont==0?1:0;
	$_SESSION['jkk_user_flag_bigfont']=$this->flag_bigfont;
	if($this->logged_in()){
		$sql='UPDATE jkk_user SET style='.$this->flag_bigfont.' WHERE id='.$this->id;
		$this->db->execute($sql);
	}
}

// Check for at logged in user
function logged_in()
{
	return $this->flag_logged_in==false?false:true;
}

// Return user name or 'Anonymous'
function get_username()
{
	return $this->flag_logged_in==1?$this->name:constant('STR_ANONYMOUS');
}

// Read user data from session object
function read_session()
{
	// Check that a user exists on the session object
	if((''.@$_SESSION['jkk_user_id'])=='') return false;

	// Clear buffer and read session values
	$this->clear();
	$this->id=$_SESSION['jkk_user_id'];
	$this->access=$_SESSION['jkk_user_access'];
	$this->name=$_SESSION['jkk_user_name'];
	$this->firstname=$_SESSION['jkk_user_firstname'];
	$this->surname=$_SESSION['jkk_user_surname'];
	$this->email=$_SESSION['jkk_user_email'];
	$this->flag_bigfont=$_SESSION['jkk_user_flag_bigfont'];

	// Signal that a user is logged in and return success
	return $this->flag_logged_in=true;
}

// Write user data to session object
function write_session()
{
	$_SESSION['jkk_user_id']=$this->id;
	$_SESSION['jkk_user_access']=$this->access;
	$_SESSION['jkk_user_name']=$this->name;
	$_SESSION['jkk_user_firstname']=$this->firstname;
	$_SESSION['jkk_user_surname']=$this->surname;
	$_SESSION['jkk_user_email']=$this->email;
	$_SESSION['jkk_user_flag_bigfont']=$this->flag_bigfont;
	return true;
}

// Load user data from database
function load_user($name)
{
	// Lookup user in database
	$this->db->open("SELECT * FROM jkk_user WHERE name='".make_db_string($name)."'");
	if($this->db->move_next()){

		// Load the user data
		$this->id=$this->db->field('id');
		$this->access=$this->db->field('access');
		$this->name=$this->db->field('name');
		$this->firstname=$this->db->field('firstname');
		$this->surname=$this->db->field('surname');
		$this->surname=$this->db->field('email');
		$this->flag_bigfont=$this->db->field('style');

		// Write the loaded user data to session object
		$this->write_session();

		// Signal online user
		$this->flag_logged_in=1;
	}else{
		$this->id='';
		$this->flag_logged_in=0;
	}
	$this->db->close();

	// Return status
	return $this->flag_logged_in;
}

// Save userdata to database
function save_user()
{
	// Check that a user exists in the buffer
	if($this->id=='') return false;

	// Return success
	return true;
}

};

?>
