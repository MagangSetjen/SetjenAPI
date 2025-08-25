<?php
function connect() {
    $serverName = "DESKTOP-IR2Q0I5\\SQLEXPRESS";
    $connectionOptions = [
        "Database" => "tv_app_db",
        "Uid" => "",
        "PWD" => ""
    ];

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["connection_error" => sqlsrv_errors()]);
        exit;
    }

    return $conn;
}