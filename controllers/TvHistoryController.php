<?php
require_once __DIR__ . '/../models/TvHistory.php';

class TvHistoryController {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    public function index(array $query, $response) {
        $sn = $query['sn_tv'] ?? '';
        if ($sn === '') {
            return $response->json(['status'=>'error','message'=>'sn_tv required'], 400);
        }
        try {
            $rows = TvHistory::listBySn($this->conn, $sn, 200);
            return $response->json(['status'=>'success','data'=>$rows], 200);
        } catch (Throwable $e) {
            TvHistory::log('INDEX ERR: '.$e->getMessage());
            return $response->json(['status'=>'error','message'=>'server error'], 500);
        }
    }

    public function store(array $body, $response) {
        // Accept client-provided fields but validate strictly
        $sn  = trim((string)($body['sn_tv'] ?? ''));
        $app = trim((string)($body['app_name'] ?? ''));
        $url = trim((string)($body['app_url'] ?? ''));
        $thumb = (string)($body['thumbnail'] ?? '');
        $appDur = (int)($body['app_duration'] ?? 0);
        $tvDur  = (int)($body['tv_duration'] ?? 0);
        // date is set by server (GETDATE()), client value ignored

        TvHistory::log('STORE payload: '.json_encode($body, JSON_UNESCAPED_SLASHES));

        if ($sn==='' || $app==='' || $appDur <= 0) {
            return $response->json(['status'=>'error','message'=>'invalid payload'], 400);
        }

        try {
            TvHistory::insertRow($this->conn, [
                'sn_tv'        => $sn,
                'app_name'     => $app,
                'app_url'      => $url,
                'thumbnail'    => $thumb,
                'app_duration' => $appDur,
                'tv_duration'  => $tvDur
            ]);
            return $response->json(['status'=>'success'], 200);
        } catch (Throwable $e) {
            TvHistory::log('INSERT ERR: '.$e->getMessage());
            // Foreign key helpful hint
            if (str_contains($e->getMessage(), 'FOREIGN KEY') || str_contains($e->getMessage(), 'FK__tv_histor')) {
                return $response->json([
                    'status'=>'error',
                    'message'=>'sn_tv is not registered'
                ], 409);
            }
            return $response->json(['status'=>'error','message'=>'server error'], 500);
        }
    }
}
