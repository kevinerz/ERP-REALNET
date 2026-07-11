<!DOCTYPE html>
<html>
<head>
    <title>Form Reimburse BBM</title>
</head>
<body>
    <h2>Form Reimburse BBM</h2>
    <form action="simpan_reimburse.php" method="post">
        <label>Nama Pengaju (Leader Area):</label><br>
        <input type="text" name="nama_pengaju" required><br><br>

        <label>Tanggal:</label><br>
        <input type="date" name="tanggal" required><br><br>

        <label>Tujuan:</label><br>
        <input type="text" name="tujuan" required><br><br>

        <label>Total BBM (Liter):</label><br>
        <input type="number" name="liter" step="0.01" required><br><br>

        <label>Total Biaya (Rp):</label><br>
        <input type="number" name="total" step="0.01" required><br><br>

        <label>Catatan:</label><br>
        <textarea name="catatan" rows="3"></textarea><br><br>

        <button type="submit">Kirim Reimburse</button>
    </form>
</body>
</html>
