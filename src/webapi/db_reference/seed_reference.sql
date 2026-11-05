-- LOGION 더미 데이터 참조본 (팀 ERD 기준) — MariaDB 담당에게 전달용
-- ※ DB 담당의 실제 테이블 DDL(컬럼명/제약)에 맞춰 적용. 비밀번호는 bcrypt 사전 생성값.
-- WebAPI 앱이 이 데이터(로그인ID·주문·배송)를 기대하므로, 같은 값으로 적재해야 CASE 시연이 된다.
USE logion;

-- 사용자 (role: CUSTOMER/STAFF, status: ACTIVE/DISABLED)
INSERT INTO users (login_id, password_hash, name, phone, role, status) VALUES
 ('ua',        '$2y$10$N5bnl9g3Ee6YHYCrWhziQeNQwYxVoZSsV1mXzvlcTyglj.GwBX2Qa', '김에이',      '010-1111-0001', 'CUSTOMER', 'ACTIVE'),
 ('ub',        '$2y$10$bXE/Ti6GYhfBDgBhr0xyhOnw0Nv8rjSd1v5GPJNvO7tt8ZXUm3K4O', '이비',        '010-1111-0002', 'CUSTOMER', 'ACTIVE'),
 ('staff01',   '$2y$10$dI5fAxcdktX29mA6LeZavudbWUm60A9tJYFEbOhNn7b99ihJlsppe', '운영자',      '010-2222-0001', 'STAFF',    'ACTIVE'),
 -- 잔존(미폐기) 협력업체 실습 계정 — CASE 3. 원래 DISABLED 여야 하나 ACTIVE 로 남아있음.
 ('partner01', '$2y$10$ZG/RJE6dPHz61jP5jyr0mO3f9MjmPh8hH69f4eTPF3M3z/12Wpvg.', '협력사테스트', '010-3333-0001', 'STAFF',    'ACTIVE');

-- 주문 (order_no UNIQUE, order_id 는 AUTO_INCREMENT 가정)
INSERT INTO orders (user_id, order_no, item_name, order_status) VALUES
 ((SELECT user_id FROM users WHERE login_id='ua'), 'ORD-001', '방한 점퍼 x2',   '결제완료'),
 ((SELECT user_id FROM users WHERE login_id='ub'), 'ORD-002', '생활용품 세트', '배송중');

-- 배송 (배송지는 여기. CASE 1 BOLA 의 대상 = shipments.delivery_address)
INSERT INTO shipments (order_id, tracking_no, recipient_name, recipient_phone, delivery_address, delivery_status) VALUES
 ((SELECT order_id FROM orders WHERE order_no='ORD-001'), 'TRK-0001', '김에이', '010-1111-0001', '가상 대구센터 7-3', '준비중'),
 ((SELECT order_id FROM orders WHERE order_no='ORD-002'), 'TRK-0002', '이비',  '010-1111-0002', '가상 부산센터 2-11', '배송중');

-- shipment_address_history / api_audit_logs / uploaded_documents 는 비워둠(시험 중 앱이 기록).
