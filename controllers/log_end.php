<?php
header("Content-Type: application/json");
$input = json_decode(file_get_contents("php://input"), true);

$sn_tv = $input["sn_tv"];
$app_name = $input["app_name"];
$end_time = $input["end_time"]; // timestamp

try {
    $conn = new PDO("sqlsrv:Server=localhost;Database=your_db", "username", "password");

    // Find the latest matching row
    $stmt = $conn->prepare("SELECT TOP 1 * FROM tv_history WHERE sn_tv = ? AND app_name = ? ORDER BY id DESC");
    $stmt->execute([$sn_tv, $app_name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $start_time = new DateTime($row["date"]);
        $end_time_obj = new DateTime($end_time);
        $duration = $end_time_obj->getTimestamp() - $start_time->getTimestamp();

        $update = $conn->prepare("UPDATE tv_history SET app_duration = ? WHERE id = ?");
        $update->execute([$duration, $row["id"]]);

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "No matching row found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>