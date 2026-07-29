<?php
// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "iot_data";
$port       = 3307; // specify your MySQL port

// Pass the port to mysqli
$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if data is received
if (isset($_POST['temperature']) && isset($_POST['humidity'])) {

    $temperature = $_POST['temperature'];
    $humidity    = $_POST['humidity'];

    $sql = "INSERT INTO sensor_data (temperature, humidity)
            VALUES ('$temperature', '$humidity')";

    if ($conn->query($sql) === TRUE) {
        echo "Data Inserted Successfully";
    } else {
        echo "Error: " . $conn->error;
    }

} else {
    echo "No Data Received";
}

$conn->close();
?>
