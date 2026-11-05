# WebAPI ↔ MariaDB 연동 규격 (DB 담당 전달용)

WebAPI 앱이 **팀 ERD 그대로** 동작한다. DB 담당은 아래만 맞춰주면 됨.

## 1. 앱이 읽고 쓰는 테이블·컬럼 (ERD와 동일)
- **users**(`user_id`, `login_id`, `password_hash`, `name`, `phone`, `role`, `status`) — role=`CUSTOMER`/`STAFF`, status=`ACTIVE`/`DISABLED`
- **orders**(`order_id`, `user_id`, `order_no`, `item_name`, `order_status`)
- **shipments**(`shipment_id`, `order_id`, `tracking_no`, `recipient_name`, `recipient_phone`, `delivery_address`, `delivery_status`, `updated_at`)
- **shipment_address_history**(`history_id`, `shipment_id`, `actor_user_id`, `old_address`, `new_address`, `changed_at`) — 앱이 **INSERT**
- **api_audit_logs**(`log_id`, `actor_user_id`, `action`, `target_type`, `target_id`, `source_ip`, `http_status`, `result`, `created_at`) — 앱이 **INSERT**
- **uploaded_documents**(`document_id`, `uploaded_by`, `original_filename`, `stored_filename`, `mime_type`, `file_size`, `validation_status`, `uploaded_at`) — 앱이 **INSERT**

→ 컬럼명이 ERD와 다르면 앱 쿼리가 깨지니 **이 이름 그대로** 생성.

## 2. 원격 접속 (WebAPI VM 에서 붙음)
- DB 계정: `logion` / 비번 `logion_dev_pw` / DB명 `logion`
- `bind-address = 10.10.20.10` (MariaDB VM의 LOGION-srv 어댑터1 IP, 팀 확정값)
- **grant 호스트 2개 모두 필요** (하나라도 빠지면 그쪽에서 접속 거부):
  - `logion`@`10.10.10.%` → **WebAPI(DMZ 10.10.10.20)에서 포털 구동용** ← 신규, 꼭 추가
  - `logion`@`10.10.20.%` → Bastion(srv)에서 접속 테스트용 (유지)
  - ※ WebAPI는 DMZ에서 붙어서 MariaDB가 보는 출발지가 `10.10.10.x`임. `10.10.20.%`만 있으면 포털이 DB 연결 실패.
- 권한: `SELECT, INSERT, UPDATE` on `logion.*` (앱에 DELETE 경로 없음 — 이 3개로 충분)

## 3. 더미 데이터
- `db_reference/seed_reference.sql` 그대로 적재(또는 DDL에 맞춰 조정).
- 반드시 포함돼야 하는 것:
  - CUSTOMER 2명 `ua`/`ub` (각각 주문·배송 1건) — CASE 1 BOLA 대상
  - STAFF `staff01` — 운영 포털
  - **잔존 STAFF `partner01` (status=ACTIVE)** — CASE 3 벡터 (조치 시 DISABLED)

## 4. 계정/비번 (bcrypt 사전 생성, seed_reference.sql 에 포함)
| login_id | 비번 | role | status |
|---|---|---|---|
| ua | ua_pass123 | CUSTOMER | ACTIVE |
| ub | ub_pass123 | CUSTOMER | ACTIVE |
| staff01 | staff_pass123 | STAFF | ACTIVE |
| partner01 | partner123 | STAFF | ACTIVE(잔존) |
