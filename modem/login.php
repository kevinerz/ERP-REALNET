<?php
require_once "core/db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $q = $conn->prepare("SELECT * FROM karyawan WHERE username=? AND password=? LIMIT 1");
    $q->bind_param("ss", $user, $pass);
    $q->execute();
    $res = $q->get_result();

    if ($res->num_rows > 0) {
        $u = $res->fetch_assoc();
        $_SESSION['id_karyawan'] = $u['id'];
        $_SESSION['nama']        = $u['nama'];
        $_SESSION['divisi']      = $u['divisi'];

        header("Location: index.php");
        exit;
    } else {
        $error = "Username / Password salah!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width:400px;">
<div class="card p-4 shadow-sm">
<h4 class="mb-3 text-center">Login Sistem</h4>

<?php if(isset($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <button class="btn btn-primary w-100">Login</button>
</form>
</div>
</div>

</body>
</html>
