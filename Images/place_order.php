<?php

session_start();

require_once "./db_Config/config.php";

$user=$_SESSION['username'];

$name=$_POST['fullname'];

$phone=$_POST['phone'];

$address=$_POST['address'];

$total=$_POST['total'];

mysqli_query($Connection,"
INSERT INTO orders
(user_name,total_amount,status)

VALUES
('$user','$total','Pending')
");

mysqli_query($Connection,"
DELETE FROM cart
WHERE user_name='$user'
");

echo "<script>

alert('Order Placed Successfully');

window.location='products.php';

</script>";

?>