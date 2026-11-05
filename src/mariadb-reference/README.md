# LOGION MariaDB VM — 제작 & OVA Export 가이드

내부 서버망에 둘 **DB 전용 VM**. 웹앱은 이 VM에 원격으로 붙는다. **WebAPI VM보다 먼저 만들어야** 한다.

- **사양(팀 기준)**: 1 vCPU · RAM **1GB** · 디스크 **16GB** · NIC 1
- **OVA 파일명**: `LOGION_MariaDB_v1.ova`

## 1. 골든 Ubuntu VM
- **Ubuntu Server 24.04.4 LTS (amd64)** 설치, OpenSSH 서버 체크, 디스크 16GB.
- 설치 후: `sudo apt update && sudo apt -y upgrade`

## 2. 이 폴더를 VM으로 복사 후 프로비저닝
```
cd ~/logion-mariadb
sudo bash provision-mariadb.sh
```
→ MariaDB 설치 + 원격 접속 허용 + 스키마/더미데이터 적재.
완료 메시지에 뜨는 **이 VM의 IP**를 기록해둘 것(WebAPI 쪽에 넣을 값).

## 3. 동작 확인
```
mysql -u logion -p'logion_dev_pw' -e "SELECT id,user_id,shipping_address FROM logion.orders;"
```
O-001(U-A), O-002(U-B) 가 보이면 정상.

## 4. OVA Export
`sudo poweroff` → VirtualBox → 가상 시스템 내보내기 → OVA 1.0 → **`LOGION_MariaDB_v1.ova`** → 드라이브 업로드.

## 참고
- 원격 접속 계정은 `logion@'%'`(교육 랩 기준). 운영 시엔 웹 서브넷으로 제한 권장.
- 집에서 조립 시: WebAPI VM의 `config.php` `$DB_HOST` 에 **이 VM의 최종 IP**를 넣는다(IP는 집에서 자유롭게 지정).
- 취약점/계정/조치 매핑은 WebAPI 패키지의 `security/VULN_NOTES.md` 참고.
