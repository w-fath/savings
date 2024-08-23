<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/db.php';

date_default_timezone_set('Asia/Jakarta'); // Atau zona waktu yang sesuai

$id_nabung = $_GET['id'] ?? null;

if (!$id_nabung) {
    header("Location: dashboard.php");
    exit;
}

// Ambil data detail tabungan dari database
$query = "SELECT n.*, u.fullname, t.img, m.nama FROM nabung n 
JOIN users u ON n.user_id = u.id 
LEFT JOIN transfer t ON n.id = t.id_nabung 
JOIN metode m ON n.id_pembayaran = m.id 
WHERE n.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_nabung);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: dashboard.php");
    exit;
}

$detail_savings = $result->fetch_assoc();
$stmt->close();

// Pisahkan tanggal dan waktu
$datetime = new DateTime($detail_savings['tanggal']);
$formatted_date = $datetime->format('d-m-Y');
$formatted_time = $datetime->format('H:i:s');

// Ambil metode pembayaran
$formatted_metode = $detail_savings['nama'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tabungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .logout {
            color: red;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Hai, <b><?php echo $_SESSION['fullname']; ?></b></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php#savings">Hasil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="nabung.php">Tabungan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">Riwayat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Kontak</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout" href="../logout.php" id="logoutLink"><b>Logout</b></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card savings-box mt-4 mx-1">
                    <h3 class="card-title text-center mb-4">Detail Tabungan</h3>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Nama Pengguna</th>
                                <td><?php echo htmlspecialchars($detail_savings['fullname']); ?></td>
                            </tr>
                            <tr>
                                <th>Pembayaran</th>
                                <td><?php echo htmlspecialchars($formatted_metode); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td><?php echo htmlspecialchars($formatted_date); ?></td>
                            </tr>
                            <tr>
                                <th>Jam</th>
                                <td><?php echo htmlspecialchars($formatted_time); ?></td>
                            </tr>
                            <tr>
                                <th>Jumlah</th>
                                <td>Rp<?php echo number_format($detail_savings['jumlah'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><?php echo htmlspecialchars($detail_savings['status']); ?></td>
                            </tr>
                        </table>
                        <!-- Menampilkan gambar yang di-upload -->
                        <?php if (!empty($detail_savings['img'])): ?>
                            <div class="mt-4">
                                <h5>Gambar Bukti Transfer:</h5>
                                <img src="../uploads/<?php echo htmlspecialchars($detail_savings['img']); ?>" alt="Gambar Bukti Transfer" class="img-fluid" style="max-width: 300px; height: auto;">
                            </div>
                        <?php else: ?>
                            <p class="mt-4">Tidak ada gambar yang di-upload.</p>
                        <?php endif; ?>
                        <a href="javascript:history.back()" class="btn btn-primary mt-3">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('logoutLink').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Apakah Anda yakin ingin logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    </script>
</body>

</html>