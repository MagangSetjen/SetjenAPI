<?php
class TvHistory {
    private $db;
    public function __construct($dbConnection) { $this->db = $dbConnection; }

    public function insert(array $row) {
        // Required keys: npsn, sn_tv, date, app_name, app_title, app_duration, tv_duration
        $sql = "INSERT INTO tv_history
                (npsn, sn_tv, date, app_name, app_title, app_duration, tv_duration)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $row['npsn'],
            $row['sn_tv'],
            $row['date'],
            $row['app_name'],
            $row['app_title'],
            (int)$row['app_duration'],
            (int)$row['tv_duration'],
        ];
        $stmt = sqlsrv_prepare($this->db, $sql, $params);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            error_log("TvHistory insert failed: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        sqlsrv_free_stmt($stmt);
        return true;
    }

    public function listByDevice(string $npsn, string $snTv, int $limit = 500) {
        $limit = max(1, min(1000, $limit));
        $sql = "SELECT TOP $limit id, npsn, sn_tv, date, app_name, app_title,
                       app_duration, tv_duration
                FROM tv_history
                WHERE npsn = ? AND sn_tv = ?
                ORDER BY id DESC";
        $stmt = sqlsrv_prepare($this->db, $sql, [$npsn, $snTv]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            error_log("TvHistory listByDevice failed: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // normalize DateTime to string
            if (isset($r['date']) && $r['date'] instanceof \DateTimeInterface) {
                $r['date'] = $r['date']->format('Y-m-d H:i:s');
            }
            $rows[] = $r;
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }
}
