<?php
include_once 'variable.php';

// group setting, group index with status
$room_count = 0;
$room_list = array();

function add_room($room_name, $ip) {
	global $room_count;
	global $room_list;
	global $ROOM_MAX_LIMIT;
	if($room_count > $ROOM_MAX_LIMIT) {
		echo "EXCEED_ROOM_LIMIT".PHP_EOL;
		return 100;
	} else if(isset($room_list[$room_name])) {
		echo "ROOM_NAME_COLLISION".PHP_EOL;
		return 101;
	} else {
		$room_list[$room_name] = array();
		set_game($room_name, $ip);
		set_client($room_name);
		set_object($room_name);
		set_socket($room_name);
		$room_count++;
		return "ADD_COMPlETE ".$room_name.PHP_EOL;
	}
}
function remove_room($room_name) {
	global $room_count;
	global $room_list;
	if($room_count < 1) {
		echo "ROOM_LIST_EMPTY".PHP_EOL;
		return 102;
	} else if(!isset($room_list[$room_name])) {
		echo "ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	} else {
		unset($room_list[$room_name]);
		$room_count--;
		return "REMOVE_COMPLETE ".$room_name.PHP_EOL;
	}
}
function get_room($room_name) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		echo "ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	} else {
		return $room_list[$room_name];
	}
}

function get_client_from_room($room_name, $unique_key) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		return 103;
	} else if(!isset($room_list[$room_name]["client"][$unique_key])) {
		return 121;
	} else {
		return $room_list[$room_name]["client"][$unique_key];
	}
}
function get_client_from_room_list($unique_key) {
	global $room_list;
	$checker;
	foreach($room_list as $current_room_name => $current_room) {
		$checker = get_client_from_room($current_room_name, $unique_key);
		if($checker == 103 || $checker == 121) {
			continue;
		} else {
			return $checker;
		}
	}
	return 112;		//CLIENT_NOT_FOUND in current room
}

function pop_client_from_room_with_inversed_count($room_name, $unique_key, $socket) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		return 103;
	} else if(!isset($room_list[$room_name]["client"][$unique_key])) {
		return 121;
	} else if($room_list[$room_name]["socket"][$unique_key] != $socket) {
		return 115;
	} else {
		unset($room_list[$room_name]["client"][$unique_key]);
		unset($room_list[$room_name]["socket"][$unique_key]);
		$room_list[$room_name]["game"]["current_client_no"]--;
		return (-1) * $room_list[$room_name]["game"]["current_client_no"];
	}
}
function pop_client_from_room_list($unique_key, $socket, &$target_type) {
	global $room_list;
	$checker;
	foreach($room_list as $current_room_name => $current_room) {
		$checker = pop_client_from_room_with_inversed_count($current_room_name, $unique_key, $socket);
		if($checker == 103 || $checker == 121 || $checker == 115) {
			continue;
		} else {
			echo $current_room_name." has ".(-1*$checker).' clients.'.PHP_EOL;
			if($checker == 0) {
				echo $current_room_name." is removed because it is empty.".PHP_EOL;
				remove_room($current_room_name);
				$target_type = "NONE";
				return array();
			} else if($checker < 0) {
				$target_type = "GROUP";
				return array('response'=>'ROOM_EXIT_CLAIM', 'room'=>$current_room_name, 'detail'=>get_info($current_room_name, "room"));
			}
		}
	}
	echo "CLIENT_NOT_FOUND".PHP_EOL;
	$target_type = "NONE";
	return 112;		//CLIENT_NOT_FOUND
}

function push_client_to_room($room_name, $unique_key, $client_id, $client_socket) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		return 103;
	} else if(isset($room_list[$room_name]["client"][$unique_key])) {
		echo "SAME_IP_CLIENT_ALREADY_EXISTS".PHP_EOL;
		return 110;		//SAME_IP_CLIENT_ALREADY_EXISTS
	} else if($room_list[$room_name]["game"]["client_max_limit"] == $room_list[$room_name]["game"]["current_client_no"]) {
		echo "CLIENT_MAX_LIMIT".PHP_EOL;
		return 114;		//CLIENT_MAX_LIMIT
	} else {
		$room_list[$room_name]["client"][$unique_key] = array('client_id'=>$client_id, 'client_ready'=>0);
		$room_list[$room_name]["socket"][$unique_key] = $client_socket;
		$room_list[$room_name]["game"]["current_client_no"]++;
		return 210;		//CLIENT_PUSHED
	}
}

