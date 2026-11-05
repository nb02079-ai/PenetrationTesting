<?php
// === CASE 1 조치(재검증) 버전: 서버 측 소유권 검사 추가 (shipment → order → user) ===
require __DIR__ . '/../config.php';
require_login();
$me = current_user();

$shipment_id = (int)($_POST['shipment_id'] ?? 0);
$new_address  = $_POST['new_address'] ?? '';
if ($shipment_id <= 0 || $new_address === '') {
    header('Location: ../dashboard.php?s=err&m=' . urlencode('배송 ID와 배송지를 입력하세요.'));
    exit;
}

// 소유권 확인: 이 배송이 세션 사용자의 주문에 속하는가 (STAFF 는 예외 허용)
if ($me['role'] === 'STAFF') {
    $own = $conn->prepare('SELECT delivery_address FROM shipments WHERE shipment_id = ?');
    $own->bind_param('i', $shipment_id);
} else {
    $own = $conn->prepare(
        'SELECT s.delivery_address
         FROM shipments s JOIN orders o ON o.order_id = s.order_id
         WHERE s.shipment_id = ? AND o.user_id = ?'
    );
    $own->bind_param('ii', $shipment_id, $me['user_id']);
}
$own->execute();
$row = $own->get_result()->fetch_assoc();

if (!$row) {
    // 비인가 변경 시도 — 감사 로그에 거부 기록
    log_api($conn, $me['user_id'], 'ADDRESS_UPDATE', 'SHIPMENT', $shipment_id, 403, 'DENIED');
    http_response_code(403);
    header('Location: ../dashboard.php?s=err&m=' . urlencode('본인 배송이 아니거나 존재하지 않습니다.'));
    exit;
}
$old = $row['delivery_address'];

$up = $conn->prepare('UPDATE shipments SET delivery_address = ?, updated_at = NOW() WHERE shipment_id = ?');
$up->bind_param('si', $new_address, $shipment_id);
$up->execute();

$h = $conn->prepare('INSERT INTO shipment_address_history (shipment_id, actor_user_id, old_address, new_address) VALUES (?,?,?,?)');
$h->bind_param('iiss', $shipment_id, $me['user_id'], $old, $new_address);
$h->execute();

log_api($conn, $me['user_id'], 'ADDRESS_UPDATE', 'SHIPMENT', $shipment_id, 200, 'SUCCESS');
header('Location: ../dashboard.php?s=ok&m=' . urlencode('배송 ' . $shipment_id . ' 배송지가 변경되었습니다.'));
