<?php
require_once __DIR__ . '/../config/db.php';
$conn = connect();

require_once __DIR__ . '/../controllers/TvHistoryController.php';
require_once __DIR__ . '/../controllers/SchoolRegistrationController.php';
require_once __DIR__ . '/../controllers/SchoolReferenceController.php';

$requestUri    = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$parsedInput   = json_decode(file_get_contents('php://input'), true) ?? [];

if (!$conn) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$response = new class {
    public function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
};

$tvController        = new TvHistoryController($conn);
$schoolController    = new SchoolRegistrationController($conn);
$referenceController = new SchoolReferenceController($conn);

/* -------- TV History -------- */
if ($requestUri === '/api/tv-history') {
    if ($requestMethod === 'GET')  { $tvController->index($response); }
    elseif ($requestMethod === 'POST') { $tvController->store($parsedInput, $response); }
    else { $response->json(['error'=>'Method not allowed'], 405); }
}

/* ---- School Registration ---- */
elseif ($requestUri === '/api/school-registration') {
    if     ($requestMethod === 'GET')  { $schoolController->getSchools($response); }
    elseif ($requestMethod === 'POST') { $schoolController->registerSchool($parsedInput, $response); }
    else { $response->json(['error'=>'Method not allowed'], 405); }
}
elseif ($requestUri === '/api/school-registration/by-npsn' && $requestMethod === 'GET') {
    $schoolController->getSchoolByNpsn($response);
}
elseif ($requestUri === '/api/school-registration/by-serial' && $requestMethod === 'GET') {
    $schoolController->getSchoolBySerial($response);
}
elseif ($requestUri === '/api/school-registration/tables' && $requestMethod === 'GET') {
    $schoolController->listTables($response);
}
elseif ($requestUri === '/api/school-registration/table' && $requestMethod === 'GET') {
    $schoolController->getTable($response);
}

/* ------ Check Registration --- */
elseif ($requestUri === '/api/check-registration' && $requestMethod === 'GET') {
    $schoolController->checkRegistration($_GET, $response);
}

/* ------- School Reference ---- */
elseif ($requestUri === '/api/school-reference' && $requestMethod === 'GET') {
    // keep this if you use reference data lookups
    $referenceController->lookupByNpsn($_GET, $response);
}

/* -------- Fallback ----------- */
else {
    $response->json(['error' => 'Route not found'], 404);
}
