<script type="text/javascript">
$(document).ready(function(){
	if(typeof(window['FB']) == "undefined"){
				var temp = window.fbAsyncInit;
				
				window.fbAsyncInit = function() { 
					if((typeof temp) == "function"){
						temp.call();
					}
					else{
						eval(temp);
					}
					FB.Event.subscribe('auth.login',function(){
						$("div.facebook_please_log_in").hide();
						$("div.facebook_form_container").show();
					});
					FB.Event.subscribe('auth.logout',function(){
						$("div.facebook_please_log_in").show();
						$("div.facebook_form_container").hide();
					});
					
				};
	}
	else{
		FB.Event.subscribe('auth.login',function(){
			$("div.facebook_please_log_in").hide();
			$("div.facebook_form_container").show();
		});
		FB.Event.subscribe('auth.logout',function(){
			$("div.facebook_please_log_in").show();
			$("div.facebook_form_container").hide();
		});
	}
});
</script>