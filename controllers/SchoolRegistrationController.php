<?php
class SchoolRegistrationController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function registerSchool($req, $res) {
        $raw = file_get_contents('php://input');
        error_log('Raw JSON received: ' . $raw);
        $parsed = json_decode($raw, true);

        $data = is_array($req) ? $req : (is_array($parsed) ? $parsed : null);

        if (!is_array($data)) {
            return $res->json([
                'status' => 'error',
                'message' => 'Invalid JSON input.'
            ], 400);
        }

        $NPSN          = strtoupper(trim($data['npsn'] ?? ''));
        $school_name   = trim($data['nama_sekolah'] ?? '');
        $sn_tv         = strtoupper(trim($data['serial_number_tv'] ?? ''));
        $registered_at = date('Y-m-d H:i:s');

        error_log("REGISTER incoming sn_tv: $sn_tv");

        if (strlen($NPSN) !== 8 || $school_name === '' || $sn_tv === '') {
            return $res->json([
                'status'  => 'error',
                'message' => 'Invalid input. NPSN must be 8 characters, and all fields are required.'
            ], 400);
        }

        // Duplicate NPSN
        $checkNpsnStmt = sqlsrv_prepare($this->db, 'SELECT * FROM school_registration WHERE NPSN = ?', [$NPSN]);
        if (!$checkNpsnStmt || !sqlsrv_execute($checkNpsnStmt)) {
            error_log("NPSN check failed: " . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error during NPSN check.'], 500);
        }
        if (sqlsrv_fetch_array($checkNpsnStmt, SQLSRV_FETCH_ASSOC)) {
            return $res->json(['status'=>'warning','message'=>'NPSN sudah terdaftar.'], 200);
        }
        sqlsrv_free_stmt($checkNpsnStmt);

        // Duplicate school name
        $checkNameStmt = sqlsrv_prepare($this->db, 'SELECT * FROM school_registration WHERE school_name = ?', [$school_name]);
        if (!$checkNameStmt || !sqlsrv_execute($checkNameStmt)) {
            error_log("School name check failed: " . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error during school name check.'], 500);
        }
        if (sqlsrv_fetch_array($checkNameStmt, SQLSRV_FETCH_ASSOC)) {
            return $res->json(['status'=>'warning','message'=>'Nama sekolah sudah terdaftar.'], 200);
        }
        sqlsrv_free_stmt($checkNameStmt);

        // Duplicate serial
        $checkSnStmt = sqlsrv_prepare($this->db, 'SELECT * FROM school_registration WHERE sn_tv = ?', [$sn_tv]);
        if (!$checkSnStmt || !sqlsrv_execute($checkSnStmt)) {
            error_log("Serial check failed: " . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error during serial number check.'], 500);
        }
        if (sqlsrv_fetch_array($checkSnStmt, SQLSRV_FETCH_ASSOC)) {
            return $res->json(['status'=>'warning','message'=>'Serial number TV sudah terdaftar.'], 200);
        }
        sqlsrv_free_stmt($checkSnStmt);

        // Insert new registration
        $insertStmt = sqlsrv_prepare(
            $this->db,
            'INSERT INTO school_registration (NPSN, school_name, sn_tv, registered_at) VALUES (?, ?, ?, ?)',
            [$NPSN, $school_name, $sn_tv, $registered_at]
        );
        if (!$insertStmt || !sqlsrv_execute($insertStmt)) {
            error_log("Insert failed: " . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Failed to register school.'], 500);
        }
        sqlsrv_free_stmt($insertStmt);

        return $res->json([
            'status'  => 'success',
            'message' => 'School registered successfully.',
            'data'    => [
                'NPSN'          => $NPSN,
                'school_name'   => $school_name,
                'sn_tv'         => $sn_tv,
                'registered_at' => $registered_at
            ]
        ], 201);
    }

    public function checkRegistration($req, $res) {
        // Accept either sn_tv or serial_number_tv as query key
        $sn_tv = strtoupper(trim($_GET['sn_tv'] ?? $_GET['serial_number_tv'] ?? ''));
        error_log("CHECK incoming sn_tv: $sn_tv");

        if ($sn_tv === '') {
            return $res->json([
                'status'  => 'error',
                'message' => 'Missing serial number.'
            ], 400);
        }

        $stmt = sqlsrv_prepare(
            $this->db,
            'SELECT NPSN, school_name, sn_tv FROM school_registration WHERE sn_tv = ?',
            [$sn_tv]
        );
        if (!$stmt || !sqlsrv_execute($stmt)) {
            error_log("Check query failed: " . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error.'], 500);
        }

        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if ($result) {
            error_log("CHECK matched row: " . print_r($result, true));
            return $res->json([
                'status'     => 'success',
                'registered' => true,
                'data'       => $result
            ], 200);
        } else {
            return $res->json([
                'status'     => 'success',
                'registered' => false
            ], 200);
        }
    }

    /* =======================
       EXTRA GET HELPERS (ADD)
       ======================= */

    // List recent registrations
    public function getSchools($res) {
        $limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));
        $sql = "SELECT TOP $limit NPSN, school_name, sn_tv, registered_at
                FROM school_registration
                ORDER BY registered_at DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if (!$stmt) {
            error_log('getSchools failed: ' . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error.'], 500);
        }
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $k=>$v) {
                if ($v instanceof \DateTimeInterface) $row[$k] = $v->format('Y-m-d H:i:s');
            }
            $rows[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        return $res->json(['status'=>'success','count'=>count($rows),'data'=>$rows], 200);
    }

    // One registration by NPSN
    public function getSchoolByNpsn($res) {
        $npsn = strtoupper(trim($_GET['npsn'] ?? ''));
        if (strlen($npsn) !== 8) {
            return $res->json(['status'=>'error','message'=>'npsn (8 chars) required'], 400);
        }
        $stmt = sqlsrv_prepare($this->db,
            'SELECT TOP 1 NPSN, school_name, sn_tv, registered_at FROM school_registration WHERE NPSN = ?',
            [$npsn]
        );
        if (!$stmt || !sqlsrv_execute($stmt)) {
            error_log('getSchoolByNpsn failed: ' . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error.'], 500);
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        if ($row) {
            foreach ($row as $k=>$v) if ($v instanceof \DateTimeInterface) $row[$k] = $v->format('Y-m-d H:i:s');
            return $res->json(['status'=>'success','data'=>$row], 200);
        }
        return $res->json(['status'=>'success','data'=>null], 200);
    }

    // One registration by Serial Number
    public function getSchoolBySerial($res) {
        $sn = strtoupper(trim($_GET['sn_tv'] ?? $_GET['serial_number_tv'] ?? ''));
        if ($sn === '') {
            return $res->json(['status'=>'error','message'=>'sn_tv (or serial_number_tv) required'], 400);
        }
        $stmt = sqlsrv_prepare($this->db,
            'SELECT TOP 1 NPSN, school_name, sn_tv, registered_at FROM school_registration WHERE sn_tv = ?',
            [$sn]
        );
        if (!$stmt || !sqlsrv_execute($stmt)) {
            error_log('getSchoolBySerial failed: ' . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error.'], 500);
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        if ($row) {
            foreach ($row as $k=>$v) if ($v instanceof \DateTimeInterface) $row[$k] = $v->format('Y-m-d H:i:s');
            return $res->json(['status'=>'success','data'=>$row], 200);
        }
        return $res->json(['status'=>'success','data'=>null], 200);
    }

    // List all base tables (handy for testing)
    public function listTables($res) {
        $sql = "SELECT TABLE_SCHEMA, TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_SCHEMA, TABLE_NAME";
        $stmt = sqlsrv_query($this->db, $sql);
        if (!$stmt) {
            error_log('listTables failed: ' . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Database error.'], 500);
        }
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $r['TABLE_SCHEMA'].'.'.$r['TABLE_NAME'];
        }
        sqlsrv_free_stmt($stmt);
        return $res->json(['status'=>'success','tables'=>$rows], 200);
    }

    // Dump rows from any table safely (validated)
    public function getTable($res) {
        $raw = trim($_GET['table'] ?? '');
        if ($raw === '') return $res->json(['status'=>'error','message'=>'table parameter required'], 400);

        // validate "schema.table" or "table"
        if (!preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $raw)) {
            return $res->json(['status'=>'error','message'=>'invalid table name'], 400);
        }
        $parts  = explode('.', $raw, 2);
        $schema = count($parts) === 2 ? $parts[0] : 'dbo';
        $table  = count($parts) === 2 ? $parts[1] : $parts[0];

        // ensure table exists
        $chk = sqlsrv_prepare($this->db,
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [$schema, $table]
        );
        if (!$chk || !sqlsrv_execute($chk) || !sqlsrv_fetch($chk)) {
            return $res->json(['status'=>'error','message'=>'table not found'], 404);
        }
        sqlsrv_free_stmt($chk);

        // columns for safe order by
        $cols = [];
        $colStmt = sqlsrv_prepare($this->db,
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION", [$schema, $table]
        );
        if ($colStmt && sqlsrv_execute($colStmt)) {
            while ($c = sqlsrv_fetch_array($colStmt, SQLSRV_FETCH_ASSOC)) $cols[] = $c['COLUMN_NAME'];
            sqlsrv_free_stmt($colStmt);
        }
        if (!$cols) $cols = ['(SELECT NULL)']; // fallback

        $limit = max(1, min(500, (int)($_GET['limit'] ?? 50)));
        $sort  = $_GET['sort'] ?? $cols[0];
        if (!in_array($sort, $cols, true)) $sort = $cols[0];
        $dir   = strtoupper($_GET['dir'] ?? 'DESC');
        if (!in_array($dir, ['ASC','DESC'], true)) $dir = 'DESC';

        $sql = "SELECT TOP $limit * FROM [$schema].[$table] ORDER BY [$sort] $dir";
        $stmt = sqlsrv_query($this->db, $sql);
        if (!$stmt) {
            error_log('getTable failed: ' . print_r(sqlsrv_errors(), true));
            return $res->json(['status'=>'error','message'=>'Query error.'], 500);
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $k=>$v) if ($v instanceof \DateTimeInterface) $row[$k] = $v->format('Y-m-d H:i:s');
            $rows[] = $row;
        }
        sqlsrv_free_stmt($stmt);

        return $res->json([
            'status'=>'success',
            'table'=> "$schema.$table",
            'count'=> count($rows),
            'data'=> $rows
        ], 200);
    }
}
