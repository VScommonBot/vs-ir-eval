# VS IR Evaluation Skill (vs-ir-eval)

`vs-ir-eval` is a public framework for reviewing startup IR decks, pitch materials, and business plans from a VentureSquare-style mentoring perspective.

It goes beyond simple summarization. The skill asks practical early-stage questions such as:

- Are the founders meeting real customers?
- Is the business hard for others to copy?
- Is the team chasing unfinished technology without enough customer evidence?
- Can the company reach paid usage, operating leverage, and a credible exit path?

The 2026-05 version also uses public Sequoia, Y Combinator, a16z, Bessemer, marketplace, and SaaS evaluation lenses as supporting references. This repository is for public use, so it does **not** replace real investment decisions, investment advice, confidential investment committee standards, or due diligence.

Before using this in a public demo or sharing it externally, read [Public Use Notice](NOTICE.md).

## Korean Version

The English public version is the default at the repository root. The Korean version is kept alongside it for Korean founders and reviewers:

- [Korean README](README.ko.md)
- [Korean public notice](NOTICE.ko.md)
- [Korean skill file](SKILL.ko.md)
- [Korean prompt](VS_IR_EVAL.ko.prompt.md)

The demo web app also supports Korean and English output. Select the preferred output language before running a review.

## Usage

Give an OpenClaw agent or compatible prompt runtime a business plan, pitch deck text, product summary, or idea brief and ask:

> "Evaluate this business plan with the vs-ir-eval skill."

> "Analyze this startup in a VentureSquare-style review."

The recommended default is **Coaching mode**. For public demos and founder self-checks, it is safer to focus on questions, missing evidence, and next steps instead of showing scores first.

Use **Screening mode** when you need a compact investor-style pre-review memo. Use **Full report mode** only when a longer diagnostic report is explicitly needed.

## Responsible Use

This skill is a mentoring and preparation aid. Do not outsource investment judgment to AI.

- It must not be used to produce investment advice, suitability judgments, investment commitments, pass/fail notifications, or official VentureSquare investment decisions.
- Public demos must not receive confidential IR materials, personal information, contracts, source financial documents, term sheets, cap tables, shareholder lists, or trade secrets.
- Reviewers must verify outputs through source materials, founder meetings, customer validation, financial/legal/technical due diligence, and human judgment before using them in any decision process.
- AI outputs can vary by model state, prompt interpretation, runtime conditions, and traffic load.
- Numeric scores are secondary ordering aids. Public-facing judgment should use grade bands and traceable evidence.
- Founder claims and externally verified facts must be separated.
- If live browsing is unavailable, the output must mark external verification as `Not performed (no browsing tool)` or `Unverified`.

## Output Format

1. **Executive Summary**: one-line assessment, review opinion, rationale, and the largest unverified assumption
2. **VS Capability Radar and Evidence Trace**: stage classification, stage-based weighting, Team/Market/Moat/Scale/Strategy scores, and evidence gaps
3. **TIPS / LIPS Fit Diagnosis**: technology-startup or local/lifestyle program fit, suggested project title, and required evidence
4. **VentureSquare-Style Upside and Downside Factors**: cost structure, funding ability, time to monetization, dependency risks, and operating-profit path
5. **Global Investor Framework Cross-Check**: Sequoia, YC, a16z/Bessemer, marketplace, and SaaS lenses where applicable
6. **Market, Competitor, and External Fact Check**: separate founder claims, verified facts, and unverified items
7. **Comparable Cases and Valuation Readiness**: comparable candidates and valuation assumptions only when data quality supports them
8. **VCS Investor/Fund Search Guide**: official VCS search guidance and, only when verified, public-information-based sample outreach candidates
9. **Deep Dive**: strengths and red flags
10. **Investment Review Checklist**: numbers, documents, and pass conditions to verify next
11. **Public Mentoring Advice**: pressure questions, missing proof, and next actions for the founder

## Web Research Standard

Unless the user explicitly forbids web search, the skill should perform current internet research before producing a report.

If the runtime does not provide live web search or browsing tools:

- Use only sources and URLs supplied by the user.
- Do not imply that external verification was performed.
- Mark external verification as `Not performed (no browsing tool)` or `Unverified`.
- Turn market, valuation, and VCS sections into search guidance and verification checklists.

Research should prioritize official company materials, filings, DART/SEC/exchange data, investor or acquirer announcements, government/public statistics, patent/clinical/regulatory databases, credible media, and reputable investment databases.

## Grade Bands

| Weighted score | Public grade | Meaning |
|---:|---|---|
| 90-100 | Excellent | Strong evidence across team, market, moat, scale, and strategic rationale |
| 75-89 | Strong | Worth deeper review, with important verification items remaining |
| 60-74 | Moderate | Useful for mentoring or observation, but evidence must improve before investment review |
| 45-59 | Weak | One or more core assumptions, evidence areas, or business-model elements need major repair |
| 0-44 | Needs Work | Too many unverified assumptions or fatal risks; use primarily for coaching |

