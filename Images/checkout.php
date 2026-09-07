<?php
session_start();

if(!isset($_SESSION['username']))
{
    header("Location: signin.php");
    exit();
}

require_once "./db_Config/config.php";

$user = $_SESSION['username'];

$result = mysqli_query($Connection,"SELECT * FROM cart WHERE user_name='$user'");

$grandTotal = 0;

while($row = mysqli_fetch_assoc($result))
{
    $grandTotal += $row['price'] * $row['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Checkout | PharmacyX</title>

<style>

body{
font-family:Arial;
background:#f4f8ff;
margin:0;
}

.container{
width:500px;
margin:60px auto;
background:white;
padding:30px;
border-radius:10px;
box-shadow:0 0 15px rgba(0,0,0,.2);
}

h2{
text-align:center;
color:#0077b6;
}

input{
width:100%;
padding:12px;
margin:10px 0;
font-size:16px;
}

button{
width:100%;
padding:15px;
background:#28a745;
color:white;
border:none;
font-size:18px;
border-radius:8px;
cursor:pointer;
}

button:hover{
background:#218838;
}

.total{
text-align:center;
font-size:22px;
margin:20px;
font-weight:bold;
color:#0077b6;
}

</style>

</head>

<body>

<div class="container">

<h2>Checkout</h2>

<div class="total">

Grand Total : ₹<?php echo number_format($grandTotal,2); ?>

</div>

<form action="place_order.php" method="POST">

<input type="text" name="fullname" placeholder="Full Name" required>

<input type="text" name="phone" placeholder="Phone Number" required>

<input type="text" name="address" placeholder="Delivery Address" required>

<input type="hidden" name="total" value="<?php echo $grandTotal; ?>">

<button type="submit">

Place Order

</button>

</form>

</div>

</body>

</html>