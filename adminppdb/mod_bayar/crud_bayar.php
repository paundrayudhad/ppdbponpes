<?php
require("../../config/database.php");
require("../../config/function.php");
require("../../config/functions.crud.php");
session_start();

if (!isset($_SESSION['id_user'])) {
    die('Anda tidak diijinkan mengakses langsung');
}

// === UBAH PEMBAYARAN ===
if ($pg == 'ubah') {
    $verifikasi = isset($_POST['verifikasi']) ? 1 : 0;
    $data = [
        'nama_bayar' => $_POST['nama'],
        'verifikasi' => $verifikasi,
        'keterangan' => $_POST['keterangan']
    ];
    $id_bayar = $_POST['id_bayar'];
    $exec = update($koneksi, 'bayar', $data, ['id_bayar' => $id_bayar]);
    echo $exec;
}

// === TAMBAH PEMBAYARAN BARU ===
if ($pg == 'tambah') {
    $today = date("Ymd");

    $query = "SELECT max(id_bayar) AS last FROM bayar WHERE id_bayar LIKE '$today%'";
    $hasil = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_array($hasil);
    $lastNoTransaksi = $data['last'];
    $lastNoUrut = substr($lastNoTransaksi, 8, 4);
    $nextNoUrut = $lastNoUrut + 1;
    $nextNoTransaksi = $today . sprintf('%04s', $nextNoUrut);

    $data = [
        'id_bayar'    => $nextNoTransaksi,
        'id_siswa'    => $_POST['id'],
        'jumlah'      => str_replace(",", "", $_POST['jumlah']),
        'tgl_bayar'   => $_POST['tgl'],
        'keterangan'  => $_POST['keterangan'],
        'id_user'     => $_SESSION['id_user']
    ];
    $exec = insert($koneksi, 'bayar', $data);
    echo $exec;
}

// === HAPUS PEMBAYARAN ===
if ($pg == 'hapus') {
    $id_bayar = $_POST['id_bayar'];
    delete($koneksi, 'bayar', ['id_bayar' => $id_bayar]);
}

// === VERIFIKASI PEMBAYARAN ===
if ($pg == 'verifikasi') {
    $id_bayar = $_POST['id_bayar'];
    $data = ['verifikasi' => 1];

    $query = mysqli_query($koneksi, "SELECT * FROM bayar a 
                                     JOIN siswa b ON a.id_siswa = b.id_siswa 
                                     WHERE a.id_bayar = '$id_bayar'");
    while ($data0 = mysqli_fetch_array($query)) {
        $namaSiswa = $data0['nama_siswa'];
        $noHP = $data0['no_hp'];
        $id = $data0['id_bayar'];
    }

    $pesanWA  = "Assalamualaikum wr wb.\n";
    $pesanWA .= "Yth. *$namaSiswa*,\n";
    $pesanWA .= "Status Pembayaran Anda dengan No. Transaksi *$id_bayar* telah *DI-VERIFIKASI* oleh *Panitia PPDB*.\n\n";
    $pesanWA .= "*Bukti Pembayaran*: http://$_SERVER[HTTP_HOST]/ppdb/content/pdf2_kwitansi.php?id=$id\n\n";
    $pesanWA .= "Salam kami,\n*$setting[nama_sekolah]*\n";
    $pesanWA .= "$setting[klikchat]\n";

    $dataWA = [
        'api_key' => $setting['apikey'],
        'sender'  => $setting['sender'],
        'number'  => $noHP,
        'message' => $pesanWA,
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "$setting[server]/api/send_jadwal.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($dataWA)
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    echo $response;
    update($koneksi, 'bayar', $data, ['id_bayar' => $id_bayar]);
}

// === BATAL VERIFIKASI ===
if ($pg == 'batalverifikasi') {
    $id_bayar = $_POST['id_bayar'];
    $data = ['verifikasi' => 0];
    update($koneksi, 'bayar', $data, ['id_bayar' => $id_bayar]);
}
?>
