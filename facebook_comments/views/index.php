
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=facebook_comments'.AMP.'method=processForm')?>

<?php
	$this->table->set_heading("Settings");
	

	//Application ID 
	$input = array("name"=>"appId");
	if(!empty($settings['appId']))
		$input['value'] = $settings['appId'];
	$this->table->add_row("Application ID",form_input($input));
	
	//Application Secret Key
	$input = array("name"=>"secret");
	if(!empty($settings['secret']))
		$input['value'] = $settings['secret'];
	$this->table->add_row("Application Secret",form_input($input));
	
	//Default iFrame width
	$input = array("name"=>"iframe-width");
	if(!empty($settings['iframe-width']))
		$input['value'] = $settings['iframe-width'];
	$this->table->add_row("Default iFrame Width",form_input($input));
	
	//Default Number of Comments
	$input = array("name"=>"num-comments");
	if(!empty($settings['num-comments']))
		$input['value'] = $settings['num-comments'];
	$this->table->add_row("Default Number of Comments Per Page",form_input($input));
	
	echo $this->table->generate();
?>

<p>
	<?=form_submit(array('name' => 'submit', 'class' => 'submit' ,'value'=>'Update'))?>
</p>

<?=form_close()?>