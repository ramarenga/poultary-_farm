<?php
session_start();

if(!isset($_SESSION['worker_id'])){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","hencare");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$worker_id = $_SESSION['worker_id'];
$email = $_SESSION['worker_email'];

// Fetch existing data
$result = $conn->query("SELECT chicks, brooders, adults, phone FROM workers WHERE id=$worker_id");
$data = $result->fetch_assoc();

if(isset($_POST['save'])){

    $chicks = $_POST['chicks'];
    $brooders = $_POST['brooders'];
    $adults = $_POST['adults'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("UPDATE workers 
        SET chicks=?, brooders=?, adults=?, phone=? 
        WHERE id=?");

    $stmt->bind_param("iiisi", $chicks, $brooders, $adults, $phone, $worker_id);

    if($stmt->execute()){
        echo "<script>
                alert('Saved Successfully');
                window.location='dashboard.php';
              </script>";
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Worker Setup</title>
<style>
body{font-family:Arial;background:#f0fdf4;padding:40px;}
.box{max-width:400px;margin:auto;background:white;padding:25px;border-radius:10px;}
input{width:100%;padding:8px;margin:8px 0;}
button{width:100%;padding:10px;background:#16a34a;color:white;border:none;}
</style>
</head>
<body>

<div class="box">
<h2>Worker Setup</h2>

<form method="POST">

Email:<br>
<input type="text" value="<?php echo $email; ?>" readonly><br>

Chicks:<br>
<input type="number" name="chicks" 
value="<?php echo $data['chicks'] ?? 0; ?>" required><br>

Brooders:<br>
<input type="number" name="brooders" 
value="<?php echo $data['brooders'] ?? 0; ?>" required><br>

Adults:<br>
<input type="number" name="adults" 
value="<?php echo $data['adults'] ?? 0; ?>" required><br>

Phone:<br>
<input type="text" name="phone" 
value="<?php echo $data['phone'] ?? ''; ?>" required><br>

<button type="submit" name="save">Save</button>

</form>
</div>

</body>
</html>
