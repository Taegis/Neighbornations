<!DOCTYPE html>
<html lang="en" >

<head>
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (empty($_SESSION["Username"])){
	$_SESSION["Username"] = '';
}

if (empty($_SESSION["Password"])){
	$_SESSION["Password"] = '';
}



$Username = $_SESSION["Username"];
$Password = $_SESSION["Password"];

include 'common-data.php';



?>
  <meta charset="UTF-8">
  <title>NeighborNations Index</title>
  
  
  <link rel='stylesheet' href='http://netdna.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css'>
  <link rel="stylesheet" href="css/style.css">

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>


  
</head>

<body>

    <div class="wrapper">
    <form class="form-signin">       
      <h2 class="form-signin-heading">Please login</h2>
      <input id='username' type="text" class="form-control" name="username" placeholder="User Name" required="" autofocus="" />
      <input id='password' type="password" class="form-control" name="password" placeholder="Password" required=""/>      
      <label class="checkbox">
        <input type="checkbox" value="remember-me" id="rememberMe" name="rememberMe"> Remember me
      </label>
      <button type="button" onclick='LoginClick();' class="btn btn-lg btn-primary btn-block">Login</button>   
    </form>
  </div>
  
  <script type="text/javascript">

  	if ("<?php echo $_SESSION["Username"]; ?>" != '') {
  		window.location.href = "WorldMap.php";
  	}
  	
  	function LoginClick() {
  		var username = document.getElementById('username').value;
  		var password = document.getElementById('password').value;
  		
  		$.post("QueryInsertUpdate.php",
		  {
		    action: "verifyLogin",
		    username: username,
		    password: password
		  },
		  function(data, status){
		    alert("Data: " + data + "\nStatus: " + status);
		  });

  		console.log('User is: ' + username);
  		console.log('Password is: ' + password);
  		console.log('<?php echo $Username; ?>')
  	}

  </script>

</body>

</html>
