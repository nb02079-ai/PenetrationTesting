# LOGION CASE 실행 통합본 (프로세스 + 실제 명령)

> **팀 매뉴얼(역할·캡처 체크리스트)** 과 **시나리오북(실제 공격 명령)** 을 합친 실행본.
> **RED**는 `실제 명령/행동`을 그대로 실행하고, **BLUE**는 `캡처` 시점에 증거를 확보한다.
> 교육용 격리 랩 전용 · 더미 데이터 · 증빙 없는 성공/탐지 주장 금지 · 민감정보 마스킹 · 미실행은 "미실행/미확인" 명기.

## 0. PC 배치 · 역할
| 구분 | 내용 |
|---|---|
| PC-1 (데이터망) | Kali·pfSense Edge·Internal·WAF·WebAPI·MariaDB·Bastion·Office — CASE별 필요한 것만 실행 |
| PC-2 (관제망) | Wazuh Manager·Indexer·Dashboard (10.10.40.100). PC-1 각 서버 SOC NIC는 물리 LAN 브리지로 연결 |
| RED 1 | CASE 1·2 공격 요청·캡처 |
| RED 2 | 초기·변경 결과 검증, CASE 3 접근 시험·재시험 |
| BLUE 1 | pfSense·WAF·WebAPI 로그 및 방어(조치) |
| BLUE 2 | Wazuh·MariaDB·Bastion 로그와 판정 |

> PC-1이 16GB면 전부 동시 X. CASE별 필요 VM만 켠다.

## CASE별 가동 VM
| CASE | PC-1 | PC-2 |
|---|---|---|
| 01 BOLA | Edge + Internal + WAF + WebAPI + MariaDB + Kali | Wazuh |
| 02 업로드 | Edge + WAF + WebAPI + **MariaDB + Internal** + Kali | Wazuh |
| 03 외주계정 | Internal + Bastion + Office PC (경로상 Edge도) | Wazuh |

> ※ CASE 02: 업로드가 `uploaded_documents`에 **항상 기록**되므로 MariaDB+Internal 필수.

---

## 1. 사전 점검 (공통 · CAP-00~06)
| 순 | 담당 | 행동 | 실제 명령/확인 | 캡처 |
|---|---|---|---|---|
| 01 | BLUE 2 | Wazuh 시작·접속 | (PC-2) `sudo systemctl restart wazuh-indexer; sleep 20; sudo systemctl restart wazuh-manager` → `sudo /var/ossec/bin/agent_control -l` | CAP-00 대시보드/서비스 |
| 02 | BLUE 1 | pfSense NIC·규칙·라우트 | Edge/Internal 콘솔 메인화면, `netstat -rn` (정적경로 10.10.20/30→10.10.10.254) | CAP-01 인터페이스·규칙 |
| 03 | RED·Office | 공격자/필요 VM 실행 | VirtualBox 실행 목록 | CAP-02 VM 목록 |
| 04 | BLUE 1 | 통신 검증 | 각 서버 `ip -br a` / `ip route get <대상>` | CAP-03 IP·라우트 / CAP-04 서비스 연결 |
| 05 | BLUE 2 | 에이전트·이벤트 | `agent_control -l` Active + 최근 이벤트 | CAP-05 Agent 상태 |
| 06 | RED 2·BLUE 2 | 더미 초기값·백업 | DB 초기값/계정/시간 확인 + (조치 전) `sudo cp -a /var/www/html /var/www/html.vuln-bak` | CAP-06 초기값·백업 |

---

## 2. CASE 01 | 배송지 바꿔치기 (BOLA)
**목표**: A로 로그인한 상태에서 B의 배송지를 무단 변경할 수 있는지 확인 → 소유권 검증 조치 후 재시험.
**경로**: Kali 10.10.99.10 → Edge → WAF 10.10.10.5 → WebAPI 10.10.10.20 → Internal → MariaDB 10.10.20.10
**기법**: OWASP API1 BOLA / MITRE T1565.001

