"""python3 -m unittest discover -s tests"""

import json
import os
import re
import subprocess
import sys
import unittest

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.join(ROOT, "scripts"))

import ir_calc  # noqa: E402


def scores(value):
    return {axis: value for axis in ir_calc.AXES}


def holders(**terms):
    preferred = {"name": "P", "class": "preferred", "shares": 200, "invested": 1000,
                 "multiple": 1, "rank": 1, "participating": False}
    preferred.update(terms)
    return [{"name": "C", "class": "common", "shares": 800}, preferred]


class ScoreTest(unittest.TestCase):
    def test_weights_match_skill_documents(self):
        # 문서의 가중치 표가 바뀌면 계산 도구도 함께 바뀌어야 합니다.
        for filename in ("SKILL.md", "SKILL.ko.md"):
            with open(os.path.join(ROOT, filename), encoding="utf-8") as handle:
                text = handle.read()
            rows = re.findall(r"^\|[^|\n]+\|" + r"\s*(\d+)%\s*\|" * 5, text, re.M)
            self.assertEqual([tuple(map(int, row)) for row in rows],
                             list(ir_calc.STAGE_WEIGHTS.values()), filename)

    def test_grade_boundaries_do_not_round_up(self):
        for value, grade in ((10, "최상"), (9, "최상"), (7.5, "우수"), (7.49, "보통"),
                             (6, "보통"), (4.5, "미흡"), (4.4, "보완 필요"), (0, "보완 필요")):
            result = ir_calc.score({"stage": "pre", "scores": scores(value)})
            self.assertEqual(result["grade"], grade, value)

    def test_missing_axis_holds_total(self):
        partial = scores(8)
        partial["moat"] = None
        result = ir_calc.score({"stage": "pre", "scores": partial})
        self.assertIsNone(result["weighted_score"])
        self.assertEqual(result["missing"], ["moat"])

    def test_rejects_bad_input(self):
        for bad in (11, -1, True, "7", float("nan")):
            with self.assertRaises(ValueError):
                ir_calc.score({"stage": "pre", "scores": scores(bad)})


class MetricsTest(unittest.TestCase):
    def test_burn_multiple_uses_same_period(self):
        result = ir_calc.metrics({"monthly_net_burn": 10, "net_new_arr": 60, "period_months": 12})
        self.assertEqual(result["recalculated"]["burn_multiple"], 2)

    def test_claim_mismatch_and_unknown(self):
        result = ir_calc.metrics({"cash": 120, "monthly_net_burn": 10,
                                  "claims": {"runway_months": 18, "ltv": 100}})
        self.assertEqual(result["claim_check"]["runway_months"]["status"], "불일치")
        self.assertEqual(result["claim_check"]["ltv"]["status"], "미확인")

    def test_zero_churn_is_not_invented(self):
        result = ir_calc.metrics({"arpu_monthly": 10, "gross_margin": 0.5, "monthly_churn": 0})
        self.assertIsNone(result["recalculated"]["ltv"])


class MarketTest(unittest.TestCase):
    def test_foreign_currency_shows_krw(self):
        result = ir_calc.market({
            "currency": "USD", "krw_per_unit": 1000, "fx_date": "2026-01-01",
            "bottom_up": {"total_customers": 10, "serviceable_customers": 5,
                          "obtainable_customers": 1, "annual_revenue_per_customer": 100}})
        self.assertEqual(result["methods"]["bottom_up"]["tam"], "USD 1,000 (약 1,000,000원)")

    def test_foreign_currency_needs_rate_and_date(self):
        with self.assertRaises(ValueError):
            ir_calc.market({"currency": "USD"})
        with self.assertRaises(ValueError):
            ir_calc.market({"currency": "USD", "krw_per_unit": 0, "fx_date": "2026-01-01"})

    def test_top_down_only_is_warned(self):
        result = ir_calc.market({"top_down": {"total_market": 100, "serviceable_share": 0.5,
                                             "obtainable_share": 0.1}})
        self.assertTrue(any("하향식 추정만" in w for w in result["warnings"]))


