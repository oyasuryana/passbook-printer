<?php
require 'vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

try {
    include('koneksi.php');

    $nasabah_id = '635';
    // Ambil SEMUA data transaksi (maksimal 30 baris untuk 2 halaman)
    $sql = mysqli_query($koneksi, "SELECT *, 
                                    concat(LPAD(day(tgl), 2, '0'), '-', LPAD(month(tgl), 2, '0'), '-', year(tgl)) as tgl_ind 
                                    FROM tb_transaksi 
                                    WHERE idUser='$nasabah_id'
                                    ORDER BY id ASC");

    $connector = new WindowsPrintConnector("EPSON PLQ-35");
    $printer = new Printer($connector);

    $printer->initialize();

    // SETTINGS DASAR
    $printer->getPrintConnector()->write("\x1B\x5B\x31\x68"); // Alignment
    $printer->getPrintConnector()->write("\x1B\x67");         // 15 CPI
    $printer->getPrintConnector()->write("\x1B\x32");         // Jarak baris 1/6"

    $marginDasar = 2; 
    $barisHalamanMaks = 34; // Contoh: 1 halaman berisi 15 baris transaksi
    $counterBaris = 1;

    // Loop melalui data transaksi
    while ($dataTabungan = mysqli_fetch_array($sql, MYSQLI_ASSOC)) {
        
        // Cek jika pindah ke halaman 2 (Baris 16)
        if ($counterBaris == $barisHalamanMaks + 1) {
            // Eject halaman pertama agar user bisa membalik buku
            $printer->getPrintConnector()->write("\x0C"); 
            $printer->close();
            
            echo "Halaman 1 selesai. Silakan balik buku ke Halaman 2, lalu refresh/lanjutkan.<br>";
            // Catatan: Dalam aplikasi web asli, Anda mungkin perlu membagi proses ini 
            // menjadi dua request atau memberikan jeda (pause).
            // Untuk script ini, kita asumsikan printer akan terus berjalan jika buku langsung dimasukkan.
            
            $connector = new WindowsPrintConnector("EPSON PLQ-35");
            $printer = new Printer($connector);
            $printer->initialize();
            $printer->getPrintConnector()->write("\x1B\x5B\x31\x68");
            $printer->getPrintConnector()->write("\x1B\x67");
            $printer->getPrintConnector()->write("\x1B\x32");
        }

        // Hitung posisi baris relatif terhadap halaman aktif
//        $barisAktif = ($counterBaris > $barisHalamanMaks) ? ($counterBaris - $barisHalamanMaks) : $counterBaris;
        
        // Pindah ke posisi baris tujuan
        // Jika ini baris baru di halaman baru, dia kembali ke atas + margin
  /*      if ($counterBaris == 1 || $counterBaris == $barisHalamanMaks + 1) {
            for ($i = 0; $i < ($marginDasar + ($barisAktif - 1)); $i++) {
                $printer->text("\n");
            }
        }
*/
        /* FORMAT DATA */
        $tgl    = $dataTabungan['tgl_ind'];
        $debet  = number_format($dataTabungan['setor'], 0, ',', '.');
        $kredit = number_format($dataTabungan['tarik'], 0, ',', '.');
        $saldo  = number_format($dataTabungan['salsettran'], 0, ',', '.');

        $line = str_pad($tgl, 12) . 
                str_pad($debet, 15, " ", STR_PAD_LEFT) . 
                str_pad($kredit, 20, " ", STR_PAD_LEFT) . 
                str_pad($saldo, 15, " ", STR_PAD_LEFT) . "\n";

        $printer->text($line);
        
        $counterBaris++;
    }

    /* EJECT AKHIR */
    $printer->getPrintConnector()->write("\x0C");
    $printer->close();

    echo "Pencetakan  baris berhasil.";

} catch (Exception $e) {
    echo "Gagal mencetak: " . $e->getMessage();
}