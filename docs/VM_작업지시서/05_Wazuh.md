# LOGION Wazuh VM 작업지시서

- **담당**: (팀원) · **상태**: 제안값 · **OVA**: `LOGION_Wazuh_v1.ova`
- **역할**: SIEM 올인원(Indexer+Manager+Dashboard). 감시대상 서버 로그 분석·경보. BLUE 핵심.

## 1. 사양
4 vCPU · RAM **8GB** · Disk 50GB · Ubuntu Server 24.04.4 LTS · NIC 1(+빌드시 NAT)
> 메모리 많이 먹음 — 켤 땐 Kali·Suricata 끄기.

## 2. 네트워크
| 어댑터 | 내부망 | IP | GW |
|---|---|---|---|
| 1 | `LOGION-soc` | 10.10.40.100/24 | **없음**(OOB 평면) |
| 2 | NAT | (설치 중만) | - |

- 관제망은 OOB라 게이트웨이 없음. 감시대상 서버들이 각자 soc NIC로 **직접** 접속.

## 3. 구성
```
curl -sO https://packages.wazuh.com/4.9/wazuh-install.sh
sudo bash wazuh-install.sh -a        # 올인원 설치(버전은 최신 4.x 기준)
```
→ 설치 후 출력되는 admin 비밀번호 보관. Dashboard: `https://10.10.40.100`.

## 4. 완료 기준
- `https://10.10.40.100` 대시보드 로그인.
- `sudo systemctl status wazuh-manager wazuh-indexer wazuh-dashboard` → active.
- 에이전트 등록 대기 상태 확인(대상: WAF·WebAPI·MariaDB·Bastion → manager 주소 `10.10.40.100`).

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_Wazuh_v1.ova`. (Disk 50GB라 업로드 용량 주의)

## 6. 연동 포인트
- 감시대상 VM의 어댑터2(soc)에 Wazuh 에이전트 설치 → manager `10.10.40.100` 지정.
- WAF의 ModSecurity 로그, Bastion auditd, MariaDB 쿼리로그를 수집해 CASE 1·2·3 타임라인 구성.
