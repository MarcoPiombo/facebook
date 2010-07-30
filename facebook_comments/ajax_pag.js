var updating = false;
function updateAjax(limit, offset,xid,act){
	if(!updating){
		updating = true;
		var queryString = "?ACT=" + act + "&LIMIT=" + limit + "&OFFSET=" + offset + "&FB-XID=" + xid;
		$resp = $.ajax({url:window.location + queryString,dataType:'json',success:function(data){

			var finalText = '';
			for(i in data){
				var text = $("div.facebook_hidden_markup[xid=" + xid + "]").html();
				for(j in data[i]){
					text = text.replace("%" + j + "%",data[i][j]);
				}
				finalText += text;
			}
			$("div.facebook_comments_wrap[xid=" + xid + "]").html(finalText);
			updating = false;
		} });
	}
}		
$(document).ready(function(){
			var offset = $("div.facebook_hidden_markup[xid=" + xid + "]").attr("offset");
			var limit = $("div.facebook_hidden_markup[xid=" + xid + "]").attr("limit");
			var act = $("div.facebook_hidden_markup[xid=" + xid + "]").attr("act");
			if(typeof(window['FB']) == "undefined"){
				var temp = window.fbAsyncInit;
				window.fbAsyncInit = function() { 
					if((typeof temp) == "function"){
						temp.call();
					}
					else{
						eval(temp);
					}
					FB.Event.subscribe('auth.login',function () { updateAjax(limit,offset,xid,act) ;} );
				};
			}
			else
				FB.Event.subscribe('auth.login',function () { updateAjax(limit,offset,xid,act); });
			
			$("ul.facebook_comments_pagination[xid=" + xid + "] a").each(function(){
				$(this).attr("href","#");
				$(this).click(function(){ updateAjax(limit,$(this).text() - 1,xid,act); return false; });
			});
});