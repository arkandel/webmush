<?php
//include_once 'includes/world_functions.php';
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';

/* 	A character leaves a room.
	Input: Character's clientID, old room id, new room id
	Returns: Nothing.
*/
function changeRoom($clientID, $oldroom, $newroom)
{
	global $Server;

	// First we tell people in the character's OLD room that they left and update their room list.
	foreach ( $Server->wsClients as $id => $client )
		if ( $id != $clientID && $Server->character[$clientID]['room'] == $Server->character[$id]['room'] )
		{
			$Server->wsSend($id, $Server->character[$clientID]['name'] . " has left the room.");
			$Server->wsSend($id, "|roomListRem ". $Server->character[$clientID]['name'] );
		}

	// We assign them the new room.
	$Server->character[$clientID]['room'] = $newroom;
	$Server->log( $Server->character[$clientID]['name'] . " ($clientID) is now in room ".$Server->character[$clientID]['room'] );

	// Now we update room list and announce to people at the character's NEW room that someone new joined it.
	updateRoom($clientID);
}

function updateRoom($clientID)
{
	global $Server;

	foreach ( $Server->wsClients as $id => $client )
		if ( $Server->character[$clientID]['room'] == $Server->character[$id]['room'] )
		{
			if ($clientID != $id)
				{
				$Server->wsSend($id, "|roomListAdd ". $Server->character[$clientID]['name']);
				$Server->wsSend($clientID, "|roomListAdd ". $Server->character[$id]['name']);
				$Server->wsSend($id, $Server->character[$clientID]['name'] . " has joined the room.");
				}
			else
				$Server->wsSend($clientID, "|roomListAdd ". $Server->character[$clientID]['name']);
		}
}

// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	// check if message length is 0
	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}

	if($message[0]=='|')
	{
		$command = explode(' ', trim($message) );
		switch($command[0])
		{
			case "|username":
				$Server->character[$clientID]['name'] = $command[1];
				$Server->log( "$ip ($clientID) is now called ".$Server->character[$clientID]['name'] );
				break;
			case "|changeRoom":
				changeRoom($clientID, $Server->character[$clientID]['room'], $command[1]);
				break;
		}
		$commandIssued = 1; // If it's a command, don't send it to the textbox.
	}
	
	// In case someone is in the middle of logging on and hasn't gotten a room number yet, we'll assume it's the OOC room.
	if (!isset($Server->character[$clientID]['room']))
		$Server->character[$clientID]['room'] =1;

	// We populate the newly logged on character's room list with the names of those already there and update their own.
	updateRoom($clientID);

	//Send the message to everyone but the person who said it
	foreach ( $Server->wsClients as $id => $client )
		if ( $id != $clientID && $Server->character[$clientID]['room'] == $Server->character[$id]['room'] )
		{
			if (!isset($commandIssued))
				$Server->wsSend($id, $Server->character[$clientID]['name']." said \"$message\"");
		}
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
/*
	$Server->log( $Server->character[$clientID]['name'] . " - $ip ($clientID) - has connected." );

	//Send a join notice to everyone but the person who joined
	foreach ( $Server->wsClients as $id => $client )
		if ( $id != $clientID )
			$Server->wsSend($id, "Visitor $clientID ($ip) has joined the room.");
*/
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( $Server->character[$clientID]['name'] . " - $ip ($clientID) - has disconnected." );

	//Send a user left notice to everyone in the room
	foreach ( $Server->wsClients as $id => $client )
		if ( $id != $clientID && $Server->character[$clientID]['room'] == $Server->character[$id]['room'] )
			$Server->wsSend($id, $Server->character[$clientID]['name'] . " has disconnected from the game.");

}

// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('127.0.0.1', 9300);

?>
