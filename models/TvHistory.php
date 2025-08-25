<?php
require_once __DIR__ . '/../config/db.php';

class TvHistory {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM tv_history";
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

    public static function create($sn_tv, $date, $app_name, $app_url, $app_duration, $thumbnail, $tv_duration) {
        global $conn;
        $sql = "INSERT INTO tv_history (sn_tv, date, app_name, app_url, app_duration, thumbnail, tv_duration)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$sn_tv, $date, $app_name, $app_url, $app_duration, $thumbnail, $tv_duration];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(json_encode(["sql_error" => sqlsrv_errors()]));
        }

        return true;
    }
}
?>