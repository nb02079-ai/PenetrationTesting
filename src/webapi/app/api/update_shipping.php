<?php
// === CASE 1: BOLA (OWASP API1:2023 / T1565.001) 취약 버전 ===
// 취약점: 세션 사용자가 "그 배송(shipment)이 본인 주문에 속하는지" 서버에서 검증하지 않는다.
//         shipment_id 만 바꾸면 남의 배송지도 변경된다. (shipments.delivery_address)
require __DIR__ . '/../config.php';
require_login();
$me = current_user();

$shipment_id = (int)($_POST['shipment_id'] ?? 0);
$new_address  = $_POST['new_address'] ?? '';

if ($shipment_id <= 0 || $new_address === '') {
    header('Location: ../dashboard.php?s=err&m=' . urlencode('배송 ID와 배송지를 입력하세요.'));
    exit;
}

// 변경 전 주소 (이력용)
$q = $conn->prepare('SELECT delivery_address FROM shipments WHERE shipment_id = ?');
$q->bind_param('i', $shipment_id);
$q->execute();
$row = $q->get_result()->fetch_assoc();
if (!$row) {
    log_api($conn, $me['user_id'], 'ADDRESS_UPDATE', 'SHIPMENT', $shipment_id, 404, 'ERROR');
    header('Location: ../dashboard.php?s=err&m=' . urlencode('존재하지 않는 배송입니다.'));
    exit;
}
$old = $row['delivery_address'];

// *** 취약 지점: 소유권 검사 없이 shipment_id 만으로 UPDATE ***
$up = $conn->prepare('UPDATE shipments SET delivery_address = ?, updated_at = NOW() WHERE shipment_id = ?');
$up->bind_param('si', $new_address, $shipment_id);
$up->execute();

// 배송지 변경 이력 (Blue: actor_user_id 가 배송 소유자와 불일치하면 비인가 변경)
$h = $conn->prepare('INSERT INTO shipment_address_history (shipment_id, actor_user_id, old_address, new_address) VALUES (?,?,?,?)');
$h->bind_param('iiss', $shipment_id, $me['user_id'], $old, $new_address);
$h->execute();

log_api($conn, $me['user_id'], 'ADDRESS_UPDATE', 'SHIPMENT', $shipment_id, 200, 'SUCCESS');
header('Location: ../dashboard.php?s=ok&m=' . urlencode('배송 ' . $shipment_id . ' 배송지가 변경되었습니다.'));
