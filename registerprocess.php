<?php 
session_start();
require 'config.php';
$error = $success = "";
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])){
    $name = trim($_POST['name']);
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    if($password !== $confirmpassword){
        $error = "Password Do Not Match !";
    }else{
        $query = $con->prepare("SELECT id FROM users WHERE email=?");
        $query->bind_param('s',$email);
        $query->execute();
        $query->store_result();
        if($query->num_rows > 0){
            $error = "Email already taken!";
        }else{
            $hashed_password = password_hash($password,PASSWORD_BCRYPT);
            $insert = $con->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)");
            $insert->bind_param("sss",$name, $email, $hashed_password);
            if($insert->execute()){
                $success = "Sign-up successful! You can Now <a href='login.php'>Sign in</a>.";
            }
            else{
                $error = "An error accurred. Please try again.";
            }
            $insert->close();
        }
        $query->close();
    }
    $con->close();

    $_SESSION['signup_error'] = $error;
    $_SESSION['signup_success'] = $success;

    header("Location: register.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h1>Register Done!</h1>
</body>
</html>