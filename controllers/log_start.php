<?php
header("Content-Type: application/json");
$input = json_decode(file_get_contents("php://input"), true);

$sn_tv = $input["sn_tv"];
$app_name = $input["app_name"];
$app_url = $input["app_url"];
$thumbnail = $input["thumbnail"];
$date = $input["date"]; // timestamp

try {
    $conn = new PDO("sqlsrv:Server=localhost;Database=your_db", "username", "password");
    $stmt = $conn->prepare("INSERT INTO tv_history (sn_tv, date, app_name, app_url, thumbnail) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$sn_tv, $date, $app_name, $app_url, $thumbnail]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>