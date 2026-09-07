<?php

require_once "./db_Config/config.php";

$id=$_GET['id'];

mysqli_query($Connection,"DELETE FROM cart
WHERE cart_id='$id'");

header("Location: cart.php");

?>