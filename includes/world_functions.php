<?php
include_once 'dbconfig.php';   // As functions.php is not included
$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);

function roomList()
{
	global $mysqli;
	$sql = "SELECT * FROM world;";
	if (!$result = $mysqli->query($sql)) 
	{
		die ('There was an error running world fetch query[' . $mysqli->error . ']');
	}

	while ($row = $result->fetch_array()) {
		echo "<option value='".$row['id']."'>".$row['name']."</option>";
	}
}

?>
