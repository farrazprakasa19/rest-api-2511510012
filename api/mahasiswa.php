<?php

require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

$sqlSelect = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan
              FROM mahasiswa m LEFT JOIN jurusan j ON m.jurusan_id = j.id";

switch ($method) {

    case 'GET':
        // GET by ID
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $stmt = mysqli_prepare($koneksi, $sqlSelect . " WHERE m.id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            if (!$r) gagalQuery($koneksi);
            $data = mysqli_fetch_assoc($r);
            if (!$data) sendResponse(false, "mahasiswa tidak ditemukan", null, 404);
            sendResponse(true, "berhasil", $data, 200);
        }

        // GET search
        if (isset($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $stmt = mysqli_prepare($koneksi, $sqlSelect . " WHERE m.nama LIKE ? OR m.nim LIKE ? ORDER BY m.id DESC");
            mysqli_stmt_bind_param($stmt, "ss", $search, $search);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            if (!$r) gagalQuery($koneksi);
            $data = [];
            while ($row = mysqli_fetch_assoc($r)) { $data[] = $row; }
            sendResponse(true, "berhasil", $data, 200);
        }

        // GET all
        $r = mysqli_query($koneksi, $sqlSelect . " ORDER BY m.id DESC");
        if (!$r) gagalQuery($koneksi);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) { $data[] = $row; }
        sendResponse(true, "berhasil", $data, 200);
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])
            || trim((string)$input['nama']) === '' || trim((string)$input['nim']) === '') {
            sendResponse(false, "field nama, nim, jurusan_id tidak boleh kosong", null, 400);
        }
        $nama = trim((string) $input['nama']);
        $nim = trim((string) $input['nim']);
        $jurusan_id = (int) $input['jurusan_id'];

        // cek jurusan
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM jurusan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $jurusan_id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) == 0) sendResponse(false, "jurusan_id tidak ditemukan", null, 404);

        // cek duplikasi nim
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM mahasiswa WHERE nim = ?");
        mysqli_stmt_bind_param($stmt, "s", $nim);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) > 0) sendResponse(false, "nim sudah terdaftar", null, 409);

        // insert
        $stmt = mysqli_prepare($koneksi, "INSERT INTO mahasiswa (nama,nim,jurusan_id,created_at) VALUES (?,?,?,CURDATE())");
        mysqli_stmt_bind_param($stmt, "ssi", $nama, $nim, $jurusan_id);
        if (mysqli_stmt_execute($stmt)) {
            sendResponse(true, "data tersimpan", ["id" => mysqli_insert_id($koneksi)], 201);
        } else {
            gagalQuery($koneksi);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) sendResponse(false, "id wajib diisi pada url", null, 400);

        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])
            || trim((string)$input['nama']) === '' || trim((string)$input['nim']) === '') {
            sendResponse(false, "field nama, nim, jurusan_id tidak boleh kosong", null, 400);
        }
        $id = (int) $_GET['id'];
        $nama = trim((string) $input['nama']);
        $nim = trim((string) $input['nim']);
        $jurusan_id = (int) $input['jurusan_id'];

        // cek mahasiswa
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM mahasiswa WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) == 0) sendResponse(false, "mahasiswa tidak ditemukan", null, 404);

        // cek jurusan
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM jurusan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $jurusan_id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) == 0) sendResponse(false, "jurusan_id tidak ditemukan", null, 404);

        // cek nim duplikat (selain milik id ini)
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM mahasiswa WHERE nim = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $nim, $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) > 0) sendResponse(false, "nim sudah terdaftar", null, 409);

        // update
        $stmt = mysqli_prepare($koneksi, "UPDATE mahasiswa SET nama = ?, nim = ?, jurusan_id = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $nama, $nim, $jurusan_id, $id);
        if (mysqli_stmt_execute($stmt)) {
            sendResponse(true, "data berhasil diperbaharui", ["id" => $id], 200);
        } else {
            gagalQuery($koneksi);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) sendResponse(false, "id wajib diisi pada url", null, 400);
        $id = (int) $_GET['id'];

        $stmt = mysqli_prepare($koneksi, "SELECT id FROM mahasiswa WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (!$r) gagalQuery($koneksi);
        if (mysqli_num_rows($r) == 0) sendResponse(false, "mahasiswa tidak ditemukan", null, 404);

        $stmt = mysqli_prepare($koneksi, "DELETE FROM mahasiswa WHERE id = ?");
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
