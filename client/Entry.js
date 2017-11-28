$(document).ready(function(){
	$('#join_button').click(function() {
		IP = $('#input_ip').val();
		port = $('#input_port').val();
		ID = $('#input_id').val();
		room = $('#input_room').val();
		if(IP == '' || port == '' || ID == '' || room == '')
			return;
		action = 'ROOM_JOIN';

		websocket = new WebSocket("ws://"+IP+":"+port+address_after_root);
		activate_websocket();
	});
	$('#create_button').click(function() {
		IP = $('#input_ip').val();
		port = $('#input_port').val();
		ID = $('#input_id').val();
		room = $('#input_room').val();
		if(IP == '' || port == '' || ID == '' || room == '')
			return;
		action = 'ROOM_CREATE';

		websocket = new WebSocket("ws://"+IP+":"+port+address_after_root);
		activate_websocket();
	});
});