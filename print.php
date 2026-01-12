<?php
require 'vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

try {
    // 1. Koneksi Database
    include('koneksi.php');
    
    // Ambil ID dari URL
    $nasabah_id = mysqli_real_escape_string($koneksi, $_GET['idUser']);
    
    $sql = mysqli_query($koneksi, "SELECT *, 
                                    concat(LPAD(day(tgl), 2, '0'), '-', LPAD(month(tgl), 2, '0'), '-', year(tgl)) as tgl_ind 
                                    FROM tb_transaksi 
                                    WHERE idUser=$nasabah_id
                                    ORDER BY id DESC LIMIT 1");
    $dataTabungan = mysqli_fetch_array($sql, MYSQLI_ASSOC);

    if (!$dataTabungan) {
        die("Data tidak ditemukan.");
    }

    /* LOGIKA KELIPATAN 35 BARIS:
       Jika baris 35 -> tetap baris 35.
       Jika baris 36 -> jadi baris 1 (halaman baru).
       Jika baris 71 -> jadi baris 1 (halaman baru).
    */
    $barisDatabase = isset($dataTabungan['barisTransaksi']) ? (int)$dataTabungan['barisTransaksi'] : 1;
    
    // Menghitung posisi baris relatif terhadap halaman (1-35)
    $barisTujuan = $barisDatabase % 35;
    if ($barisTujuan == 0) { $barisTujuan = 35; } 

    // 2. Koneksi ke Printer
    $connector = new WindowsPrintConnector("EPSON PLQ-35");
    $printer = new Printer($connector);
    $printer -> initialize();

    /* --- STEP 1: SETTINGS (HURUF BESAR & ALIGNMENT) --- */
    $printer->getPrintConnector()->write("\x1B\x5B\x31\x68"); // Auto Alignment
    $printer -> getPrintConnector() -> write("\x1B\x67");// Set 15 CPI (Huruf Kecil)
    $printer->getPrintConnector()->write("\x1B\x32");         // Line Spacing 1/6"
    
    // Margin Kiri 2cm (sekitar 8 karakter pada 10 CPI)
    $printer->getPrintConnector()->write("\x1B\x6C\x04");

    /* --- STEP 2: POSISI MARGIN ATAS & BARIS --- */
    $marginAtasDasar = 2; // Jarak 2cm dari bibir atas buku
    $totalEnter = $marginAtasDasar + ($barisTujuan - 1);

    for ($i = 0; $i < $totalEnter; $i++) {
        $printer -> text("\n");
    }

    /* --- STEP 3: FORMAT DATA --- */
    $tgl    = $dataTabungan['tgl_ind'];
    $debet  = number_format($dataTabungan['setor'], 0, ',', '.');
    $kredit = number_format($dataTabungan['tarik'], 0, ',', '.');
    $saldo  = number_format($dataTabungan['salsettran'], 0, ',', '.');

    // Karena huruf besar (10 CPI), total karakter per baris lebih sedikit (sekitar 80-90 karakter)
    // Sesuaikan str_pad agar tidak melebihi lebar buku
    $line = str_pad($tgl, 2) . 
            str_pad($debet, 15, " ", STR_PAD_LEFT) . 
            str_pad($kredit, 18, " ", STR_PAD_LEFT) . 
            str_pad($saldo, 18, " ", STR_PAD_LEFT);

    $printer -> text($line);
    
    /* --- STEP 4: EJECT --- */
    $printer -> getPrintConnector() -> write("\x0C");

    $printer -> close();
    
    echo "Berhasil mencetak baris ke-$barisDatabase (Posisi fisik: Baris $barisTujuan)";
    if ($barisDatabase % 35 == 1 && $barisDatabase > 1) {
        echo "<br><strong>Peringatan:</strong> Data ini mencetak di baris pertama halaman baru!";
    }

} catch (Exception $e) {
    echo "Gagal mencetak: " . $e -> getMessage();
}
?>