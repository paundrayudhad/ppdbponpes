<?php
if (!isset($_GET['id'])) {
    die("Parameter 'id' tidak tersedia.");
}

require "../../config/function.php";
$id_bayar = dekripsi($_GET['id']);
if (!$id_bayar) {
    die("ID tidak valid atau gagal didekripsi.");
}

require "../../config/database.php";
require "../../config/functions.crud.php";

$bayar = fetch($koneksi, 'bayar', ['id_bayar' => $id_bayar]);
if (!$bayar) {
    die("Data pembayaran tidak ditemukan.");
}

$siswa = fetch($koneksi, 'siswa', ['id_siswa' => $bayar['id_siswa']]);
if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}

$setting = fetch($koneksi, 'setting', ['id' => 1]);
$tgl_bayar = $bayar['tgl_bayar'];
$b_total = $bayar['jumlah'];

// Perbaikan keterangan
$keterangan_final = trim($bayar['keterangan']);
if (is_numeric($keterangan_final) && intval($keterangan_final) > 0) {
    $jenis = fetch($koneksi, 'jenis_pembayaran', ['id' => intval($keterangan_final)]);
    $keterangan_final = $jenis['nama_jenis'] ?? 'Belum diisi / Tidak diketahui';
} elseif ($keterangan_final === "0" || $keterangan_final === "" || strtolower($keterangan_final) === "null") {
    $keterangan_final = 'Belum diisi / Tidak diketahui';
}

ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bukti Pembayaran</title>
    <style>
        #tabel {
            font-size:15px;
            border-collapse:collapse;
        }
        #tabel td {
            padding-left:5px;
            border: 1px solid black;
        }
    </style>
</head>
<body style='font-family:tahoma; font-size:12pt;'>
<center>
    <table style='width:550px; font-size:8pt; font-family:calibri; border-collapse: collapse;' border='0'>
        <tr>
            <td width='70%' align='left' style='padding-right:80px; vertical-align:top'>
                <span style='font-size:12pt'><b><?= $setting['nama_sekolah'] ?? 'NAMA SEKOLAH' ?></b></span><br>
                Alamat <?= $setting['alamat'] ?? '-' ?><br>
                Telp : <?= $setting['no_telp'] ?? '-' ?>
            </td>
            <td style='vertical-align:top' width='30%' align='left'>
                <b><span style='font-size:12pt'>BUKTI PEMBAYARAN</span></b><br>
                No Trans. : <?= $id_bayar ?><br>
                Tanggal : <?= tgl_indo($tgl_bayar); ?><br>
            </td>
        </tr>
    </table>
    <br>
    <table cellspacing='0' style='width:550px; font-size:8pt; font-family:calibri; border-collapse: collapse;' border='1'>
        <tr>
            <td width='70%' align='left' style='padding-right:80px; vertical-align:top'>
                Nama Siswa : <?= $siswa['nama_siswa'] ?><br>
                No Daftar : <?= $siswa['no_daftar'] ?>
            </td>
            <td style='vertical-align:top' width='30%' align='left'>
                No HP : <?= $siswa['no_hp'] ?>
            </td>
        </tr>
    </table>
    <br>
    <table cellspacing='0' style='width:550px; font-size:8pt; font-family:calibri; border-collapse: collapse;' border='1'>
        <tr align='center'>
            <td width='15%'>No Transaksi</td>
            <td width='35%'>Jenis Pembayaran</td>
            <td width='13%'>Jumlah Bayar</td>
            <td width='7%'>Qty</td>
            <td width='10%'>Diskon</td>
            <td width='20%'>Total yang Dibayar</td>
        </tr>
        <tr>
            <td><?= $id_bayar ?></td>
            <td><?= $keterangan_final ?></td>
            <td style='text-align:right'><?= "Rp " . number_format($bayar['jumlah'], 2, ",", ".") ?></td>
            <td>1</td>
            <td>Rp0,00</td>
            <td style='text-align:right'><?= "Rp " . number_format($bayar['jumlah'], 2, ",", ".") ?></td>
        </tr>
        <tr>
            <td colspan='5' align='right'>Total yang Sudah Dibayar :</td>
            <td style='text-align:right'><?= "Rp " . number_format($bayar['jumlah'], 2, ",", ".") ?></td>
        </tr>
        <tr>
            <td colspan='6'><div style='text-align:right'>Terbilang : <b><?= terbilang($b_total); ?> Rupiah</b></div></td>
        </tr>
    </table>
    <br>
    <table cellspacing='0' style='width:550px; font-size:8pt; font-family:calibri; border-collapse: collapse;' border='1'>
        <tr>
            <td align='center'>Diterima Oleh,<br><br><br><u>(............)</u></td>
            <td style='border:1px solid black; padding:5px; text-align:left; width:30%'></td>
            <td align='center'>Pendaftar,<br><br><br><u>(<?= $siswa['nama_siswa'] ?>)</u></td>
        </tr>
    </table>
</center>
</body>
</html>

<?php
$html = ob_get_clean();
require_once '../../vendor/autoload.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("bukti_pembayaran.pdf", array("Attachment" => false));
exit(0);
?>
