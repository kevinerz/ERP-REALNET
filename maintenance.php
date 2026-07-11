<?php
    // Set the page header
    header('Content-Type: text/html; charset=utf-8');
    // Include the navbar
include('navbar.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situs Sedang Dalam Perawatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .maintenance-container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            background-color: #f7f7f7;
        }
        .maintenance-message {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .maintenance-message h1 {
            font-size: 36px;
            color: #ff5733;
        }
        .maintenance-message p {
            font-size: 18px;
            color: #555;
        }
        .maintenance-message a {
            font-size: 18px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="maintenance-container">
    <div class="maintenance-message">
        <h1>Situs Sedang Dalam Perawatan</h1>
        <p>Mohon maaf, situs ini sedang dalam perawatan dan akan segera kembali online.</p>
        <p>Silakan coba lagi nanti.</p>
        <a href="mailto:support@example.com">Hubungi kami jika Anda membutuhkan bantuan.</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
