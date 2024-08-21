<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/db.php';
date_default_timezone_set('Asia/Jakarta');
$logged_in_user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jumlah = isset($_POST['jumlah']) ? intval($_POST['jumlah']) : 0;
    $nama_bank = isset($_POST['nama_bank']) ? trim($_POST['nama_bank']) : '';
    $no_bank = isset($_POST['no_bank']) ? trim($_POST['no_bank']) : '';

    if ($jumlah <= 0) {
        $errors[] = "Jumlah penarikan harus lebih dari nol.";
    }
    if (empty($nama_bank)) {
        $errors[] = "Nama bank harus diisi.";
    }
    if (empty($no_bank)) {
        $errors[] = "Nomor rekening bank harus diisi.";
    }

    // Jika tidak ada error, lakukan proses penarikan
    if (empty($errors)) {
        $status = 'Pending'; // Set status awal sebagai "Pending"
        $tanggal = date('Y-m-d H:i:s');

        $query = "INSERT INTO withdrawal (user_id, jumlah, status, nama_bank, no_bank, tanggal) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissss", $logged_in_user_id, $jumlah, $status, $nama_bank, $no_bank, $tanggal);

        if ($stmt->execute()) {
            $success_message = "Penarikan berhasil diajukan.";
        } else {
            $errors[] = "Terjadi kesalahan saat mengajukan penarikan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .logout {
            color: red;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Hai, <b><?php echo $_SESSION['fullname']; ?></b></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php#savings">Results</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="nabung.php">Savings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <p class="nav-link"></p>
                    </li>
                    <li class="nav-item">
                        <p class="nav-link"></p>
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
                    <h3 class="card-title text-center mb-4">Withdrawal</h3>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div id="successAlert" class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="withdrawal.php" method="POST">
                        <div class="mb-3">
                            <label for="jumlah" class="form-label">Jumlah Penarikan</label>
                            <input type="number" class="form-control" id="jumlah" name="jumlah" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_bank" class="form-label">Nama Bank Anda</label>
                            <input type="text" class="form-control" id="nama_bank" name="nama_bank" required>
                        </div>
                        <div class="mb-3">
                            <label for="no_bank" class="form-label">Nomor Rekening Bank Anda</label>
                            <input type="text" class="form-control" id="no_bank" name="no_bank" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajukan Penarikan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById('logoutLink').addEventListener('click', function(event) {
                event.preventDefault();
                Swal.fire({
                    title: 'Apakah Anda yakin ingin logout?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Logout',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../logout.php';
                    }
                });
            });
        });
    </script>
</body>

</html>