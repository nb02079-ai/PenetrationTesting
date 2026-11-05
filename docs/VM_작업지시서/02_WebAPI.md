# LOGION WebAPI(웹서버) VM 작업지시서

- **담당**: 이도형 · **상태**: 빌드 완료(소스 `LOGION_WebAPI_src.zip`) · **OVA**: `LOGION_WebAPI_v1.ova`
- **역할**: 배송 포털 + 배송지 API + 물류문서 업로드. CASE 1·2 핵심 공격대상. DB는 별도 MariaDB VM.

## 1. 사양
1 vCPU · RAM 1.5GB · Disk 16GB · Ubuntu Server 24.04.4 LTS · NIC 2(+빌드시 NAT)

## 2. 네트워크
| 어댑터 | 내부망 | IP | GW |
|---|---|---|---|
| 1 | `LOGION-dmz` | 10.10.10.20/24 | 10.10.10.1 (pfSense#1, 생기면) |
| 2 | `LOGION-soc` | 10.10.40.20/24 | **없음** |
| 3 | NAT | (빌드 중만) | - |

## 3. 구성
```
cd ~/logion-webapi
sudo bash provision-webapi.sh       # config.php DB_HOST 기본값 10.10.20.10(MariaDB 확정값)
```
→ Apache+PHP, 앱 배포, 업로드 취약 조건. **DB 서버는 설치 안 함.**

## 4. 완료 기준
- `http://<WebAPI_IP>/` 접속 시 로그인 화면 렌더(Apache·PHP 기동).
- DB 연결 실패 메시지는 **정상**(MariaDB가 srv에 있고 라우팅 전). 끝단은 통합테스트에서.

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_WebAPI_v1.ova`.

## 6. 연동 포인트 (★ MariaDB 담당 확인)
- DB 접속: host `10.10.20.10` / DB `logion` / user `logion` / pw `logion_dev_pw`.
- 스키마·더미·권한은 `logion-webapi/db_reference/DB_REQUIREMENTS.md` + `seed_reference.sql` 기준.
- 앱은 pfSense 2대 + 정적경로 선 뒤 DB에 붙음.
