<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user();

// 본인 주문 + 배송 정보 (배송지는 shipments 에 있음)
$stmt = $conn->prepare(
    'SELECT o.order_no, o.item_name, o.order_status,
            s.shipment_id, s.tracking_no, s.delivery_address, s.delivery_status
     FROM orders o
     JOIN shipments s ON s.order_id = o.order_id
     WHERE o.user_id = ?'
);
$stmt->bind_param('i', $me['user_id']);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LOGION · 내 배송</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><div class="wrap" style="padding:0"><h1>LOGION 물류·배송 포털</h1>
  <span class="u"><?= htmlspecialchars($me['login_id']) ?> (<?= htmlspecialchars($me['role']) ?>) · <a href="logout.php" style="color:#cde0ff">로그아웃</a></span></div></header>
<div class="wrap">
  <div class="nav"><a href="dashboard.php">내 배송</a><a href="search.php">주문 검색</a>
    <?php if ($me['role'] === 'STAFF'): ?><a href="admin/">운영 포털</a><?php endif; ?></div>

  <?php if (isset($_GET['m'])): ?><div class="msg <?= ($_GET['s'] ?? 'ok') === 'err' ? 'err' : 'ok' ?>"><?= htmlspecialchars($_GET['m']) ?></div><?php endif; ?>

  <div class="card">
    <h2>내 주문 / 배송</h2>
    <table><tr><th>주문번호</th><th>상품</th><th>주문상태</th><th>배송ID</th><th>배송지</th><th>배송상태</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= htmlspecialchars($r['order_no']) ?></td><td><?= htmlspecialchars($r['item_name']) ?></td>
          <td><?= htmlspecialchars($r['order_status']) ?></td><td><?= htmlspecialchars($r['shipment_id']) ?></td>
          <td><?= htmlspecialchars($r['delivery_address']) ?></td><td><?= htmlspecialchars($r['delivery_status']) ?></td></tr>
    <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <h2>배송지 변경</h2>
    <form method="post" action="api/update_shipping.php">
      <label>배송 ID (shipment_id)</label><input name="shipment_id" value="<?= htmlspecialchars($rows[0]['shipment_id'] ?? '') ?>">
      <label>새 배송지</label><input name="new_address" placeholder="예: 가상 대구센터 7-3">
      <button type="submit">변경 요청</button>
    </form>
    <p style="font-size:12px;color:#5c6775;margin-top:10px">배송 완료 전 건에 한해 배송지를 수정할 수 있습니다.</p>
  </div>
</div></body></html>
