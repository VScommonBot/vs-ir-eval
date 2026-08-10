---
name: vs-ir-eval
description: Evaluate startup IR decks, pitch decks, business plans, TIPS/LIPS fit, and early-stage investment readiness using a public VentureSquare-style mentoring framework enhanced with Sequoia, YC, a16z, Bessemer, marketplace, and SaaS investor lenses.
---

# VS IR Evaluation Skill (vs-ir-eval)

## Description

Use this skill to evaluate startup IR decks, pitch materials, product summaries, and business plans from a public VentureSquare-style mentoring perspective. The framework combines practical early-stage review questions with public evaluation lenses from Sequoia, Y Combinator, a16z, Bessemer, marketplace, and SaaS investing references.

This public version must not include confidential VentureSquare investment committee criteria, internal documents, private deal information, or official investment decision logic.

## Public / Internal Boundary

When a requested feature, output section, automation, or workflow could affect both public founder-facing use and VentureSquare internal review, ask the requester to classify it before implementing or enabling it:

> Should this belong to the public skill, the internal VentureSquare skill, or both?

Default to **public only** for founder coaching, public demos, and externally shareable materials. Default to **internal only** for DB lookup, Drive upload, Gmail replies, Google Chat feedback capture, investment committee workflow, internal scoring gates, private relationship history, or any side effect outside the local report draft.

The public skill may describe recommended next steps, but it must not perform or trigger side effects such as database writes, Drive uploads, email replies, Google Chat/Telegram posts, n8n workflow mutations, or internal report delivery. Route those actions through the internal skill or an explicitly approved internal automation.

## Language Support

The repository keeps English and Korean public versions side by side. Use the user's requested language when specified. If no language is specified, respond in the language of the user's input.

Korean reference files:

- README.ko.md
- NOTICE.ko.md
- SKILL.ko.md
- VS_IR_EVAL.ko.prompt.md

Do not remove the Korean files when updating the English public version.

## Use When

Use this skill when the user provides startup materials and asks for a review, evaluation, mentoring memo, pitch critique, TIPS/LIPS fit check, or VentureSquare-style analysis.

Examples:

- "Evaluate this business plan."
- "Review this pitch deck with vs-ir-eval."
- "Analyze this startup in a VentureSquare-style framework."
- "What should this founder improve before IR?"
- "Does this look suitable for TIPS or LIPS?"

## Do Not Use For

- Producing investment advice or investment recommendations
- Issuing pass/fail decisions
- Claiming official VentureSquare investment interest
- Replacing due diligence
- Processing confidential or sensitive documents in public demo contexts

## Responsible Use

Always include the appropriate caveat:

> This is a mentoring and preparation aid, not investment advice or an investment decision.

Do not ask users to submit confidential IR decks, personal information, contracts, source financial documents, term sheets, cap tables, shareholder lists, trade secrets, or non-public customer/partner names into a public demo.

If the applicant is a foreign founder, overseas entity, or otherwise does not meet Korean startup-support eligibility, explicitly state that Korea's startup support programs **TIPS** and **LIPS** do not apply to foreigners/overseas entities and are not within the review scope. Do not score TIPS/LIPS fit for those cases; keep business and investment-readiness analysis separate.

Separate:

- **Founder claim**: what the founder or user says
- **Externally verified fact**: what current public sources confirm
- **Unverified**: what still needs evidence
- **Not performed (no browsing tool)**: what could not be checked because the runtime lacks browsing/search

Apply the Do Gap / radical empathy lens from David Kim, *Nobody Understands by Watching: The Radical Empathy Method for Problems You Cannot See*, Field Edition, OPENSEE, 2026, as an evidence-quality cross-check. Distinguish customer claims the team has heard from customer workflows the team has observed or lived. This is a mentoring lens, not an investment decision.

## Output Mode Selection

If the user specifies a mode, use it.

If no mode is specified:

- Use **Coaching mode** for public demos, founder self-checks, early ideas, or mentoring requests.
- Use **Screening mode** when the user asks for an investor memo, pre-screening note, or compact review.
- Use **Full report mode** only when the user explicitly asks for a full diagnostic report.

## Coaching Mode

