<?php
include_once 'includes/dbconnect.php';
include_once 'includes/functions.php';
include_once 'includes/world_functions.php';

sec_session_start(); 
$username = $_SESSION['username'];

if(login_check($mysqli) == true) {
        // Add your protected page content here!
?>
<!doctype html>
<html>
<head>
	<meta charset='UTF-8' />
	<style>
		input, textarea {border:1px solid #CCC;margin:0px;padding:0px}

		#body {max-width:800px;margin:auto}
		#log {width:100%;height:400px}
		#message {width:100%;line-height:20px}
	</style>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script src="fancywebsocket.js"></script>
	<script>
		var Server;
		var roomNamesList = [];

		function log( text ) 
		{
			$log = $('#log');
			//Add text to log
			$log.append(($log.val()?"\n":'')+text);
			//Autoscroll
			$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
		}

		// Purpose: Display the current room's long description
		// Input: room id number
		function roomdesc( text ) 
		{
			$.get( 
					"includes/roomDesc.php",
					{ roomid: text },
					function(roomlongdesc) {
						document.getElementById('roomdesc').innerHTML = roomlongdesc;
					}

			     );
		}

		// Purpose: Set the list of characters in the room.
		// Input: Array of character names.
		function setRoomList( charnames ) 
		{
			$roomlist = $('#roomList');
			$roomlist.html("");
			for (var i = 0; i < charnames.length; ++i) 
			{
				$roomlist.append(charnames[i] + ' ');
			}
		}


		function send( text ) {
			Server.send( 'message', text );
		}

		// Purpose: Remove item from array
		// Input: The array, the item to be removed.

		function removeFromArray(arr, what) {
			var found = arr.indexOf(what);

			while (found !== -1) {
				arr.splice(found, 1);
				found = arr.indexOf(what);
			}
		}

		$(document).ready(function() {
			log('Connecting...');
			Server = new FancyWebSocket('ws://127.0.0.1:9300');

			$('#message').keypress(function(e) {
				if ( e.keyCode == 13 && this.value ) {
					log( 'You: ' + this.value );
					send( this.value );

					$(this).val('');
				}
			});

			// Initial first-connect stuff here.
			Server.bind('open', function() {
				send("|username <?php echo $username;?>");
			//	send("|changeRoom 1");
				log( "Connected." );
			});

			// We place them in room #1 by default. Change this to where they logged out from last.
			roomdesc('1');

			//OH NOES! Disconnection occurred.
			Server.bind('close', function( data ) {
				log( "Disconnected." );
			});

			// Resolve any system messages from the server. Log any non-system messages sent from server.
			Server.bind('message', function( payload ) {
				if( payload.charAt(0) != '|')
				{
					log( payload );
				}
				else
				{	// ugly, rewrite this.
					log (payload);
					var commandString = payload.split(' ');
					var command = commandString[0];
					var argument = payload.substr(payload.indexOf(' ') +1 );

					switch(command)
					{
						case '|roomListAdd':
						if ($.inArray(argument, roomNamesList) <0 )
							{
							roomNamesList.push(argument);
							setRoomList(roomNamesList);
							}
						break;
						case '|roomListRem':
							removeFromArray(roomNamesList, argument);
							setRoomList(roomNamesList);
						break;
					}
				}
			});

			Server.connect();
		});
	</script>
</head>

<body>
	<div id='body'>
		<span id="room">The OOC Room</span> Characters here: <span id="roomList"></span><br/>
		<span id="roomdesc"></span><br/>
		<textarea id='log' name='log' readonly='readonly'></textarea><br/>
		<input type='text' id='message' name='message' />
	</div>
	<select id="rooms" name="rooms" onChange="send('|changeRoom ' + this.value);document.getElementById('room').innerHTML = document.getElementById('rooms').options[document.getElementById('rooms').selectedIndex].text;roomdesc(this.value)";>
	<?php roomList();?>
	</select>
</body>

</html>
<?php } else { 
        echo "You are not authorized to access this page, please <a href='login.php'>login</a>.";
}

