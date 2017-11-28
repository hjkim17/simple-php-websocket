# Client side websocket

# Accepted request
1. CLOSE_CONNECTION
	ROOM_EXIT_CLAIM	-> to other left
2. ROOM_CREATE
	ROOM_ACCEPT		-> to sender / ROOM_ENTER_CLAIM	-> to other left / ROOM_EXIT_CLAIM	-> to other left
	ROOM_REJECT		-> to sender
3. ROOM_ENTER
	ROOM_ACCEPT		-> to sender / ROOM_ENTER_CLAIM	-> to other left / ROOM_EXIT_CLAIM	-> to other left
	ROOM_REJECT		-> to sender
4. ROOM_LEAVE
	ROOM_ACCEPT		-> to sender / ROOM_ENTER_CLAIM	-> to other left / ROOM_EXIT_CLAIM	-> to other left
	CONNECTION_CLOSE	-> to sender (client is dropped to nowhere) / ROOM_EXIT_CLAIM	-> to other left
5. ROOM_INFO_REQUEST
	NONE			-> to sender
	ROOM_INFO_RESPONSE to sender
6. ROOM_CLIENT_READY
	NONE
	ROOM_READY_FAILURE
	ROOM_READY_CLAIM
	ROOM_ALL_READY
7. ROOM_USER_MESSAGE
	NONE
	ROOM_USER_MESSAGE_CLAIM
8. ROOM_GRANT_OWNER
	GRANT_OWNER_REJECT
	GRANT_OWNER_CLAIM
9. ROOM_KICK_CLIENT
	KICK_CLIENT_REJECT
	KICK_CLIENT_CLAIM -> to other left / KICK_ACCEPT -> to sender
	KICK_CLIENT_CLAIM -> to other left / CONNECTION_CLOSE -> to sender