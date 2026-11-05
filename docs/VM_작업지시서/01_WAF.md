# LOGION WAF VM 작업지시서

- **담당**: 이도형 · **상태**: 빌드 완료(소스 `LOGION_WAF_src.zip`) · **OVA**: `LOGION_WAF_v1.ova`
- **역할**: DMZ에서 WebAPI 앞단. Nginx + ModSecurity v3 + OWASP CRS 리버스 프록시.

## 1. 사양
1 vCPU · RAM 1.5GB · Disk 16GB · Ubuntu Server 24.04.4 LTS · NIC 2(+빌드시 NAT)

## 2. 네트워크
| 어댑터 | 내부망 | IP | GW |
|---|---|---|---|
| 1 | `LOGION-dmz` | 10.10.10.5/24 | 10.10.10.1 (pfSense#1, 생기면) |
| 2 | `LOGION-soc` | 10.10.40.5/24 | **없음** |
| 3 | NAT | (빌드 중만) | - |

## 3. 구성
```
cd ~/logion-waf
sudo bash provision-waf.sh          # origin 기본값 10.10.10.20(WebAPI)로 이미 설정됨
```
→ Nginx+ModSecurity+CRS 설치, 리버스 프록시, **DetectionOnly**(탐지만) 모드.

## 4. 완료 기준
- `sudo nginx -t` → syntax ok, CRS 룰 로드.
- `curl -I http://localhost` → **502 정상**(WebAPI·라우팅 전). 끝단은 통합테스트에서 확인.
- SQLi 탐지: `curl "http://localhost/search.php?q=' OR '1'='1"` → `/var/log/modsec/audit.log`에 CRS 기록.

## 5. OVA 내보내기
공통 5번. 파일명 `LOGION_WAF_v1.ova`.

## 6. 연동 포인트
- origin(WebAPI) = **10.10.10.20**. 바뀌면 `sudo bash provision-waf.sh <새IP>` 재실행.
- 로그는 soc NIC로 Wazuh(10.10.40.100) 에이전트가 수집(통합 단계).
