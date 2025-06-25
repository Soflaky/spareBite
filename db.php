<?php
// database connection settings - this is where we tell php how to connect to mysql
$host = 'localhost'; // the server where mysql is running (usually localhost for xampp)
$db = 'sparebite'; // name of our database 
$user = 'root'; // mysql username (root is default for xampp)
$pass = ''; // password (empty by default in xampp)

// create a new connection to the database using mysqli
$conn = new mysqli($host, $user, $pass, $db);

// check if the connection worked - if not, stop everything and show error
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
// if we get here, the connection worked! now other files can use $conn to talk to database
?>