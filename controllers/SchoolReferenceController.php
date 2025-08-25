<?php
class SchoolReferenceController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function lookupByNpsn($query, $response) {
        if (!isset($query['npsn'])) {
            $response->json(["error" => "NPSN parameter is required"], 400);
            return;
        }

        $npsn = $query['npsn'];
        $sql = "SELECT * FROM school_reference WHERE npsn = ?";
        $stmt = sqlsrv_prepare($this->conn, $sql, [$npsn]);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            $response->json(["error" => "Database query failed"], 500);
            return;
        }

        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($result) {
            $response->json(["status" => "success", "data" => $result]);
        } else {
            $response->json(["error" => "School not found"], 404);
        }
    }
}
?>