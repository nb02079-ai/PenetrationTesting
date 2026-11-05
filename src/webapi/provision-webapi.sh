#!/usr/bin/env bash
# LOGION WebAPI VM 프로비저닝 — Ubuntu Server 24.04 LTS 에서 root(sudo)로 실행
# 사용법:  이 폴더(logion-webapi)에서  sudo bash provision-webapi.sh [MariaDB_IP]
#   MariaDB_IP 를 주면 config.php 의 DB 호스트를 그 값으로 설정(안 주면 기본값/환경변수 사용)
set -euo pipefail

WEBROOT="/var/www/html"
HERE="$(cd "$(dirname "$0")" && pwd)"
DB_IP="${1:-}"

echo "[1/4] 패키지 설치 (apache2, php, php-mysql) — DB 서버는 설치하지 않음..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y apache2 php libapache2-mod-php php-mysql unzip

echo "[2/4] 앱 배포 → ${WEBROOT}..."
rm -f "${WEBROOT}/index.html"
cp -rf "${HERE}/app/." "${WEBROOT}/"
mkdir -p "${WEBROOT}/uploads"
chown -R www-data:www-data "${WEBROOT}"
chmod -R 0777 "${WEBROOT}/uploads"   # CASE 2 취약 조건(의도적)

if [ -n "${DB_IP}" ]; then
  echo "[3/4] DB 호스트 설정 → ${DB_IP} ..."
  sed -i "s/\\\$DB_HOST = getenv('LOGION_DB_HOST') ?: '[^']*';/\$DB_HOST = getenv('LOGION_DB_HOST') ?: '${DB_IP}';/" "${WEBROOT}/config.php"
else
  echo "[3/4] DB 호스트: config.php 기본값 사용 (집에서 MariaDB IP로 수정 필요)"
fi

echo "[4/4] Apache 재시작..."
systemctl enable --now apache2
systemctl restart apache2

IP=$(hostname -I | awk '{print $1}')
cat <<DONE

====================================================================
 LOGION WebAPI 프로비저닝 완료
 접속:   http://${IP}/     운영포털: http://${IP}/admin/
 ※ DB 는 이 VM 에 없음. MariaDB VM 이 먼저 떠 있어야 로그인됨.
   DB 호스트 설정 위치:  ${WEBROOT}/config.php  (\$DB_HOST)
   또는 재실행:  sudo bash provision-webapi.sh <MariaDB_IP>
 더미 계정: U-A/ua_pass123 · U-B/ub_pass123 · admin/admin_pass123 · partner_test/partner123
 재검증(조치):  sudo bash security/harden.sh <MariaDB_IP>
====================================================================
DONE
