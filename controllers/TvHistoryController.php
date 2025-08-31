<?php
class TvHistoryController {
    private $db;
    public function __construct($dbConnection) { $this->db = $dbConnection; }

    // GET /api/tv-history?npsn=...&sn_tv=...&date_from=YYYY-MM-DD hh:mm:ss&date_to=...
    public function index($req, $res) {
        $npsn = trim($req['npsn'] ?? '');
        $sn   = trim($req['sn_tv'] ?? '');
        $dateFrom = trim($req['date_from'] ?? '');
        $dateTo   = trim($req['date_to'] ?? '');

        if ($npsn === '' || $sn === '') {
            return $res->json(['status'=>'error','message'=>'npsn and sn_tv required'], 400);
        }

        $sql = "SELECT id, npsn, sn_tv, date, app_name, app_title, app_duration, tv_duration
                FROM tv_history
                WHERE npsn = ? AND sn_tv = ?";
        $params = [$npsn, $sn];

        if ($dateFrom !== '') { $sql .= " AND date >= ?"; $params[] = $dateFrom; }
        if ($dateTo   !== '') { $sql .= " AND date <= ?"; $params[] = $dateTo; }

        $sql .= " ORDER BY date DESC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if (!$stmt) {
            error_log('tv_history index error: '.print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error'], 500);
        }

        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($r as $k=>$v) {
                if ($v instanceof \DateTimeInterface) $r[$k] = $v->format('Y-m-d H:i:s');
            }
            $rows[] = $r;
        }
        sqlsrv_free_stmt($stmt);
        return $res->json(['status'=>'success','data'=>$rows], 200);
    }

    // POST /api/tv-history  (body includes npsn, sn_tv, date, app_name, app_title, app_duration, tv_duration)
    public function store($req, $res) {
        $npsn  = trim($req['npsn'] ?? '');
        $sn_tv = trim($req['sn_tv'] ?? '');
        $date  = trim($req['date'] ?? '');
        $app_name   = trim($req['app_name'] ?? '');
        $app_title  = trim($req['app_title'] ?? '');
        $app_dur    = (int)($req['app_duration'] ?? 0);
        $tv_dur     = (int)($req['tv_duration'] ?? 0);

        if ($npsn==='' || $sn_tv==='' || $date==='') {
            return $res->json(['status'=>'error','message'=>'npsn, sn_tv, date required'], 400);
        }

        $sql = "INSERT INTO tv_history (npsn, sn_tv, date, app_name, app_title, app_duration, tv_duration)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$npsn, $sn_tv, $date, $app_name, $app_title, $app_dur, $tv_dur];

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if (!$stmt) {
            error_log('tv_history store error: '.print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Insert failed'], 500);
        }
        sqlsrv_free_stmt($stmt);
        return $res->json(['status'=>'success'], 201);
    }
}
