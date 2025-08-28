<?php
class TvHistoryController {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // GET /api/tv-history?npsn=XXXXXX&sn_tv=YYYY
    public function index($q, $res) {
        $npsn = strtoupper(trim($q['npsn']  ?? ''));
        $sn   = strtoupper(trim($q['sn_tv'] ?? ''));

        if ($npsn === '' || $sn === '') {
            return $res->json(['status'=>'error','message'=>'npsn and sn_tv are required'], 400);
        }

        $sql = "SELECT id, npsn, sn_tv, [date], app_name, app_url, thumbnail, app_duration, tv_duration
                FROM tv_history
                WHERE npsn = ? AND sn_tv = ?
                ORDER BY [date] DESC, id DESC";
        $stmt = sqlsrv_prepare($this->db, $sql, [$npsn, $sn]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            error_log('tv_history index failed: '.print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'db error'], 500);
        }
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($r as $k=>$v) if ($v instanceof \DateTimeInterface) $r[$k] = $v->format('Y-m-d H:i:s');
            $rows[] = $r;
        }
        sqlsrv_free_stmt($stmt);

        return $res->json(['status'=>'success','data'=>$rows], 200);
    }

    // POST /api/tv-history  (body can include npsn, and must include sn_tv, date, etc.)
    public function store($body, $res) {
        // accept JSON or form
        $data = is_array($body) ? $body : [];
        $npsn = strtoupper(trim($data['npsn']  ?? ''));   // optional but recommended
        $sn   = strtoupper(trim($data['sn_tv'] ?? ''));
        $date = trim($data['date'] ?? '');
        $app  = trim($data['app_name'] ?? '');
        $url  = trim($data['app_url'] ?? '');
        $thumb = trim($data['thumbnail'] ?? '');
        $app_dur = (int)($data['app_duration'] ?? 0);
        $tv_dur  = (int)($data['tv_duration'] ?? 0);

        if ($sn === '' || $date === '') {
            return $res->json(['status'=>'error','message'=>'sn_tv and date required'], 400);
        }

        // If npsn missing, try to resolve from school_registration by sn_tv
        if ($npsn === '') {
            $r = sqlsrv_prepare($this->db, 'SELECT TOP 1 NPSN FROM school_registration WHERE sn_tv = ?', [$sn]);
            if ($r && sqlsrv_execute($r)) {
                $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC);
                if ($row && !empty($row['NPSN'])) $npsn = strtoupper($row['NPSN']);
            }
            if ($r) sqlsrv_free_stmt($r);
        }

        // Insert with BOTH keys; tv_history has column npsn
        $ins = sqlsrv_prepare(
            $this->db,
            'INSERT INTO tv_history (npsn, sn_tv, [date], app_name, app_url, thumbnail, app_duration, tv_duration)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$npsn, $sn, $date, $app, $url, $thumb, $app_dur, $tv_dur]
        );
        if (!$ins || !sqlsrv_execute($ins)) {
            error_log('tv_history insert failed: '.print_r(sqlsrv_errors(), true));
            // If you still keep a unique constraint, 409 helps you notice duplicates
            return $res->json(['status'=>'error','message'=>'insert failed'], 500);
        }
        sqlsrv_free_stmt($ins);

        return $res->json(['status'=>'success'], 201);
    }
}
