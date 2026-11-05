# LOGION pfSense#1 (경계 방화벽) 작업지시서

- **담당**: (팀원) · **상태**: 제안값 · **OVA**: `LOGION_pfSense1_v1.ova`
- **역할**: 외부(공격자)↔DMZ 1차 차단. pfSense CE 2.7.x.

## 1. 사양
1 vCPU · RAM 1GB · Disk 8GB · NIC 2

## 2. 인터페이스
| 인터페이스 | 내부망 | IP |
|---|---|---|
| WAN (em0) | `LOGION-wan` | 10.10.99.1/24 |
| LAN (em1) | `LOGION-dmz` | 10.10.10.1/24 |

- WAN은 랩 내부망이라 **"Block private networks"·"Block bogon" 체크 해제**(기본 켜져 있으면 공격 트래픽이 막힘).

## 3. 룰·라우팅
- **WAN 룰**: 외부(Kali)에서 **WAF(10.10.10.5)로 TCP 80,443만 허용**, 나머지 deny.
- **정적 경로(핵심)**: 내부 대역을 pfSense#2로 넘김
  - System→Routing→Gateways: `GW_P2 = 10.10.10.254`(pfSense#2 dmz측)
  - Static Routes: `10.10.20.0/24 → GW_P2`, `10.10.30.0/24 → GW_P2`
- NAT: 교육 랩이라 외부 공인망 없음 → outbound NAT는 기본값으로 두되, 공격 재현에 지장 없게 확인.

## 4. 완료 기준
- Kali(10.10.99.10)에서 `curl http://10.10.10.5` → WAF 응답(통합 시).
- DMZ 호스트에서 `traceroute 10.10.20.10` → pfSense#1→pfSense#2 경유 확인.

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_pfSense1_v1.ova`.

## 6. 연동 포인트
- DMZ 호스트(WAF·WebAPI)의 기본 GW = 10.10.10.1(이 VM). 내부행은 정적경로로 자동 처리.
