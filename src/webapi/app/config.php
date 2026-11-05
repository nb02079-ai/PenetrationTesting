<?php
// LOGION 물류 포털 (WebAPI VM) - 원격 MariaDB 연결. 팀 ERD 스키마 기준.
// (교육용 격리 랩 전용)

// ★★★ 집에서 조립 시 여기만 MariaDB VM의 IP로 바꾸면 됨 ★★★
$DB_HOST = getenv('LOGION_DB_HOST') ?: '10.10.20.10';   // ← MariaDB VM IP (팀 확정값, 내부 서버망 LOGION-srv)
$DB_USER = 'logion';
$DB_PASS = 'logion_dev_pw';
$DB_NAME = 'logion';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('DB 연결 실패(' . $DB_HOST . '): ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return isset($_SESSION['user_id']) ? [
        'user_id'  => $_SESSION['user_id'],
        'login_id' => $_SESSION['login_id'],
        'role'     => $_SESSION['role'],
    ] : null;
}

function require_login() {
    if (!current_user()) {
        header('Location: index.php');
        exit;
    }
}

// api_audit_logs 기록 (Blue 가 CASE 1·3 를 분석하는 근거)
function log_api($conn, $actor_user_id, $action, $target_type, $target_id, $http_status, $result) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare(
        'INSERT INTO api_audit_logs (actor_user_id, action, target_type, target_id, source_ip, http_status, result)
         VALUES (?,?,?,?,?,?,?)'
    );
    // actor_user_id, target_id 는 NULL 허용
    $stmt->bind_param('issisis', $actor_user_id, $action, $target_type, $target_id, $ip, $http_status, $result);
    $stmt->execute();
}
