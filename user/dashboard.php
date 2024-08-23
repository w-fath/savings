<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/db.php';
date_default_timezone_set('Asia/Jakarta');
$logged_in_user = $_SESSION['fullname'];

// Ambil data pengguna
$stmt = $conn->prepare("SELECT fullname FROM users");
$stmt->execute();
$result = $stmt->get_result();

$other_users = [];

while ($user = $result->fetch_assoc()) {
    if ($user['fullname'] !== $logged_in_user) {
        $other_users[] = $user['fullname'];
    }
}

// Ambil ID pengguna yang sedang login
$stmt = $conn->prepare("SELECT id FROM users WHERE fullname = ?");
$stmt->bind_param("s", $logged_in_user);
$stmt->execute();
$result = $stmt->get_result();
$user_id = $result->fetch_assoc()['id'];

// Ambil total tabungan pengguna yang sedang login dengan status 'Done'
$stmt = $conn->prepare("
    SELECT SUM(jumlah) as total_amount 
    FROM nabung 
    WHERE user_id = ? 
    AND status = 'Done'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$logged_in_user_amount = $result->fetch_assoc()['total_amount'] ?? 0; // Gunakan 0 jika tidak ada data

// Ambil total penarikan pengguna yang sedang login
$stmt = $conn->prepare("
    SELECT SUM(jumlah) as total_withdrawn 
    FROM withdrawal 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_withdrawn = $result->fetch_assoc()['total_withdrawn'] ?? 0; // Gunakan 0 jika tidak ada data

// Hitung saldo akhir setelah dikurangi penarikan
$logged_in_user_amount -= $total_withdrawn;

// Ambil total tabungan untuk pengguna lain dengan status 'Done'
$other_users_amounts = [];
foreach ($other_users as $other_user) {
    // Ambil ID pengguna lain
    $stmt = $conn->prepare("SELECT id FROM users WHERE fullname = ?");
    $stmt->bind_param("s", $other_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_user_id = $result->fetch_assoc()['id'];

    // Ambil total tabungan untuk nama pengguna lain dengan status 'Done'
    $stmt = $conn->prepare("
        SELECT SUM(jumlah) as total_amount 
        FROM nabung 
        WHERE user_id = ? 
        AND status = 'Done'
    ");
    $stmt->bind_param("i", $other_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_users_amounts[$other_user] = $result->fetch_assoc()['total_amount'] ?? 0; // Gunakan 0 jika tidak ada data
}

// Hitung total tabungan semua pengguna
$total_all_users_amount = $logged_in_user_amount;
foreach ($other_users as $other_user) {
    $total_all_users_amount += $other_users_amounts[$other_user];
}

// Mengambil teks marquee
$query = "SELECT text FROM text WHERE id = 1";
$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $marqueeText = $row['text']; // Menyimpan teks dari database
} else {
    $marqueeText = "Default marquee text"; // Teks default jika query gagal
}
function terbilang($number)
{
    $number = intval($number);
    $words = [
        0 => 'Nol',
        1 => 'Satu',
        2 => 'Dua',
        3 => 'Tiga',
        4 => 'Empat',
        5 => 'Lima',
        6 => 'Enam',
        7 => 'Tujuh',
        8 => 'Delapan',
        9 => 'Sembilan',
        10 => 'Sepuluh',
        11 => 'Sebelas',
        12 => 'Dua Belas',
        13 => 'Tiga Belas',
        14 => 'Empat Belas',
        15 => 'Lima Belas',
        16 => 'Enam Belas',
        17 => 'Tujuh Belas',
        18 => 'Delapan Belas',
        19 => 'Sembilan Belas',
        20 => 'Dua Puluh',
        30 => 'Tiga Puluh',
        40 => 'Empat Puluh',
        50 => 'Lima Puluh',
        60 => 'Enam Puluh',
        70 => 'Tujuh Puluh',
        80 => 'Delapan Puluh',
        90 => 'Sembilan Puluh',
        100 => 'Seratus',
        1000 => 'Ribu',
        1000000 => 'Juta',
        1000000000 => 'Miliar'
    ];

    if ($number < 21) {
        return $words[$number];
    } elseif ($number < 100) {
        $tens = floor($number / 10) * 10;
        $units = $number % 10;
        return $words[$tens] . ($units ? ' ' . $words[$units] : '');
    } elseif ($number < 1000) {
        $hundreds = floor($number / 100);
        $remainder = $number % 100;
        return ($hundreds > 1 ? $words[$hundreds] : '') . ' Ratus' . ($remainder ? ' ' . terbilang($remainder) : '');
    } elseif ($number < 1000000) {
        $thousands = floor($number / 1000);
        $remainder = $number % 1000;
        return terbilang($thousands) . ' Ribu' . ($remainder ? ' ' . terbilang($remainder) : '');
    } elseif ($number < 1000000000) {
        $millions = floor($number / 1000000);
        $remainder = $number % 1000000;
        return terbilang($millions) . ' Juta' . ($remainder ? ' ' . terbilang($remainder) : '');
    } else {
        return 'Jumlah terlalu besar';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Tabungan</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .logout {
            color: red;
        }

        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
            width: 100%;
            color: #fff;
            position: absolute;
            top: 56px;
            left: 0;
            padding: 20px 0;
            z-index: 100;
        }

        .marquee-text {
            display: inline-block;
            position: absolute;
            white-space: nowrap;
            animation: marquee 20s linear infinite;
        }

        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100vw);
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="">Hai, <b><?php echo htmlspecialchars($logged_in_user); ?></b></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#savings">Results</a>
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

    <!-- Header + Tambahkan text animasi jalan dari kanan ke kiri-->
    <header class="bg-primary text-white text-center py-5 d-flex align-items-center min-vh-100">
        <div class="container">
            <div class="marquee-container">
                <div class="marquee-text">
                    <?php echo htmlspecialchars($marqueeText); ?>
                </div>
            </div>
            <h1 class="display-4">Manage Your Savings Efficiently</h1>
            <p class="lead">Track your savings and achieve your financial goals with ease.</p>
            <a href="nabung.php" class="btn btn-light btn-lg mt-4">Get Started</a>
        </div>
    </header>

    <!-- Notification -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if (!isset($_SESSION['notif_shown']) || $_SESSION['notif_shown'] !== true): ?>
                Swal.fire({
                    title: 'Selamat datang, <?php echo htmlspecialchars($logged_in_user); ?>',
                    text: 'Tetap semangat menabung demi masa depan!',
                    icon: 'info',
                    confirmButtonText: 'OK'
                }).then(function() {
                    <?php $_SESSION['notif_shown'] = true; ?>
                });
            <?php endif; ?>
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

    <!-- Savings Section -->
    <section id="savings" class="py-5 bg-light text-center">
        <div class="container">
            <h2 class="mb-5">Hasil Tabungan Kalian</h2>
            <div class="row justify-content-center">
                <div class="col-md-4 savings-box mb-5 mx-3">
                    <p><b><?php echo htmlspecialchars($logged_in_user); ?></b></p>
                    <h3>Rp <?php echo number_format($logged_in_user_amount, 0, ',', '.'); ?></h3>
                    <p><?php echo ucfirst(terbilang($logged_in_user_amount)); ?> Rupiah</p>
                    <b><a href="withdrawal.php" style="text-decoration: none;">Tarik Tunai</a></b>
                </div>

                <?php foreach ($other_users as $other_user) : ?>
                    <div class="col-md-4 savings-box mb-5 mx-3">
                        <p><b><?php echo htmlspecialchars($other_user); ?></b></p>
                        <h3>Rp <?php echo number_format($other_users_amounts[$other_user], 0, ',', '.'); ?></h3>
                        <p><?php echo ucfirst(terbilang($other_users_amounts[$other_user])); ?> Rupiah</p>
                        <?php if ($other_user == $logged_in_user) : ?>
                            <b><a href="" style="text-decoration: none;">Tarik Tunai</a></b>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="col-md-6 savings-box mb-5 mx-3">
                    <p>
                    <h5><b style="color: #555;">Total Tabungan</b></h5>
                    </p>
                    <h3>Rp <?php echo number_format($total_all_users_amount, 0, ',', '.'); ?></h3>
                    <p><?php echo ucfirst(terbilang($total_all_users_amount)); ?> Rupiah</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-dark text-white text-center py-4">
        <div class="container">
            <h2>Contact Us</h2>
            <p>Email: support@mysavings.com</p>
            <p>Phone: 123-456-7890</p>
            <p>&copy; 2024 MySavings. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</body>

</html>