
		if(typeof(window['FB']) == "undefined"){
			var temp = window.fbAsyncInit;
			window.fbAsyncInit = function() { 
				if((typeof temp) == "function"){
					temp.call();
				}
				else{
					eval(temp);
				}
				var settings = new Array();
				settings["method"] = "comments.get";
				settings["xid"] = xid;
				FB.api(settings,function(response){
					if (!response || response.error) {
						$("script[fb-xid=" + xid + "]").replaceWith("Error")
					} 
					else {
						if((typeof response.length) == "undefined")
							$("script[fb-xid=" + xid + "]").replaceWith("0");
						else
							$("script[fb-xid=" + xid + "]").replaceWith('' + (response.length));
					}
				});
				
			};
		}
		else{
			var settings = new Array();
				settings["method"] = "comments.get";
				settings["xid"] = xid;
				FB.api(settings,function(response){
					if (!response || response.error) {
						$("script[fb-xid=" + xid + "]").replaceWith("Error")
					} 
					else {
						
						if((typeof response.length) == "undefined")
							$("script[fb-xid=" + xid + "]").replaceWith("0");
						else
							$("script[fb-xid=" + xid + "]").replaceWith('' + (response.length));
					}
				});
		}
	});
