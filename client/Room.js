$(document).ready(function(){
	page=1;
	translate_command(["ROOM_ENTER", room]);
	$('body').on("click", "#_debug_resend_info_req", function() {
		translate_command(["ROOM_INFO_REQUEST", room, "ALL"]);
	});
	$('body').on("click", "#_debug_request_ready_loaded", function() {
		translate_command(["ROOM_CLIENT_READY", room, "LOAD"]);
	});
	$('body').on("click", "#_debug_request_ready_cancel", function() {
		translate_command(["ROOM_CLIENT_READY", room, "CANCEL"]);
	});
	$('body').on("keydown", "#_debug_user_message", function(ev) {
		if(ev.which == 13 && $('#_debug_user_message').val() != '') {
			translate_command(["ROOM_USER_MESSAGE", room, $('#_debug_user_message').val()]);
			$('#_debug_user_message').val('');
		}
	});
});