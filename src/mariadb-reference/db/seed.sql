-- LOGION 더미 데이터 (비밀번호는 password_hash/bcrypt 사전 생성값)
USE logion;

INSERT INTO users (username, password, role, active) VALUES
  ('U-A',          '$2y$10$N5bnl9g3Ee6YHYCrWhziQeNQwYxVoZSsV1mXzvlcTyglj.GwBX2Qa', 'user',    1),
  ('U-B',          '$2y$10$bXE/Ti6GYhfBDgBhr0xyhOnw0Nv8rjSd1v5GPJNvO7tt8ZXUm3K4O', 'user',    1),
  ('admin',        '$2y$10$cyPREfPd4umPcgjf4pj6OeAqSrV7EUXuTLDw43HcqEdReMBoC5LqC', 'admin',   1),
  -- 잔존(미폐기) 협력업체 실습 계정 — CASE 3. 원래 active=0 이어야 하나 1 로 남아있음.
  ('partner_test', '$2y$10$ZG/RJE6dPHz61jP5jyr0mO3f9MjmPh8hH69f4eTPF3M3z/12Wpvg.', 'partner', 1);

INSERT INTO orders (id, user_id, product, status, shipping_address) VALUES
  ('O-001', (SELECT id FROM users WHERE username='U-A'), '방한 점퍼 x2',   '배송준비', '가상 대구센터 7-3'),
  ('O-002', (SELECT id FROM users WHERE username='U-B'), '생활용품 세트', '배송중',   '가상 부산센터 2-11');
