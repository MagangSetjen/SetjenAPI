<?php
class TvHistory {
    public static function listBySn($conn, $sn, $limit = 200) {
        $sql = "SELECT TOP {$limit} id, sn_tv, [date], app_name, app_url, app_duration, thumbnail, tv_duration
                FROM tv_history
                WHERE sn_tv = ?
                ORDER BY id DESC";
        self::log('LIST SQL: '.$sql.' | sn='.$sn);
        $stmt = sqlsrv_prepare($conn, $sql, [$sn]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            self::throwLast();
        }
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // convert DateTime to string
            if (isset($r['date']) && $r['date'] instanceof DateTimeInterface) {
                $r['date'] = $r['date']->format('Y-m-d H:i:s');
            }
            $rows[] = $r;
        }
        return $rows;
    }

    public static function insertRow($conn, array $p) {
        $sql = "INSERT INTO tv_history (sn_tv, [date], app_name, app_url, app_duration, thumbnail, tv_duration)
                VALUES (?, GETDATE(), ?, ?, ?, ?, ?)";
        self::log('INSERT SQL: '.$sql.' | params='.json_encode($p));
        $stmt = sqlsrv_prepare($conn, $sql, [
            $p['sn_tv'], $p['app_name'], $p['app_url'], (int)$p['app_duration'], $p['thumbnail'], (int)$p['tv_duration']
        ]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            self::throwLast();
        }
    }

    private static function throwLast(): void {
        $errs = sqlsrv_errors(SQLSRV_ERR_ALL);
        $msg  = [];
        if ($errs) {
            foreach ($errs as $e) { $msg[] = "[{$e['SQLSTATE']}]{$e['code']} {$e['message']}"; }
        }
        $m = implode(' | ', $msg);
        self::log('SQL ERROR: '.$m);
        throw new RuntimeException($m ?: 'SQL error');
    }

    public static function log($txt): void {
        $line = '['.date('Y-m-d H:i:s').'] '.$txt.PHP_EOL;
        @file_put_contents(sys_get_temp_dir().'/tv_api.log', $line, FILE_APPEND);
    }
}
