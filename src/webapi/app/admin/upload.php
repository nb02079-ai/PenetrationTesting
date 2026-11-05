<?php
// === CASE 2: 파일 업로드 검증 누락 (T1505.003) 취약 버전 ===
// 취약점: 확장자/콘텐츠 검증 없이 원본 파일명 그대로 실행 가능한 /uploads 에 저장.
//   ※ 검증/시연은 플랜대로 "무해한 마커 파일"로만. 실제 악성코드 금지.
require __DIR__ . '/../config.php';
require_login();
$me = current_user();
if ($me['role'] !== 'STAFF') { http_response_code(403); exit('403'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['doc'])) {
    header('Location: index.php?s=err&m=' . urlencode('파일을 선택하세요.'));
    exit;
}

$dir = __DIR__ . '/../uploads/';
if (!is_dir($dir)) { mkdir($dir, 0777, true); }

// *** 취약 지점: 확장자/MIME 검증 없음, 원본 파일명 사용 ***
$orig = basename($_FILES['doc']['name']);
$dest = $dir . $orig;
$mime = $_FILES['doc']['type'] ?? '';
$size = (int)($_FILES['doc']['size'] ?? 0);

if (move_uploaded_file($_FILES['doc']['tmp_name'], $dest)) {
    // uploaded_documents 메타데이터 기록 (검증 생략 → validation_status=ACCEPTED 로 무조건 저장)
    $log = $conn->prepare(
        'INSERT INTO uploaded_documents (uploaded_by, original_filename, stored_filename, mime_type, file_size, validation_status)
         VALUES (?,?,?,?,?,?)'
    );
    $stored = $orig; $status = 'ACCEPTED';
    $log->bind_param('isssis', $me['user_id'], $orig, $stored, $mime, $size, $status);
    $log->execute();
    log_api($conn, $me['user_id'], 'DOC_UPLOAD', 'DOCUMENT', null, 200, 'SUCCESS');
    header('Location: index.php?s=ok&m=' . urlencode('업로드 완료: /uploads/' . $orig));
} else {
    header('Location: index.php?s=err&m=' . urlencode('업로드 실패.'));
}
