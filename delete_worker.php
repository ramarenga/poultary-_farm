<?php

$conn = new mysqli("localhost","root","","hencare");

if(isset($_GET['id'])){

$id = $_GET['id'];

$conn->query("DELETE FROM workers WHERE id='$id'");

header("Location: admin_dashboard.php");
exit;

}
else{
echo "No ID received";
}

?>
