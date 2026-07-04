<?php
// ==========================================
// 1. KONFIGURASI & KONEKSI DATABASE
// ==========================================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "imigrasi_db"; 

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// --- OTOMATISASI PEMBUATAN TABEL (DIJALANKAN DI AWAL) ---
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pendaftaran (
    no_daftar VARCHAR(20) NOT NULL,
    nama_pemohon VARCHAR(100) NOT NULL,
    tgl_daftar DATE NOT NULL,
    hari_datang VARCHAR(20) NOT NULL,
    tgl_datang DATE NOT NULL,
    jam_datang TIME NOT NULL,
    PRIMARY KEY (no_daftar)
) ENGINE=InnoDB");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pengurusan (
    no_antrian INT NOT NULL AUTO_INCREMENT,
    no_daftar VARCHAR(20) NOT NULL,
    ktp VARCHAR(10) NOT NULL,
    kk VARCHAR(10) NOT NULL,
    ijazah_akta VARCHAR(10) NOT NULL,
    berkas VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    keterangan VARCHAR(50) NOT NULL,
    pembayaran INT NOT NULL,
    PRIMARY KEY (no_antrian)
) ENGINE=InnoDB");
// ------------------------------------------------

$page = isset($_GET['page']) ? $_GET['page'] : 'daftar';

// A. Proses Simpan Pendaftaran Baru (Tombol Simpan)
if (isset($_POST['simpan_daftar'])) {
    $nama_pemohon = mysqli_real_escape_string($conn, $_POST['nama_pemohon']);
    $tgl_daftar   = date('Y-m-d');
    $no_daftar    = "REG-" . date('His');

    $tgl_target = date('Y-m-d');
    while (true) {
        $query_kuota = "SELECT COUNT(*) as total FROM pendaftaran WHERE tgl_datang = '$tgl_target'";
        $res_kuota = mysqli_query($conn, $query_kuota);
        $data_kuota = mysqli_fetch_assoc($res_kuota);
        
        if ($data_kuota['total'] < 5) {
            break; 
        } else {
            $tgl_target = date('Y-m-d', strtotime($tgl_target . ' +1 day')); 
        }
    }

    $hari_array = array("Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu");
    $hari_datang = $hari_array[date('w', strtotime($tgl_target))];
    $tgl_datang  = $tgl_target;
    $jam_datang  = "08:00:00"; 

    $query_insert = "INSERT INTO pendaftaran (no_daftar, nama_pemohon, tgl_daftar, hari_datang, tgl_datang, jam_datang) VALUES ('$no_daftar', '$nama_pemohon', '$tgl_daftar', '$hari_datang', '$tgl_datang', '$jam_datang')";
    
    if (mysqli_query($conn, $query_insert)) {
        echo "<script>alert('Pendaftaran Berhasil!'); window.location='?page=daftar';</script>";
        exit();
    } else {
        die("Gagal menyimpan ke database: " . mysqli_error($conn));
    }
}

// B. Proses Update/Edit Data Pendaftaran
if (isset($_POST['update_daftar'])) {
    $no_daftar    = $_POST['no_daftar'];
    $nama_pemohon = mysqli_real_escape_string($conn, $_POST['nama_pemohon']);
    $tgl_daftar   = $_POST['tgl_daftar'];
    $tgl_datang   = $_POST['tgl_datang'];
    $jam_datang   = $_POST['jam_datang'];

    $hari_array  = array("Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu");
    $hari_datang = $hari_array[date('w', strtotime($tgl_datang))];

    $query_update = "UPDATE pendaftaran SET 
                        nama_pemohon = '$nama_pemohon', 
                        tgl_daftar = '$tgl_daftar', 
                        hari_datang = '$hari_datang', 
                        tgl_datang = '$tgl_datang', 
                        jam_datang = '$jam_datang' 
                     WHERE no_daftar = '$no_daftar'";
                     
    if (mysqli_query($conn, $query_update)) {
        echo "<script>alert('Data pendaftaran berhasil diperbarui!'); window.location='?page=daftar';</script>";
        exit();
    } else {
        die("Gagal memperbarui data: " . mysqli_error($conn));
    }
}

