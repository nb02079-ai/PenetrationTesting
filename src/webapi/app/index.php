<?php require __DIR__ . '/config.php'; if (current_user()) { header('Location: dashboard.php'); exit; } ?>
<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LOGION 물류 포털 · 로그인</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><div class="wrap" style="padding:0"><h1>LOGION 물류·배송 포털</h1></div></header>
<div class="wrap">
  <div class="card">
    <h2>로그인</h2>
    <?php if (isset($_GET['e'])): ?><div class="msg err"><?= htmlspecialchars($_GET['e']) ?></div><?php endif; ?>
    <form method="post" action="login.php">
      <label>로그인 아이디</label><input name="login_id" autofocus>
      <label>비밀번호</label><input name="password" type="password">
      <button type="submit">로그인</button>
    </form>
    <p style="font-size:12px;color:#5c6775;margin-top:14px">교육용 격리 랩 · 더미 계정만 사용</p>
  </div>
</div></body></html>
