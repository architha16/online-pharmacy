<?php

$Server = "localhost";
$Username = "root";
$Password = "";
$Database = "pharmacyx_db";

$Connection = mysqli_connect(
    $Server,
    $Username,
    $Password,
    $Database
);

if (!$Connection) {
    die("Connection Failed: " . mysqli_connect_error());
}

?>