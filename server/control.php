<?php
include_once 'variable.php';
// group setting, group index with status
include_once 'group.php';

function close_control($ip, $changed_socket, &$target_type) {
	$close_data = pop_client_from_room_list($ip, $changed_socket, $target_type);
	return $close_data;
}

function request_control($received_msg, $ip, $changed_socket, &$target_type) {
	print_all_room();
	//prepare data to be sent to client
	if(!isset($received_msg)) {
		$target_type = "NONE";
		return array();
	} else if($received_msg->action == "CLOSE_CONNECTION") {
		$target_type = "CLOSE";
		return array();
	} else if($received_msg->action == "ROOM_CREATE") {
		$flag = add_room($received_msg->room, $ip);
		$target_type = "ONE";
		if($flag == 101) {
			return array('response'=>'ROOM_REJECT', 'detail'=>'ROOM_NAME_COLLISION');
		} else if($flag == 100) {
			return array('response'=>'ROOM_REJECT', 'detail'=>'EXCEED_ROOM_LIMIT');
		} else {
			if(get_client_from_room_list($ip) != 112) {
				remove_room($received_msg->room);
				return array('response'=>'ROOM_REJECT', 'detail'=>'SOCKET_ALREADY_ON_ROOM');
			} else {
				$flag = push_client_to_room($received_msg->room, $ip, $received_msg->ID, $changed_socket);
				if($flag == 210) {
					return array('response'=>'ROOM_ACCEPT');
				} else if($flag == 103) {
					remove_room($received_msg->room);
					return array('response'=>'ROOM_REJECT', 'detail'=>'ROOM_NOT_FOUND');
				} else if($flag == 110) {
					remove_room($received_msg->room);
					return array('response'=>'ROOM_REJECT', 'detail'=>'SAME_IP_COLLISION');
				}  else if($flag == 114) {
					remove_room($received_msg->room);
					return array('response'=>'ROOM_REJECT', 'detail'=>'CLIENT_MAX_LIMIT');
				}
			}
		}
	} else if($received_msg->action == "ROOM_JOIN") {
		$target_type = "ONE";
		if(get_client_from_room_list($ip) != 112) {
			echo "ERROR LOG - SAME IP ALREADY ON ROOM".PHP_EOL;
			return array('response'=>'ROOM_REJECT', 'detail'=>'SOCKET_ALREADY_ON_ROOM');
		} else {
			$flag = push_client_to_room($received_msg->room, $ip, $received_msg->ID, $changed_socket);
			if($flag == 210) {
				return array('response'=>'ROOM_ACCEPT');
			} else if($flag == 103) {
				return array('response'=>'ROOM_REJECT', 'detail'=>'ROOM_NOT_FOUND');
			} else if($flag == 110) {
				return array('response'=>'ROOM_REJECT', 'detail'=>'SAME_IP_COLLISION');
			} else if($flag == 114) {
				return array('response'=>'ROOM_REJECT', 'detail'=>'CLIENT_MAX_LIMIT');
			}
		}
	} else if($received_msg->action == "ROOM_ENTER") {
		$checker = get_client_from_room($received_msg->room, $ip);
		if($checker == 103 || $checker == 121) {
			$target_type = "NONE";
			return array();
		} else {
			$target_type = "GROUP";
			return array('response'=>'ROOM_ENTER_CLAIM', 'room'=>$received_msg->room, 'detail'=>get_info($received_msg->room, "room"));
		}
	} else if($received_msg->action == "ROOM_INFO_REQUEST") {
		$checker = get_client_from_room($received_msg->room, $ip);
		if($checker == 103 || $checker == 121) {
			$target_type = "NONE";
			return array();
		} else {
			if($received_msg->mode == "ALL") {
				$target_type = "ONE";
				return array('response'=>'ROOM_INFO_RESPONSE', 'detail'=>get_info($received_msg->room, "room"));
			}
		}
	} else if($received_msg->action == "ROOM_CLIENT_READY") {
		$checker = get_client_from_room($received_msg->room, $ip);
		if($checker == 103 || $checker == 121) {
			$target_type = "NONE";
			return array();
		} else {
			$ready_error_code;
			if($received_msg->mode == "LOAD") {
				$ready_error_code = set_client_info($received_msg->room, $ip, 'client_ready', 1);
				if($ready_error_code == -1) {
					$target_type = "ONE";
					return array('response'=>'ROOM_READY_FAILURE', 'detail'=>'READY_FAILED');
				} else if($ready_error_code == 103) {
					$target_type = "ONE";
					return array('response'=>'ROOM_READY_FAILURE', 'detail'=>'ROOM_NOT_DEFINED');
				} else {
					$target_type = "GROUP";
					if(check_all_ready($received_msg->room) == 1) {
						return array('response'=>'ROOM_ALL_READY', 'room'=>$received_msg->room, 'detail'=>get_info($received_msg->room, "room"));
					} else {
						return array('response'=>'ROOM_READY_CLAIM', 'room'=>$received_msg->room, 'detail'=>get_info($received_msg->room, "room"));
					}
				}
			} else if($received_msg->mode == "CANCEL") {
				$ready_error_code = set_client_info($received_msg->room, $ip, 'client_ready', 0);
				if($ready_error_code == -1) {
					$target_type = "ONE";
					return array('response'=>'ROOM_READY_FAILURE', 'detail'=>'READY_CANCEL_FAILED');
				} else if($ready_error_code == 103) {
					$target_type = "ONE";
					return array('response'=>'ROOM_READY_FAILURE', 'detail'=>'ROOM_NOT_DEFINED');
				} else {
					$target_type = "GROUP";
					return array('response'=>'ROOM_READY_CLAIM', 'room'=>$received_msg->room, 'detail'=>get_info($received_msg->room, "room"));
				}
			}
		}
	} else if($received_msg->action == "ROOM_USER_MESSAGE") {
		$checker = get_client_from_room($received_msg->room, $ip);
		if($checker == 103 || $checker == 121) {
			$target_type = "NONE";
			return array();
		} else {
			$target_type = "GROUP";
			return array('response'=>'ROOM_USER_MESSAGE_CLAIM', 'room'=>$received_msg->room, 'from'=>get_client_info($received_msg->room, $ip, 'client_id'), 'detail'=>$received_msg->user_message);
		}
	} else if($received_msg->action == "ROOM_GRANT_OWNER") {
		$checker = get_client_from_room($received_msg->room, $received_msg->next_owner);
		if($checker == 103 || $checker == 121) {
			$target_type = "ONE";
			return array('response'=>'GRANT_OWNER_REJECT');
		} else {
			$target_type = "GROUP";
			grant_owner($received_msg->room, $received_msg->next_owner, $ip);
			return array('response'=>'GRANT_OWNER_CLAIM', 'room'=>$received_msg->room, 'detail'=>$received_msg->next_owner);
		}
	}
	return array();
}