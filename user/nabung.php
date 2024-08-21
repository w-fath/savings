<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/db.php';
date_default_timezone_set('Asia/Jakarta');
$logged_in_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = $_POST['jumlah'];
    $metode = $_POST['metode'];
    $status = 'Pending';
    $tanggal = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO nabung (user_id, jumlah, id_pembayaran, status, tanggal) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $logged_in_user_id, $jumlah, $metode, $status, $tanggal);

    if ($stmt->execute()) {
        $last_inserted_id = $conn->insert_id;
        $_SESSION['last_inserted_id'] = $last_inserted_id;
        $_SESSION['success'] = "Data berhasil ditambahkan!";
        header("Location: nabung.php?status=success");
        exit;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
        header("Location: nabung.php?status=error");
        exit;
    }
}

$query = "SELECT id, nama FROM metode";
$result = $conn->query($query);

// Ambil data untuk tampil data
$query = "SELECT nabung.id, nabung.jumlah, nabung.status, nabung.tanggal, metode.nama AS metode_pembayaran 
          FROM nabung 
          JOIN metode ON nabung.id_pembayaran = metode.id 
          WHERE nabung.user_id = ? 
          ORDER BY nabung.tanggal DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result_history = $stmt->get_result();

$options = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['nama']) . '</option>';
    }
} else {
    $options = '<option value="">Tidak ada metode yang tersedia</option>';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Tabungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                        <a class="nav-link" href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php#savings">Results</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="nabung.php">Savings</a>
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
                    <h3 class="card-title text-center mb-4">Menabung</h3>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="jumlah" class="form-label">Jumlah</label>
                            <input type="number" class="form-control" id="jumlah" name="jumlah" placeholder="Jumlah Nominal" required>
                        </div>
                        <div class="mb-3">
                            <label for="metode" class="form-label">Metode Pembayaran</label>
                            <select class="form-select" id="metode" name="metode" required>
                                <option value="" selected hidden>Pilih Pembayaran</option>
                                <?php echo $options; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Konfirmasi</button>
                        <div class="mb-3"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card savings-box mt-4 mx-1">
                    <h5 class="card-title text-center mb-4">History Tabungan</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Jumlah</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Ket.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_history->num_rows > 0): ?>
                                    <?php while ($row = $result_history->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>Rp <?php echo number_format(htmlspecialchars($row['jumlah']), 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($row['metode_pembayaran']); ?></td>
                                            <td>
                                                <?php
                                                $status = htmlspecialchars($row['status']);
                                                if ($status === 'Done') {
                                                    echo '<span class="badge bg-success">Done</span>';
                                                } elseif ($status === 'Pending') {
                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                } elseif ($status === 'Ditolak') {
                                                    echo '<span class="badge bg-danger">Ditolak</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td><?php echo date('H:i:s', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="color: white;">
                                                        Aksi
                                                    </button>
                                                    <ul class=" dropdown-menu">
                                                        <?php if ($row['status'] === 'Pending'): ?>
                                                            <li><a class="dropdown-item" href="pembayaran.php?id=<?php echo $row['id']; ?>">Transfer</a></li>
                                                            <li><a class="dropdown-item" href="detail_savings.php?id=<?php echo $row['id']; ?>">Batalkan</a></li>
                                                        <?php elseif ($row['status'] === 'Done'): ?>
                                                            <li><a class="dropdown-item" href="detail_savings.php?id=<?php echo $row['id']; ?>">Detail</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
            <?php if (isset($_SESSION['success'])): ?>
                Swal.fire({
                    title: '<?php echo $_SESSION['success']; ?>',
                    icon: 'success',
                    confirmButtonText: 'OK',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'pembayaran.php?id=<?php echo $_SESSION['last_inserted_id']; ?>';
                    }
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            // Notifikasi Error Input Data
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    title: '<?php echo $_SESSION['error']; ?>',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>