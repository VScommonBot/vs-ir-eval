# 숫자 계산 도구 (ir_calc.py)

판단은 평가 프레임과 사람이 하고, 계산은 이 도구가 합니다. 같은 입력에는 항상 같은 결과가 나옵니다. Python 3 표준 라이브러리만 사용하며, 파일을 저장하거나 네트워크를 호출하지 않습니다.

결과는 근거를 정렬하기 위한 참고값이며 투자 판단이 아닙니다.

```bash
python3 scripts/ir_calc.py score    examples/score.json
python3 scripts/ir_calc.py metrics  examples/metrics.json
python3 scripts/ir_calc.py market   examples/market.json
python3 scripts/ir_calc.py captable examples/captable.json
```

정상 종료는 0, 입력 오류는 2입니다. `examples/`의 값은 모두 가상입니다.

## score: 가중 종합점수

- `stage`: `pre`(예비창업), `under_3y`, `under_5y`, `under_7y`, `over_7y`
- `scores`: `team`, `market`, `moat`, `scale`, `strategy` (각 0~10)
- 업력별 가중치와 5단계 등급 구간은 `SKILL.ko.md`와 같습니다.
- 평가축이 하나라도 비면 총점과 등급을 내지 않습니다.

## metrics: 단위경제·런웨이 재계산

- 입력: `arpu_monthly`, `gross_margin`(0~1), `monthly_churn`(0~1), `cac`, `cash`, `monthly_net_burn`(월평균 순소진), `net_new_arr`(기간 순증 ARR), `period_months`
- `claims`: IR에 적힌 값(`ltv`, `ltv_cac`, `cac_payback_months`, `runway_months`, `burn_multiple`). 재계산 값과 5% 넘게 다르면 `불일치`입니다(`tolerance`로 변경).
- 번 멀티플 = 월평균 순소진 × 기간(월) ÷ 같은 기간의 순증 ARR
- 참고선(LTV/CAC 3배, 회수 18개월, 번 멀티플 2배, 런웨이 18개월)은 합격 기준이 아니라 추가 질문용입니다.

## market: TAM/SAM/SOM

- `top_down`: `total_market`, `serviceable_share`, `obtainable_share`(0~1), `source`, `source_year`
- `bottom_up`: `total_customers`, `serviceable_customers`, `obtainable_customers`, `annual_revenue_per_customer`
- 비율에는 기본값이 없습니다. 근거를 가지고 직접 입력해야 합니다.
- 두 방식의 차이가 3배를 넘으면 경고합니다(`max_ratio`로 변경). `claims`의 tam/sam/som은 10% 이내면 `일치`입니다.
- 외화 입력은 `currency`, `krw_per_unit`, `fx_date`가 필요하며 원화 환산을 괄호로 함께 표시합니다. 금액은 입력 통화 기준입니다.

## captable: 지분 희석과 매각대금 분배

- `holders`: `name`, `class`(`common`, `preferred`, `option_pool`), `shares`. 우선주는 `invested`, `multiple`(청산우선 배수), `participating`(참가 여부), `rank`(1이 최선순위)
- `convertibles`: `name`, `principal`, 그리고 `conversion_price` 또는 `valuation_cap`·`discount`
- `round`: `name`, `pre_money`, `investment`, `option_pool_topup_pct`(0~0.5), 우선권 조건
- `exits`: 분배 대상 금액 목록

가정과 한계:

- 라운드 단가 = 프리머니 ÷ 라운드 직전 완전희석 주식수(전환 전 증권 제외). 계약상 산정 기준이 다르면 `conversion_price`를 직접 넣으세요.
- 미행사 옵션풀은 매각대금 분배에서 제외합니다.
- 비참가적 우선주는 주주별로 유리한 경우 보통주 전환으로 계산합니다.
- 누적배당, 상환, 이자, 세금, 거래비용, 희석방지, 참가 상한, 종류주 일괄전환은 반영하지 않습니다.
- 상환전환우선주(RCPS), 전환사채, 조건부지분인수계약 등의 국내 법적 효력과 세무는 이 도구가 판단하지 않습니다. 정관·계약서 원문과 법률 검토가 필요합니다.

실제 주주명부와 투자조건은 공개 데모나 외부 AI에 입력하지 말고, 필요하면 본인 컴퓨터에서만 계산하세요.

## 테스트

```bash
python3 -m unittest discover -s tests -v
```
