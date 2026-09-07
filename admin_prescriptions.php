<?php
include 'db_Config/config.php';

$result = mysqli_query($Connection, "SELECT * FROM prescriptions");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Prescriptions</title>
</head>
<body>

<h2>Uploaded Prescriptions</h2>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Patient Name</th>
    <th>Mobile</th>
    <th>Prescription</th>
    <th>Status</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)) { ?>

<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['patient_name']; ?></td>
    <td><?php echo $row['mobile']; ?></td>

    <td>
        <a href="uploads/<?php echo $row['prescription_file']; ?>" target="_blank">
            View Prescription
        </a>
    </td>

    <td><?php echo $row['status']; ?></td>
</tr>

<?php } ?>

</table>

</body>
</html>