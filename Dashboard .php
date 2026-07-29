<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("Asia/Kolkata");

require_once "send_email.php";

/* ------------------ DB CONNECTION ------------------ */
$conn = new mysqli("localhost","root","","iot_data",3307);
if ($conn->connect_error) die("Database Connection Failed: ".$conn->connect_error);

/* ------------------ WORKER ID ------------------ */
$worker_id = $_SESSION['worker_id'] ?? 0;

/* ------------------ FARM SETUP SAVE ------------------ */
if(isset($_POST['save_setup'])){
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $shed_type = $_POST['shed_type'];

    if($shed_type=="Chicks Shed"){ $temp=33; $hum=65; }
    elseif($shed_type=="Brooders Shed"){ $temp=30; $hum=60; }
    elseif($shed_type=="Adults Shed"){ $temp=28; $hum=59; }
    else{ $temp=0; $hum=0; }

    $stmt = $conn->prepare("INSERT INTO farm_setup(worker_id,phone,email,shed_type,ideal_temp,ideal_humidity) VALUES(?,?,?,?,?,?)");
    $stmt->bind_param("isssii",$worker_id,$phone,$email,$shed_type,$temp,$hum);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php"); exit();
}

/* ------------------ FETCH LATEST FARM SETUP ------------------ */
$setup = null;
$setup_stmt = $conn->prepare("SELECT * FROM farm_setup WHERE worker_id=? ORDER BY id DESC LIMIT 1");
$setup_stmt->bind_param("i",$worker_id);
$setup_stmt->execute();
$setup_result = $setup_stmt->get_result();
if($setup_result && $setup_result->num_rows>0) $setup=$setup_result->fetch_assoc();
$setup_stmt->close();

/* ------------------ LATEST SENSOR DATA ------------------ */
$temperature=0; $humidity=0; $lastUpdated="No Data";
$sensor_stmt=$conn->prepare("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1");
$sensor_stmt->execute();
$sensor_result=$sensor_stmt->get_result();
if($sensor_result && $sensor_result->num_rows>0){
    $row=$sensor_result->fetch_assoc();
    $temperature=$row['temperature'];
    $humidity=$row['humidity'];
    $lastUpdated=date("d M Y - h:i:s A",strtotime($row['created_at']));

    // Email alert
    if($setup && $temperature > $setup['ideal_temp']){
        sendAlertEmail($setup['email'],$temperature,$humidity);
    }
}
$sensor_stmt->close();

/* ------------------ CHART DATA ------------------ */
$tempData=[]; $humData=[]; $timeData=[];
$graph_stmt=$conn->prepare("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 20");
$graph_stmt->execute();
$graph_result=$graph_stmt->get_result();
if($graph_result && $graph_result->num_rows>0){
    while($row=$graph_result->fetch_assoc()){
        $tempData[]=$row['temperature'];
        $humData[]=$row['humidity'];
        $timeData[]=date("H:i:s",strtotime($row['created_at']));
    }
    $tempData=array_reverse($tempData);
    $humData=array_reverse($humData);
    $timeData=array_reverse($timeData);
}
$graph_stmt->close();

