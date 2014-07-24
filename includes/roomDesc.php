<?php
include_once 'dbconfig.php';   // As functions.php is not included
$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);

$roomNum = $_GET['roomid'];
$sql = "SELECT * FROM world WHERE id=$roomNum;";
if (!$result = $mysqli->query($sql))
{
	return ('Error fetching room description! Please let a member of staff know.');
}

$row = $result->fetch_array();
mysqli_close($mysqli);

echo $row['longdesc'];

?>

