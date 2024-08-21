<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/db.php';
date_default_timezone_set('Asia/Jakarta');
$logged_in_user_id = $_SESSION['user_id'];

// Query untuk menampilkan data dari tabel nabung
$query_nabung = "SELECT nabung.id, nabung.jumlah, nabung.status, nabung.tanggal 
                 FROM nabung 
                 WHERE nabung.user_id = ? 
                 ORDER BY nabung.tanggal DESC";

$stmt_nabung = $conn->prepare($query_nabung);
$stmt_nabung->bind_param("i", $logged_in_user_id);
$stmt_nabung->execute();
$result_nabung = $stmt_nabung->get_result();

// Query untuk menampilkan data dari tabel withdrawal
$query_withdrawal = "SELECT withdrawal.id, withdrawal.jumlah, withdrawal.status, withdrawal.tanggal 
                     FROM withdrawal 
                     WHERE withdrawal.user_id = ? 
                     ORDER BY withdrawal.tanggal DESC";

$stmt_withdrawal = $conn->prepare($query_withdrawal);
$stmt_withdrawal->bind_param("i", $logged_in_user_id);
$stmt_withdrawal->execute();
$result_withdrawal = $stmt_withdrawal->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Tabungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .mb-5 p.separator {
            margin: 1px 0;
            border-top: 1px dashed #ddd;
            border-bottom: 1px dashed #ddd;
        }
        .logout{
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
                        <a class="nav-link" href="nabung.php">Savings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="history.php">History</a>
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

    <section id="savings" class="py-5 bg-light text-center">
        <div class="container">
            <div class="row justify-content-center">
                <!-- Section for Savings -->
                <div class="col-md-5 savings-box mb-5 mx-1">
                    <h3 class="mb-5">Savings</h3>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_nabung->num_rows > 0): ?>
                                    <?php while ($row = $result_nabung->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>Rp <?php echo number_format(htmlspecialchars($row['jumlah']), 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Aksi
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($row['status'] === 'Pending'): ?>
                                                            <li><a class="dropdown-item" href="pembayaran.php?id=<?php echo $row['id']; ?>">Transfer</a></li>
                                                            <li><a class="dropdown-item cancel-nabung" href="#" data-id="<?php echo $row['id']; ?>">Batal</a></li>
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
                                        <td colspan="5" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section for Withdrawal -->
                <div class="col-md-5 savings-box mb-5 mx-1">
                    <h3 class="mb-5">Withdrawal</h3>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_withdrawal->num_rows > 0): ?>
                                    <?php while ($row = $result_withdrawal->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>Rp <?php echo number_format(htmlspecialchars($row['jumlah']), 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Aksi
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($row['status'] === 'Pending'): ?>
                                                            <li><a class="dropdown-item cancel-withdrawal" href="#" data-id="<?php echo $row['id']; ?>">Batal</a></li>
                                                            <li><a class="dropdown-item" href="detail_withdrawal.php?id=<?php echo $row['id']; ?>">Detail</a></li>
                                                        <?php elseif ($row['status'] === 'Done'): ?>
                                                            <li><a class="dropdown-item" href="detail_withdrawal.php?id=<?php echo $row['id']; ?>">Detail</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Konfirmasi sebelum logout
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

            // Konfirmasi sebelum membatalkan withdrawal
            const cancelWithdrawalButtons = document.querySelectorAll('.cancel-withdrawal');
            cancelWithdrawalButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const withdrawalId = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Apakah kamu yakin ingin membatalkan Penarikan?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Iya, Batalkan',
                        cancelButtonText: 'Tidak'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to PHP script to handle the deletion
                            window.location.href = 'cancel_withdrawal.php?id=' + withdrawalId;
                        }
                    });
                });
            });

            // Konfirmasi sebelum membatalkan nabung
            const cancelNabungButtons = document.querySelectorAll('.cancel-nabung');
            cancelNabungButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const nabungId = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Apakah kamu yakin ingin membatalkan Nabung?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Iya, Batalkan',
                        cancelButtonText: 'Tidak'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to PHP script to handle the cancellation
                            window.location.href = 'cancel_nabung.php?id=' + nabungId;
                        }
                    });
                });
            });
        });
    </script>

</body>

</html>