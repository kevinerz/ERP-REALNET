<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Define database credentials as constants
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u272457353_kevinsamsung99');
define('DB_PASSWORD', 'Admionkevin99');
define('DB_NAME', 'u272457353_umumdata');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in user's username
$username = $_SESSION['username'];

// Query to get employee data
$sql = "SELECT * FROM karyawan WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Fetch data
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    $error_message = "No data found for the logged-in user.";
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #84fab0 0%, #8fd3f4 100%);
            min-height: 100vh;
            padding-top: 20px;
        }
        .container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-header img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        .details-table th {
            width: 30%;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php else: ?>
            <div class="profile-header">
                <img src="<?php echo $row['avatar'] ?? 'path/to/default/avatar.jpg'; ?>" alt="Profile Picture" class="img-fluid">
                <h1><?php echo $row['nama']; ?></h1>
                <p class="text-muted"><?php echo $row['divisi']; ?></p>
            </div>
            <table class="table table-striped details-table">
                <tr>
                    <th>NIK</th>
                    <td><?php echo $row['nik']; ?></td>
                </tr>
                <tr>
                    <th>Nomor KK</th>
                    <td><?php echo $row['nomor_kk']; ?></td>
                </tr>
                <tr>
                    <th>SIM Type & Number</th>
                    <td><?php echo $row['tipe_nomor_sim']; ?></td>
                </tr>
                <tr>
                    <th>Jenis Kelamin</th>
                    <td><?php echo $row['jenis_kelamin']; ?></td>
                </tr>
                <tr>
                    <th>Tempat & Tanggal Lahir</th>
                    <td><?php echo $row['tempat_tanggal_lahir']; ?></td>
                </tr>
                <tr>
                    <th>Umur</th>
                    <td><?php echo $row['umur']; ?> tahun</td>
                </tr>
                <tr>
                    <th>Agama</th>
                    <td><?php echo $row['agama']; ?></td>
                </tr>
                <tr>
                    <th>Status Pernikahan</th>
                    <td><?php echo $row['status_pernikahan']; ?></td>
                </tr>
                <tr>
                    <th>No. Telepon</th>
                    <td><?php echo $row['no_telp']; ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo $row['email']; ?></td>
                </tr>
                <tr>
                    <th>Alamat</th>
                    <td><?php echo $row['alamat']; ?></td>
                </tr>
                <tr>
                    <th>Status Kepegawaian</th>
                    <td><?php echo $row['status_kepegawaian']; ?></td>
                </tr>
                <tr>
                    <th>Gaji</th>
                    <td>Rp <?php echo number_format($row['gaji'], 0, ',', '.'); ?></td>
                </tr>
            </table>
        <?php endif; ?>
    </div>
    <div class="container text-center">
        <a href="menu_teknisi.php" class="btn btn-danger">kembali</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>