## Output Modes

- **Coaching mode**: no total score or radar-first framing; focus on pressure questions, missing evidence, and next actions
- **Screening mode**: one-page investor memo with grade, evidence, risks, and follow-up checks
- **Full report mode**: full diagnostic report with all sections

The public web app defaults to Coaching mode because founders can misread score-first outputs as pass/fail judgments.

## Stage-Based Weights

| Stage | Team | Market | Moat | Scale/Exit | Strategy/TIPS |
|---|---:|---:|---:|---:|---:|
| Pre-incorporation | 35% | 25% | 15% | 10% | 15% |
| Under 3 years | 25% | 25% | 20% | 15% | 15% |
| Under 5 years | 20% | 20% | 20% | 25% | 15% |
| Under 7 years | 15% | 20% | 20% | 30% | 15% |
| 7+ years | 10% | 15% | 20% | 40% | 15% |

Key evidence by stage:

- **Pre-incorporation**: founder-problem fit, customer interviews, MVP plan, initial funding plan
- **Under 3 years**: MVP/PoC, first paid customers, usage logs, technical/regulatory/IP validation
- **Under 5 years**: revenue, retention, CAC/LTV, gross margin, customer references
- **Under 7 years**: repeat revenue, sales pipeline, break-even path, follow-on funding or exit feasibility
- **7+ years**: financial statements, operating-profit path, market share, IPO/M&A comparables, governance risks

## VentureSquare-Style Review Factors

Upside factors:

- Simple cost structure and realistic founder/team financing capacity
- Short path from product completion to paid customer usage
- Potential for operating-profit conversion through automation, cost reduction, or unit-economics improvement
- Repeatable revenue supported by systems and process, not only founder heroics

Downside factors:

- Founder or core team lacks relevant experience, domain knowledge, or execution history
- Heavy cost structure that may remain unprofitable even after multiple funding rounds
- High dependency on one customer, partner, platform, institution, grant, or manual project work
- Revenue may exist, but the path to operating profit is unclear

The final opinion should explain either why a downside is tolerable or why an upside is still insufficient.

## External Frameworks Used

- **Sequoia Capital**: purpose, problem, solution, why now, market, competition, product, business model, team, financials, and milestones
- **Y Combinator**: making something people want, early user love, launch discipline, user conversations, and doing things that do not scale
- **a16z**: revenue quality, CAC/LTV, gross margin, churn, retention, burn, engagement, and metric discipline
- **a16z Marketplace**: GMV, take rate, liquidity, fill/match rate, repeat usage, and disintermediation risk
- **Bessemer**: SaaS/cloud ARR/MRR quality, CAC payback, net revenue retention, gross margin, and burn multiple

Reference links:

- https://sequoiacap.com/article/writing-a-business-plan/
- https://www.ycombinator.com/blog/ycs-essential-startup-advice/
- https://www.ycombinator.com/blog/how-not-to-fail/
- https://a16z.com/16-startup-metrics/
- https://a16z.com/the-marketplace-glossary/
- https://www.bvp.com/atlas/10-laws-of-cloud
- https://www.vcs.go.kr/web/portal/investor/list
- https://www.vcs.go.kr/web/portal/rsh/list

## Installation

Copy this repository into your OpenClaw skills directory.

```bash
cd ~/.openclaw/workspace/skills
git clone https://github.com/VScommonBot/vs-ir-eval.git
```

## Web App Setup

`web-app/index.php` is a single-file PHP demo app. Do not place API keys in source code. Inject them only through server environment variables.

```bash
export OPENCLAW_MODEL="openai/gpt-5.4-mini"
export OPENAI_MODEL="<your-preferred-chat-model>"
php -S 127.0.0.1:8080 -t web-app
```

Public deployment checklist:

- Tell users that submitted text is sent to the OpenAI API and require consent.
- Warn users not to submit confidential IR materials, personal information, contracts, source financials, term sheets, cap tables, shareholder lists, or trade secrets.
- Keep Coaching mode as the public default.
- Keep Korean and English outputs available. Do not remove the Korean prompt/doc files when updating the English public version.
- Sanitize Markdown before rendering it as HTML. The demo app uses DOMPurify.
- Add request-size limits, rate limiting, monitoring, and logging appropriate for your server.
- Pin CDN assets with integrity checks or self-host them before production deployment.
- The demo app does not provide live browsing tools. When external verification is needed, connect a separate search/browsing tool or provide verified sources in the input.

## Philosophy

This framework reflects a public VentureSquare-style mentoring view:

- **Conditions for growth**: a timely market, appropriate technology, founders who meet real customers, a defensible reason why this team can win, and a team that can persist.
- **Conditions for failure**: unclear customer definition, obsession with unfinished technology, uncontrollable regulatory risk, weak leadership, and no path to paid usage or operating leverage.
- **Investor lens**: exit feasibility, M&A/global potential, public-support fit such as TIPS/LIPS, and evidence quality.
