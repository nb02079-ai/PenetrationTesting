<?php
require __DIR__ . '/../config.php';
require_login();
$me = current_user();
// 운영 포털: STAFF 역할만 (CASE 3: 잔존 STAFF 계정이 여기 접근)
if ($me['role'] !== 'STAFF') {
    http_response_code(403);
    exit('403 · 운영 포털 접근 권한이 없습니다.');
}
?>
<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LOGION · 운영 포털</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<header><div class="wrap" style="padding:0"><h1>LOGION 운영 포털</h1>
  <span class="u"><?= htmlspecialchars($me['login_id']) ?> (STAFF) · <a href="../logout.php" style="color:#cde0ff">로그아웃</a></span></div></header>
<div class="wrap">
  <div class="nav"><a href="../dashboard.php">← 포털</a><a href="index.php">운영 홈</a></div>
  <?php if (isset($_GET['m'])): ?><div class="msg <?= ($_GET['s'] ?? 'ok') === 'err' ? 'err' : 'ok' ?>"><?= htmlspecialchars($_GET['m']) ?></div><?php endif; ?>
  <div class="card">
    <h2>물류 문서 업로드</h2>
    <form method="post" action="upload.php" enctype="multipart/form-data">
      <label>문서 파일</label><input type="file" name="doc">
      <button type="submit">업로드</button>
    </form>
    <p style="font-size:12px;color:#5c6775;margin-top:10px">운송장·입출고 문서를 업로드합니다. 업로드된 파일은 <code>/uploads/</code> 에 저장됩니다.</p>
  </div>
</div></body></html>
