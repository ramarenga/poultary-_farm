```php
<?php
session_start();

/* ADMIN LOGIN PROTECTION */
if(!isset($_SESSION['admin_id'])){
header("Location: login.php");
exit;
}

/* DATABASE CONNECTIONS */

$hencare = new mysqli("localhost","root","","hencare",3307);
$iot = new mysqli("localhost","root","","iot_data",3307);

if($hencare->connect_error){
die("Hencare DB connection failed");
}

if($iot->connect_error){
die("IoT DB connection failed");
}

/* TOTAL WORKERS */

$totalWorkers = 0;

$r = $hencare->query("SELECT COUNT(*) AS total FROM workers");

if($r){
$row = $r->fetch_assoc();
$totalWorkers = $row['total'];
}

/* WORKERS LIST */

$workers = $hencare->query("SELECT id,name,email FROM workers ORDER BY id DESC");

/* LATEST SENSOR DATA */

$temperature="--";
$humidity="--";
$lastUpdate="--";

$sensor = $iot->query("SELECT temperature,humidity,created_at FROM sensor_data ORDER BY id DESC LIMIT 1");

if($sensor && $sensor->num_rows>0){
$data = $sensor->fetch_assoc();
$temperature = $data['temperature'];
$humidity = $data['humidity'];
$lastUpdate = $data['created_at'];
}

/* ANALYTICS */

$avgTemp="--";
$maxTemp="--";
$minTemp="--";

$analytics = $iot->query("
SELECT 
AVG(temperature) as avgT,
MAX(temperature) as maxT,
MIN(temperature) as minT
FROM sensor_data
");

if($analytics){
$a = $analytics->fetch_assoc();
$avgTemp = round($a['avgT'],1);
$maxTemp = $a['maxT'];
$minTemp = $a['minT'];
}

/* TOTAL SENSOR READINGS */

$totalReadings = 0;

$count = $iot->query("SELECT COUNT(*) as total FROM sensor_data");

if($count){
$crow = $count->fetch_assoc();
$totalReadings = $crow['total'];
}

/* TODAY'S SENSOR DATA */

$todayReadings=0;

$tod = $iot->query("
SELECT COUNT(*) as total 
FROM sensor_data 
WHERE DATE(created_at)=CURDATE()
");

if($tod){
$t=$tod->fetch_assoc();
$todayReadings=$t['total'];
}

/* TODAY MAX TEMP */

$todayMax="--";

$tm = $iot->query("
SELECT MAX(temperature) as maxT 
FROM sensor_data 
WHERE DATE(created_at)=CURDATE()
");

if($tm){
$m=$tm->fetch_assoc();
$todayMax=$m['maxT'];
}

/* TODAY MIN TEMP */

$todayMin="--";

$tn = $iot->query("
SELECT MIN(temperature) as minT 
FROM sensor_data 
WHERE DATE(created_at)=CURDATE()
");

if($tn){
$n=$tn->fetch_assoc();
$todayMin=$n['minT'];
}

/* SENSOR STATUS */

$sensorStatus="Offline";

if($lastUpdate!="--"){
$last = strtotime($lastUpdate);
$now = time();

if(($now-$last) < 300){
$sensorStatus="Online";
}
}

/* POULTRY COMFORT */

$poultryComfort="Unknown";

if($temperature!="--"){

if($temperature < 20){
$poultryComfort="Too Cold";
}
elseif($temperature <= 32){
$poultryComfort="Comfortable";
}
else{
$poultryComfort="Too Hot";
}

}

/* SENSOR HISTORY */

$history = $iot->query("
SELECT * FROM sensor_data 
ORDER BY id DESC 
LIMIT 10
");

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>HenCare Admin Dashboard</title>

<style>

*{
box-sizing:border-box;
font-family:Segoe UI;
}

body{
margin:0;
background:#f4f6f8;
}

.layout{
display:flex;
height:100vh;
}

.sidebar{
width:240px;
background:#1e293b;
color:white;
padding:20px;
display:flex;
flex-direction:column;
}

.sidebar h2{
text-align:center;
margin-bottom:30px;
}

.sidebar button{
background:none;
border:none;
color:white;
padding:12px;
margin-bottom:8px;
cursor:pointer;
text-align:left;
border-radius:8px;
}

.sidebar button:hover{
background:#334155;
}

.logout{
margin-top:auto;
text-align:center;
color:#ff7b7b;
text-decoration:none;
padding:10px;
}

.content{
flex:1;
padding:25px;
overflow:auto;
}

.page{
display:none;
}

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:20px;
margin-bottom:30px;
}

.card{
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 6px 20px rgba(0,0,0,0.08);
text-align:center;
}

.card h3{
color:#555;
}

.card p{
font-size:26px;
font-weight:bold;
}

table{
width:100%;
border-collapse:collapse;
background:white;
border-radius:10px;
overflow:hidden;
margin-bottom:30px;
}

th{
background:#e5e7eb;
padding:12px;
}

td{
padding:12px;
border-bottom:1px solid #eee;
text-align:center;
}

.online{
color:green;
font-weight:bold;
}

.offline{
color:red;
font-weight:bold;
}

</style>

</head>

<body>

<div class="layout">

<div class="sidebar">

<h2>🐔 HenCare</h2>

<button onclick="openPage('dashboard')">📊 Dashboard</button>
<button onclick="openPage('workers')">👷 Workers</button>
<button onclick="openPage('history')">📈 Sensor History</button>
<button onclick="openPage('farm')">🐔 Farm Status</button>
<button onclick="openPage('analytics')">📉 Analytics</button>
<a href="../worker/export_csv.php">
<button>Download CSV Report</button>
</a>
<a href="logout.php" class="logout">Logout</a>

</div>

<div class="content">

<!-- DASHBOARD -->

<section id="dashboard" class="page">

<h1>Admin Dashboard</h1>

<div class="cards">

<div class="card">
<h3>Total Workers</h3>
<p><?php echo $totalWorkers; ?></p>
</div>

<div class="card">
<h3>Temperature</h3>
<p><?php echo $temperature; ?> °C</p>
</div>

<div class="card">
<h3>Humidity</h3>
<p><?php echo $humidity; ?> %</p>
</div>

<div class="card">
<h3>Sensor Status</h3>
<p class="<?php echo strtolower($sensorStatus); ?>"><?php echo $sensorStatus; ?></p>
</div>

<div class="card">
<h3>Poultry Comfort</h3>
<p><?php echo $poultryComfort; ?></p>
</div>

</div>

</section>

<!-- FARM STATUS -->

<section id="farm" class="page">

<h1>Farm Status</h1>

<div class="cards">

<div class="card">
<h3>Environment</h3>
<p><?php echo $poultryComfort; ?></p>
</div>

<div class="card">
<h3>Last Sensor Update</h3>
<p><?php echo $lastUpdate; ?></p>
</div>

<div class="card">
<h3>Total Readings</h3>
<p><?php echo $totalReadings; ?></p>
</div>

</div>

</section>

<!-- ANALYTICS -->

<section id="analytics" class="page">

<h1>Analytics</h1>

<div class="cards">

<div class="card">
<h3>Today's Readings</h3>
<p><?php echo $todayReadings; ?></p>
</div>

<div class="card">
<h3>Highest Temp Today</h3>
<p><?php echo $todayMax; ?> °C</p>
</div>

<div class="card">
<h3>Lowest Temp Today</h3>
<p><?php echo $todayMin; ?> °C</p>
</div>

<div class="card">
<h3>Average Temp</h3>
<p><?php echo $avgTemp; ?> °C</p>
</div>

</div>

</section>

<!-- WORKERS -->

<section id="workers" class="page">

<h1>Workers</h1>

<table>

<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Action</th>
</tr>

<?php
if($workers){
while($row=$workers->fetch_assoc()){
?>

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>

<td>
<a href="edit_worker.php?id=<?php echo $row['id']; ?>">Edit</a> |
<a href="delete_worker.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this worker?')">
Delete
</a>
</td>

</tr>

<?php
}}
?>

</table>

</section>

<!-- SENSOR HISTORY -->

<section id="history" class="page">

<h1>Sensor History</h1>

<table>

<tr>
<th>ID</th>
<th>Temperature</th>
<th>Humidity</th>
<th>Time</th>
</tr>

<?php
if($history){
while($row=$history->fetch_assoc()){
?>

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['temperature']; ?> °C</td>
<td><?php echo $row['humidity']; ?> %</td>
<td><?php echo $row['created_at']; ?></td>

</tr>

<?php
}}
?>

</table>

</section>

</div>

</div>

<script>

function openPage(page){
document.querySelectorAll('.page').forEach(p=>p.style.display='none');
document.getElementById(page).style.display='block';
}

openPage('dashboard');

</script>

</body>
</html>
```
