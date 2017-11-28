var IP;
var port;
var ID;
var room;
var address_after_root = "";
var websocket="";
var action="";
var page=0;		//0 - Entry

var reject_log="";
var reject_flag = 0;

$(document).ready(function(){
	$("body").load("Entry.html");
	$("body").on("change", "#_debug_action_string", function() {
		selection_change($('#_debug_action_string option:selected').val());
	});
	$("body").on("click", "#_debug_command_trigger", function() {
		translate_command(gather_command());
	});
});

function activate_websocket() {
	websocket.onopen = function(ev) { // connection is open
		$('#message_box').html("");
		$('#message_box').append("<div class=\"system_msg\">Connection Opened</div>");
	}

	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		var response = msg.response; //message type
		console.log(msg);
		if(page==0) {
			if(response == 'CONNECTION_ESTABILISHED') {
				$('#message_box').html("");
				$('#message_box').append("<div class=\"system_msg\">Connection Estabilished</div>");
				translate_command([action, room, ID]);
			} else if(response == 'ROOM_ACCEPT') {
				$('#message_box').html("");
				$('#message_box').append("<div class=\"system_msg\">Room Request Accepted</div>");
				page = 0.5;
				$("body").load("Room.html");
			} else if(response == 'ROOM_REJECT') {
				$('#message_box').html("");
				$('#message_box').append("<div class=\"system_msg\">Room Request Rejected</div>");
				$('#message_box').append("<div class=\"system_msg\">Detail - "+msg.detail+"</div>");
				reject_log = msg.detail;
				reject_flag = 1;
				translate_command(["CLOSE_CONNECTION"]);
			}
		} else if(page==1) {		//for room page
			if(response == 'ROOM_ENTER_CLAIM' || response == 'ROOM_EXIT_CLAIM' || response == 'ROOM_ALL_READY' || response == 'ROOM_READY_CLAIM') {
				var room_info = msg.detail;
				var game_info = room_info['game'];
				var client_info = room_info['client'];
				var object_info = room_info['object'];
				var game_log =
					'game_status : ' + game_info['game_status'] + '<br>' +
					'client number : ' + game_info['current_client_no'] + "/" + game_info['client_max_limit'] + '<br>' +
					'room owner : ' + game_info['owner'] + '<br>';
				var client_log = 'client_log' + '<br>';
				for (var index in client_info){
					client_log += 'client ID : ' + client_info[index]['client_id'] +
						' ready : '+ client_info[index]['client_ready'] +
						'<br>';
				}
				$('#room_debug_log').html("");
				$('#room_debug_log').append("<div class=\"debug_log\">"+game_log+"</div>");
				$('#room_debug_log').append("<div class=\"debug_log\">"+client_log+"</div>");
				$('#room_debug_log').append("<div class=\"debug_log\">"+object_info+"</div>");
			} else if(response == 'ROOM_INFO_RESPONSE') {
				if(msg.detail == 103) {
					$('#room_debug_log').html("");
					$('#room_debug_log').append("<div class=\"debug_log\">ROOM NOT DEFINED</div>");
				} else {
					var room_info = msg.detail;
					var game_info = room_info['game'];
					var client_info = room_info['client'];
					var object_info = room_info['object'];
					var game_log =
						'game_status : ' + game_info['game_status'] + '<br>' +
						'client number : ' + game_info['current_client_no'] + "/" + game_info['client_max_limit'] + '<br>' +
						'room owner : ' + game_info['owner'] + '<br>';
					var client_log = 'client_log' + '<br>';
					for (var index in client_info){
						client_log += 'client ID : ' + client_info[index]['client_id'] +
							' ready : '+ client_info[index]['client_ready'] +
							'<br>';
					}
					$('#room_debug_log').html("");
					$('#room_debug_log').append("<div class=\"debug_log\">"+game_log+"</div>");
					$('#room_debug_log').append("<div class=\"debug_log\">"+client_log+"</div>");
					$('#room_debug_log').append("<div class=\"debug_log\">"+object_info+"</div>");
				}
			} else if(response == 'ROOM_USER_MESSAGE_CLAIM') {
				$('#chat_log').append("<div class=\"chat_line\">" + msg.from + " : "+msg.detail+"</div>");
				$('#chat_log').scrollTop($('.chat_line').length * 30);
			}
		}
	};

	websocket.onerror	= function(ev){$('#message_box').html("");$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
	websocket.onclose 	= function(ev){
		$('#message_box').html("");
		$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");
		if(reject_flag == 1) {
			reject_flag = 0;
			$('#message_box').append("<div class=\"system_msg\">Error Log : "+ reject_log +"</div>");
		}
		if(page != 0) {
			$('#message_box').append("<div class=\"system_msg\"><a href='./'>back to Entry</a></div>");
		}
		websocket="";
	};
}

