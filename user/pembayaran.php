<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/db.php';
date_default_timezone_set('Asia/Jakarta');
$logged_in_user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_metode = intval($_POST['id_metode']);
    $tanggal = date('Y-m-d H:i:s');
    $img = '';

    // Tangani unggahan file
    if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['img']['tmp_name'];
        $fileName = $_FILES['img']['name'];
        $fileSize = $_FILES['img']['size'];
        $fileType = $_FILES['img']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = '../uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $img = $newFileName;
            } else {
                $errors[] = 'Terjadi kesalahan saat memindahkan file yang diunggah.';
            }
        } else {
            $errors[] = 'Unggahan gagal. Jenis file yang diizinkan: ' . implode(', ', $allowedfileExtensions);
        }
    } else {
        $errors[] = 'Silakan unggah gambar.';
    }

    // Ambil id_nabung dari tabel nabung
    $id_nabung = null;
    $query = "SELECT id FROM nabung WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $logged_in_user_id);
    $stmt->execute();
    $stmt->bind_result($id_nabung);
    $stmt->fetch();
    $stmt->close();

    if (empty($id_nabung)) {
        $errors[] = 'ID tabungan tidak ditemukan. Pastikan Anda memiliki tabungan.';
    }

    // Masukkan ke database jika tidak ada kesalahan
    if (empty($errors)) {
        $query = "INSERT INTO transfer (user_id, id_metode, img, tanggal, id_nabung) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissi", $logged_in_user_id, $id_metode, $img, $tanggal, $id_nabung);

        if ($stmt->execute()) {
            $success = true;

            // Cek apakah img sudah terupload dan id_nabung sudah ada
            $queryCheck = "SELECT COUNT(*) FROM transfer WHERE id_nabung = ? AND img IS NOT NULL";
            $stmtCheck = $conn->prepare($queryCheck);
            $stmtCheck->bind_param("i", $id_nabung);
            $stmtCheck->execute();
            $stmtCheck->bind_result($imgCount);
            $stmtCheck->fetch();
            $stmtCheck->close();

            // Jika data img sudah ada, update status pada tabel nabung
            if ($imgCount > 0) {
                $queryUpdate = "UPDATE nabung SET status = 'Done' WHERE id = ?";
                $stmtUpdate = $conn->prepare($queryUpdate);
                $stmtUpdate->bind_param("i", $id_nabung);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            } else {
                $queryUpdate = "UPDATE nabung SET status = 'Panding' WHERE id = ?";
                $stmtUpdate = $conn->prepare($queryUpdate);
                $stmtUpdate->bind_param("i", $id_nabung);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }

            header("Location: pembayaran.php?success=1&id=$id_nabung");
            exit;
        } else {
            $errors[] = 'Gagal menyimpan transfer. Silakan coba lagi.';
        }
        $stmt->close();
    }
}

// Tangani pesan sukses
$success = isset($_GET['success']) && $_GET['success'] == 1;

// Ambil data metode pembayaran
$some_id = $_GET['id'] ?? null;
$nama_metode = "Metode pembayaran tidak ditemukan";
$no_metode = "Nomor tidak ditemukan";

if ($some_id !== null) {
    $query = "SELECT n.id_pembayaran, m.nama, m.nomor FROM nabung n JOIN metode m ON n.id_pembayaran = m.id WHERE n.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $some_id);
    $stmt->execute();
    $stmt->bind_result($id_metode, $nama_metode, $no_metode);
    $stmt->fetch();
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer</title>
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
            <a class="navbar-brand" href="">Hai, <b><?php echo $_SESSION['fullname']; ?></b></a>
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
                        <p class="nav-link"></p>
                    </li>
                    <li class="nav-item">
                        <p class="nav-link"></p>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout logout" href="../logout.php" id="logoutLink"><b>Logout</b></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card savings-box mt-4 mx-1">
                    <h3 class="card-title text-center mb-4">Transfer</h3>

                    <?php if ($success): ?>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    title: 'Transfer telah berhasil dicatat.',
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'history.php?id=<?php echo htmlspecialchars($id_metode); ?>';
                                    }
                                });
                            });
                        </script>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="pembayaran.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="metode" class="form-label">Metode Pembayaran</label>
                            <input type="text" name="nama_metode" id="nama_metode" class="form-control" value="<?php echo htmlspecialchars($nama_metode); ?>" readonly>
                            <input type="hidden" name="id_metode" value="<?php echo htmlspecialchars($id_metode); ?>">
                        </div>

                        <?php if ($id_metode == 3): ?>
                            <div class="mb-3">
                                <label for="qr_code" class="form-label">Scan QR Code</label><br>
                                <img src="../assets/qr/qr.jpg" alt="QR Code" class="qr-code img-fluid">
                            </div>
                            <style>
                                .qr-code {
                                    width: 150px;
                                }
                            </style>
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="no_metode" class="form-label">Rek Penerima</label>
                                <input type="text" name="no_metode" id="no_metode" class="form-control" value="<?php echo htmlspecialchars($no_metode); ?>" readonly>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="img" class="form-label">Unggah Bukti Pembayaran</label>
                            <input type="file" name="img" id="img" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Kirim</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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