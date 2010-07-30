<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Facebook_comments_mcp {

	function Facebook_comments_mcp()
	{
		$this->EE =& get_instance();
	}
	function index(){
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('facebook_comments_module_name'));
		$query = $this->EE->db->get("exp_facebook_comments_options");
		$ret = array();
		foreach($query->result_array() as $row){
			$ret[$row['key']] = $row['value'];
		}
		$this->EE->load->library('table');
		return $this->EE->load->view("index",array("settings"=>$ret),TRUE);
	}
	function processForm(){
	$this->EE->load->library('table');
		if(!empty($_POST)){
			$appId = $_POST['appId'];
			$query = $this->EE->db->query("SELECT * FROM exp_facebook_comments_options WHERE `key`= 'appId'");
			if(!empty($_POST['iframe-width']) && (!is_numeric($_POST['iframe-width']) || $_POST['iframe-width'] <= 0)){
				$this->EE->session->set_flashdata('message_failure', lang('invalid_width'));
				return $this->EE->load->view("index",array("settings"=>$_POST),TRUE);
				
			}
			foreach($_POST as $key => $value){
				if($key == "submit")
					continue;
				$query = $this->EE->db->query("SELECT * FROM exp_facebook_comments_options WHERE `key`= '$key'");
				if($query->num_rows())
					$query = $this->EE->db->query("UPDATE exp_facebook_comments_options SET `value`= '$value' WHERE `key`='$key'");
				else
					$query = $this->EE->db->query("INSERT INTO exp_facebook_comments_options (`key`,`value`) VALUES ('$key','$value')");
				
			}
			
			return $this->EE->load->view("index",array("settings"=>$_POST),TRUE);
		
		}
		else $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=facebook_comments');
	}
}
?>