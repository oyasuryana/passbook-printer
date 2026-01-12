<?php
require 'vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

try {
    include('koneksi.php');

    $nasabah_id = $_GET['idUser'];

    // 1. Query Data
    $sqlUser = mysqli_query($koneksi, "SELECT * FROM `tb_user` WHERE `id` = '$nasabah_id'");
    $dataUser = mysqli_fetch_array($sqlUser, MYSQLI_ASSOC);

    if (!$dataUser) { die("Data Nasabah tidak ditemukan."); }

    // 2. Inisialisasi Printer
    $connector = new WindowsPrintConnector("EPSON PLQ-35");
    $printer = new Printer($connector);
    $printer->initialize();

    /* --- STEP 1: PENGATURAN HURUF & MARGIN --- */
    
    // A. Aktifkan Alignment Otomatis
    $printer->getPrintConnector()->write("\x1B\x5B\x31\x68"); 

    // B. Ukuran Huruf Lebih Besar (10 CPI - Pica)
    // Mengganti \x1B\x67 (15 CPI) menjadi \x1B\x50 (10 CPI)
    $printer->getPrintConnector()->write("\x1B\x50"); 

    // C. Margin Kiri (sekitar 2 cm)
    // ESC l <n> : n adalah jumlah kolom. Pada 10 CPI, 1 cm kira-kira 4 kolom.
    // Untuk 2 cm, kita gunakan nilai 8.
    $printer->getPrintConnector()->write("\x1B\x6C\x08"); 

    // D. Margin Atas (sekitar 2 cm)
    // Kita gunakan feed baris manual. 1 inci = 6 baris standar (\x1B\x32).
    // 2 cm kira-kira adalah 5 baris kosong.
    $printer->text(str_repeat("\n", 5));

    /* --- STEP 2: CETAK IDENTITAS NASABAH --- */
    
    // Opsional: Membuat Judul Bold (Tebal) agar terlihat lebih besar/tegas
    $printer->getPrintConnector()->write("\x1B\x45"); // Bold On
    $printer->text("IDENTITAS NASABAH\n");
    $printer->getPrintConnector()->write("\x1B\x46"); // Bold Off
    
    $printer->text(str_repeat("-", 30) . "\n"); // Garis pembatas
    
    $printer->text("Nomor Rekening : " . $dataUser['username'] . "\n");
    $printer->text("Nama Nasabah   : " . $dataUser['nama'] . "\n");
    $printer->text("Tanggal Cetak  : " . date('d-m-Y') . "\n");
  
/*  
	$printer->text("\n");
    $printer->text(str_repeat("\n", 25));

    $printer->text("Petugas, \n");
	$printer->text("\n");
	$printer->text("\n");
	$printer->text("\n");
    $printer->text("____________________, \n");
*/
    /* --- STEP 3: EJECT --- */
    $printer->getPrintConnector()->write("\x0C");

    $printer->close();
    echo "Pencetakan Identitas Berhasil!";
    
} catch (Exception $e) {
    echo "Gagal mencetak: " . $e->getMessage();
}