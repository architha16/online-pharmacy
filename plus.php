<?php

require_once "./db_Config/config.php";

$id=$_GET['id'];

mysqli_query($Connection,"UPDATE cart
SET quantity=quantity+1
WHERE cart_id='$id'");

header("Location: cart.php");

?>