| 순 | 담당 | 실제 명령 (Kali / MariaDB) | 캡처 |
|---|---|---|---|
| 01 | RED1/BLUE2 | (MariaDB) `SELECT shipment_id,delivery_address FROM shipments;` — ua=1, ub=2 초기 상태 | C01-01 초기 배송지 |
| 02 | RED1/BLUE1 | ```curl -s -c /tmp/ua.txt -d "login_id=ua&password=ua_pass123" http://10.10.10.5/login.php``` → 본인(1) 정상 변경 ```curl -s -i -b /tmp/ua.txt --data-urlencode "shipment_id=1" --data-urlencode "new_address=서울 정상변경" http://10.10.10.5/api/update_shipping.php``` | C01-02 정상 요청·응답 |
| 03 | RED1 | **BOLA** — 남(ub=2) 변경 ```curl -s -i -b /tmp/ua.txt --data-urlencode "shipment_id=2" --data-urlencode "new_address=서울 공격구 탈취로 666" http://10.10.10.5/api/update_shipping.php``` → `s=ok` 면 성공 | C01-03 공격 요청·응답·시간 |
| 04 | RED2/BLUE2 | (MariaDB) 비인가 변경 확인 ```SELECT h.shipment_id,h.actor_user_id,o.user_id owner,h.old_address,h.new_address FROM shipment_address_history h JOIN shipments s ON s.shipment_id=h.shipment_id JOIN orders o ON o.order_id=s.order_id WHERE h.actor_user_id<>o.user_id;``` | C01-04 DB 전후 상태 |
| 05 | BLUE1·2 | `api_audit_logs` 조회 + (WAF) `sudo tail -20 /var/log/modsec/audit.log` → **BOLA는 CRS 탐지 없음(공백)** + Wazuh 이벤트 유무 판정 | C01-05 로그와 경고(있다면) |
| 06 | BLUE1 | 조치 (WebAPI) `cd ~/logion-webapi && sudo bash security/harden.sh 10.10.20.10` (소유권 검증 적용) | C01-06 방어 코드·정책 |
| 07 | RED1·2 | 03 동일 요청 재전송 → **거부** 확인 | C01-07 재공격 요청·응답 |
| 08 | BLUE2 | DB 값 불변·추가 이력 없음 확인 | C01-08 최종 DB 상태 |

**종료 판정**: □ 취약 상태 허용/거부 기록됨 □ BLUE가 로그 수집과 실제 경고를 구별 □ 조치 후 동일 비인가 변경 거부·DB 유지

---

## 3. CASE 02 | 물류문서 업로드 취약점
**목표**: 검증 없는 무해 파일 저장 확인 → 검증·저장 정책 수정 후 재업로드 차단.
**경로**: Kali → WAF 10.10.10.5 → WebAPI 10.10.10.20 (+MariaDB 기록)
**기법**: MITRE T1505.003

| 순 | 담당 | 실제 명령 (Kali) | 캡처 |
|---|---|---|---|
| 01 | RED1/BLUE1 | 무해 파일 준비 `echo '정상문서' > /tmp/normal.txt` · 마커 `echo '<?php echo "LOGION-MARKER"; ?>' > /tmp/marker.php` · (WebAPI) `/var/www/html/uploads/` 확인 | C02-01 파일/정책/초기 폴더 |
| 02 | RED1 | partner01 로그인 + 정상 업로드 ```curl -s -c /tmp/p.txt -d "login_id=partner01&password=partner123" http://10.10.10.5/login.php``` ```curl -s -i -b /tmp/p.txt -F "doc=@/tmp/normal.txt" http://10.10.10.5/admin/upload.php``` | C02-02 정상 업로드 응답 |
| 03 | RED1 | 비허용 파일 업로드 ```curl -s -i -b /tmp/p.txt -F "doc=@/tmp/marker.php" http://10.10.10.5/admin/upload.php``` → 실행 확인 `curl -s http://10.10.10.5/uploads/marker.php` | C02-03 요청·응답·파일명 |
| 04 | RED2/BLUE1 | (WebAPI) `ls -l /var/www/html/uploads/` + (MariaDB) `SELECT original_filename,validation_status FROM uploaded_documents;` | C02-04 저장 여부 |
| 05 | BLUE1·2 | (WAF) `sudo tail -20 /var/log/modsec/audit.log` → CRS **933xxx(PHP)** · Wazuh 룰 **100304** | C02-05 로그·이벤트 |
| 06 | BLUE1 | 조치 (a)`sudo bash security/harden.sh 10.10.20.10` (b) WAF 차단 On ```sudo sed -i 's/^SecRuleEngine .*/SecRuleEngine On/' /etc/nginx/modsec/modsecurity.conf && sudo systemctl restart nginx``` | C02-06 변경 정책/설정 |
| 07 | RED1 | 03 동일 업로드 재시도 → 거부/차단(403) | C02-07 거부 응답 |
| 08 | BLUE1·2 | `/uploads/`에 미저장 + 차단 로그 확인 | C02-08 폴더·로그 |

