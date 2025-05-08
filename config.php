<?php
$host="localhost";
$username="root"; // Default XAMPP username
$password=""; // Default XAMPP password
$dbname="levelupfood_db"; // <<<< Make sure this matches the database name you created

$con = new mysqli($host,$username,$password,$dbname);

// Set character set for the connection (important!)
if (!$con->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $con->error);
    // exit(); // Consider exiting if charset fails
}


if($con->connect_error){
    // Use error logging in production instead of die()
    error_log("Database Connection Failed: " . $con->connect_error);
    die("Database Connection Failed: " . $con->connect_error); // Keep die() for development simplicity for now
}else{
    // echo "Connection Successful !"; // Comment out for production
}
?>