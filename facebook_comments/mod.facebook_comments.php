<?php
	class Facebook_comments{
		//Default settings
		var $settings = array("appId"=>"REPLACE ME","secret"=>"REPLACE ME","iframe-width"=>550,'num-comments'=>10);
		
		protected function  get_facebook_cookie($app_id, $application_secret) {
		  $args = array();
		  parse_str(trim($_COOKIE['fbs_' . $app_id], '\\"'), $args);
		  ksort($args);
		  $payload = '';
		  foreach ($args as $key => $value) {
			  $payload .= $key . '=' . $value;
		  }
		  if (md5($payload . $application_secret) != $args['sig']) {
			return null;
		  }
		  return $args;
		}
		
		
		/**
		* Sets up ref to EE
		*/
		function Facebook_comments() {
			
			// Make a local reference to the ExpressionEngine super object
			$this->EE =& get_instance();
			error_reporting(E_ALL);
			
		}
		
		
		/*
		* Gets the module's settings. Not done in constructor since not every method needs the settings.
		* Sets $this->settings to array of settings.
		*
		* @return none
		*/
		protected function getSettings(){
			//Get settings- use rand=rand to make sure query isn't cached
			$rand = rand(100,100000);
			$query = $this->EE->db->query("SELECT * FROM exp_facebook_comments_options WHERE '$rand' = '$rand'");
			$newSettings = array();
			foreach( $query->result_array() as $row){
				$newSettings[$row['key']] = $row['value'];
			}
			$this->settings = array_merge($this->settings,$newSettings);
		}
		
		/**
		* Returns the javascript SDK code. This should be called in the footer or header once per page that it is needed.
		*
		* @return String containing JS code for graph API's JS SDK
		*/
		function sdk(){
			$this->getSettings();
			return "<div id=\"fb-root\"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({appId: '" . $this->settings['appId'] . "', status: true, cookie: true,
             xfbml: true});
             
  };
  (function() {
    var e = document.createElement('script'); e.async = true;
    e.src = document.location.protocol +
      '//connect.facebook.net/en_US/all.js';
    document.getElementById('fb-root').appendChild(e);
    
  }());
