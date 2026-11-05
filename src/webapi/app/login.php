<?php
require __DIR__ . '/config.php';
$lid = $_POST['login_id'] ?? '';
$pw  = $_POST['password'] ?? '';

// 인증은 prepared statement + password_verify (취약점 대상 아님)
$stmt = $conn->prepare('SELECT user_id, login_id, password_hash, role, status FROM users WHERE login_id = ?');
$stmt->bind_param('s', $lid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res || !password_verify($pw, $res['password_hash'])) {
    header('Location: index.php?e=' . urlencode('아이디 또는 비밀번호가 올바르지 않습니다.'));
    exit;
}
// 비활성(DISABLED) 계정 차단 — 단, 잔존 계정은 ACTIVE 로 남아있어 통과됨 (CASE 3)
if ($res['status'] !== 'ACTIVE') {
    header('Location: index.php?e=' . urlencode('비활성화된 계정입니다.'));
    exit;
}

$_SESSION['user_id']  = (int)$res['user_id'];
$_SESSION['login_id'] = $res['login_id'];
$_SESSION['role']     = $res['role'];
header('Location: dashboard.php');
