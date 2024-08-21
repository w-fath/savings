<?php
// Token API Bot Telegram
$botToken = "6704739363:AAFUmXVkuoe4jnRHMm-sy-pHTOHo4p6fnBI";
$apiUrl = "https://api.telegram.org/bot$botToken/";

// Ambil data yang diterima dari Telegram
$update = file_get_contents('php://input');
$update = json_decode($update, true);

// Ambil pesan dan ID pengguna dari data yang diterima
$message = $update['message']['text'] ?? '';
$chatId = $update['message']['chat']['id'] ?? '';

// Kirim balasan ke pengguna
$responseText = "Pesan Anda telah diterima!";
file_get_contents($apiUrl . "sendMessage?chat_id=" . $chatId . "&text=" . urlencode($responseText));
