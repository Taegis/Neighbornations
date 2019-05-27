<?php

$ServerName = '45.33.99.62';
$Database = 'Neighbornations';
$DBUser = 'Taegis';
$DBPassword = 'Soccer393';

#pass a query and column name you're expecting - returns a single value
function getSingleResult($strQuery, $strColumnName) {

	global $ServerName, $Database, $DBUser, $DBPassword;

	$conn = new mysqli($ServerName, $DBUser, $DBPassword, $Database);
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}


	$result = $conn->query($strQuery);
	if ($result->num_rows > 0) {
	    // output data of each row
	    while($row = $result->fetch_assoc()) {
	        echo $row["$strColumnName"];
	    }
	} else {
	    echo "0 results";
	}
	$conn->close();
}


?>