// C. Proses Hapus Data Pendaftaran
if (isset($_GET['action']) && $_GET['action'] == 'hapus') {
    $id_hapus = $_GET['id'];
    mysqli_query($conn, "DELETE FROM pengurusan WHERE no_daftar = '$id_hapus'");
    $query_hapus = "DELETE FROM pendaftaran WHERE no_daftar = '$id_hapus'";
    
    if (mysqli_query($conn, $query_hapus)) {
        echo "<script>alert('Data berhasil dihapus!'); window.location='?page=daftar';</script>";
        exit();
    } else {
        die("Gagal menghapus data: " . mysqli_error($conn));
    }
}

// D. Proses Validasi Berkas Fisik
if (isset($_POST['proses_berkas'])) {
    $no_daftar   = $_POST['no_daftar'];
    $ktp         = isset($_POST['ktp']) ? 'Ada' : 'Tidak';
    $kk          = isset($_POST['kk']) ? 'Ada' : 'Tidak';
    $ijazah_akta = isset($_POST['ijazah_akta']) ? 'Ada' : 'Tidak';

    if ($ktp == 'Ada' && $kk == 'Ada' && $ijazah_akta == 'Ada') {
        $berkas      = "Lengkap";
        $status      = "Diterima";
        $keterangan  = "OK";
        $pembayaran  = 355000; 
    } else {
        $berkas      = "Tidak Lengkap";
        $status      = "Ditolak";
        $keterangan  = "Tidak";
        $pembayaran  = 0;
    }

    $cek = mysqli_query($conn, "SELECT * FROM pengurusan WHERE no_daftar = '$no_daftar'");
    if (mysqli_num_rows($cek) > 0) {
        $query_update = "UPDATE pengurusan SET ktp='$ktp', kk='$kk', ijazah_akta='$ijazah_akta', berkas='$berkas', status='$status', keterangan='$keterangan', pembayaran='$pembayaran' WHERE no_daftar='$no_daftar'";
        mysqli_query($conn, $query_update);
    } else {
        $query_insert = "INSERT INTO pengurusan (no_daftar, ktp, kk, ijazah_akta, berkas, status, keterangan, pembayaran) VALUES ('$no_daftar', '$ktp', '$kk', '$ijazah_akta', '$berkas', '$status', '$keterangan', '$pembayaran')";
        mysqli_query($conn, $query_insert);
    }
    echo "<script>alert('Validasi berkas diproses!'); window.location='?page=daftar_ulang';</script>";
    exit();
}

