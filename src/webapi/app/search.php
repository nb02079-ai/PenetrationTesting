<?php
// === (선택) T1190 초기 접근 벡터: 주문 검색 SQL Injection 취약 버전 ===
require __DIR__ . '/config.php';
require_login();
$me = current_user();
$q = $_GET['q'] ?? '';
$rows = [];
if ($q !== '') {
    // *** 취약 지점: 파라미터 바인딩 없이 문자열 결합 ***
    $sql = "SELECT order_no, item_name, order_status FROM orders WHERE item_name LIKE '%$q%'";
    $r = $conn->query($sql);
    if ($r) { $rows = $r->fetch_all(MYSQLI_ASSOC); }
}
?>
<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LOGION · 주문 검색</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><div class="wrap" style="padding:0"><h1>LOGION 물류·배송 포털</h1>
  <span class="u"><?= htmlspecialchars($me['login_id']) ?> · <a href="logout.php" style="color:#cde0ff">로그아웃</a></span></div></header>
<div class="wrap">
  <div class="nav"><a href="dashboard.php">내 배송</a><a href="search.php">주문 검색</a></div>
  <div class="card">
    <h2>주문 상품 검색</h2>
    <form method="get"><label>상품명</label><input name="q" value="<?= htmlspecialchars($q) ?>"><button>검색</button></form>
    <?php if ($q !== ''): ?>
      <table style="margin-top:14px"><tr><th>주문번호</th><th>상품</th><th>주문상태</th></tr>
      <?php foreach ($rows as $row): ?>
        <tr><td><?= htmlspecialchars($row['order_no']) ?></td><td><?= htmlspecialchars($row['item_name']) ?></td><td><?= htmlspecialchars($row['order_status']) ?></td></tr>
      <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div></body></html>
