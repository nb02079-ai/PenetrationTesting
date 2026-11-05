# LOGION Suricata VM 작업지시서

- **담당**: (팀원) · **상태**: 제안값 · **OVA**: `LOGION_Suricata_v1.ova`
- **역할**: 길목 IDS. 감시 구간 트래픽을 무차별(promiscuous) 캡처해 공격 시그니처 탐지.

## 1. 사양
2 vCPU · RAM 2GB · Disk 20GB · Ubuntu Server 24.04.4 LTS · NIC 2(+빌드시 NAT)

## 2. 네트워크
| 어댑터 | 내부망 | IP | 비고 |
|---|---|---|---|
| 1 | `LOGION-dmz`(감시구간) | **IP 없음** | **무차별 모드 = 모두 허용** |
| 2 | `LOGION-soc` | 10.10.40.30/24 | GW 없음(관리·로그 전송) |
| 3 | NAT | (설치 중만) | - |

- ★ VirtualBox: 어댑터1 → 고급 → **무차별 모드 = "모두 허용"**. 그래야 남의 트래픽까지 보임.
- 어댑터1은 IP 없이 캡처 전용(센서), 관리는 어댑터2(soc)로.

## 3. 구성
```
sudo apt install -y suricata
# 캡처 인터페이스를 어댑터1(예: enp0s3)로 지정
sudo sed -i 's/interface: eth0/interface: enp0s3/' /etc/suricata/suricata.yaml
sudo suricata-update                 # 룰셋 갱신
sudo systemctl enable --now suricata
```

## 4. 완료 기준
- `sudo systemctl status suricata` → active.
- `ip -br a` → 어댑터1 UP(IP 없음), 어댑터2 10.10.40.30.
- 테스트 공격 발생 시 `/var/log/suricata/fast.log`에 alert 기록.

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_Suricata_v1.ova`.

## 6. 연동 포인트
- 캡처 대상 구간 조정 가능(dmz 외 다른 구간 감시하려면 어댑터1 내부망 이름 변경).
- fast.log/eve.json을 soc NIC로 Wazuh(10.10.40.100)가 수집하면 SIEM에 통합.