</script>";
		}

		/**
		* Returns a facebook iFrame for comments.
		*
		* @return FBML string for comments based on template tag params + default settings.
		*/
		function getIframe(){
			//Get our configurations
			$this->getSettings();
			$xid = $entry_id = $this->EE->TMPL->fetch_param('xid');
			$width = $this->settings['iframe-width'];
			
			//Override width via tag param
			$tagWidth  = $entry_id = $this->EE->TMPL->fetch_param('width');
			if($tagWidth) 
				$width = $tagWidth;
				
			//	
			$numComments = $this->settings['num-comments'];
			//Override numCom from tag param
			$tagNumComments  = $entry_id = $this->EE->TMPL->fetch_param('numposts');
			if($tagNumComments) 
				$numComments = $tagNumComments;
				
			//Return FBML with given params	
			return  '<fb:comments xid="' . $xid . '" publish_feed="false" width="' . $width . '" numposts="' . $numComments . '"></fb:comments>';
		}
		
		/**
		* Gets the number of comment for a given xid.
		*
		* @return an int representing the number of comments made to this xid.
		*/
		function numComments(){
			
			$xid = $this->EE->TMPL->fetch_param('xid');
			
			//If AJAX enabled, let JS handle this
			if($this->EE->TMPL->fetch_param('ajax')){
				$script = file_get_contents($this->EE->functions->remove_double_slashes(PATH_THIRD . "/facebook_comments/ajax_get_num_comments.js"));
				return '<script type="text/javascript" fb-xid="' . $xid . '">$(document).ready(function(){var xid = ' . $xid. ";\n" . $script . '</script>';
				
			}
			
			//Load settings
			$this->getSettings();
			//Fetch the XID
			$xid = $entry_id = $this->EE->TMPL->fetch_param('xid');
			
			//Load Facebook PHP SDK
			require_once("facebook.php");
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false; //Temporary Hack
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
			
			//Init facebook API with our settings
			$facebook = new Facebook(array(
			  'appId'  => $this->settings['appId'],
			  'secret' => $this->settings['secret'],
			  'cookie' => true,
			));
			
			//Load a results array of our comments
			$result;
			try{
				$result = $facebook->api(array('method' => 'comments.get','xid'=>$xid));
			}
			catch (FacebookApiException $e) {
				error_log($e);
				die("Error");
			}
			return count($result);
		}
		/**
		 * For now, just returns FBML login button. FB JS SDK detects if log in/out needed.
		 * @return FBML log in/out button. 
		 */
		function logBox(){
			return '<fb:login-button autologoutlink="true"></fb:login-button>';
		}
		function comments(){
			//Get our config & load facebook api
			$xid = $this->EE->TMPL->fetch_param('xid');
			$this->getSettings();
			$ajaxOn = $this->EE->TMPL->fetch_param('ajax');
			$tagData = $this->EE->TMPL->tagdata;
			require_once("facebook.php");
			//Init facebook API with our settings
			$facebook = new Facebook(array(
			  'appId'  => $this->settings['appId'],
			  'secret' => $this->settings['secret'],
			  'cookie' => true
			));
			
			
			//Parse out date formatting information from the fb_time var
			$DateFormat = "m/d/y h:m A";
			foreach($this->EE->TMPL->var_single as $key=>$var){
				if(preg_match("/^fb_time\s*=.+/i", $key)){
					$params =  $this->EE->functions->assign_parameters($key);
					if(!empty($params['fb_time'])){
						$DateFormat = $params['fb_time'];
						$tagData = $this->EE->TMPL->swap_var_single($key, '{fb_time}', $tagData);
					}
				}					
			}
			//Pull comments for this XID from Facebook API
			try{
				$result = $facebook->api(array('method' => 'comments.get','xid'=>$xid));
			}
			catch (FacebookApiException $e) {
				error_log($e);
				return "Error connecting to Facebook."; 
			}
			//Try to get offset from tag params
			$offset = $this->EE->TMPL->fetch_param('offset');
			
			//If it is not set, try a best guess from URL
			$segs = $this->EE->uri->segment_array();
			if(!$offset && $this->EE->uri->total_segments() > 0){
				$last = $segs[$this->EE->uri->total_segments()];
				
				//If the last part of the URL is numeric, use it for pagination offset. If not, leave it blank.
				if(is_numeric($last))
					$offset = $last;
			}
			
			//save this for later since we will be changing the $result array. 
			$numResults = count($result);
			
			//Keep just our desired list
			if($offset){
				
				if($limit = $this->EE->TMPL->fetch_param('limit'))
					$result = array_slice($result,$offset*$limit,$limit);
				else
					$result = array_slice($result,$offset);
			}
			else if($limit = $this->EE->TMPL->fetch_param('limit'))
				$result = array_slice($result,0,$limit);
				
				
			$vars = array();
			$users = array();
			
			//Get a list of user ID's
			foreach($result as $comment){
				if(!isset($users[$comment['fromid']])){
					$users[$comment['fromid']] =  $comment['fromid'];
				}
			}
			//Build query string for user ID's
			$queryStr = '';
			foreach($users as $userid){
				$queryStr .= $userid . ",";
			}
			//If we have a query string, get the user information. 
			if($queryStr){
				$queryStr = "/?ids=" . substr($queryStr,0,-1);
				$users = $facebook->api($queryStr);
			}
			
			//Use the comment array and the user info to build the comment variables
			foreach($result as $comment){
				
				//Get our user from the array. 
				$user = $users[$comment['fromid']];
				
				//Make an entry for this comment in our var array
				$rowVars = array();
				
				foreach($comment as $key=>$value){
					$rowVars["fb_" . $key] = $value;
				}
				foreach($user as $key=>$value){
					$rowVars["fb_user_" . $key] = $value;
				}
				$rowVars["fb_user_picture"] = "http://graph.facebook.com/" . $rowVars['fb_user_id'] . "/picture";
				$rowVars["fb_time"] = date($DateFormat,$rowVars["fb_time"]);
				$vars[] = $rowVars;
					
			}
			$noPag = $this->EE->TMPL->fetch_param("nopagination");
			//Build the pagination, if we need it. 
			$pagination = '';
			if(!$noPag && !empty($limit) && $limit < $numResults){
				
				//Get link to this page. If we need to, rebuild it without the pagination arg.  
				if((empty($offset) && $offset != 0) || ($this->EE->uri->total_segments() > 0 && $segs[$this->EE->uri->total_segments()] != $offset)){
					$myURL = $this->EE->uri->uri_string() . "/";
				}
				else {
					$myURL = '';
					for($i = 0; $i < $this->EE->uri->total_segments(); $i++){
						if(!empty($segs[$i]))
							$myURL .= $segs[$i] . "/" ;
					}
				}
				$pagination = "<ul class='facebook_comments_pagination' xid=\"" . $xid . "\">\n";
				for($i = 0; $i < $numResults / $limit; $i++){
					if($i == $offset){
						$pagination .= "<li class='active'>\n";
					}
					else{
						$pagination .= "<li>\n";
					}
					
					//Make sure the display page number is +1 since most people don't index from 0
					$index = $this->EE->config->item('index_page');
					if(!empty($index)){
						$index .="/";
					}
					$pagination .= "<a href='" .  $this->EE->config->item('base_url') .  $index . $myURL . $i . "'>" . ($i + 1) . "</a>\n";
					$pagination .= "</li>\n";
				}
				$pagination .= "</ul>\n";
			}
			
			//If we have processed comments, template parse + return. Else, return 'no comments'.
			if($vars){
				
				$return = $this->EE->TMPL->parse_variables($tagData, $vars);
				//If AJAX is enabled, add a hidden template div for JS to use for styling the JSON response. 
				$return = "<div class=\"facebook_comments_wrap\" xid=\"" . $xid . "\">" . $return . "</div>\n";
				if($ajaxOn){
					$act = $this->EE->functions->fetch_action_id('Facebook_comments', 'ajax_comments');
					$return .= "<div class=\"facebook_hidden_markup\" style=\"display:none\" offset=\"" . $offset . "\" limit=\"" . $limit . "\" xid=\"" . $xid . "\" act=\"" . $act . "\">" . str_replace(array("{","}"),"%",$tagData) . "</div>\n";
					$script = "function xid" . $xid . "() { var xid = " . $xid . "; " . file_get_contents($this->EE->functions->remove_double_slashes(PATH_THIRD . "/facebook_comments/ajax_pag.js")) . "}xid" . $xid . "();";
					$return .= "<script type=\"text/javascript\">" . $script . "</script>";
				}
				return $return . $pagination;
				
				
			}
			else
				return "No comments.";
		}
		
		/**
		 * Returns a comment form for adding comments to the given XID. If not logged in, will give a 
		 * login button.
		 * 
		 * @return Comment form if logged in, login button if not.
		 */
		function comment_form(){
			//Get settings and connect to OGraph API
			$this->getSettings();
			require_once("facebook.php");
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false; //Temporary Hack
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
			$facebook = new Facebook(array(
			  'appId'  => $this->settings['appId'],
			  'secret' => $this->settings['secret'],
			  'cookie' => true,
			));
			
			//Check if user is logged in
			//$facebook->setSession($this->get_facebook_cookie($this->settings['appId'],$this->settings['secret']),false);
			$session = $facebook->getSession();
			
			$me = false;
			
			if($session){
				try{
					$me = $facebook->getUser();
				}
				 catch (FacebookApiException $e) {
					error_log($e);
				}
			}
			//If user IS logged in, return a comment form.
			$logStyle = '';
			$formStyle = '';
			
			//Determine default form display (login box or comment form, depending on login status)
			if($me){
				$logStyle = 'style="display:none"';
			}
			else{
				$formStyle = 'style="display:none"';
			}
				
				//Build the comment form + login box
				
				
				//Set the hidden fields (action id, return url, and comment's XID
				$hidden_fields = array(
				'ACT'=>$this->EE->functions->fetch_action_id('Facebook_comments', 'submit_comment'),
				'RET'=>$this->EE->functions->fetch_current_uri(),
				'FB-XID'=>$this->EE->TMPL->fetch_param('xid')
				);
				
				
				//Build + render the form
				$data = array(
							'hidden_fields'	=> $hidden_fields,
							'action'		=> $this->EE->functions->fetch_current_uri(),
							'class'			=> $this->EE->TMPL->form_class
						);
				$form  = $this->EE->functions->form_declaration($data);
				$form .="\n<textarea name='fb-commentText'></textarea><input type='submit' name='submit' value='submit'></form>";
				
				//Add script + wrappers, return.
				$vars = array();
				$script = file_get_contents($this->EE->functions->remove_double_slashes(PATH_THIRD . "/facebook_comments/ajax_form_display.js"));
				return "<div class=\"facebook_please_log_in\" $logStyle>Please log in to comment: <br> " . $this->logBox() . "</div>\n" . "<div class=\"facebook_form_container\" $formStyle>" . $this->EE->TMPL->parse_variables($form, $vars ) . "</div>" . $script;
		}
		
		/**
		 * Process a submitted comment. Redirects to the contents of the RET post var. 
		 * @return none
		 */
		function submit_comment(){
			//Get our settings and connect to OGraph API
			$this->getSettings();
			$xid = $_POST['FB-XID'];
			require_once("facebook.php");
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false; //Temporary Hack
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
			$facebook = new Facebook(array(
			  'appId'  => $this->settings['appId'],
			  'secret' => $this->settings['secret'],
			  'cookie' => true,
			));
			
			//If there is a comment to test.. 
			if(!empty($_POST['fb-commentText'])){
				//Test for logged in user
				$session = $facebook->getSession();
				if($session){
					$me;
					try{
						$me = $facebook->getUser();
					}
					 catch (FacebookApiException $e) {
						error_log($e);
					}
					if($me){
						
						//Add the comment!
						try{
							$facebook->api(array("method"=>"comments.add","xid"=>$xid,"text"=>$_POST['fb-commentText']));
						}
						 catch (FacebookApiException $e) {
							error_log($e);
							die("Error submitting comment to facebook.");
							
						}
					
					}
					else{
						echo "ERROR: No Facebook user.";
						return false;
					}
				}
				else{
					echo "ERROR: No Facebook Session.";
					return false;
				}
			}
			else{
			}
			//Send it back to the referrering page\
			$this->EE->functions->redirect($_POST['RET']);
		}
		
		/**
		 * Func for returning JSON array of post information for AJAX pagination/refreshing
		 * 
		 * @return echoes a JSON array of comments/users, then dies. No return. 
		 */
		function ajax_comments(){
			
			//Get our configs
			$this->getSettings();
			$xid = $_GET['FB-XID'];
			$limit = $_GET['LIMIT'];
			$offset = $_GET['OFFSET'];
			require_once("facebook.php");
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false; //Temporary Hack
			//Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
			$facebook = new Facebook(array(
			  'appId'  => $this->settings['appId'],
			  'secret' => $this->settings['secret'],
			  'cookie' => true,
			));
			
			//Get our results
			try{
				$result = $facebook->api(array("method"=>"comments.get","xid"=>$xid));
			}
		 	catch (FacebookApiException $e) {
				error_log($e);
				die("Error connecting to Facebook.");
			}
			//Clip results to offset and limit
			if($offset){
				
				if($limit)
					$result = array_slice($result,$offset*$limit,$limit);
				else
					$result = array_slice($result,$offset);
			}
			else if($limit)
				$result = array_slice($result,0,$limit);
				
			$return = array();
			$users = array();
			
			//Get a list of user ID's
			foreach($result as $comment){
				if(!isset($users[$comment['fromid']])){
					$users[$comment['fromid']] =  $comment['fromid'];
				}
			}
			//Build query string for user ID's
			$queryStr = '';
			foreach($users as $userid){
				$queryStr .= $userid . ",";
			}
			//If we have a query string, get the user information. 
			if($queryStr){
				$queryStr = "/?ids=" . substr($queryStr,0,-1);
				$users = $facebook->api($queryStr);
			}
			
			$vars = array();
			//Use the comment array and the user info to build the final comment array
			foreach($result as $comment){
				//Get our user from the array
				$user = $users[$comment['fromid']];
				//Make an entry for this comment in our var array
				$rowVars = array();
				
				//Add each element from comment to result array with fb_ prefix
				foreach($comment as $key=>$value){
					$rowVars["fb_" . $key] = $value;
				}
				//Add each piece of user info to array with fb_user_ prefix
				foreach($user as $key=>$value){
					$rowVars["fb_user_" . $key] = $value;
				}
				$rowVars["fb_user_picture"] = "http://graph.facebook.com/" . $rowVars['fb_user_id'] . "/picture";
				$rowVars["fb_sane_date_time"] = date("m/d/y h:m",$rowVars["fb_time"]);
				$vars[] = $rowVars;
			}
			
			echo json_encode($vars); 
			die();
		}
	}
			