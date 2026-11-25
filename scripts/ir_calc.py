#!/usr/bin/env python3
"""vs-ir-eval 숫자 계산 도구 (표준 라이브러리만 사용).

사용법: python3 scripts/ir_calc.py {score|metrics|market|captable} 입력.json

숫자 계산만 합니다. 판단은 사람과 평가 프레임이 하며, 결과는 투자 결정이 아닙니다.
입력 파일을 읽어 표준출력에 JSON을 쓰고, 파일 저장이나 네트워크 호출은 하지 않습니다.

"deterministic Python for numbers" 구성은 isanthoshgandhi/venture-capital-intelligence
(MIT, Copyright (c) 2026 Santhosh Gandhi)를 참고했습니다. THIRD_PARTY_NOTICES.md 참조.
"""

import argparse
import json
import math
import sys

AXES = ("team", "market", "moat", "scale", "strategy")

# SKILL.md / SKILL.ko.md 의 업력별 가중치(%)와 같아야 합니다.
STAGE_WEIGHTS = {
    "pre": (35, 25, 15, 10, 15),       # 예비창업
    "under_3y": (25, 25, 20, 15, 15),  # 설립 후 3년 미만
    "under_5y": (20, 20, 20, 25, 15),  # 설립 후 5년 미만
    "under_7y": (15, 20, 20, 30, 15),  # 설립 후 7년 미만
    "over_7y": (10, 15, 20, 40, 15),   # 7년 이상
}

GRADES = ((90, "최상"), (75, "우수"), (60, "보통"), (45, "미흡"), (0, "보완 필요"))


def number(data, key, minimum=0.0, maximum=None, required=False):
    """입력 숫자를 검증해 돌려줍니다. 없으면 None."""
    value = data.get(key)
    if value is None:
        if required:
            raise ValueError("필수 입력이 없습니다: %s" % key)
        return None
    if isinstance(value, bool) or not isinstance(value, (int, float)) or not math.isfinite(value):
        raise ValueError("숫자가 아닙니다: %s" % key)
    if minimum is not None and value < minimum:
        raise ValueError("%s 값은 %s 이상이어야 합니다" % (key, minimum))
    if maximum is not None and value > maximum:
        raise ValueError("%s 값은 %s 이하여야 합니다" % (key, maximum))
    return value


def compare(claimed, computed, tolerance):
    """IR 주장과 재계산 값을 비교합니다."""
    if claimed is None or computed is None:
        return "미확인"
    if computed == 0:
        return "일치" if claimed == 0 else "불일치"
    return "일치" if abs(claimed - computed) / abs(computed) <= tolerance else "불일치"


# ── 1. 가중 종합점수 ─────────────────────────────────────────────
def score(data):
    stage = data.get("stage")
    if stage not in STAGE_WEIGHTS:
        raise ValueError("stage 는 다음 중 하나여야 합니다: %s" % ", ".join(STAGE_WEIGHTS))
    weights = dict(zip(AXES, STAGE_WEIGHTS[stage]))
    raw = data.get("scores") or {}
    unknown = sorted(set(raw) - set(AXES))
    if unknown:
        raise ValueError("알 수 없는 평가축: %s" % ", ".join(unknown))
    scores = {axis: number(raw, axis, 0, 10) for axis in AXES}
    missing = [axis for axis in AXES if scores[axis] is None]
    result = {"stage": stage, "weights_pct": weights, "scores": scores, "missing": missing}
    if missing:
        # 빈 항목을 0점이나 평균으로 채우지 않습니다.
        result.update(weighted_score=None, grade=None, status="산출 보류: 근거가 없는 평가축이 있음")
        return result
    # 항목별로 먼저 반올림하면 등급 경계가 올라갈 수 있어, 합산 뒤에 부동소수 오차만 정리합니다.
    total = round(sum(scores[axis] * weights[axis] for axis in AXES) / 10, 6)
    result.update(
        contributions={axis: round(scores[axis] * weights[axis] / 10, 2) for axis in AXES},
        weighted_score=total,
        grade=next(name for floor, name in GRADES if total >= floor),
        status="산출 완료 (근거 정렬용 보조값이며 투자 결정이 아님)",
    )
    return result


