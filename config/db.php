<?php
function connect() {
    $serverName = "LAPTOP-CPOISVPG\SQLEXPRESS";
    $connectionOptions = [
        "Database" => "tv_app_db",
        "Uid" => "sa",
        "PWD" => "ishom210404"
    ];

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["connection_error" => sqlsrv_errors()]);
        exit;
    }

    return $conn;
}