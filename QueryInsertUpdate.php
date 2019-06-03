<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();

include 'common-data.php';

$sessUsername = $_SESSION["Username"];


$action = $_POST['action'];

$username = $_POST['username'];
$password = $_POST['password'];

$placeProvince = $_POST['placeProvince'];
$placeCoords = $_POST['placeCoords'];

switch ($action) {

	case 'verifyLogin':
		$conn = new mysqli($ServerName, $DBUser, $DBPassword, $Database);
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		}

		$result = $conn->query("select Username from Users where Username = '$username' and Password = '$password'");
		if ($result->num_rows > 0) {
		    // output data of each row
		    while($row = $result->fetch_assoc()) {
		        if ($row["Username"] == $username) {
		        	echo 'Success';
		        	$_SESSION["Username"] = $username;
		        	$_SESSION["Password"] =  $password;
		        }
		        else {
		        	echo 'Failure';
		        }
		    }
		} else {
		    echo "0 results";
		    echo $username;
		}
		$conn->close();

		break;


	case 'placeSettlement':
		$conn = new mysqli($ServerName, $DBUser, $DBPassword, $Database);
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		}

		$result = $conn->query("select SEOwner from Settlements where SEOwner = '$sessUsername'");
		if ($result->num_rows > 0) {
		    // output data of each row
		    while($row = $result->fetch_assoc()) {
		        if ($row["SEOwner"] == $sessUsername) {
		        	echo 'This user already owns a settlement - logging event';
		        }
		        else {
		        	echo 'Failed to get rows';
		        }
		    }
		} else {
		    $result = $conn->query("insert into Neighbornations.Settlements values ('$sessUsername', '$placeCoords', '$placeProvince')");
		    echo "Success";
		}
		$conn->close();

		break;
}

?>