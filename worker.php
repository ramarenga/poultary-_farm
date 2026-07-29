<?php

$conn = new mysqli("localhost","root","","hencare");

$id = $_GET['id'];

$result = $conn->query("SELECT * FROM workers WHERE id=$id");
$data = $result->fetch_assoc();

if(isset($_POST['update'])){

$name = $_POST['name'];
$email = $_POST['email'];

$conn->query("UPDATE workers SET name='$name', email='$email' WHERE id=$id");

header("Location: admin_dashboard.php");
}

?>

<h2>Edit Worker</h2>

<form method="POST">

Name <br>
<input type="text" name="name" value="<?php echo $data['name']; ?>">

<br><br>

Email <br>
<input type="email" name="email" value="<?php echo $data['email']; ?>">

<br><br>

<button name="update">Update Worker</button>

</form>