# ── 2. 단위경제·런웨이 재계산 ────────────────────────────────────
def metrics(data):
    arpu = number(data, "arpu_monthly")
    margin = number(data, "gross_margin", 0, 1)
    churn = number(data, "monthly_churn", 0, 1)
    cac = number(data, "cac")
    cash = number(data, "cash")
    burn = number(data, "monthly_net_burn", None)
    new_arr = number(data, "net_new_arr", None)
    months = number(data, "period_months", 1)
    tolerance = number(data, "tolerance", 0, 1)
    tolerance = 0.05 if tolerance is None else tolerance
    notes = []

    monthly_gross = arpu * margin if arpu is not None and margin is not None else None
    ltv = monthly_gross / churn if monthly_gross is not None and churn else None
    if churn == 0:
        notes.append("월 이탈률 0: LTV를 산출하지 않음. 관측 기간과 코호트를 확인하세요.")
    ltv_cac = ltv / cac if ltv is not None and cac else None
    payback = cac / monthly_gross if cac is not None and monthly_gross else None
    runway = cash / burn if cash is not None and burn is not None and burn > 0 else None
    if burn is not None and burn <= 0:
        notes.append("월 순소진이 0 이하: 런웨이·번 멀티플 대상이 아니거나 입력 확인이 필요합니다.")
    burn_multiple = None
    if burn is not None and burn > 0 and new_arr is not None and months is not None:
        if new_arr > 0:
            # 같은 기간의 순소진 ÷ 순증 ARR
            burn_multiple = burn * months / new_arr
        else:
            notes.append("순증 ARR이 0 이하: 번 멀티플을 산출하지 않음. 성장 정체 여부를 확인하세요.")

    computed = {
        "ltv": ltv,
        "ltv_cac": ltv_cac,
        "cac_payback_months": payback,
        "runway_months": runway,
        "burn_multiple": burn_multiple,
    }
    claims = data.get("claims") or {}
    unknown = sorted(set(claims) - set(computed))
    if unknown:
        raise ValueError("알 수 없는 claims 항목: %s" % ", ".join(unknown))
    claim_check = {}
    for key in claims:
        claimed = number(claims, key, None)
        claim_check[key] = {
            "ir_claim": claimed,
            "recalculated": None if computed[key] is None else round(computed[key], 2),
            "status": compare(claimed, computed[key], tolerance),
        }

    # 해외 SaaS에서 흔히 쓰는 참고선. 합격 기준이 아니라 추가 질문을 띄우는 용도입니다.
    checks = []
    if ltv_cac is not None and ltv_cac < 3:
        checks.append("LTV/CAC 3배 미만: 획득비용과 유지율을 확인하세요.")
    if payback is not None and payback > 18:
        checks.append("CAC 회수 18개월 초과: 회수 전 자금 공백을 확인하세요.")
    if burn_multiple is not None and burn_multiple > 2:
        checks.append("번 멀티플 2배 초과: 성장 대비 자금 효율을 확인하세요.")
    if runway is not None and runway < 18:
        checks.append("런웨이 18개월 미만: 다음 조달 시점과 계획을 확인하세요.")

    return {
        "recalculated": {k: None if v is None else round(v, 2) for k, v in computed.items()},
        "not_computable": [k for k, v in computed.items() if v is None],
        "claim_check": claim_check,
        "tolerance": tolerance,
        "reference_checks": checks,
        "reference_note": "참고선은 해외 SaaS 관행이며 업종·단계별 판단이 필요합니다.",
        "notes": notes,
    }


# ── 3. TAM / SAM / SOM ──────────────────────────────────────────
def market(data):
    currency = data.get("currency", "KRW")
    rate = 1
    if currency != "KRW":
        rate = number(data, "krw_per_unit", 0, required=True)
        if rate <= 0:
            raise ValueError("krw_per_unit 은 0보다 커야 합니다")
        if not data.get("fx_date"):
            raise ValueError("외화 입력에는 환율 기준일 fx_date 가 필요합니다")

    def money(value):
        if currency == "KRW":
            return "{:,.0f}원".format(value)
        return "{} {:,.0f} (약 {:,.0f}원)".format(currency, value, value * rate)

    methods, warnings = {}, []
    top = data.get("top_down")
    if top:
        total = number(top, "total_market", required=True)
        sam = total * number(top, "serviceable_share", 0, 1, required=True)
        som = sam * number(top, "obtainable_share", 0, 1, required=True)
        methods["top_down"] = {"tam": total, "sam": sam, "som": som}
        if not top.get("source") or not top.get("source_year"):
            warnings.append("하향식 시장 규모의 출처 또는 기준연도가 미확인입니다.")
    bottom = data.get("bottom_up")
    if bottom:
        price = number(bottom, "annual_revenue_per_customer", required=True)
        counts = [number(bottom, k, required=True) for k in
                  ("total_customers", "serviceable_customers", "obtainable_customers")]
        if not counts[0] >= counts[1] >= counts[2]:
            raise ValueError("고객 수는 전체 >= 접근 가능 >= 확보 목표 순이어야 합니다")
        methods["bottom_up"] = dict(zip(("tam", "sam", "som"), (c * price for c in counts)))
    if not methods:
        raise ValueError("top_down 또는 bottom_up 중 하나는 필요합니다")
    if "bottom_up" not in methods:
        warnings.append("하향식 추정만 있습니다. 고객 수 × 객단가의 상향식 근거가 필요합니다.")
    if "top_down" not in methods:
        warnings.append("상향식 추정만 있습니다. 외부 시장 자료로 교차확인하세요.")

    max_ratio = number(data, "max_ratio", 1)
    max_ratio = 3 if max_ratio is None else max_ratio
    ratios = {}
    if len(methods) == 2:
        for level in ("tam", "sam", "som"):
            low, high = sorted(m[level] for m in methods.values())
            ratios[level] = round(high / low, 2) if low else None
            if ratios[level] is None or ratios[level] > max_ratio:
                warnings.append("%s: 두 방식의 차이가 %s배를 넘거나 한쪽이 0입니다. 정의와 가정을 맞춰 보세요."
                                % (level.upper(), max_ratio))

    claims = data.get("claims") or {}
    claim_check = {}
    for level in claims:
        if level not in ("tam", "sam", "som"):
            raise ValueError("claims 는 tam, sam, som 만 허용합니다")
        claimed = number(claims, level)
        claim_check[level] = {
            "ir_claim": None if claimed is None else money(claimed),
            "vs": {name: compare(claimed, values[level], 0.1) for name, values in methods.items()},
        }

    return {
        "currency": currency,
        "fx": None if currency == "KRW" else {"krw_per_unit": rate, "fx_date": data["fx_date"]},
        "methods": {name: {k: money(v) for k, v in values.items()} for name, values in methods.items()},
        "high_to_low_ratio": ratios,
        "claim_check": claim_check,
        "warnings": warnings,
    }


