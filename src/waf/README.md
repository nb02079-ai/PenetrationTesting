# LOGION WAF VM — 제작 & OVA Export 가이드

DMZ에 둘 **Nginx + ModSecurity + OWASP CRS 리버스 프록시**. WebAPI(origin) 앞단에 선다.

- **사양(팀 기준)**: 1 vCPU · RAM **1.5GB** · 디스크 **16GB** · NIC 1
- **OVA 파일명**: `LOGION_WAF_v1.ova`

## 1. 골든 Ubuntu VM
- **Ubuntu Server 24.04.4 LTS (amd64)**, OpenSSH 서버 체크, 디스크 16GB.
- `sudo apt update && sudo apt -y upgrade`
- ※ CRS를 git으로 받으므로 **빌드 중 인터넷(NAT) 연결 필요**.

## 2. 이 폴더를 VM으로 복사 후 프로비저닝
```
cd ~/logion-waf
sudo bash provision-waf.sh                 # origin IP 나중에
# 또는 WebAPI IP를 알면 바로:
sudo bash provision-waf.sh <WebAPI_IP>
```
→ Nginx + ModSecurity 설치, OWASP CRS 내려받기, 리버스 프록시 설정, DetectionOnly 적용.

## 3. origin(WebAPI) IP 지정 (집에서 조립 시)
- `/etc/nginx/sites-available/logion-waf.conf` 의 `proxy_pass http://<IP>:80;`
- 또는 `sudo bash provision-waf.sh <WebAPI_IP>` 재실행 → `sudo systemctl restart nginx`

## 4. 동작 확인
- WAF 단독이면 origin이 없어 502가 뜨는 게 정상.
- WebAPI·DB까지 떠 있으면 `http://<WAF_IP>/` 에서 로그인 화면이 WAF를 거쳐 보인다.
- SQLi 탐지 확인: `http://<WAF_IP>/search.php?q=' OR '1'='1` → `/var/log/modsec/audit.log` 에 CRS 탐지 기록.

## 5. OVA Export
`sudo poweroff` → VirtualBox → 가상 시스템 내보내기 → OVA 1.0 → **`LOGION_WAF_v1.ova`** → 드라이브 업로드.

## 파일 구성
```
provision-waf.sh        Nginx+ModSecurity+CRS 설치·설정
conf/modsecurity.conf   ModSecurity 기본(DetectionOnly) 설정
conf/logion-waf.conf    Nginx 리버스 프록시 (origin 치환)
WAF_NOTES.md            설계(탐지 공백)·차단 전환·로그·패키지 대안
```

> 자세한 설계·운영·문제해결은 `WAF_NOTES.md`. 교육용 격리 랩 전용.
> `libnginx-mod-http-modsecurity` 설치가 안 되면 Apache 버전으로 바로 바꿔줄게(WAF_NOTES 참고).