// Ambil data lama untuk form edit jika parameter edit aktif
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $id_edit = $_GET['id'];
    $res_edit = mysqli_query($conn, "SELECT * FROM pendaftaran WHERE no_daftar = '$id_edit'");
    $edit_data = mysqli_fetch_assoc($res_edit);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aplikasi Pengajuan Paspor - Kantor Imigrasi</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background-color: #f0f4f8; color: #2d3748; }
        .header-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 15px; border-top: 5px solid #008080; }
        .navbar { background: #1a365d; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        .navbar a { color: #e2e8f0; text-decoration: none; margin-right: 20px; font-weight: bold; padding: 6px 12px; border-radius: 5px; transition: 0.3s; }
        .navbar a:hover { background: #2c5282; color: white; }
        .navbar a.active { background: #008080; color: white; text-decoration: none; }
        .container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; overflow: hidden; border-radius: 4px; }
        table, th, td { border: 1px solid #e2e8f0; padding: 12px; text-align: left; }
        th { background: #edf2f7; color: #4a5568; }
        tr:hover { background-color: #f7fafc; }
        .btn { background: #008080; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-blue { background: #2b6cb0; }
        .btn-orange { background: #dd6b20; }
        .btn-red { background: #e53e3e; }
        .btn:hover { opacity: 0.85; }
        .form-control { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; border: 1px solid #cbd5e0; border-radius: 6px; }
        .form-control:focus { outline: none; border-color: #008080; box-shadow: 0 0 0 3px rgba(0,128,128,0.1); }
        .checkbox-group { margin: 12px 0; font-size: 15px; line-height: 1.8; }
        .badge-ok { background: #319795; color: white; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .badge-no { background: #e53e3e; color: white; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .income-box { background: #ebf8ff; padding: 15px; border-left: 6px solid #2b6cb0; border-radius: 4px; font-size: 16px; margin-top: 15px; color: #2b6cb0; font-weight: bold; }
        .row-edit { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    </style>
</head>
<body>

    <div class="header-box">
        <h2>PENGAJUAN PASPOR</h2>
        <h3>Kantor Imigrasi Cabang</h3>
        <p><strong>Programmer:</strong> Jaka Oktavian</p>
    </div>

    <div class="navbar">
        <a href="?page=daftar" class="<?php echo $page == 'daftar' ? 'active' : ''; ?>">Daftar</a> | 
        <a href="?page=daftar_ulang" class="<?php echo $page == 'daftar_ulang' ? 'active' : ''; ?>">Daftar Ulang / Pengurusan</a>
    </div>

    <?php if ($page == 'daftar'): ?>
        <div class="container">
            <?php if ($edit_data): ?>
                <h3>Edit Pendaftaran Lengkap (<?php echo $edit_data['no_daftar']; ?>)</h3>
                <form action="?page=daftar" method="POST">
                    <input type="hidden" name="no_daftar" value="<?php echo $edit_data['no_daftar']; ?>">
                    
                    <label>Nama Pemohon:</label>
                    <input type="text" name="nama_pemohon" class="form-control" value="<?php echo $edit_data['nama_pemohon']; ?>" required>
                    
                    <div class="row-edit">
                        <div>
                            <label>Tanggal Daftar:</label>
                            <input type="date" name="tgl_daftar" class="form-control" value="<?php echo $edit_data['tgl_daftar']; ?>" required>
                        </div>
                        <div>
                            <label>Tanggal Datang (Jadwal):</label>
                            <input type="date" name="tgl_datang" class="form-control" value="<?php echo $edit_data['tgl_datang']; ?>" required>
                        </div>
                    </div>

                    <label>Jam Kedatangan:</label>
                    <input type="text" name="jam_datang" class="form-control" value="<?php echo $edit_data['jam_datang']; ?>" placeholder="Contoh: 08:00:00" required>
                    <small style="color: gray; display:block; margin-bottom:15px;">*Hari datang akan diperbarui otomatis oleh sistem.</small>
                    
                    <button type="submit" name="update_daftar" class="btn btn-orange">Update Data</button>
                    <a href="?page=daftar" class="btn" style="background: #718096;">Batal</a>
                </form>
            <?php else: ?>
                <h3>Input Pendaftaran</h3>
                <form action="?page=daftar" method="POST">
                    <label>Nama Pemohon:</label>
                    <input type="text" name="nama_pemohon" class="form-control" placeholder="Masukkan nama lengkap pemohon" required>
                    <button type="submit" name="simpan_daftar" class="btn">Simpan</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="container">
            <h3>Data Pendaftar (Jadwal Kedatangan Berdasarkan Kuota)</h3>
            <table>
                <thead>
                    <tr>
                        <th>No. Daftar</th>
                        <th>Nama Pemohon</th>
                        <th>Tgl Daftar</th>
                        <th>Hari Datang</th>
                        <th>Tanggal Datang</th>
                        <th>Jam</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM pendaftaran ORDER BY tgl_datang ASC, jam_datang ASC";
                    $result = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                    <td>{$row['no_daftar']}</td>
                                    <td>{$row['nama_pemohon']}</td>
                                    <td>{$row['tgl_daftar']}</td>
                                    <td>{$row['hari_datang']}</td>
                                    <td>{$row['tgl_datang']}</td>
                                    <td>{$row['jam_datang']} WIB</td>
                                    <td style='text-align: center;'>
                                        <a href='?page=daftar&action=edit&id={$row['no_daftar']}' class='btn btn-orange' style='padding: 4px 10px; font-size: 12px;'>Edit</a>
                                        <a href='?page=daftar&action=hapus&id={$row['no_daftar']}' class='btn btn-red' style='padding: 4px 10px; font-size: 12px;' onclick='return confirm(\"Apakah Anda yakin ingin menghapus data ini?\")'>Hapus</a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; color:gray;'>Belum ada data pendaftar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($page == 'daftar_ulang'): ?>
        <div class="container">
            <h3>Input Data Daftar Ulang & Pengurusan Berkas</h3>
            <form action="?page=daftar_ulang" method="POST">
                <label><strong>Pilih Pemohon (No. Daftar):</strong></label>
                <select name="no_daftar" class="form-control" required>
                    <option value="">-- Silahkan Pilih Dokumen Pemohon --</option>
                    <?php
                    $pendaftar = mysqli_query($conn, "SELECT no_daftar, nama_pemohon FROM pendaftaran");
                    while ($p = mysqli_fetch_assoc($pendaftar)) {
                        echo "<option value='{$p['no_daftar']}'>{$p['no_daftar']} - {$p['nama_pemohon']}</option>";
                    }
                    ?>
                </select>

                <div class="checkbox-group">
                    <label><strong>Checklist Berkas Fisik yang Dibawa:</strong></label><br>
                    <input type="checkbox" name="ktp" value="Ada"> KTP Elektronik Asli<br>
                    <input type="checkbox" name="kk" value="Ada"> Kartu Keluarga (KK) Asli<br>
                    <input type="checkbox" name="ijazah_akta" value="Ada"> Ijazah / Akta Kelahiran Asli<br>
                </div>
                
                <button type="submit" name="proses_berkas" class="btn btn-blue">Proses & Simpan Berkas</button>
            </form>
        </div>

        <div class="container">
            <h3>Data Pengurusan Paspor</h3>
            <table>
                <thead>
                    <tr>
                        <th>No. Antrian</th>
                        <th>No. Daftar</th>
                        <th>Nama Pemohon</th>
                        <th>Berkas</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Ambil akumulasi total pendapatan imigrasi dengan aman di dalam scope halaman
                    $query_pendapatan = "SELECT SUM(pembayaran) as total_masuk FROM pengurusan";
                    $res_pendapatan = mysqli_query($conn, $query_pendapatan);
                    $data_pendapatan = mysqli_fetch_assoc($res_pendapatan);
                    $total_pendapatan = $data_pendapatan['total_masuk'] ?? 0;

                    $sql = "SELECT p.no_daftar, p.nama_pemohon, o.no_antrian, o.berkas, o.status, o.keterangan, o.pembayaran 
                            FROM pendaftaran p 
                            LEFT JOIN pengurusan o ON p.no_daftar = o.no_daftar 
                            ORDER BY o.no_antrian ASC, p.no_daftar ASC";
                    $result = mysqli_query($conn, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $no_antrian  = (isset($row['no_antrian']) && $row['no_antrian']) ? sprintf("%03d", $row['no_antrian']) : "<em style='color:gray;'>Belum Verifikasi</em>";
                        $berkas      = $row['berkas'] ?? "-";
                        $status      = $row['status'] ?? "-";
                        
                        $keterangan  = "-";
                        if (isset($row['keterangan']) && $row['keterangan'] == "OK") {
                            $keterangan = "<span class='badge-ok'>OK</span>";
                        } elseif (isset($row['keterangan']) && $row['keterangan'] == "Tidak") {
                            $keterangan = "<span class='badge-no'>Tidak</span>";
                        }
                        
                        $pembayaran  = (isset($row['pembayaran']) && $row['pembayaran']) ? "Rp " . number_format($row['pembayaran'], 0, ',', '.') : "-";

                        echo "<tr>
                                <td><b>{$no_antrian}</b></td>
                                <td>{$row['no_daftar']}</td>
                                <td>{$row['nama_pemohon']}</td>
                                <td>{$berkas}</td>
                                <td>{$status}</td>
                                <td>{$keterangan}</td>
                                <td>{$pembayaran}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="income-box">
                <strong>TOTAL PENDAPATAN IMIGRASI:</strong> Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>