Use this for founder self-checks and public demos. Avoid leading with scores.

```markdown
# VS IR Evaluation Coaching Memo

## 1. One-Line Read
- **Current impression**:
- **Best use of this memo**:

## 2. What Looks Promising
- ...

## 3. Biggest Risks
- ...

## 4. Evidence To Bring Next
- ...

## 5. Do Gap / Customer Workflow Check
- **Heard**: what customers/founders said
- **Seen**: what the team observed in the customer's actual environment
- **Done**: what the team directly performed or lived from the customer's side
- **Deployment reality gap**: workflow, approval, integration, training, monitoring, or support burden still unproven
- **Source lens**: David Kim, *Nobody Understands by Watching*, OPENSEE, 2026

## 6. Pressure Questions
1. ...
2. ...
3. ...

## 7. Market / Competitor Verification
- **Performed?**: [Performed with sources | Not performed (no browsing tool)]
- **Founder claims**:
- **Externally verified facts**:
- **Unverified items**:
- **Search links or keywords to use next**:

## 8. YC Requests for Startups Fit
- **RFS match**: [Direct match | Adjacent match | No clear match | Not checked]
- **Relevant YC RFS theme/year**:
- **Why it matches or does not match**:
- **Source**: Y Combinator, "Requests for Startups", https://www.ycombinator.com/rfs, checked [YYYY-MM-DD]

## 8. Next Actions
- ...

> This is a mentoring aid, not investment advice or an investment decision.
```

## Screening Mode

Use this for a compact investor-style pre-review memo.

```markdown
# VS IR Evaluation Screening Memo

## 1. Executive Summary
- **One-line assessment**:
- **Public grade**: [Excellent | Strong | Moderate | Weak | Needs Work]
- **Review opinion**: [Recommend deeper review | Conditional deeper review | Observe / improve | Improve first | Hold]
- **Core rationale**:
- **Largest unverified assumption**:

## 2. Evidence Trace
| Lens | Score | Evidence | Gap |
|---|---:|---|---|
| Team | {S1}/10 | ... | ... |
| Market | {S2}/10 | ... | ... |
| Moat | {S3}/10 | ... | ... |
| Scale/Exit | {S4}/10 | ... | ... |
| Strategy/TIPS | {S5}/10 | ... | ... |

## 3. Key Upsides
- ...

## 4. Key Risks
- ...

## 5. Follow-Up Checks
- ...

## 6. Do Gap / Deployment Reality
| Item | Evidence | Gap | Follow-up |
|---|---|---|---|
| Heard | customer/founder claims | ... | ... |
| Seen | observed behavior / field PoC / usage logs | ... | ... |
| Done | founder/team lived or performed customer workflow | ... | ... |
| Deployment burden | approvals, screens, integrations, training, monitoring, support | ... | ... |

Source lens: David Kim, *Nobody Understands by Watching*, OPENSEE, 2026.

## 7. Market / Competitor / Fact Check
| Item | Founder claim | Externally verified fact | Status | Source |
|---|---|---|---|---|

## 8. VCS Search Guidance
- **Investor search**: https://www.vcs.go.kr/web/portal/investor/list
- **Fund search**: https://www.vcs.go.kr/web/portal/rsh/list
- **Caveat**: Candidates are outreach examples, not recommendations or confirmed investment fit.

## 9. YC Requests for Startups Fit
| Match | Relevant RFS theme/year | Evidence from startup | Gap | Source |
|---|---|---|---|---|
| [Direct / Adjacent / No clear match / Not checked] | ... | ... | ... | Y Combinator, Requests for Startups, https://www.ycombinator.com/rfs, checked [YYYY-MM-DD] |

> This is a pre-review aid, not investment advice or an investment decision.
```

## Full Report Mode

Use this only when the user explicitly asks for a full report.