// for debugging purpose
function print_all_room() {
	global $room_list;
	echo "         == room info log ==".PHP_EOL;
	foreach($room_list as $current_room_name => $current_room) {
		echo "         room_name : ".$current_room_name.PHP_EOL;
		echo "         -- game --".PHP_EOL;
		foreach($current_room['game'] as $k => $v) {
			echo "         ".$k." => ".$v.PHP_EOL;
		}
		echo "         -- client --".PHP_EOL;
		foreach($current_room['client'] as $k => $v) {
			echo "         ".$k." => ".$v['client_id'].PHP_EOL;
		}
		echo "         -- object --".PHP_EOL;
		foreach($current_room['object'] as $k => $v) {
			echo "         ".$k." => ".$v.PHP_EOL;
		}
		echo "         -- socket --".PHP_EOL;
		foreach($current_room['socket'] as $k => $v) {
			echo "         ".$k." => ".$v.PHP_EOL;
		}
	}
}

function set_game($room_name, $ip) {
	global $room_list;
	$room_list[$room_name]["game"] = array(
		'game_status'=>0,
		'client_max_limit'=>2,
		'current_client_no'=>0,
		'owner'=>$ip
		);
}
function set_client($room_name) {
	global $room_list;
	$room_list[$room_name]["client"] = array();
}
function set_object($room_name) {
	global $room_list;
	$room_list[$room_name]["object"] = array(1,2);
}
function set_socket($room_name) {
	global $room_list;
	$room_list[$room_name]["socket"] = array();
}

function get_info($room_name, $type) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		echo "ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	} else if($type == "room") {
		return array('game'=>$room_list[$room_name]["game"], 'client'=>$room_list[$room_name]["client"], 'object'=>$room_list[$room_name]["object"]);
	} else if($type == "game") {
		return $room_list[$room_name]["game"];
	} else if($type == "client") {
		return $room_list[$room_name]["client"];
	} else if($type == "object") {
		return $room_list[$room_name]["object"];
	}
}

function get_client_info($room_name, $ip, $attr) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		echo "ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	} else if($attr == 'client_id'){
		return $room_list[$room_name]["client"][$ip][$attr];
	} else {
		return -1;
	}
}
function set_client_info($room_name, $ip, $attr, $val) {
	global $room_list;
	if(!isset($room_list[$room_name])) {
		echo "ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	} else if($attr == 'client_ready'){
		if($room_list[$room_name]["game"]['game_status'] != 0) {
			echo 'GAME_STATUS_NOT_0';
			return -1;
		} else {
			$room_list[$room_name]["client"][$ip][$attr] = $val;
			return 0;
		}
	} else {
		return -1;
	}
}

function check_all_ready($room_name) {
	global $room_list;
	$client_cnt = 0;
	$game_min_client = 2;
	if(!isset($room_list[$room_name])) {
		echo "ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	} else {
		foreach($room_list[$room_name]["client"] as $client) {
			if($client['client_ready'] == 0) {
				return 0;
			}
			$client_cnt++;
		}
		if($client_cnt < $game_min_client) {
			return 0;	
		} else {
			$room_list[$room_name]['game']['game_status'] = 1;
			return 1;
		}
	}
}

function grant_owner($room_name, $ip, $owner_ip) {
	global $room_list;
	if(isset($room_list[$room_name]['game']['owner']) && $room_list[$room_name]['game']['owner'] == $owner_ip) {
		$room_list[$room_name]['game']['owner'] = $ip;
		return 1;
	} else {
		echo "ROOM_NOT_DEFINED".PHP_EOL;
		return 103;
	}
}
?>