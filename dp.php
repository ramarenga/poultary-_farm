<?php
$conn = new mysqli("localhost", "root", "", "hencare");

if ($conn->connect_error) {
  die("Database connection failed");
}
?>
