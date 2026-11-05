# LOGION pfSense#2 (내부 방화벽) 작업지시서

- **담당**: (팀원) · **상태**: 제안값 · **OVA**: `LOGION_pfSense2_v1.ova`
- **역할**: DMZ↔내부서버망/사내업무망 2차 차단(원래 ASAv 자리). CASE 3 ACL. pfSense CE 2.7.x.

## 1. 사양
1 vCPU · RAM 1GB · Disk 8GB · NIC 3

## 2. 인터페이스
| 인터페이스 | 내부망 | IP | GW |
|---|---|---|---|
| WAN (em0) | `LOGION-dmz` | 10.10.10.254/24 | 10.10.10.1 (pfSense#1) |
| LAN (em1) | `LOGION-srv` | 10.10.20.1/24 | - |
| OPT1 (em2) | `LOGION-office` | 10.10.30.1/24 | - |

- WAN(dmz)측 **"Block private networks" 체크 해제**(내부망끼리 통신해야 함).
- 관제(soc) 다리 **없음**(OOB라 라우팅 불필요).

## 3. 룰 (IP 기준, 시나리오 진행에 맞춰 조임)
- **WAN(dmz)→srv**: `WebAPI 10.10.10.20 → MariaDB 10.10.20.10 : 3306 (TCP) 허용`, 나머지 deny.
- **office→srv**: `협력업체 10.10.30.20 → Bastion 10.10.20.6 : 22 (SSH) 허용`(CASE 3), 그 외 srv deny.
- **srv→office**: 기본 deny(필요시 응답만).
- ★ **Outbound NAT 켜지 말 것(내부-내부)**: WebAPI→MariaDB가 **출발지 10.10.10.20 그대로** 보여야 MariaDB의 `logion@10.10.10.%` grant가 매칭됨. NAT하면 출발지가 10.10.20.1로 바뀌어 Blue 로그·grant가 어긋남.

## 4. 완료 기준
- WebAPI(10.10.10.20)에서 `mysql -h 10.10.20.10 -u logion -p` 접속 성공.
- MariaDB 로그에 출발지 `10.10.10.20`으로 찍힘(NAT 안 된 것 확인).
- office 협력계정에서 Bastion SSH 되고, 직접 MariaDB는 막힘(ACL).

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_pfSense2_v1.ova`.

## 6. 연동 포인트
- srv·office 호스트의 기본 GW = 각 10.10.20.1 / 10.10.30.1(이 VM).
- 이 VM 자체의 기본 GW = 10.10.10.1(pfSense#1)로 외부행 처리.
