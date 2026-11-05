<?php
// === CASE 2 조치(재검증) 버전: 확장자 허용목록 + 랜덤 파일명 + 웹루트 밖 저장 ===
require __DIR__ . '/../config.php';
require_login();
$me = current_user();
if ($me['role'] !== 'STAFF') { http_response_code(403); exit('403'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['doc'])) {
    header('Location: index.php?s=err&m=' . urlencode('파일을 선택하세요.'));
    exit;
}

$allow = ['pdf', 'png', 'jpg', 'jpeg', 'csv', 'xlsx', 'txt'];
$orig  = basename($_FILES['doc']['name']);
$ext   = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
$mime  = $_FILES['doc']['type'] ?? '';
$size  = (int)($_FILES['doc']['size'] ?? 0);

if (!in_array($ext, $allow, true) || $size > 10 * 1024 * 1024) {
    $log = $conn->prepare('INSERT INTO uploaded_documents (uploaded_by, original_filename, stored_filename, mime_type, file_size, validation_status) VALUES (?,?,?,?,?,?)');
    $none = ''; $rej = 'REJECTED';
    $log->bind_param('isssis', $me['user_id'], $orig, $none, $mime, $size, $rej);
    $log->execute();
    header('Location: index.php?s=err&m=' . urlencode('허용되지 않은 파일(형식/크기): .' . $ext));
    exit;
}

// 웹 루트 밖(비실행 경로)에 랜덤 이름으로 저장
$store = '/var/lib/logion-uploads/';
if (!is_dir($store)) { mkdir($store, 0750, true); }
$safe = bin2hex(random_bytes(8)) . '.' . $ext;

if (move_uploaded_file($_FILES['doc']['tmp_name'], $store . $safe)) {
    $log = $conn->prepare('INSERT INTO uploaded_documents (uploaded_by, original_filename, stored_filename, mime_type, file_size, validation_status) VALUES (?,?,?,?,?,?)');
    $acc = 'ACCEPTED';
    $log->bind_param('isssis', $me['user_id'], $orig, $safe, $mime, $size, $acc);
    $log->execute();
    log_api($conn, $me['user_id'], 'DOC_UPLOAD', 'DOCUMENT', null, 200, 'SUCCESS');
    header('Location: index.php?s=ok&m=' . urlencode('업로드 완료 (검증·격리 저장): ' . $safe));
} else {
    header('Location: index.php?s=err&m=' . urlencode('업로드 실패.'));
}
