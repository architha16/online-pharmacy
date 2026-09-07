<?php

session_start();

include("db_Config/config.php");


/* =====================================================
   GET PRESCRIPTIONS
===================================================== */

$sql = "

    SELECT

        prescriptions.*,

        products.product_name

    FROM prescriptions

    LEFT JOIN products

        ON prescriptions.product_id =
           products.product_id

    ORDER BY
        prescriptions.upload_date DESC

";


$result =
    mysqli_query(
        $Connection,
        $sql
    );


if (!$result) {

    die(
        "Database Error: " .
        mysqli_error($Connection)
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
Pharmacist Prescriptions | PharmacyX
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    font-family: Arial, sans-serif;

    background: #f4f7fb;

    margin: 0;

    padding: 30px;
}

h1 {

    text-align: center;

    color: #0077cc;

    margin-bottom: 30px;
}

.table-container {

    width: 100%;

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    background: white;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.1);

    border-radius: 8px;

    overflow: hidden;
}

th {

    background: #0077cc;

    color: white;

    padding: 14px;
}

td {

    padding: 12px;

    text-align: center;

    border-bottom: 1px solid #ddd;
}

tr:hover {

    background: #f5f5f5;
}

.prescription-link {

    color: #0077cc;

    font-weight: bold;

    text-decoration: none;
}

.prescription-link:hover {

    text-decoration: underline;
}

.status {

    font-weight: bold;
}

.pending {

    color: orange;
}

.approved {

    color: green;
}

.rejected {

    color: red;
}

input[type="text"] {

    width: 220px;

    padding: 9px;

    border: 1px solid #ccc;

    border-radius: 5px;
}

button {

    padding: 9px 15px;

    color: white;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    margin: 3px;

    font-weight: bold;
}

.approve-btn {

    background: #28a745;
}

.approve-btn:hover {

    background: #218838;
}

.reject-btn {

    background: #dc3545;
}

.reject-btn:hover {

    background: #c82333;
}

.completed {

    font-weight: bold;

    color: #555;
}

.no-data {

    padding: 25px;

    text-align: center;

    color: #777;
}

</style>

</head>

<body>

<h1>

Uploaded Prescriptions

</h1>


<div class="table-container">

<table>

<tr>

<th>ID</th>

<th>Patient Name</th>

<th>Mobile</th>

<th>Medicine</th>

<th>Prescription</th>

<th>Status</th>

<th>Recommended Medicines</th>

<th>Action</th>

</tr>


<?php

if (
    mysqli_num_rows($result) > 0
) {

    while (
        $row =
        mysqli_fetch_assoc($result)
    ) {

?>

<tr>


<td>

<?php

echo htmlspecialchars(
    $row['id']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['patient_name']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['mobile']
);

?>

</td>


<td>

<strong>

<?php

echo htmlspecialchars(
    $row['product_name']
    ?? 'Unknown Medicine'
);

?>

</strong>

</td>


<td>

<a

class="prescription-link"

href="uploads/<?php

echo htmlspecialchars(
    $row['prescription_file']
);

?>"

target="_blank"

>

View Prescription

</a>

</td>


<td class="status">


<?php

if (
    $row['status'] === 'Approved'
) {

?>

<span class="approved">

Approved

</span>

<?php

}

elseif (
    $row['status'] === 'Rejected'
) {

?>

<span class="rejected">

Rejected

</span>

<?php

}

else {

?>

<span class="pending">

Pending

</span>

<?php

}

?>

</td>


<td>


<?php

if (
    $row['status'] === 'Pending'
) {

?>


<form

action="update_prescription.php"

method="POST"

>


<input

type="hidden"

name="id"

value="<?php

echo $row['id'];

?>"


>


<input

type="text"

name="recommended_medicines"

placeholder="Enter medicines"

>


</td>


<td>


<button

type="submit"

name="action"

value="approve"

class="approve-btn"

>

Approve

</button>


<button

type="submit"

name="action"

value="reject"

class="reject-btn"

>

Reject

</button>


</form>


<?php

}

else {

?>


<?php

echo htmlspecialchars(

    $row['recommended_medicines']
    ?? '—'

);

?>


</td>


<td>

<span class="completed">

Completed

</span>

</td>


<?php

}

?>

</tr>


<?php

    }

}

else {

?>

<tr>

<td
colspan="8"
class="no-data"
>

No prescriptions found.

</td>

</tr>

<?php

}

?>

</table>

</div>

</body>

</html>