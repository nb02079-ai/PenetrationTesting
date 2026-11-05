# LOGION WAF — 설계·운영 노트 (Red/Blue 공용)

Nginx 리버스 프록시 + ModSecurity v3 + OWASP CRS. DMZ에서 WebAPI(origin) 앞단에 선다.
클라이언트 → pfSense → **WAF(여기)** → WebAPI. 외부에서 보는 웹 진입점은 이 VM.

## 핵심 설계 — "탐지 공백" 대조 (Purple 하이라이트)
기본 모드는 **DetectionOnly**(탐지·로깅만, 차단 안 함). 이유:
- 모든 공격이 origin까지 도달 → **CASE 1~3 이 성립**한다.
- WAF는 "무엇을 탐지했나"만 남긴다 → Blue가 **탐지 커버리지 공백**을 보여줄 수 있다:
  - **SQLi/XSS** → CRS가 탐지(로그 남음)
  - **BOLA(배송지 변경)·파일 업로드** → CRS 시그니처 없음 → **탐지 안 됨(공백)**
- 이 "막은(탐지한) 것 vs 놓친 것" 대조가 보고서 Purple 매칭의 핵심.

## 차단 모드로 전환 (선택 데모)
`/etc/nginx/modsec/modsecurity.conf` 의 `SecRuleEngine DetectionOnly` → `On` 으로 바꾸고
`sudo systemctl restart nginx`. 그러면 CRS가 SQLi 등을 **차단**한다(BOLA/업로드는 여전히 통과).

## 로그 (Blue 근거)
- ModSecurity 감사 로그: `/var/log/modsec/audit.log` (탐지 룰·요청 상세)
- 웹 접근 로그: `/var/log/nginx/logion_access.log`
- 이 로그들을 Wazuh 에이전트로 수집하면 SIEM 타임라인에 WAF 탐지가 올라온다.

## 동작 확인
- origin(WebAPI)·DB가 떠 있어야 끝단까지 응답한다. WAF만 단독이면 502(origin 없음)가 정상.
- `http://<WAF_IP>/` → WebAPI 로그인 화면이 WAF를 거쳐 보이면 프록시 정상.
- SQLi 탐지 테스트: `http://<WAF_IP>/search.php?q=' OR '1'='1` 요청 후 `/var/log/modsec/audit.log` 에 CRS 탐지가 남는지 확인.

## 패키지 관련 (설치에서 막히면)
`libnginx-mod-http-modsecurity` 가 Ubuntu 24.04 저장소에 없으면 `apt-get install` 이 실패한다.
그 경우 두 가지 대안:
1. **Apache + mod_security2 버전**으로 교체(동일한 ModSecurity+CRS 엔진, apt로 확실히 설치됨) — 요청하면 바로 줄게.
2. ModSecurity-nginx 커넥터를 소스 빌드(권장 안 함, 2주 일정에 과함).
설치 단계에서 에러가 나면 그 메시지를 공유해줘.
