<?php

require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

$sqlSelect = "SELECT id, kode_jurusan, nama_jurusan, fakultas FROM jurusan";

// validasi body JSON (dipakai POST & PUT)
function ambilInputJurusan() {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['kode_jurusan']) || !isset($input['nama_jurusan']) || !isset($input['fakultas'])
        || trim((string)$input['kode_jurusan']) === ''
        || trim((string)$input['nama_jurusan']) === ''
        || trim((string)$input['fakultas']) === '') {
        sendResponse(false, "field kode_jurusan, nama_jurusan, fakultas tidak boleh kosong", null, 400);
    }
    return [
        trim((string) $input['kode_jurusan']),
        trim((string) $input['nama_jurusan']),
        trim((string) $input['fakultas'])
    ];
}

switch ($method) {

    case 'GET':
        // GET by ID
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $stmt = mysqli_prepare($koneksi, $sqlSelect . " WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            if (!$r) gagalQuery($koneksi);
            $data = mysqli_fetch_assoc($r);
            if (!$data) sendResponse(false, "jurusan tidak ditemukan", null, 404);
            sendResponse(true, "berhasil", $data, 200);
        }

        // GET search (kode / nama / fakultas)
        if (isset($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $stmt = mysqli_prepare($koneksi, $sqlSelect . " WHERE kode_jurusan LIKE ? OR nama_jurusan LIKE ? OR fakultas LIKE ? ORDER BY id DESC");
            mysqli_stmt_bind_param($stmt, "sss", $search, $search, $search);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            if (!$r) gagalQuery($koneksi);
            $data = [];
            while ($row = mysqli_fetch_assoc($r)) { $data[] = $row; }
            sendResponse(true, "berhasil", $data, 200);
        }

        // GET all
        $r = mysqli_query($koneksi, $sqlSelect . " ORDER BY id DESC");
        if (!$r) gagalQuery($koneksi);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) { $data[] = $row; }
        sendResponse(true, "berhasil", $data, 200);
        break;

    case 'POST':
        list($kode, $nama, $fakultas) = ambilInputJurusan();

        // cek duplikasi kode_jurusan
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM jurusan WHERE kode_jurusan = ?");
        mysqli_stmt_bind_param($stmt, "s", $kode);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) > 0) sendResponse(false, "kode_jurusan sudah terdaftar", null, 409);

        $stmt = mysqli_prepare($koneksi, "INSERT INTO jurusan (kode_jurusan, nama_jurusan, fakultas) VALUES (?,?,?)");
        mysqli_stmt_bind_param($stmt, "sss", $kode, $nama, $fakultas);
        if (mysqli_stmt_execute($stmt)) {
            sendResponse(true, "data tersimpan", ["id" => mysqli_insert_id($koneksi)], 201);
        } else {
            gagalQuery($koneksi);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) sendResponse(false, "id wajib diisi pada url", null, 400);
        $id = (int) $_GET['id'];
        list($kode, $nama, $fakultas) = ambilInputJurusan();

        // cek jurusan ada
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM jurusan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) == 0) sendResponse(false, "jurusan tidak ditemukan", null, 404);

        // cek kode_jurusan duplikat (selain milik id ini)
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM jurusan WHERE kode_jurusan = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $kode, $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) > 0) sendResponse(false, "kode_jurusan sudah terdaftar", null, 409);

        $stmt = mysqli_prepare($koneksi, "UPDATE jurusan SET kode_jurusan = ?, nama_jurusan = ?, fakultas = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $kode, $nama, $fakultas, $id);
        if (mysqli_stmt_execute($stmt)) {
            sendResponse(true, "data berhasil diperbaharui", ["id" => $id], 200);
        } else {
            gagalQuery($koneksi);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) sendResponse(false, "id wajib diisi pada url", null, 400);
        $id = (int) $_GET['id'];

        // cek jurusan ada
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM jurusan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) == 0) sendResponse(false, "jurusan tidak ditemukan", null, 404);

        // jaga integritas FK: tolak jika masih dipakai mahasiswa / matakuliah
        $stmt = mysqli_prepare($koneksi,
            "SELECT (SELECT COUNT(*) FROM mahasiswa WHERE jurusan_id = ?) AS jml_mhs,
                    (SELECT COUNT(*) FROM matakuliah WHERE jurusan_id = ?) AS jml_mk");
        mysqli_stmt_bind_param($stmt, "ii", $id, $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        $cnt = mysqli_fetch_assoc($r);
        if ((int)$cnt['jml_mhs'] > 0 || (int)$cnt['jml_mk'] > 0) {
            sendResponse(false, "jurusan masih dipakai oleh mahasiswa/matakuliah", null, 409);
        }

        $stmt = mysqli_prepare($koneksi, "DELETE FROM jurusan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            sendResponse(true, "data berhasil dihapus", ["id" => $id], 200);
        } else {
            gagalQuery($koneksi);
        }
        break;

    default:
        sendResponse(false, "Method Not Allowed", null, 405);
        break;
}
