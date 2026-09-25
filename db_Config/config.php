<?php

$Server   = getenv('DB_HOST') ?: 'localhost';
$Port     = getenv('DB_PORT') ?: '3306';
$Username = getenv('DB_USER') ?: 'root';
$Password = getenv('DB_PASSWORD') ?: '';
$Database = getenv('DB_NAME') ?: 'pharmacyx_db';

$Connection = mysqli_connect(
    $Server,
    $Username,
    $Password,
    $Database,
    (int)$Port
);

if (!$Connection) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($Connection, "utf8mb4");