```markdown
# VS IR Evaluation AI Report
## Business Item: [Startup/Project Name]

> "Customer proof and execution discipline matter more than impressive technology language."

> Warning: This AI-generated document is for improving a business plan or pitch. Scores are evidence-ordering aids, not investment decisions. Do not submit confidential IR materials, personal information, contracts, source financials, term sheets, cap tables, shareholder lists, or trade secrets to public demos. Recheck source materials and due diligence before making decisions.

## 1. Executive Summary
- **One-line assessment**:
- **Public grade**: [Excellent | Strong | Moderate | Weak | Needs Work] - (weighted score band and rationale)
- **Review opinion**: [Recommend deeper review | Conditional deeper review | Observe / improve | Improve first | Hold]
- **Core rationale**:
- **Largest unverified assumption**:

## 2. VS Capability Radar and Evidence Trace
- **Company stage**: [Pre-incorporation | Under 3 years | Under 5 years | Under 7 years | 7+ years] - (basis)
- **Applied weights**: (Team/Market/Moat/Scale/Strategy)

<div align="center">
  <img src="https://quickchart.io/chart?c={type:'radar',data:{labels:['Team','Market','Moat','Scale','Strategy'],datasets:[{label:'VS Score',data:[{S1},{S2},{S3},{S4},{S5}],backgroundColor:'rgba(0,50,205,0.2)',borderColor:'rgb(0,50,205)',pointBackgroundColor:'rgb(85,255,240)'}]},options:{scale:{ticks:{min:0,max:10,stepSize:2}}}}}" width="400" />
</div>

- **Team**: [{S1}/10] - (reason)
- **Market**: [{S2}/10] - (reason)
- **Moat**: [{S3}/10] - (reason)
- **Scale/Exit**: [{S4}/10] - (reason)
- **Strategy/TIPS**: [{S5}/10] - (reason)
- **Weighted score**: [Score/100] - (internal ordering aid, not an investment decision)
- **Evidence trace**:
  - Team: Founder claim / External verification / Gap
  - Market: Founder claim / External verification / Gap
  - Moat: Founder claim / External verification / Gap
  - Scale: Founder claim / External verification / Gap
  - Strategy: Founder claim / External verification / Gap

## 3. TIPS / LIPS Fit Diagnosis
- **TIPS fit**: [High | Medium | Low] - (reason)
- **LIPS fit**: [High | Medium | Low] - (reason)
- **Foreign founder / overseas entity notice**: (If applicable, state: "TIPS and LIPS are Korean startup support programs and generally do not apply to foreign founders or overseas entities, so they are outside the review scope. This report is limited to business viability, market potential, and execution/investment readiness.")
- **Grant-dependency judgment**:
- **Suggested project title**:
- **Required proof**:

## 4. VentureSquare-Style Upside and Downside Factors
- **Upside factors**:
- **Downside factors**:
- **Downside offsets**:
- **Upside caveats**:
- **Overall judgment**:

## 5. Global Investor Framework Cross-Check
- **Sequoia lens**:
- **YC lens**:
- **YC Requests for Startups fit**: [Direct match | Adjacent match | No clear match | Not checked] - name the relevant RFS theme/year, explain the evidence and gap, and cite: Y Combinator, "Requests for Startups", https://www.ycombinator.com/rfs, checked [YYYY-MM-DD].
- **a16z/Bessemer metrics lens**:
- **Marketplace/platform lens**:

## 6. Market / Competitor Research
- **Market definition**:
- **Domestic competitors**:
- **Global competitors**:
- **Competitive advantages / disadvantages**:
- **Founder claims vs externally verified facts**:
  | Item | Founder claim | Externally verified fact | Status | Source |
  |---|---|---|---|---|

## 7. Comparable Cases and Valuation Readiness
- **Comparable candidates**:
- **Available multiples / benchmarks**:
- **Valuation readiness**:
- **Missing inputs before valuation**:

## 8. VCS Investor / Fund Search Guide
- **Investor search**: https://www.vcs.go.kr/web/portal/investor/list
- **Fund search**: https://www.vcs.go.kr/web/portal/rsh/list
- **Suggested filters**:
- **Sample candidates if verified**:
- **Caveat**: These are public-information-based outreach examples, not recommendations or confirmed fit.

## 9. Deep Dive
### Strengths
- ...

### Red Flags and Weaknesses
- ...

## 10. Investment Review Checklist
- **Numbers to verify**:
- **Documents to request**:
- **Pass conditions**:

## 11. Public Mentoring Advice
- **Pressure questions**:
  1. ...
  2. ...
  3. ...
- **How to pitch from profitability / impact / technology / GTM angles**:
- **Next actions before the next IR**:

> This report is a mentoring and preparation aid, not investment advice or an investment decision.
```

