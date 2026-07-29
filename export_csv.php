<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

/* DATABASE CONNECTION */
$conn = new mysqli("localhost","root","","iot_data",3307);

if($conn->connect_error){
    die("Connection Failed: ".$conn->connect_error);
}

/* CSV HEADERS */
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="hencare_data.csv"');

/* OPEN OUTPUT STREAM */
$output = fopen("php://output","w");

/* COLUMN HEADERS */
fputcsv($output, array("Type","Date Time","Temperature","Humidity","Status"));

/* SENSOR DATA */
$sql = "SELECT created_at,temperature,humidity FROM sensor_data ORDER BY id DESC";
$result = $conn->query($sql);

if($result && $result->num_rows>0){
    while($row=$result->fetch_assoc()){
        $heater = ($row['temperature']<28) ? "ON" : "OFF";

        fputcsv($output, array(
            "Sensor",
            $row['created_at'],
            $row['temperature'],
            $row['humidity'],
            $heater
        ));
    }
}

/* ALERT DATA */
$sql2 = "SELECT created_at,message,status FROM alerts ORDER BY id DESC";
$result2 = $conn->query($sql2);

if($result2 && $result2->num_rows>0){
    while($row=$result2->fetch_assoc()){
        fputcsv($output, array(
            "Alert",
            $row['created_at'],
            "",
            "",
            $row['message']." (".$row['status'].")"
        ));
    }
}

fclose($output);
$conn->close();
exit();
?>
