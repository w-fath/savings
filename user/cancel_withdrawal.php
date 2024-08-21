<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $withdrawal_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Hapus data withdrawal berdasarkan id dan user_id
    $query = "DELETE FROM withdrawal WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $withdrawal_id, $user_id);

    if ($stmt->execute()) {
        // Redirect kembali ke halaman history dengan pesan sukses
        header("Location: history.php?status=success&message=Penarikan berhasil dibatalkan");
    } else {
        // Redirect kembali ke halaman history dengan pesan error
        header("Location: history.php?status=error&message=Gagal membatalkan penarikan");
    }
}
