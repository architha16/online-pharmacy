<?php
session_start();

if(!isset($_SESSION['username']))
{
    header("Location: signin.php");
    exit();
}

require_once "./db_Config/config.php";
?>

<!DOCTYPE html>
<html>

<head>

<title>Customer Orders</title>

<style>

body{
font-family:Arial;
background:#f2f8ff;
margin:0;
padding:20px;
}

.container{
width:95%;
margin:auto;
background:#fff;
padding:20px;
border-radius:10px;
box-shadow:0 0 10px #ccc;
}

h2{
text-align:center;
color:#0077cc;
}

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
}

table th{
background:#0077cc;
color:white;
padding:12px;
}

table td{
padding:10px;
text-align:center;
border:1px solid #ddd;
}

.accept{
background:green;
color:white;
padding:8px 15px;
text-decoration:none;
border-radius:5px;
}

.reject{
background:red;
color:white;
padding:8px 15px;
text-decoration:none;
border-radius:5px;
}

.deliver{
background:orange;
color:white;
padding:8px 15px;
text-decoration:none;
border-radius:5px;
}

.pending{
color:#ff9800;
font-weight:bold;
}

.accepted{
color:green;
font-weight:bold;
}

.rejected{
color:red;
font-weight:bold;
}

.delivered{
color:blue;
font-weight:bold;
}

</style>

</head>

<body>

<div class="container">

<h2>Customer Orders</h2>

<table>

<tr>

<th>Order ID</th>
<th>Customer</th>
<th>Medicine</th>
<th>Quantity</th>
<th>Total</th>
<th>Payment</th>
<th>Status</th>
<th>Action</th>

</tr>

<?php

$sql="SELECT * FROM orders ORDER BY order_id DESC";

$result=mysqli_query($Connection,$sql);

if(mysqli_num_rows($result)>0)
{
while($row=mysqli_fetch_assoc($result))
{

$status=$row['order_status'];
?>

<tr>

<td><?php echo $row['order_id']; ?></td>

<td><?php echo $row['user_name']; ?></td>

<td><?php echo $row['product_id']; ?></td>

<td><?php echo $row['qty']; ?></td>

<td>₹<?php echo $row['Order_total']; ?></td>

<td><?php echo $row['payment_method']; ?></td>

<td>

<?php

switch($status)
{
    case "Pending":
        echo "<span class='pending'>🟡 Pending</span>";
        break;

    case "Accepted":
        echo "<span class='accepted'>🟢 Accepted</span>";
        break;

    case "Packed":
        echo "<span style='color:purple;font-weight:bold;'>📦 Packed</span>";
        break;

    case "Out for Delivery":
        echo "<span style='color:#ff6600;font-weight:bold;'>🚚 Out for Delivery</span>";
        break;

    case "Delivered":
        echo "<span class='delivered'>✅ Delivered</span>";
        break;

    case "Rejected":
        echo "<span class='rejected'>❌ Rejected</span>";
        break;
}
?>

</td>

<td>

<?php

if($status=="Pending")
{
?>

<a class="accept"
href="update_order.php?id=<?php echo $row['order_id'];?>&status=Accepted"
onclick="return confirm('Accept this order?');">
Accept
</a>

&nbsp;

<form action="update_order.php" method="POST">

<input type="hidden"
name="id"
value="<?php echo $row['order_id']; ?>">

<input type="hidden"
name="status"
value="Rejected">

<select
name="reject_option"
required>

<option value="">Select Reason</option>

<option value="Invalid Prescription">
Invalid Prescription
</option>

<option value="Out of Stock">
Out of Stock
</option>

<option value="Other">
Other
</option>

</select>

<br><br>

<textarea
name="reason"
rows="3"
placeholder="Write reason if Other"></textarea>

<br><br>

<input
type="submit"
class="reject"
value="Reject">

</form>

<?php
}

else if($status=="Accepted")
{
?>

<a class="deliver"
href="update_order.php?id=<?php echo $row['order_id'];?>&status=Packed">
📦 Pack
</a>

<?php
}

else if($status=="Packed")
{
?>

<a class="deliver"
href="update_order.php?id=<?php echo $row['order_id'];?>&status=Out for Delivery">
🚚 Out for Delivery
</a>

<?php
}

else if($status=="Out for Delivery")
{
?>

<a class="deliver"
href="update_order.php?id=<?php echo $row['order_id'];?>&status=Delivered">
✅ Delivered
</a>

<?php
}

else
{
echo "<b>No Action</b>";
}

?>

</td>

</tr>

<?php
}
}
else
{
?>

<tr>

<td colspan="8">No Orders Found</td>

</tr>

<?php
}
?>

</table>

</div>

</body>

</html>