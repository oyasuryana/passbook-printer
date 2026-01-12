<?php
/* Buat koneksi dengan fungsi mysqli_connect(host,user,password,db_name) 
   dalam hal ini :
   host 	adalah localhost
   user 	adalah root
   password adalah kosong ('')
   db_name 	adalah db_mini_bank
   
   maka koneksinya adalah sbb disimpan dalam variabel $koneksi
   */

$koneksi=mysqli_connect('localhost','root','','bank-mini-smk');

// uji koneksi
// if($koneksi==true) { echo 'Sukses konek';} else { echo 'Gagal Konek';}
 
 //di browser jalankan   localhost/Mini-Bank/koneksi.php
?>