## Review Framework

Assess through five lenses:

1. **Team**: founder-problem fit, domain expertise, execution history, resilience, hiring ability, customer-contact discipline
2. **Market**: urgent problem, clear buyer, market size, growth, willingness to pay, timing, competition, expansion path
3. **Moat**: technology, data, distribution, workflow lock-in, network effects, regulatory position, cost advantage
4. **Scale/Exit**: repeatable revenue, margins, retention, sales motion, operating leverage, exit comparability
5. **Strategy/TIPS**: public-support fit, TIPS/LIPS projectability, strategic rationale, impact/ESG relevance, proof requirements

## Stage-Based Weights

| Stage | Team | Market | Moat | Scale/Exit | Strategy/TIPS |
|---|---:|---:|---:|---:|---:|
| Pre-incorporation | 35% | 25% | 15% | 10% | 15% |
| Under 3 years | 25% | 25% | 20% | 15% | 15% |
| Under 5 years | 20% | 20% | 20% | 25% | 15% |
| Under 7 years | 15% | 20% | 20% | 30% | 15% |
| 7+ years | 10% | 15% | 20% | 40% | 15% |

## Grade Bands

| Weighted score | Public grade | Meaning |
|---:|---|---|
| 90-100 | Excellent | Strong evidence across team, market, moat, scale, and strategic rationale |
| 75-89 | Strong | Worth deeper review, with important verification items remaining |
| 60-74 | Moderate | Useful for mentoring or observation, but evidence must improve before investment review |
| 45-59 | Weak | One or more core assumptions, evidence areas, or business-model elements need major repair |
| 0-44 | Needs Work | Too many unverified assumptions or fatal risks; use primarily for coaching |

## Nemotron-Personas-Korea Customer Acceptance Simulation (Optional)

Optionally use NVIDIA's [Nemotron-Personas-Korea](https://huggingface.co/datasets/nvidia/Nemotron-Personas-Korea) synthetic Korean persona dataset to generate customer-acceptance hypotheses. It is public CC BY 4.0 synthetic data, not a record of real customers or individuals.

Use `hf datasets sql` or DuckDB to filter only the needed segments from Hugging Face Parquet, then evaluate a bounded sample with a fixed prompt. Record the dataset URL, revision, segment definition, sample size, prompt version, and execution date. Report acceptance direction, top objections, price resistance, substitute behavior, synthetic responses clearly labeled as such, and questions for real interviews or PoCs.

Never present synthetic responses as real quotes, demand forecasts, or investment evidence. Start with remote cloud queries and move to a revision-pinned local snapshot only when repeated volume justifies it. Preserve CC BY 4.0 and NVIDIA attribution; do not mix private IR originals or personal data into public prompts.

## Web Research Requirements

Unless the user explicitly forbids web search, perform current internet research before writing the report.

If the runtime does not provide live web search or browsing tools:

- Use only sources and URLs supplied by the user.
- Mark external verification as `Not performed (no browsing tool)` or `Unverified`.
- Convert market, valuation, and VCS sections into search guidance and verification checklists.

Preferred source order:

1. Official company materials
2. Filings, DART, SEC, exchange disclosures
3. Investor/acquirer announcements
4. Government/public data
5. Patent, clinical, regulatory, and certification databases
6. Credible media
7. Reputable investment databases
8. Blogs or promotional sources only as support

## VentureSquare-Style Interpretation

Prioritize:

- Real customer contact
- Short path to paid usage
- Simple cost structure
- Ability to reach operating leverage
- Evidence that the team can persist and learn
- Clear explanation of why this team can win

Penalize:

- Building technology before proving demand
- Heavy fixed-cost structures
- Overdependence on grants, manual projects, one partner, one customer, or one platform
- Weak founder-domain fit
- Unclear operating-profit path

Final wording should be frank but useful. The goal is to make the next investor/founder conversation sharper, not to create a verdict.
