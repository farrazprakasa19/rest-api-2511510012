<?php

// Helper response JSON standar untuk seluruh endpoint
function sendResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data
    ]);
    exit();
}

// Helper error query: pesan error MySQL tidak dibocorkan ke client
function gagalQuery($koneksi) {
    error_log("MySQL error: " . mysqli_error($koneksi));
    sendResponse(false, "query gagal", null, 500);
}
