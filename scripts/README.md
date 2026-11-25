# Number Calculator (ir_calc.py)

The review framework and people make the judgments; this tool does the arithmetic, so the same input always gives the same result. It uses only the Python 3 standard library, writes no files, and makes no network calls. Output is Korean.

Results are aids for ordering evidence, not investment decisions.

```bash
python3 scripts/ir_calc.py score    examples/score.json
python3 scripts/ir_calc.py metrics  examples/metrics.json
python3 scripts/ir_calc.py market   examples/market.json
python3 scripts/ir_calc.py captable examples/captable.json
```

Exit code 0 means success and 2 means an input error. All values in `examples/` are synthetic.

## score

- `stage`: `pre`, `under_3y`, `under_5y`, `under_7y`, `over_7y`
- `scores`: `team`, `market`, `moat`, `scale`, `strategy` (0-10)
- Stage weights and grade bands match `SKILL.md`. Grades print as 최상 (Excellent), 우수 (Strong), 보통 (Moderate), 미흡 (Weak), 보완 필요 (Needs Work).
- If any axis is missing, no total or grade is produced.

## metrics

- Input: `arpu_monthly`, `gross_margin` (0-1), `monthly_churn` (0-1), `cac`, `cash`, `monthly_net_burn` (monthly average), `net_new_arr` (over the period), `period_months`
- `claims`: values stated in the deck (`ltv`, `ltv_cac`, `cac_payback_months`, `runway_months`, `burn_multiple`). A gap above 5% is marked `불일치` (mismatch); change it with `tolerance`.
- Burn multiple = monthly net burn x months / net new ARR over the same period
- Reference lines (LTV/CAC 3x, payback 18 months, burn multiple 2x, runway 18 months) prompt follow-up questions; they are not pass criteria.

## market

- `top_down`: `total_market`, `serviceable_share`, `obtainable_share` (0-1), `source`, `source_year`
- `bottom_up`: `total_customers`, `serviceable_customers`, `obtainable_customers`, `annual_revenue_per_customer`
- Shares have no defaults; supply them with evidence.
- A gap above 3x between methods raises a warning (`max_ratio`). A tam/sam/som claim within 10% is a match.
- Foreign-currency input needs `currency`, `krw_per_unit`, and `fx_date`; the KRW equivalent is shown in parentheses.

## captable

- `holders`: `name`, `class` (`common`, `preferred`, `option_pool`), `shares`; preferred also takes `invested`, `multiple`, `participating`, `rank` (1 is most senior)
- `convertibles`: `name`, `principal`, and `conversion_price` or `valuation_cap` / `discount`
- `round`: `name`, `pre_money`, `investment`, `option_pool_topup_pct` (0-0.5), preference terms
- `exits`: list of distributable amounts

Assumptions and limits:

- Round price = pre-money / fully diluted shares just before the round (excluding unconverted securities). If the contract uses another basis, pass `conversion_price`.
- Unexercised option pool receives no exit proceeds.
- Non-participating preferred converts holder by holder when that pays more.
- Cumulative dividends, redemption, interest, tax, fees, anti-dilution, participation caps, and class-wide conversion are not modeled.
- The tool does not judge the legal or tax effect of RCPS, convertible bonds, SAFE-type contracts, or other instruments under Korean law. Check the articles, contracts, and legal advice.

Do not enter real shareholder lists or deal terms into public demos or external AI services; run the calculation locally.

## Tests

```bash
python3 -m unittest discover -s tests -v
```