**종료 판정**: □ 비허용 파일 저장 여부 전후 비교 □ Wazuh/WAF 탐지 경로·이벤트 사실대로 □ 조치 후 동일 파일 미저장

---

## 4. CASE 03 | 외주 관리계정의 그림자
**목표**: 폐기 안 된 잔존 외주 계정의 Bastion 로그인 확인 → 계정 폐기/권한 조치 후 거부.
**경로**: Office 협력PC 10.10.30.20 → pfSense Internal → Bastion 10.10.20.6:22 → MariaDB
**기법**: MITRE T1078 + T1021
> 사전: Bastion 켜고 Wazuh에서 `bastion` 에이전트 Active. **협력 OS 계정이 Bastion에 있는지 Bastion 담당과 확인**(없으면 그 자리에서 생성).

| 순 | 담당 | 실제 명령 | 캡처 |
|---|---|---|---|
| 01 | RED2/BLUE2 | Office PC IP·잔존계정 상태 확인 (MariaDB) `SELECT login_id,status FROM users WHERE login_id='partner01';` → ACTIVE(잔존) | C03-01 테스트 IP·계정 상태 |
| 02 | RED2/BLUE1 | (Office PC) Bastion 22 연결 `nc -zv 10.10.20.6 22` + pfSense Internal 허용규칙 | C03-02 TCP 연결/규칙 |
| 03 | RED2 | 잔존계정 로그인: 포털 ```curl -s -i -c /tmp/pt.txt -d "login_id=partner01&password=partner123" http://10.10.10.5/login.php``` + SSH 피벗 `ssh partner01@10.10.20.6` → `mysql -h 10.10.20.10 -u logion -plogion_dev_pw logion -e "SELECT login_id,name,phone FROM users;"` | C03-03 접속 성공/실패·시간 |
| 04 | BLUE1·2 | (Bastion) `sudo tail /var/log/audit/audit.log` + Wazuh `rule.groups: logion_audit and agent.name: bastion` | C03-04 인증·방화벽 로그 |
| 05 | BLUE2 | 조치: 계정 잠금/폐기 (WebAPI) `sudo bash security/harden.sh 10.10.20.10` (partner01 → DISABLED) + Bastion partner01 SSH 제거 | C03-05 계정 조치 |
| 06 | RED2 | 동일 계정 재접속 ```curl -s -i -d "login_id=partner01&password=partner123" http://10.10.10.5/login.php``` → "비활성화된 계정입니다" + SSH 거부 | C03-06 인증 거부 |
| 07 | BLUE1·2 | SSH/방화벽 로그로 차단 원인 확인 | C03-07 최종 로그 |

**종료 판정**: □ Office에 테스트 PC·유효 계정 준비 □ 초기 로그인·SSH 연결됨 □ 조치 후 동일 계정 접근 거부

---

## 5. 증빙 폴더 · 파일명
```
LOGION_Evidence / 00_Preparation / CASE_01_BOLA / CASE_02_Upload / CASE_03_Bastion
예: C01-03_Red_Attack.png, C01-05_Blue_Logs.png, C01-07_Retest.png
```

## 6. 최종 제출 체크리스트
| 구분 | RED 제출 | BLUE 제출 |
|---|---|---|
| 환경 | VM·IP·테스트 계정·기준 데이터 | pfSense·서비스·Agent 상태와 데이터 백업 |
| 공격 증거 | 실행 명령/요청·응답·시간 | API/DB/WAF/SSH 등 연관 로그 |
| 결과 | 데이터 변경·파일 저장·접속 성공 여부 | 로그 수신·경고 발화 구별 기록 |
| 방어 | 같은 조건 재공격 결과 | 바뀐 코드·정책·계정 설정 |
| 마무리 | 최종 요청·응답 캡처 | DB 불변/파일 미저장/로그인 거부 확인 |

## 7. 검사 핵심 기준
- □ 각 캡처에서 시스템/출발지/대상/시간/실제 결과가 확인되는가?
- □ 동일한 요청·계정·파일로 재시험했는가?
- □ 차단 원인이 WAF·WebAPI·DB·SSH·pfSense 중 무엇인지 구분했는가?
- □ 민감 비밀번호/토큰/개인정보를 마스킹했는가?
- □ 실제 동작을 확인 못 했으면 "미실행/미확인"으로 기록했는가?
