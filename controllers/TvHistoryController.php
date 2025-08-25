<?php

class TvHistoryController {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function store($req, $res) {
        $sn_tv = trim($req['sn_tv'] ?? '');
        $date = trim($req['date'] ?? '');
        $app_name = trim($req['app_name'] ?? '');
        $app_url = trim($req['app_url'] ?? '');
        $app_duration = intval($req['app_duration'] ?? 0);
        $thumbnail = trim($req['thumbnail'] ?? '');
        $tv_duration = intval($req['tv_duration'] ?? 0);

        if (empty($sn_tv) || empty($date) || empty($app_name)) {
            return $res->json([
                'status' => 'error',
                'message' => 'Missing required fields.'
            ], 400);
        }

        $insertQuery = "INSERT INTO tv_history (sn_tv, date, app_name, app_url, app_duration, thumbnail, tv_duration)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = sqlsrv_prepare($this->db, $insertQuery, [$sn_tv, $date, $app_name, $app_url, $app_duration, $thumbnail, $tv_duration]);
        $success = sqlsrv_execute($stmt);

        if ($success) {
            return $res->json([
                'status' => 'success',
                'message' => 'TV history logged.',
                'data' => compact('sn_tv', 'date', 'app_name', 'app_url', 'app_duration', 'thumbnail', 'tv_duration')
            ], 201);
        } else {
            return $res->json([
                'status' => 'error',
                'message' => 'Failed to log TV history.',
                'sql_error' => sqlsrv_errors()
            ], 500);
        }
    }

    public function index($res) {
        $query = "SELECT * FROM tv_history";
        $stmt = sqlsrv_query($this->db, $query);

        $history = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $history[] = $row;
        }

        return $res->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}