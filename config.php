<?php

require_once __DIR__ . "/helpers/response.php";

mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$db = "db_mhsapi";

$koneksi = @mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    sendResponse(
        false,
        "Koneksi ke database gagal",
        null,
        500
    );
} 