# ── 4. 지분 희석과 매각대금 분배 ─────────────────────────────────
def preference_terms(source):
    multiple = number(source, "multiple")
    rank = number(source, "rank", 1)
    participating = source.get("participating", False)
    if not isinstance(participating, bool):
        raise ValueError("participating 은 true 또는 false 여야 합니다")
    return {
        "multiple": 1 if multiple is None else multiple,
        "rank": 1 if rank is None else rank,
        "participating": participating,
    }


def distribute(proceeds, holders, converted):
    """우선분배(선순위부터, 동순위는 청구액 비례) 후 잔여를 주식수대로 나눕니다."""
    payout = {h["name"]: 0.0 for h in holders}
    preferred = [h for h in holders if h["class"] == "preferred" and h["name"] not in converted]
    remaining = float(proceeds)
    for rank in sorted({h["rank"] for h in preferred}):
        group = [h for h in preferred if h["rank"] == rank]
        due = sum(h["invested"] * h["multiple"] for h in group)
        paid = min(remaining, due)
        if due:
            for h in group:
                payout[h["name"]] += paid * h["invested"] * h["multiple"] / due
        remaining -= paid
    sharers = [h for h in holders
               if h["class"] == "common" or h["name"] in converted or h.get("participating")]
    shares = sum(h["shares"] for h in sharers)
    if remaining > 1e-6 and not shares:
        raise ValueError("잔여 금액을 받을 주식이 없습니다. 보통주 입력을 확인하세요")
    if shares:
        for h in sharers:
            payout[h["name"]] += remaining * h["shares"] / shares
    return payout


def waterfall(proceeds, holders):
    """비참가적 우선주는 주주별로 전환이 유리할 때만 보통주 전환한 것으로 계산합니다."""
    options = [h["name"] for h in holders if h["class"] == "preferred" and not h["participating"]]
    converted = set()
    for _ in range(len(options) * len(options) + 1):
        changed = False
        for name in options:
            as_common = distribute(proceeds, holders, converted | {name})[name]
            as_preferred = distribute(proceeds, holders, converted - {name})[name]
            want = as_common > as_preferred + 1e-6
            if want != (name in converted):
                converted ^= {name}
                changed = True
        if not changed:
            return distribute(proceeds, holders, converted), sorted(converted)
    raise ValueError("전환 선택이 수렴하지 않습니다. 계약 조건을 직접 검토하세요")


