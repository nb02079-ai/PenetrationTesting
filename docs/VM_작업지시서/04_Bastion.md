# LOGION Bastion VM 작업지시서

- **담당**: (팀원) · **상태**: 어댑터1 IP 확정(✅) · **OVA**: `LOGION_Bastion_v1.ova`
- **역할**: 내부서버망 점프호스트/관리자 출입구. CASE 3(잔존계정→Bastion→DB·파일)의 경유지.

## 1. 사양
1 vCPU · RAM 1GB · Disk 12GB · Ubuntu Server 24.04.4 LTS · NIC 2(+빌드시 NAT)

## 2. 네트워크
| 어댑터 | 내부망 | IP | GW |
|---|---|---|---|
| 1 | `LOGION-srv` | **10.10.20.6/24** ✅ | 10.10.20.1 (pfSense#2, 생기면) |
| 2 | `LOGION-soc` | 10.10.40.6/24 | **없음** |
| 3 | NAT | (빌드 중만) | - |

## 3. 구성
```
sudo apt install -y openssh-server mariadb-client auditd audispd-plugins
sudo systemctl enable --now ssh auditd
```
- auditd 감사규칙 적용(로그인·sudo·권한변경 추적) — CASE 3 탐지 근거.
- DB 접속 테스트용 클라이언트만(서버 아님).

## 4. 완료 기준
- `systemctl status ssh auditd` → 둘 다 active.
- `mysql -h 10.10.20.10 -u logion -p logion` 접속 OK(MariaDB 떠 있을 때).
- `auditctl -l` → 규칙 로드 확인.

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_Bastion_v1.ova`.

## 6. 연동 포인트
- CASE 3: 협력업체 계정(office)→Bastion(SSH)→MariaDB·Samba. pfSense#2에서 office→srv SSH 허용 룰 필요.
- 어댑터2(soc)로 auditd/ssh 로그를 Wazuh(10.10.40.100)가 수집.