/* ------------------ DAILY STATS ------------------ */
$maxTemp=$minTemp=$avgHumidity=0;
$stats_stmt=$conn->prepare("SELECT MAX(temperature) as max_temp, MIN(temperature) as min_temp, AVG(humidity) as avg_humidity
                            FROM sensor_data WHERE DATE(created_at)=CURDATE()");
$stats_stmt->execute();
$stats_result=$stats_stmt->get_result();
if($stats_result && $stats_result->num_rows>0){
    $row=$stats_result->fetch_assoc();
    $maxTemp=round($row['max_temp'],2);
    $minTemp=round($row['min_temp'],2);
    $avgHumidity=round($row['avg_humidity'],2);
}
$stats_stmt->close();

/* ------------------ SENSOR HISTORY ------------------ */
$sensorHistory=[];
$history_stmt=$conn->prepare("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 50");
$history_stmt->execute();
$history_result=$history_stmt->get_result();
if($history_result && $history_result->num_rows>0){
    while($row=$history_result->fetch_assoc()) $sensorHistory[]=$row;
}
$history_stmt->close();

/* ------------------ ALERT HISTORY ------------------ */
$alerts=[];
$alert_stmt = $conn->prepare("SELECT * FROM alerts WHERE worker_id=? ORDER BY id DESC LIMIT 50");
if($alert_stmt){
    $alert_stmt->bind_param("i",$worker_id);
    $alert_stmt->execute();
    $alert_result = $alert_stmt->get_result();
    if($alert_result && $alert_result->num_rows>0){
        while($row = $alert_result->fetch_assoc()) $alerts[] = $row;
    }
    $alert_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>HenCare Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{ font-family:'Poppins',sans-serif; margin:0; background:#f0f2f5; }
header{ display:flex; justify-content:space-between; align-items:center; padding:15px 25px; background:#1e3a8a; color:#fff; }
header h1{ margin:0; font-size:22px; }
button{ cursor:pointer; }
.container{ display:flex; flex-wrap:nowrap; }
.sidebar{ width:220px; background:#1e3a8a; color:#fff; min-height:100vh; padding-top:20px; flex-shrink:0; transition:0.3s; display:block; }
.sidebar a{ display:block; padding:12px 20px; color:#fff; text-decoration:none; margin:5px 0; border-radius:6px; }
.sidebar a:hover{ background:#2563eb; }
.main{ flex:1; padding:20px; min-width:300px; }
.grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:20px; margin-bottom:30px; }
.card{ background:#fff; padding:20px; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.08); text-align:center; }
.card h3{ margin:10px 0; }
.gauge{ width:120px; height:120px; border-radius:50%; margin:auto; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700; background:conic-gradient(#ff5252 <?= $temperature*3.6 ?>deg, #e0e0e0 0deg);}
.gauge.humidity{ background:conic-gradient(#2196f3 <?= $humidity*3.6 ?>deg, #e0e0e0 0deg); }
table{ border-collapse:collapse; width:100%; margin-bottom:30px; }
table th,table td{ border:1px solid #ccc; padding:8px; text-align:center; }
table th{ background:#2563eb; color:#fff; }
.chart-container{ overflow-x:auto; }
.chart-container canvas{ min-width:800px; height:300px; display:block; }
.section{ display:none; }
</style>
</head>
<body>

<header>
<h1>HenCare Dashboard</h1>
<button onclick="toggleSidebar()">☰ Menu</button>
</header>

<div class="container">
<div class="sidebar" id="sidebar">
<a href="#" onclick="showSection('dashboard')">Dashboard</a>
<a href="#" onclick="showSection('farmSetup')">Farm Setup</a>
<a href="#" onclick="showSection('sensorHistory')">Sensor History</a>
<a href="#" onclick="showSection('alerts')">Alerts</a>
<a href="export_csv.php" target="_blank">Export CSV</a>
</div>

<div class="main">
<!-- Dashboard -->
<div id="dashboard" class="section">
<div class="grid">
<div class="card"><h3><i class="fas fa-temperature-high"></i> Temperature</h3><div class="gauge"><?= $temperature ?>°C</div></div>
<div class="card"><h3><i class="fas fa-tint"></i> Humidity</h3><div class="gauge humidity"><?= $humidity ?>%</div></div>
</div>
<h2>Daily Stats</h2>
<div class="grid">
<div class="card">Max Temp: <?= $maxTemp ?>°C</div>
<div class="card">Min Temp: <?= $minTemp ?>°C</div>
<div class="card">Avg Humidity: <?= $avgHumidity ?>%</div>
</div>
<div class="card">
<h3>Sensor Chart</h3>
<div class="chart-container"><canvas id="sensorChart"></canvas></div>
</div>
</div>

<!-- Farm Setup -->
<div id="farmSetup" class="section">
<?php if($setup){ ?>
<div class="card">
<h3>Farm Setup</h3>
<table>
<tr><th>Phone</th><th>Email</th><th>Shed</th><th>Ideal Temp</th><th>Ideal Humidity</th></tr>
<tr>
<td><?= $setup['phone'] ?></td>
<td><?= $setup['email'] ?></td>
<td><?= $setup['shed_type'] ?></td>
<td><?= $setup['ideal_temp'] ?>°C</td>
<td><?= $setup['ideal_humidity'] ?>%</td>
</tr>
</table>
<button onclick="editSetup()">Edit Setup</button>
</div>
<?php } else { ?>
<div class="card">
<h3>Setup Your Farm</h3>
<form method="POST">
<input type="text" name="phone" placeholder="Phone" required>
<input type="email" name="email" placeholder="Email" required>
<select name="shed_type" required>
<option value="">Select Shed</option>
<option value="Chicks Shed">Chicks Shed</option>
<option value="Brooders Shed">Brooders Shed</option>
<option value="Adults Shed">Adults Shed</option>
</select>
<button type="submit" name="save_setup">Save</button>
</form>
</div>
<?php } ?>
</div>

<!-- Sensor History -->
<div id="sensorHistory" class="section">
<h2>Sensor History</h2>
<table>
<tr><th>Time</th><th>Temperature</th><th>Humidity</th></tr>
<?php foreach($sensorHistory as $row){ ?>
<tr>
<td><?= date("d M H:i",strtotime($row['created_at'])) ?></td>
<td><?= $row['temperature'] ?>°C</td>
<td><?= $row['humidity'] ?>%</td>
</tr>
<?php } ?>
</table>
</div>

<!-- Alerts -->
<div id="alerts" class="section">
<h2>Alerts History</h2>
<table>
<tr><th>Time</th><th>Message</th><th>Status</th></tr>
<?php foreach($alerts as $row){ ?>
<tr>
<td><?= date("d M H:i",strtotime($row['created_at'])) ?></td>
<td><?= $row['message'] ?></td>
<td><?= $row['status'] ?></td>
</tr>
<?php } ?>
</table>
</div>
</div>
</div>

<script>
// Sidebar toggle
function toggleSidebar(){
    const sidebar = document.getElementById('sidebar');
    sidebar.style.display = (sidebar.style.display==='none'||sidebar.style.display==='')?'block':'none';
}

// Show only selected section
function showSection(id){
    const sections = document.querySelectorAll('.section');
    sections.forEach(s => s.style.display='none');
    document.getElementById(id).style.display='block';
}

// Edit farm setup
function editSetup(){
    const farmDiv = document.getElementById('farmSetup');
    farmDiv.innerHTML = `
    <div class="card">
    <h3>Update Farm Setup</h3>
    <form method="POST">
    <input type="text" name="phone" placeholder="Phone" required>
    <input type="email" name="email" placeholder="Email" required>
    <select name="shed_type" required>
    <option value="">Select Shed</option>
    <option value="Chicks Shed">Chicks Shed</option>
    <option value="Brooders Shed">Brooders Shed</option>
    <option value="Adults Shed">Adults Shed</option>
    </select>
    <button type="submit" name="save_setup">Save</button>
    </form>
    </div>`;
}

// Chart.js
const ctx=document.getElementById('sensorChart').getContext('2d');
new Chart(ctx,{
type:'line',
data:{
labels: <?= json_encode($timeData) ?>,
datasets:[
{label:'Temperature', data:<?= json_encode($tempData) ?>, borderColor:'#ff5252', backgroundColor:'rgba(255,82,82,0.2)', fill:true, tension:0.4},
{label:'Humidity', data:<?= json_encode($humData) ?>, borderColor:'#2196f3', backgroundColor:'rgba(33,150,243,0.2)', fill:true, tension:0.4}
]
},
options:{ responsive:true, maintainAspectRatio:false }
});

// Show dashboard by default
showSection('dashboard');
</script>
</body>
</html> 
