<?php
// index.php
// (No backend logic needed here yet)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>HENCARE | Choose Role</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      height: 100vh;
      background: url("assets/poul.jpg") no-repeat center/cover;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.55);
    }

    .wrapper {
      position: relative;
      z-index: 2;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      width: 90%;
      max-width: 900px;
    }

    .card {
      background: rgba(255,255,255,0.95);
      border-radius: 18px;
      padding: 35px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    }

    .left h1 {
      color: #1e3a8a;
      font-size: 36px;
      margin-bottom: 10px;
    }

    .left p {
      color: #475569;
      line-height: 1.6;
      margin-bottom: 25px;
    }

    .badge {
      display: inline-block;
      background: #e0f2fe;
      color: #0369a1;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      margin-bottom: 20px;
    }

    .right h2 {
      color: #0f172a;
      margin-bottom: 20px;
    }

    .role {
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 18px;
      margin-bottom: 18px;
      cursor: pointer;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .role:hover {
      background: #f1f5f9;
      transform: translateY(-2px);
    }

    .icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: #fff;
    }

    .admin { background: #2563eb; }
    .worker { background: #16a34a; }

    .role h3 {
      margin: 0;
      font-size: 18px;
    }

    .role p {
      margin: 4px 0 0;
      font-size: 14px;
      color: #64748b;
    }

    .buttons {
      margin-top: 25px;
      display: flex;
      gap: 15px;
    }

    .buttons button {
      flex: 1;
      padding: 12px;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      font-size: 15px;
      color: #fff;
    }

    .btn-admin { background: #2563eb; }
    .btn-worker { background: #16a34a; }

    @media(max-width: 768px) {
      .wrapper {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

<div class="overlay"></div>

<div class="wrapper">

  <!-- LEFT INFO CARD -->
  <div class="card left">
    <div class="badge">Smart Poultry System</div>
    <h1>Welcome to HENCARE</h1>
    <p>
      HENCARE is a smart poultry monitoring and control system that helps
      maintain optimal environmental conditions inside poultry farms.
      It supports role-based access for administrators and workers to
      ensure efficient farm management.
    </p>
    <p><b>Secure • Real-time • Scalable</b></p>
  </div>

  <!-- RIGHT ROLE CARD -->
  <div class="card right">
    <h2>Select Role</h2>

    <div class="role" onclick="goAdmin()">
      <div class="icon admin">A</div>
      <div>
        <h3>Admin</h3>
        <p>Farm owner or supervisor with full access</p>
      </div>
    </div>

    <div class="role" onclick="goWorker()">
      <div class="icon worker">W</div>
      <div>
        <h3>Worker</h3>
        <p>Poultry farm staff with limited access</p>
      </div>
    </div>

    <div class="buttons">
      <button class="btn-admin" onclick="goAdmin()">Admin Login</button>
      <button class="btn-worker" onclick="goWorker()">Worker Login</button>
    </div>
  </div>

</div>

<script>
  function goAdmin() {
    window.location.href = "admin/login.php";
  }

  function goWorker() {
    window.location.href = "worker/login.php";
  }
</script>

</body>
</html>
