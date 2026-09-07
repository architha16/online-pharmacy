<?php
session_start();

require_once "./db_Config/config.php";

$id = $_GET['id'];

if(isset($_POST['submit']))
{

    $reason = $_POST['reason'];

    $other = mysqli_real_escape_string(
        $Connection,
        $_POST['other_reason']
    );

    if($reason=="Other")
    {
        $reason = $other;
    }
    else
    {
        if($other!="")
        {
            $reason .= " - ".$other;
        }
    }

    mysqli_query($Connection,"
    UPDATE orders
    SET
    order_status='Rejected',
    rejection_reason='$reason'
    WHERE order_id='$id'
    ");

    echo "<script>

    alert('Order Rejected Successfully');

    window.location='view_orders.php';

    </script>";

}
?>

<!DOCTYPE html>

<html>

<head>

<title>Reject Order</title>

<style>

body{
font-family:Arial;
background:#eef6ff;
}

.container{
width:550px;
margin:50px auto;
background:white;
padding:30px;
border-radius:10px;
box-shadow:0 0 15px #ccc;
}

h2{
text-align:center;
color:red;
}

label{
font-weight:bold;
}

select,
textarea{

width:100%;
padding:12px;
margin-top:8px;
margin-bottom:20px;
border:1px solid #ccc;
border-radius:6px;
font-size:16px;

}

button{

width:100%;
padding:15px;
background:red;
color:white;
font-size:18px;
border:none;
border-radius:6px;
cursor:pointer;

}

button:hover{

background:darkred;

}

</style>

</head>

<body>

<div class="container">

<h2>Reject Customer Order</h2>

<form method="POST">

<label>Select Reason</label>

<select
name="reason"
required>

<option value="">--Select Reason--</option>

<option value="Invalid Prescription">
Invalid Prescription
</option>

<option value="Medicine Out of Stock">
Medicine Out of Stock
</option>

<option value="Prescription Expired">
Prescription Expired
</option>

<option value="Doctor Signature Missing">
Doctor Signature Missing
</option>

<option value="Incorrect Medicine Details">
Incorrect Medicine Details
</option>

<option value="Customer Details Mismatch">
Customer Details Mismatch
</option>

<option value="Payment Verification Failed">
Payment Verification Failed
</option>

<option value="Other">
Other
</option>

</select>

<label>Additional Remarks</label>

<textarea
name="other_reason"
placeholder="Write additional reason (optional)..."></textarea>

<button
type="submit"
name="submit">

Reject Order

</button>

</form>

</div>

</body>

</html>