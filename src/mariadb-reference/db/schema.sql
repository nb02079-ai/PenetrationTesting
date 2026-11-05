-- LOGION 물류 포털 스키마 (교육용 더미 데이터 전용)
CREATE DATABASE IF NOT EXISTS logion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE logion;

DROP TABLE IF EXISTS shipping_audit;
DROP TABLE IF EXISTS uploads_log;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,        -- password_hash() 값
    role        ENUM('user','admin','partner') NOT NULL DEFAULT 'user',
    active      TINYINT(1) NOT NULL DEFAULT 1, -- 0 = 비활성(폐기) 계정
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id                VARCHAR(16) PRIMARY KEY,  -- 예: O-001
    user_id           INT NOT NULL,
    product           VARCHAR(100) NOT NULL,
    status            VARCHAR(30) NOT NULL DEFAULT '배송준비',
    shipping_address  VARCHAR(200) NOT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 배송지 변경 감사 로그 (Blue 가 CASE 1 변경 이력을 추적하는 근거)
CREATE TABLE shipping_audit (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     VARCHAR(16) NOT NULL,
    old_address  VARCHAR(200),
    new_address  VARCHAR(200),
    changed_by   INT,                 -- 요청을 보낸 세션 사용자 id
    changed_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 업로드 감사 로그 (Blue 가 CASE 2 업로드 흔적을 추적하는 근거)
CREATE TABLE uploads_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    filename     VARCHAR(255),
    stored_path  VARCHAR(255),
    uploaded_by  INT,
    uploaded_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
