<?php
include_once 'variable.php';
// group setting, group index with status
include_once 'group.php';
include_once 'control.php';
include_once 'http-process.php';

$null = NULL; //null var
$ctrl_no = 0;

//Create TCP/IP sream socket
$serv_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port : easily restart socket program
socket_set_option($serv_socket, SOL_SOCKET, SO_REUSEADDR, 1);
//bind socket to specified host
socket_bind($serv_socket, 0, $port);
//listen to port
socket_listen($serv_socket);

//create & add listning socket to the list
$socket_list = array($serv_socket);

//start endless loop, so that our script doesn't stop
while (true) {
	//manage multiple connections
	$read_detected = $socket_list;
	//returns the socket resources in $read_detected array
	socket_select($read_detected, $null, $null, 0, 10);
	
	//check for new socket
	if (in_array($serv_socket, $read_detected)) {
		$socket_new = socket_accept($serv_socket); //accpet new socket
		echo "INFO LOG - new socket detected : ".$socket_new.PHP_EOL.PHP_EOL;
		$socket_list[] = $socket_new; //add socket to client array
		
		$header = socket_read($socket_new, 2048); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
		
		//socket_getpeername($socket_new, $ip); //get ip address of connected socket
		$response = mask(json_encode(array('response'=>'CONNECTION_ESTABILISHED'))); //prepare json data
		send_message($response, $socket_new);
		
		//remove serv_socket from detected list
		$found_socket = array_search($serv_socket, $read_detected);
		unset($read_detected[$found_socket]);
	}

	//loop through all connected sockets
	foreach ($read_detected as $changed_socket) {
		echo "INFO LOG - changed socket detected : ".$changed_socket.PHP_EOL;
		socket_getpeername($changed_socket, $ip);
		$target_type = "NONE";
		//check for any incomming data
		if($ctrl_no > 10000)
			$ctrl_no = 0;
		echo '         - CONTROL # '.($ctrl_no++).PHP_EOL;
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 4) {
			$received_text = unmask($buf); //unmask data
			$target_type = "NONE";
			$received_msg;
			echo '         - RECEIVED : '.$received_text.PHP_EOL;
			if(strlen($received_text) < 4) {
				$target_type = "CLOSE";						// goto close
				break;
			} else {
				$received_msg = json_decode($received_text); //json decode
			}
			// control
			$response_data = request_control($received_msg, $ip, $changed_socket, $target_type);
			// sending
			if(send_branch_stop($target_type, $response_data, $socket_list, $changed_socket)) {
				break;										// goto close
			}
			echo PHP_EOL;
			break 2; //exist this loop
		}
		if($target_type != "CLOSE") {
			$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
			if ($buf === false) { // check disconnected client
				$target_type == "CLOSE";					// goto close
			}
		}
		if($target_type == "CLOSE") {
			$response_data = close_control($ip, $changed_socket, $target_type);
			send_branch_stop($target_type, $response_data, $null, $changed_socket);
			close_routine($changed_socket, $ip);
		}
		echo PHP_EOL;
	}
}
// close the listening socket
socket_close($serv_socket);

function send_branch_stop($target_type, $response_data, $socket_list, $changed_socket) {
	if($target_type == "ALL") {
		send_broadcast_message(mask(json_encode($response_data)), $socket_list);
		return false;
	} else if($target_type == "ONE") {
		send_message(mask(json_encode($response_data)), $changed_socket);
		return false;
	} else if($target_type == "GROUP") {
		send_group_message(mask(json_encode($response_data)), $response_data['room']);
		return false;
	} else if($target_type == "CLOSE") {
		return true;
	} else {
		return true;
	}
}

function send_broadcast_message($msg) {
	global $socket_list;
	foreach($socket_list as $changed_socket) {
		send_message($msg, $changed_socket);
	}
	return true;
}
function send_group_message($msg, $room_name) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		echo "ERROR LOG - ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	} else {
		foreach($room_list[$room_name]["socket"] as $room_socket) {
			send_message($msg, $room_socket);
		}
	}
}
function send_message($msg, $target_id) {
	global $serv_socket;
	if($target_id != $serv_socket) {
		@socket_write($target_id,$msg,strlen($msg));
		return true;
	} else {
		return false;
	}
}

function close_routine($socket, $ip) {
	global $socket_list;
	// remove client for $socket_list array
	$found_socket = array_search($socket, $socket_list);
	unset($socket_list[$found_socket]);
	echo '         - '.$ip.' disconnected'.PHP_EOL;
	socket_close($socket);
}