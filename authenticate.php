<?php
 
session_start();
 
include "includes/db.php";
 
$username=$_POST['username'];
$password=hash('sha256',$_POST['password']);
 
$sql="SELECT * FROM users
WHERE username='$username'
AND password='$password'";
 
$result=mysqli_query($conn,$sql);
 
if(mysqli_num_rows($result)>0){
 
$user=mysqli_fetch_assoc($result);
 
$_SESSION['user']=$user;
 
header("Location: dashboard.php");
 
}else{
 
echo "Invalid Username or Password.";
 
}
 
?>