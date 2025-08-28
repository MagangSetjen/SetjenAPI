<?php
require_once __DIR__ . '/../models/TvHistory.php';

class TvHistoryController {
    private $db;
    private $model;

    public function __construct($dbConnection) {
        $this->db    = $dbConnection;
        $this->model = new TvHistory($dbConnection);
    }

    // GET /api/tv-history?npsn=XXXX&sn_tv=YYYY
    public function index($req, $res) {
        $npsn = strtoupper(trim($_GET['npsn']  ?? ''));
        $snTv = strtoupper(trim($_GET['sn_tv'] ?? ''));

        if ($npsn === '' || $snTv === '') {
            return $res->json([
                'status'  => 'error',
                'message' => 'npsn and sn_tv are required.'
            ], 400);
        }

        $rows = $this->model->listByDevice($npsn, $snTv, (int)($_GET['limit'] ?? 500));
        if ($rows === false) {
            return $res->json(['status'=>'error','message'=>'Database error.'], 500);
        }

        return $res->json(['status'=>'success','data'=>$rows], 200);
    }

    // POST /api/tv-history
    public function store($req, $res) {
        // Accept JSON body or regular POST form
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $in = is_array($json) ? $json : $_POST;

        // Expect: npsn, sn_tv, date, app_name, app_title, app_duration, tv_duration
        $npsn        = strtoupper(trim($in['npsn']        ?? ''));
        $snTv        = strtoupper(trim($in['sn_tv']       ?? ''));
        $date        = trim($in['date']                   ?? '');
        $appName     = trim($in['app_name']               ?? '');
        $appTitle    = trim($in['app_title']              ?? '');
        $appDuration = (int)($in['app_duration']          ?? 0);
        $tvDuration  = (int)($in['tv_duration']           ?? 0);

        if ($npsn === '' || $snTv === '' || $date === '' || $appName === '') {
            return $res->json([
                'status'  => 'error',
                'message' => 'npsn, sn_tv, date, app_name are required.'
            ], 400);
        }

        $ok = $this->model->insert([
            'npsn'         => $npsn,
            'sn_tv'        => $snTv,
            'date'         => $date,
            'app_name'     => $appName,
            'app_title'    => $appTitle,
            'app_duration' => $appDuration,
            'tv_duration'  => $tvDuration,
        ]);

        if (!$ok) {
            return $res->json(['status'=>'error','message'=>'Insert failed.'], 500);
        }

        return $res->json(['status'=>'success'], 200);
    }
}
