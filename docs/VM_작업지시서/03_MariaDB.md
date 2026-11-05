# LOGION MariaDB VM 작업지시서

- **담당**: (팀원) · **상태**: 어댑터1 IP 확정(✅) · **OVA**: `LOGION_MariaDB_v1.ova`
- **역할**: 회원·주문·배송 데이터. CASE 1(BOLA)·3의 최종 타겟. WebAPI가 원격 접속.

## 1. 사양
1 vCPU · RAM 1GB · Disk 16GB · Ubuntu Server 24.04.4 LTS · NIC 2(+빌드시 NAT)

## 2. 네트워크
| 어댑터 | 내부망 | IP | GW |
|---|---|---|---|
| 1 | `LOGION-srv` | **10.10.20.10/24** ✅ | 10.10.20.1 (pfSense#2, 생기면) |
| 2 | `LOGION-soc` | 10.10.40.10/24 | **없음** |
| 3 | NAT | (빌드 중만) | - |

## 3. 구성
```
sudo apt install -y mariadb-server
```
- `/etc/mysql/mariadb.conf.d/50-server.cnf` → `bind-address = 10.10.20.10`
- 스키마·더미 적재: WebAPI 소스의 `db_reference/seed_reference.sql` 그대로.
- **원격 계정 — grant 호스트 2개 모두** (하나 빠지면 그쪽 접속 거부):
```sql
CREATE USER IF NOT EXISTS 'logion'@'10.10.10.%' IDENTIFIED BY 'logion_dev_pw';
CREATE USER IF NOT EXISTS 'logion'@'10.10.20.%' IDENTIFIED BY 'logion_dev_pw';
GRANT SELECT, INSERT, UPDATE ON logion.* TO 'logion'@'10.10.10.%';
GRANT SELECT, INSERT, UPDATE ON logion.* TO 'logion'@'10.10.20.%';
FLUSH PRIVILEGES;
```
  - `10.10.10.%` = WebAPI(DMZ)에서 포털 구동용 ← 꼭 추가 (WebAPI 출발지가 10.10.10.20이라 필요)
  - `10.10.20.%` = Bastion(srv) 접속 테스트용
  - 권한은 SELECT/INSERT/UPDATE만(앱에 DELETE 없음). `systemctl restart mariadb`.

## 4. 완료 기준
- 로컬: `mysql -u root -e "SHOW DATABASES;"` → `logion` 보임, 테이블 6개(users/orders/shipments/shipment_address_history/api_audit_logs/uploaded_documents).
- srv 내 Bastion(10.10.20.6)에서: `mysql -h 10.10.20.10 -u logion -p logion` 접속 OK.
- 더미 필수: CUSTOMER `ua`/`ub`, STAFF `staff01`, **잔존 STAFF `partner01`(ACTIVE)**.

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_MariaDB_v1.ova`.

## 6. 연동 포인트
- WebAPI(10.10.10.20)↔DB(3306)는 pfSense#2 WAN(dmz)측에 허용 룰 필요(08 참고).
- 어댑터2(soc)로 Wazuh 에이전트 → 10.10.40.100.
