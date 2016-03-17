jQuery(document).ready(function($){
	$(".bp-bkmklet-activate").click(function(){
		$(".bp-bkmklet-drag").slideToggle();
	});
	$(".bookmarklet-button").click(function(){
		alert( bookmarklet_button_vars.drag_message );
		return false;
	})
})