<?php
class Facebook_comments_upd { 

    var $version        = '1.0'; 
     
    function Facebook_comments_upd() 
    { 
		$this->EE =& get_instance();
    }
    function install(){
    	$data = array(
			'module_name' => 'Facebook_comments' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);
		$this->EE->db->insert('modules', $data);
		$options_table = "CREATE TABLE IF NOT EXISTS `exp_facebook_comments_options` ( 
                               `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY , 
                               `key` VARCHAR( 255 ) NOT NULL , 
                               `value` TEXT NOT NULL 
                               ) 
                               CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$this->EE->db->query($options_table);					
		
		
		$data = array(
			'class'		=> 'Facebook_comments' ,
			'method'	=> 'submit_comment'
		);
		$this->EE->db->insert('actions', $data);
		$data = array(
			'class'		=> 'Facebook_comments' ,
			'method'	=> 'ajax_comments'
		);
		$this->EE->db->insert('actions', $data);
			
			
		return true;
    }
	function update($current = '')
	{
		return false;
	}
	function uninstall(){
		$this->EE->db->query("DELETE FROM exp_modules WHERE module_name = 'Facebook_comments'");
		$this->EE->db->query("DELETE FROM exp_actions WHERE class = 'Facebook_comments'");
		$this->EE->db->query("DROP TABLE IF EXISTS exp_facebook_comments_options");
		return true;
	}
}?>