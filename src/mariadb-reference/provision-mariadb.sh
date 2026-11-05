#!/usr/bin/env bash
# LOGION MariaDB VM 프로비저닝 — Ubuntu Server 24.04 LTS 에서 root(sudo)로 실행
# 사용법:  이 폴더(logion-mariadb)에서  sudo bash provision-mariadb.sh
set -euo pipefail

DBNAME="logion"
DBUSER="logion"
DBPASS="logion_dev_pw"
HERE="$(cd "$(dirname "$0")" && pwd)"

echo "[1/5] MariaDB 설치..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y mariadb-server

echo "[2/5] 서비스 기동..."
systemctl enable --now mariadb

echo "[3/5] 원격 접속 허용(bind-address)..."
CNF="/etc/mysql/mariadb.conf.d/50-server.cnf"
if [ -f "$CNF" ]; then
  sed -i 's/^\s*bind-address\s*=.*/bind-address = 0.0.0.0/' "$CNF"
  grep -q '^bind-address' "$CNF" || echo 'bind-address = 0.0.0.0' >> "$CNF"
fi

echo "[4/5] 사용자/스키마/더미데이터..."
# 웹 VM(원격)에서 접속할 수 있도록 logion@'%' 생성. 교육 랩이라 '%' 허용(운영 시 서브넷 제한 권장).
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DBNAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DBUSER}'@'localhost' IDENTIFIED BY '${DBPASS}';
CREATE USER IF NOT EXISTS '${DBUSER}'@'%'         IDENTIFIED BY '${DBPASS}';
GRANT ALL PRIVILEGES ON ${DBNAME}.* TO '${DBUSER}'@'localhost';
GRANT ALL PRIVILEGES ON ${DBNAME}.* TO '${DBUSER}'@'%';
FLUSH PRIVILEGES;
SQL
mysql < "${HERE}/db/schema.sql"
mysql < "${HERE}/db/seed.sql"

echo "[5/5] MariaDB 재시작..."
systemctl restart mariadb

IP=$(hostname -I | awk '{print $1}')
cat <<DONE

====================================================================
 LOGION MariaDB 프로비저닝 완료
 이 VM IP: ${IP}   (← WebAPI VM 의 config.php \$DB_HOST 에 넣을 값)
 DB: ${DBNAME} / 계정 ${DBUSER} / 비번 ${DBPASS}
 적재: users 4, orders 2(O-001=U-A, O-002=U-B), 감사로그 테이블 생성
 원격 확인(웹 VM 에서):
   mysql -h ${IP} -u logion -p'${DBPASS}' -e "SELECT id,user_id FROM logion.orders;"
====================================================================
DONE
