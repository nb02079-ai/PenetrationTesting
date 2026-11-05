#!/usr/bin/env bash
# LOGION WAF VM 프로비저닝 — Ubuntu Server 24.04 LTS 에서 root(sudo)로 실행
# Nginx + ModSecurity v3 + OWASP CRS 리버스 프록시
# 사용법:  이 폴더(logion-waf)에서  sudo bash provision-waf.sh [WebAPI_origin_IP]
set -euo pipefail

ORIGIN="${1:-10.10.10.20}"      # WebAPI(origin) IP — 팀 확정값(안 주면 기본값, 집에서 수정 가능)
HERE="$(cd "$(dirname "$0")" && pwd)"
MODSEC_DIR="/etc/nginx/modsec"

echo "[1/6] 패키지 설치 (nginx + ModSecurity + git)..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
# ModSecurity v3 nginx 커넥터 모듈. 패키지가 없으면 WAF_NOTES.md 의 대안 참고.
apt-get install -y nginx git libnginx-mod-http-modsecurity

echo "[2/6] 디렉터리 및 ModSecurity 기본 설정..."
mkdir -p "${MODSEC_DIR}" /var/log/modsec
cp "${HERE}/conf/modsecurity.conf" "${MODSEC_DIR}/modsecurity.conf"

echo "[3/6] OWASP CRS 내려받기..."
if [ ! -d "${MODSEC_DIR}/crs" ]; then
  git clone --depth 1 https://github.com/coreruleset/coreruleset.git "${MODSEC_DIR}/crs"
fi
cp -n "${MODSEC_DIR}/crs/crs-setup.conf.example" "${MODSEC_DIR}/crs/crs-setup.conf"

echo "[4/6] ModSecurity main.conf 작성..."
cat > "${MODSEC_DIR}/main.conf" <<EOF
Include ${MODSEC_DIR}/modsecurity.conf
Include ${MODSEC_DIR}/crs/crs-setup.conf
Include ${MODSEC_DIR}/crs/rules/*.conf
EOF
chown -R www-data:www-data /var/log/modsec

echo "[5/6] Nginx 리버스 프록시 설정 (origin=${ORIGIN})..."
cp "${HERE}/conf/logion-waf.conf" /etc/nginx/sites-available/logion-waf.conf
sed -i "s/__ORIGIN__/${ORIGIN}/g" /etc/nginx/sites-available/logion-waf.conf
ln -sf /etc/nginx/sites-available/logion-waf.conf /etc/nginx/sites-enabled/logion-waf.conf
rm -f /etc/nginx/sites-enabled/default

echo "[6/6] 설정 검사 및 재시작..."
nginx -t
systemctl enable --now nginx
systemctl restart nginx

IP=$(hostname -I | awk '{print $1}')
cat <<DONE

====================================================================
 LOGION WAF 프로비저닝 완료
 이 VM IP: ${IP}   (외부에서 접속하는 웹 진입점)
 origin(WebAPI): ${ORIGIN}   ← 집에서 바꾸려면:
   /etc/nginx/sites-available/logion-waf.conf 의 proxy_pass, 또는
   sudo bash provision-waf.sh <WebAPI_IP>  재실행
 모드: DetectionOnly(탐지만). 차단 데모는 ${MODSEC_DIR}/modsecurity.conf 의
       SecRuleEngine 를 On 으로 바꾸고  sudo systemctl restart nginx
 로그: /var/log/modsec/audit.log · /var/log/nginx/logion_access.log
====================================================================
DONE
