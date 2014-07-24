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

		function log( text ) 
		{
			$log = $('#log');
			//Add text to log
			$log.append(($log.val()?"\n":'')+text);
			//Autoscroll
			$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
		}

		// Input: room id number
		// Output: room long description
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


		function send( text ) {
			Server.send( 'message', text );
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
				send("|changeRoom 1");
				log( "Connected." );
			});

			// We place them in room #1 by default. Change this to where they logged out from last.
			roomdesc('1');

			//OH NOES! Disconnection occurred.
			Server.bind('close', function( data ) {
				log( "Disconnected." );
			});

			//Log any messages sent from server
			Server.bind('message', function( payload ) {
				log( payload );
			});

			Server.connect();
		});
	</script>
</head>

<body>
	<div id='body'>
		<span id="room">The OOC Room</span><br/>
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

