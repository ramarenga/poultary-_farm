<?php
session_start();

/* ---------- DB CONNECTION ---------- */
$host = "127.0.0.1";
$dbUser = "root";
$dbPass = "";
$dbName = "hencare";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName,3307);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

$message = "";

/* ---------- WORKER SIGNUP ---------- */
if (isset($_POST['signup'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$name || !$email || !$password) {
        $message = "Please fill all fields.";
    } else {

        $check = $conn->prepare("SELECT id FROM workers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "⚠️ Worker already exists!";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare(
                "INSERT INTO workers (name, email, password) VALUES (?, ?, ?)"
            );

            $insert->bind_param("sss", $name, $email, $hash);

            if ($insert->execute()) {
                $message = "✅ Registration successful. Please login.";
            } else {
                $message = "❌ Registration failed.";
            }

            $insert->close();
        }

        $check->close();
    }
}

/* ---------- WORKER LOGIN ---------- */
if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT id, name, password FROM workers WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {

        $worker = $res->fetch_assoc();

        if (password_verify($password, $worker['password'])) {

            $_SESSION['worker_id'] = $worker['id'];
            $_SESSION['worker_name'] = $worker['name'];

            header("Location: dashboard.php");
            exit;

        } else {
            $message = "❌ Incorrect password!";
        }

    } else {
        $message = "❌ Worker not found!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Worker Login | HENCARE</title>

    <style>
        /* -------- BODY & BACKGROUND -------- */
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../assets/worker.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(1.5px) brightness(0.95);
            z-index: -1;
        }

        /* Form box */
        .box {
            background: rgba(255, 255, 255, 0.85);
            padding: 30px;
            width: 360px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            text-align: center;
            z-index: 1;
        }

        /* -------- HEADINGS & MESSAGES -------- */
        h2 {
            color: #166534;
            margin-bottom: 20px;
        }

        .msg {
            color: #166534;
            margin-top: 10px;
            font-weight: 600;
        }

        /* -------- INPUTS -------- */
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        /* -------- BUTTONS -------- */
        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #15803d;
        }

        /* -------- LINKS -------- */
        .link {
            display: block;
            margin-top: 10px;
            color: #16a34a;
            cursor: pointer;
            text-decoration: underline;
        }

        /* -------- HIDDEN CLASS -------- */
        .hidden {
            display: none;
        }
    </style>
</head>

<body>

    <div class="box">

        <h2 id="formTitle">Worker Login</h2>

        <!-- LOGIN FORM -->
        <form method="POST" id="loginForm">
            <input type="email" name="email" placeholder="Worker Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button name="login">Login</button>

            <p class="msg"><?php echo htmlspecialchars($message); ?></p>

            <span class="link" onclick="toggleForms()">New worker? Register</span>
            <a href="../index.php" class="link">← Back</a>
        </form>

        <!-- SIGNUP FORM -->
        <form method="POST" id="signupForm" class="hidden">
            <input type="text" name="name" placeholder="Worker Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button name="signup">Register</button>

            <span class="link" onclick="toggleForms()">Already registered? Login</span>
        </form>

    </div>

    <script>
        function toggleForms() {
            document.getElementById("loginForm").classList.toggle("hidden");
            document.getElementById("signupForm").classList.toggle("hidden");

            document.getElementById("formTitle").innerText =
                document.getElementById("loginForm").classList.contains("hidden")
                    ? "Worker Registration"
                    : "Worker Login";
        }
    </script>

</body>
</html>
