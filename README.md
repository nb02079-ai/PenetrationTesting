# LOGION 모의해킹·침해대응 랩 (Red/Blue Penetration Testing)

가상 물류기업 **LOGION**을 대상으로 한 Red/Blue 모의해킹·침해대응 팀 프로젝트.
다중 대역 격리 네트워크를 설계·구축하고, 의도적 취약점을 가진 웹 서비스를 공격·탐지·대응하는 전 과정을 다룬다.

> ⚠️ 교육용 격리 랩 전용. 모든 데이터는 더미이며, 취약점은 학습 목적으로 의도적으로 포함됨. 실제 환경 적용 금지.

## 개요

- **유형**: Red/Blue 팀 모의해킹 + 침해대응(SIEM 탐지) 프로젝트
- **가상화**: VirtualBox (단일 가상화, OVA 배포 기반)
- **네트워크**: DMZ · 내부서버망 · 사내업무망 · 보안관제망(OOB) 4개 대역을 **pfSense 이중 방화벽**(경계·내부)으로 분리
- **담당 영역**: 네트워크 아키텍처 설계, 취약 웹 포털·배송 API(WebAPI), WAF(Nginx+ModSecurity) 구축

## 아키텍처

```
Kali(공격자) → pfSense#1(경계) → WAF → WebAPI → pfSense#2(내부) → MariaDB
                                         └─ 전 구간 로그 → Wazuh SIEM(관제망)
```

| 대역 | CIDR | 구성 |
|---|---|---|
| 외부(공격자) | 10.10.99.0/24 | Kali |
| DMZ | 10.10.10.0/24 | WAF, WebAPI |
| 내부서버망 | 10.10.20.0/24 | MariaDB, Bastion |
| 사내업무망 | 10.10.30.0/24 | 직원PC, 협력업체 |
| 보안관제망(OOB) | 10.10.40.0/24 | Wazuh, Suricata |

자세한 연결 방식은 [`docs/토폴로지_연결방식.md`](docs/토폴로지_연결방식.md) 참고.

## 공격 시나리오 (CASE)

| # | 시나리오 | 기법 (MITRE) | 방어·탐지 |
|---|---|---|---|
| 1 | 배송지 무단 변경 (BOLA) | T1565.001 / OWASP API1:2023 | DB 감사 (WAF 탐지 공백) |
| 2 | 파일 업로드 검증 누락 | T1505.003 | WAF(ModSecurity CRS) |
| 3 | 잔존 협력업체 계정 침투 | T1078 / T1021 | Bastion auditd + Wazuh |

공격 명령·탐지·조치·재시험 전 과정은 [`docs/CASE_공격_시나리오북.md`](docs/CASE_공격_시나리오북.md) 참고.

**핵심 서사(Purple)**: WAF가 **탐지하는 공격(SQLi·업로드)** vs **놓치는 공격(BOLA·계정관리)** 의 대조를 통해, 경계 방어만으로는 부족하며 앱 로직·계정관리·SIEM이 함께 작동해야 함을 입증.

## 저장소 구조

```
src/
  webapi/            취약 웹 포털·배송 API (PHP/MariaDB) + 보안 조치(harden) 스크립트
  waf/               Nginx + ModSecurity + OWASP CRS 리버스 프록시
  mariadb-reference/ DB 스키마·시드 참고본
docs/
  토폴로지_연결방식.md      as-built 네트워크 연결 방식
  CASE_공격_시나리오북.md   CASE 1~3 공격·탐지·조치·재시험
  VM_작업지시서/           VM별 구축 작업지시서 (공통규칙 + 9종 + 더미)
diagrams/            토폴로지 구성도
planning/            프로젝트 계획서
```

## 사용 기술

`VirtualBox` · `pfSense` · `Nginx` · `ModSecurity (OWASP CRS)` · `PHP` · `MariaDB` ·
`Wazuh (SIEM)` · `Suricata (IDS)` · `Kali Linux` · `MITRE ATT&CK` · `OWASP API Security`
