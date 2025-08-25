<?php
require_once __DIR__ . '/../config/db.php';

class SchoolRegistration {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM school_registration ORDER BY registered_at DESC";
        $stmt = sqlsrv_query($conn, $sql);

        if ($stmt === false) {
            die(json_encode(["sql_error" => sqlsrv_errors()]));
        }

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    public static function create($NPSN, $name, $sn_tv) {
        global $conn;
        $sql = "INSERT INTO school_registration (NPSN, name, sn_tv, registered_at) VALUES (?, ?, ?, GETDATE())";
        $params = [$NPSN, $name, $sn_tv];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(json_encode(["sql_error" => sqlsrv_errors()]));
        }

        return true;
    }
}
?>