# LOGION WebAPI VM — 제작 & OVA Export 가이드 (팀 ERD 버전)

DMZ에 둘 **웹/배송 API 전용 VM**. DB는 없고 원격 MariaDB(팀 담당 제작)에 접속한다.

- **사양(팀 기준)**: 1 vCPU · RAM **1.5GB** · 디스크 **16GB** · NIC 1
- **OVA 파일명**: `LOGION_WebAPI_v1.ova`
- **DB는 팀 ERD 스키마**(users/orders/shipments/shipment_address_history/api_audit_logs/uploaded_documents) 기준으로 동작.

## DB 담당에게 먼저 전달할 것
`db_reference/` 폴더:
- `DB_REQUIREMENTS.md` — 앱이 읽고 쓰는 테이블·컬럼, 원격 접속 설정, 필요한 더미데이터
- `seed_reference.sql` — 앱이 기대하는 계정·주문·배송 더미(팀 ERD 기준)

이게 맞아야 WebAPI와 MariaDB가 맞물려 CASE가 시연된다.

## 1. 골든 Ubuntu VM
- **Ubuntu Server 24.04.4 LTS (amd64)**, OpenSSH 서버 체크, 디스크 16GB.
- `sudo apt update && sudo apt -y upgrade`

## 2. 프로비저닝
```
cd ~/logion-webapi
sudo bash provision-webapi.sh            # DB IP 나중에
# 또는:  sudo bash provision-webapi.sh <MariaDB_IP>
```
→ Apache+PHP 설치, 앱 배포, uploads 취약 조건. **DB 서버는 설치 안 함.**

## 3. DB 호스트 지정 (집에서 조립 시)
- `/var/www/html/config.php` 의 `$DB_HOST` (기본 `10.10.20.10`, 팀 확정값) → 바뀌면 MariaDB VM IP로.
- 또는 환경변수 `LOGION_DB_HOST`.

## 4. 동작 확인 (MariaDB VM이 떠 있어야 함)
- `http://<WebAPI_IP>/` → `ua / ua_pass123` 로그인 → 내 주문/배송 확인.
- **BOLA 테스트**: 배송지 변경 폼의 `shipment_id`를 ub의 배송 ID로 바꿔 전송 → 남의 배송지가 바뀌면 재현 성공.
- **업로드 테스트**: `partner01 / partner123` → `/admin/` → 파일 업로드(무해한 마커만).

## 5. OVA Export
`sudo poweroff` → VirtualBox → 가상 시스템 내보내기 → OVA 1.0 → **`LOGION_WebAPI_v1.ova`** → 드라이브 업로드.

## 6. 재검증(조치)
```
sudo bash security/harden.sh <MariaDB_IP>
```
→ BOLA 소유권 검사 / 업로드 검증·격리 / SQLi 제거 / 업로드 실행 차단 + 원격 DB의 잔존 계정(partner01) DISABLED.

## 파일 구성
```
provision-webapi.sh        Apache+PHP 설치·배포 (DB 없음)
app/config.php             ★ DB 호스트 + api_audit 로깅 헬퍼
app/ ...                   웹앱 (취약 버전, 팀 ERD)
db_reference/              ← DB 담당 전달용 (규격 + 더미 시드)
security/VULN_NOTES.md      취약점·CASE·ATT&CK·탐지·조치
security/harden.sh         재검증(조치) 적용 (원격 DB 포함)
security/fixed/*           조치 버전 엔드포인트
```

> 조립 순서: **MariaDB(팀) 먼저 → WebAPI가 그 IP로 접속**. 교육용 격리 랩 전용.
