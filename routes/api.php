<?php
// routes/api.php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../controllers/SchoolReferenceController.php';
require_once __DIR__.'/../controllers/SchoolRegistrationController.php';
require_once __DIR__.'/../controllers/TvHistoryController.php';

class Request {
    public string $method;
    public string $path;
    public array  $query;
    public array  $body;
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // 👇 Safe normalization so /api/thing/ behaves like /api/thing
        $this->path = ($rawPath !== '/' ? rtrim($rawPath, '/') : '/');
        $this->query  = $_GET ?? [];
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        if (is_array($json))         $this->body = $json;
        elseif (!empty($_POST))      $this->body = $_POST;
        else                         $this->body = $this->query; // fallback for quick tests
    }
}
class Response {
    public function json($data, int $code=200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type');
        echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }
}
if (($_SERVER['REQUEST_METHOD']??'')==='OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
    http_response_code(204); exit;
}

$conn = connect();
$req  = new Request();
$res  = new Response();

$path = $req->path;

// School reference
if ($req->method==='GET' && $path==='/api/school-reference') {
    (new SchoolReferenceController($conn))->lookupByNpsn($req->query, $res);
}

// Registration
elseif ($req->method==='POST' && $path==='/api/school-registration') {
    // FIX: call the correct method name
    (new SchoolRegistrationController($conn))->registerSchool($req->body, $res);
}
elseif ($req->method==='GET' && $path==='/api/check-registration') {
    (new SchoolRegistrationController($conn))->checkRegistration($req->query, $res);
}

// TV history
elseif ($req->method==='GET' && $path==='/api/tv-history') {
    (new TvHistoryController($conn))->index($req->query, $res);
}
elseif ($req->method==='POST' && $path==='/api/tv-history') {
    (new TvHistoryController($conn))->store($req->body, $res);
}
else {
    $res->json(['status'=>'error','message'=>'Route not found'],404);
}