class WaterfallTest(unittest.TestCase):
    def payout(self, proceeds, entries):
        return {k: round(v, 6) for k, v in ir_calc.waterfall(proceeds, entries)[0].items()}

    def test_non_participating(self):
        self.assertEqual(self.payout(500, holders()), {"C": 0, "P": 500})
        self.assertEqual(self.payout(2000, holders()), {"C": 1000, "P": 1000})
        self.assertEqual(self.payout(10000, holders()), {"C": 8000, "P": 2000})

    def test_participating(self):
        self.assertEqual(self.payout(2000, holders(participating=True)), {"C": 800, "P": 1200})

    def test_senior_rank_is_paid_first(self):
        entries = holders(rank=2)
        entries.append({"name": "S", "class": "preferred", "shares": 100, "invested": 900,
                        "multiple": 1, "rank": 1, "participating": False})
        self.assertEqual(self.payout(1200, entries), {"C": 0, "P": 300, "S": 900})

    def test_preferred_only_holders_can_convert(self):
        # 보통주가 없어도 전환 여부 비교 중에 멈추면 안 됩니다.
        only = [{"name": "P", "class": "preferred", "shares": 100, "invested": 1000,
                 "multiple": 1, "rank": 1, "participating": False}]
        self.assertEqual(self.payout(500, only), {"P": 500})
        self.assertEqual(self.payout(2000, only), {"P": 2000})
        two = only + [dict(only[0], name="Q")]
        self.assertEqual(self.payout(3000, two), {"P": 1500, "Q": 1500})

    def test_undistributable_remainder_is_rejected(self):
        no_shares = [{"name": "P", "class": "preferred", "shares": 0, "invested": 1000,
                      "multiple": 1, "rank": 1, "participating": False}]
        with self.assertRaises(ValueError):
            ir_calc.waterfall(2000, no_shares)

    def convertible_case(self, **terms):
        return {"holders": [{"name": "C", "class": "common", "shares": 100}],
                "convertibles": [dict({"name": "S", "principal": 1000}, **terms)],
                "round": {"name": "R", "pre_money": 1000, "investment": 100}}

    def test_convertible_without_terms_is_rejected(self):
        # 전환조건이 없는데 라운드 단가로 전환했다고 가정하지 않습니다.
        for terms in ({}, {"valuation_cap": 0}):
            with self.assertRaises(ValueError):
                ir_calc.captable(self.convertible_case(**terms))
        result = ir_calc.captable(self.convertible_case(discount=0))
        self.assertEqual(result["round"]["conversions"][0]["shares"], 100)

    def test_defaulted_preference_terms_are_reported(self):
        result = ir_calc.captable(self.convertible_case(discount=0, multiple=1))
        self.assertEqual(result["assumed_terms"], [
            {"name": "S", "fields": ["participating", "rank"]},
            {"name": "R", "fields": ["multiple", "participating", "rank"]}])

    def test_all_proceeds_are_distributed(self):
        with open(os.path.join(ROOT, "examples", "captable.json"), encoding="utf-8") as handle:
            result = ir_calc.captable(json.load(handle))
        for scenario in result["exits"]:
            self.assertAlmostEqual(sum(scenario["payout"].values()), scenario["proceeds"], delta=3)
        self.assertAlmostEqual(sum(h["after_pct"] for h in result["ownership"]), 100, delta=0.05)

    def test_duplicate_name_rejected(self):
        row = {"name": "A", "class": "common", "shares": 1}
        with self.assertRaises(ValueError):
            ir_calc.captable({"holders": [row, row]})


class CommandLineTest(unittest.TestCase):
    def run_tool(self, command, path):
        return subprocess.run(
            [sys.executable, os.path.join(ROOT, "scripts", "ir_calc.py"), command, path],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE)

    def test_every_example_runs(self):
        for command in ir_calc.COMMANDS:
            done = self.run_tool(command, os.path.join(ROOT, "examples", command + ".json"))
            self.assertEqual(done.returncode, 0, done.stderr.decode("utf-8", "replace"))

    def test_missing_file_exits_2(self):
        self.assertEqual(self.run_tool("score", os.path.join(ROOT, "examples", "none.json")).returncode, 2)


if __name__ == "__main__":
    unittest.main()
