#!/usr/bin/env bash
# CASE 조치(재검증) — WebAPI VM 에서 실행. 잔존 계정 비활성화는 원격 MariaDB 에 적용.
# 사용법:  logion-webapi 폴더에서  sudo bash security/harden.sh [MariaDB_IP]
set -euo pipefail
WEBROOT="/var/www/html"
HERE="$(cd "$(dirname "$0")" && pwd)"
DBPASS="logion_dev_pw"
DB_IP="${1:-$(grep -oP "getenv\('LOGION_DB_HOST'\) \?: '\K[^']+" "${WEBROOT}/config.php" || true)}"

echo "[1/3] 취약 엔드포인트 → 조치 버전 교체..."
cp -f "${HERE}/fixed/update_shipping.php" "${WEBROOT}/api/update_shipping.php"   # CASE 1 BOLA
cp -f "${HERE}/fixed/upload.php"          "${WEBROOT}/admin/upload.php"          # CASE 2 업로드
cp -f "${HERE}/fixed/search.php"          "${WEBROOT}/search.php"                # (선택) SQLi

echo "[2/3] 업로드 디렉터리 실행 차단..."
printf '<FilesMatch "\\.(php|phtml|phar)$">\n  Require all denied\n</FilesMatch>\nphp_admin_flag engine off\n' > "${WEBROOT}/uploads/.htaccess"
chmod -R 0755 "${WEBROOT}/uploads"
sed -i 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf || true
systemctl restart apache2

echo "[3/3] 잔존 STAFF 계정 비활성화 (원격 DB ${DB_IP}, status=DISABLED)..."
if [ -n "${DB_IP}" ]; then
  command -v mysql >/dev/null 2>&1 || { apt-get update -y && apt-get install -y mariadb-client; }
  mysql -h "${DB_IP}" -u logion -p"${DBPASS}" logion -e "UPDATE users SET status='DISABLED' WHERE login_id='partner01';"
  echo "  → partner01 비활성화 완료"
else
  echo "  ! MariaDB IP 를 못 찾음. 수동 실행:"
  echo "    mysql -h <MariaDB_IP> -u logion -p'${DBPASS}' logion -e \"UPDATE users SET status='DISABLED' WHERE login_id='partner01';\""
fi
echo "조치 완료 — 동일 공격을 재시험해 차단 여부를 확인하세요."
