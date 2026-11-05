# LOGION WebAPI — 의도적 취약점 노트 (팀 ERD 기준, Red/Blue 공용)

교육용 격리 랩 전용. 데이터는 가상 더미, 업로드 시연은 **무해한 마커 파일**로만(실제 악성코드 금지).

## 더미 계정 (login_id)
| login_id | 비번 | role | status | 비고 |
|---|---|---|---|---|
| ua | ua_pass123 | CUSTOMER | ACTIVE | 주문 ORD-001 / 배송 1건 |
| ub | ub_pass123 | CUSTOMER | ACTIVE | 주문 ORD-002 / 배송 1건 |
| staff01 | staff_pass123 | STAFF | ACTIVE | 운영 포털 |
| partner01 | partner123 | STAFF | **ACTIVE(잔존)** | CASE 3 — DISABLED 였어야 함 |

---

## 취약점 1 — 배송지 무단 변경 (CASE 1 · BOLA)
- **엔드포인트**: `POST /api/update_shipping.php` (`shipment_id`, `new_address`)
- **원리**: 서버가 "이 배송(shipment)이 세션 사용자 주문에 속하는지" 검사하지 않음. `shipment_id`만 바꾸면 **남의 배송지(shipments.delivery_address)**가 변경됨.
- **공격(Red)**: `ua` 로그인 → 배송지 변경 폼의 `shipment_id`를 ub의 배송 ID로 바꿔 전송(개발자도구/Burp).
- **탐지(Blue)**: `shipment_address_history`의 `actor_user_id`가 배송 소유자와 불일치하는 레코드. `api_audit_logs`(action=ADDRESS_UPDATE, result).
- **조치/재검증**: `security/fixed/update_shipping.php` — shipment→order→user 소유권 검사. 비인가 변경은 403 + `result=DENIED` 로그.
- **ATT&CK**: T1565.001 / OWASP API1:2023 (BOLA)

## 취약점 2 — 물류 문서 업로드 (CASE 2 · 파일 업로드)
- **엔드포인트**: `POST /admin/upload.php` (운영 포털, `STAFF`)
- **원리**: 확장자·콘텐츠 검증 없이 원본 파일명 그대로 실행 가능한 `/uploads/`에 저장. 메타데이터는 `uploaded_documents`(validation_status=ACCEPTED).
- **탐지(Blue)**: `uploaded_documents` 레코드, WAF·access 로그, **Wazuh FIM**(웹루트 파일 생성/수정).
- **조치/재검증**: `security/fixed/upload.php` — 허용목록 + 랜덤 파일명 + 웹루트 밖 저장, 거부 시 `validation_status=REJECTED`. `harden.sh`가 `/uploads/.htaccess`로 실행 차단.
- **ATT&CK**: T1505.003 — 실제 웹쉘 구성·사용이 확인된 경우에만 매핑.

## 취약점 3 (선택) — 주문 검색 SQL Injection
- **엔드포인트**: `GET /search.php?q=` (orders.item_name 결합)
- **조치**: `security/fixed/search.php` — prepared statement.
- **ATT&CK**: T1190 — 초기 접근 실행 증거가 있는 경우에만.

## CASE 3 연계 — 잔존 계정
`partner01`(STAFF, status=ACTIVE)은 폐기됐어야 할 협력업체 실습 계정. 이 계정으로 운영 포털 접근 가능한 상태가 CASE 3의 출발점. `harden.sh`가 원격 DB에서 `status='DISABLED'`로 전환한다.

> 앱이 쓰는 테이블/컬럼·연동 규격은 `db_reference/DB_REQUIREMENTS.md`, 더미데이터는 `db_reference/seed_reference.sql` 참고(= DB 담당 전달).
