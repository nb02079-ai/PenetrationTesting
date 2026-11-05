# LOGION Kali (공격자) 작업지시서

- **담당**: (팀원, RED) · **상태**: 제안값 · **OVA**: `LOGION_Kali_v1.ova`(또는 공식 이미지)
- **역할**: 외부 공격자. CASE 1·2·3 수행(BOLA, 업로드, 잔존계정 침투).

## 1. 사양
2 vCPU · RAM 2~3GB · Disk 20GB+ · Kali Linux · NIC 1

## 2. 네트워크
| 어댑터 | 내부망 | IP | GW |
|---|---|---|---|
| 1 | `LOGION-wan` | 10.10.99.10/24 | 10.10.99.1 (pfSense#1 WAN) |

- 외부에서 들어오는 길은 pfSense#1 → DMZ의 WAF뿐(정상 공격 경로).

## 3. 구성
- 공식 Kali VirtualBox 이미지 사용 가능. IP만 위 표대로 static 고정.
- 도구: Burp Suite, curl, nmap, sqlmap 등 기본 포함.

## 4. 완료 기준
- `ping 10.10.99.1`(pfSense#1 WAN) OK.
- 통합 시 `curl http://10.10.10.5`(WAF 경유 포털) 접근 → 공격 표면 도달 확인.

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_Kali_v1.ova`. (공식 이미지 쓰면 export 생략 가능)

## 6. 연동 포인트
- 공격 흐름: Kali → pfSense#1 → WAF(10.10.10.5) → WebAPI(10.10.10.20) → MariaDB(10.10.20.10).
- 모든 공격이 WAF·Wazuh·Suricata에 탐지 흔적을 남겨야 BLUE와 매칭됨.