def captable(data):
    holders, names = [], set()

    def add(entry):
        if not entry["name"] or entry["name"] in names:
            raise ValueError("이름이 비었거나 중복입니다: %s" % entry["name"])
        names.add(entry["name"])
        holders.append(entry)

    for source in data.get("holders") or []:
        kind = source.get("class")
        if kind not in ("common", "preferred", "option_pool"):
            raise ValueError("class 는 common, preferred, option_pool 중 하나여야 합니다")
        entry = {"name": source.get("name"), "class": kind,
                 "shares": number(source, "shares", required=True)}
        if kind == "preferred":
            entry["invested"] = number(source, "invested", required=True)
            entry.update(preference_terms(source))
        add(entry)
    pre_shares = sum(h["shares"] for h in holders)
    if pre_shares <= 0:
        raise ValueError("holders 에 주식이 없습니다")
    pre_pct = {h["name"]: h["shares"] / pre_shares * 100 for h in holders}

    deal = data.get("round")
    convertibles = data.get("convertibles") or []
    if convertibles and not deal:
        raise ValueError("convertibles 전환에는 round 입력이 필요합니다")
    round_info = None
    if deal:
        pre_money = number(deal, "pre_money", required=True)
        investment = number(deal, "investment", required=True)
        pool_pct = number(deal, "option_pool_topup_pct", 0, 0.5) or 0
        if pre_money <= 0:
            raise ValueError("pre_money 는 0보다 커야 합니다")
        price = pre_money / pre_shares
        conversions = []
        for source in convertibles:
            principal = number(source, "principal", required=True)
            conversion_price = number(source, "conversion_price")
            if conversion_price is None:
                cap = number(source, "valuation_cap")
                conversion_price = price * (1 - (number(source, "discount", 0, 0.99) or 0))
                if cap:
                    conversion_price = min(conversion_price, cap / pre_shares)
            if conversion_price <= 0:
                raise ValueError("전환가격은 0보다 커야 합니다")
            shares = math.floor(principal / conversion_price)
            entry = {"name": source.get("name"), "class": "preferred", "shares": shares,
                     "invested": principal}
            entry.update(preference_terms(source))
            add(entry)
            conversions.append({"name": entry["name"], "conversion_price": round(conversion_price, 2),
                                "shares": shares})
        entry = {"name": deal.get("name"), "class": "preferred",
                 "shares": math.floor(investment / price), "invested": investment}
        entry.update(preference_terms(deal))
        add(entry)
        if pool_pct:
            base = sum(h["shares"] for h in holders)
            add({"name": "추가 옵션풀", "class": "option_pool",
                 "shares": math.floor(base * pool_pct / (1 - pool_pct))})
        post_shares = sum(h["shares"] for h in holders)
        round_info = {
            "price_per_share": round(price, 2),
            "conversions": conversions,
            "post_shares": post_shares,
            "headline_post_money": pre_money + investment,
            "fully_diluted_post_money": round(price * post_shares),
        }

    total = sum(h["shares"] for h in holders)
    ownership = [{"name": h["name"], "class": h["class"], "shares": h["shares"],
                  "before_pct": round(pre_pct.get(h["name"], 0), 2),
                  "after_pct": round(h["shares"] / total * 100, 2)} for h in holders]

    # 미행사 옵션풀은 분배 대상에서 제외합니다.
    participants = [h for h in holders if h["class"] != "option_pool"]
    exits = []
    for proceeds in data.get("exits") or []:
        proceeds = number({"exits": proceeds}, "exits", required=True)
        payout, converted = waterfall(proceeds, participants)
        exits.append({
            "proceeds": proceeds,
            "payout": {name: round(value) for name, value in payout.items()},
            "converted_to_common": converted,
            "investor_multiple": {h["name"]: round(payout[h["name"]] / h["invested"], 2)
                                  for h in participants if h.get("invested")},
        })

    return {
        "round": round_info,
        "ownership": ownership,
        "exits": exits,
        "assumptions": [
            "라운드 단가 = 프리머니 / 라운드 직전 완전희석 주식수(전환 전 증권 제외)",
            "전환가 = conversion_price 입력값, 없으면 min(캡/직전 주식수, 단가×(1-할인율))",
            "추가 옵션풀은 라운드 후 지분 기준이며 모든 주주를 희석",
            "누적배당·상환·이자·세금·거래비용·희석방지·참가 상한·종류주 일괄전환은 반영하지 않음",
            "실제 분배는 정관과 투자계약서 원문, 법률 검토를 따름",
        ],
    }


COMMANDS = {"score": score, "metrics": metrics, "market": market, "captable": captable}


def main(argv=None):
    parser = argparse.ArgumentParser(description="vs-ir-eval 숫자 계산 도구")
    parser.add_argument("command", choices=sorted(COMMANDS))
    parser.add_argument("input", help="입력 JSON 파일 경로")
    args = parser.parse_args(argv)
    try:
        with open(args.input, encoding="utf-8") as handle:
            data = json.load(handle)
        if not isinstance(data, dict):
            raise ValueError("입력 JSON 최상위는 객체여야 합니다")
        result = COMMANDS[args.command](data)
    except (OSError, ValueError, TypeError, AttributeError, ArithmeticError) as error:
        print("입력 오류: %s" % error, file=sys.stderr)
        return 2
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    sys.exit(main())