// for debug command //
function gather_command() {
	var command_args = [$('#_debug_action_string option:selected').val(), $('#_debug_command_arg1').val(), $('#_debug_command_arg2').val(), $('#_debug_command_arg3').val()];
	return command_args;
}
function translate_command(args) {
	if(args[0] == "CLOSE_CONNECTION") {
		var msg = {
			action : args[0]
		};
	} else if(args[0] == "ROOM_CREATE") {
		var msg = {
			action : args[0],
			room : args[1],
			ID : args[2]
		};
	} else if(args[0] == "ROOM_JOIN") {
		var msg = {
			action : args[0],
			room : args[1],
			ID : args[2]
		};
	} else if(args[0] == "ROOM_ENTER") {
		var msg = {
			action : args[0],
			room : args[1]
		};
	} else if(args[0] == "ROOM_INFO_REQUEST") {
		var msg = {
			action : args[0],
			room : args[1],
			mode : args[2]
		};
	} else if(args[0] == "ROOM_CLIENT_READY") {
		var msg = {
			action : args[0],
			room : args[1],
			mode : args[2]
		};
	} else if(args[0] == "ROOM_USER_MESSAGE") {
		var msg = {
			action : args[0],
			room : args[1],
			user_message : args[2]
		};
	} else if(args[0] == "ROOM_GRANT_OWNER") {
		var msg = {
			action : args[0],
			room : args[1],
			next_owner : args[2]
		};
	} else {
		return;
	}
	websocket.send(JSON.stringify(msg));
	return;
}
function selection_change(selection_string) {
	if(selection_string == "CLOSE_CONNECTION") {
		$("#_debug_command_tag1").html("");
		$("#_debug_command_tag2").html("");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").hide();
		$("#_debug_command_div2").hide();
		$("#_debug_command_div3").hide();
	} else if(selection_string == "ROOM_CREATE") {
		$("#_debug_command_tag1").html("room");
		$("#_debug_command_tag2").html("ID");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").show();
		$("#_debug_command_div2").show();
		$("#_debug_command_div3").hide();
	} else if(selection_string == "ROOM_JOIN") {
		$("#_debug_command_tag1").html("room");
		$("#_debug_command_tag2").html("ID");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").show();
		$("#_debug_command_div2").show();
		$("#_debug_command_div3").hide();
	} else if(selection_string == "ROOM_ENTER") {
		$("#_debug_command_tag1").html("room");
		$("#_debug_command_tag2").html("");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").show();
		$("#_debug_command_div2").hide();
		$("#_debug_command_div3").hide();
	} else if(selection_string == "ROOM_INFO_REQUEST") {
		$("#_debug_command_tag1").html("room");
		$("#_debug_command_tag2").html("mode");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").show();
		$("#_debug_command_div2").show();
		$("#_debug_command_div3").hide();
	} else if(selection_string == "ROOM_CLIENT_READY") {
		$("#_debug_command_tag1").html("room");
		$("#_debug_command_tag2").html("mode");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").show();
		$("#_debug_command_div2").show();
		$("#_debug_command_div3").hide();
	} else if(selection_string == "ROOM_USER_MESSAGE") {
		$("#_debug_command_tag1").html("room");
		$("#_debug_command_tag2").html("user_message");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").show();
		$("#_debug_command_div2").show();
		$("#_debug_command_div3").hide();
	} else if(selection_string == "ROOM_GRANT_OWNER") {
		$("#_debug_command_tag1").html("room");
		$("#_debug_command_tag2").html("next owner");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").show();
		$("#_debug_command_div2").show();
		$("#_debug_command_div3").hide();
	} else {
		$("#_debug_command_tag1").html("");
		$("#_debug_command_tag2").html("");
		$("#_debug_command_tag3").html("");

		$("#_debug_command_div1").hide();
		$("#_debug_command_div2").hide();
		$("#_debug_command_div3").hide();
	}
	return;
}