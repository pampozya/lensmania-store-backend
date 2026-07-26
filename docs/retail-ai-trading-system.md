# Retail Hybrid-AI Trading System — Design Blueprint

**Role framing:** Senior Quantitative Strategist / System Architect perspective, targeted at a
retail trader with standard brokerage API access (Interactive Brokers or Alpaca), retail capital
(assume $25k–$250k), and no low-latency infrastructure.

**Honest disclaimer up front:** nothing here guarantees profit. Every edge described below is a
*statistical tendency* that decays, has drawdowns longer than you expect, and can be destroyed by
overfitting in backtests. The system design assumes you will paper-trade for months before risking
real money, and that you will size positions so that being wrong for a year is survivable.

---

## 1. The Retail Moat

The core insight: you cannot out-speed institutions, but you can operate where they *structurally
cannot go*. Institutional constraints are your moat, not your cleverness.

### Inefficiency A — Capacity-constrained small/mid-cap swing trades (multi-day horizon)

A $2B fund cannot deploy meaningful capital into a $400M market-cap stock trading $3M/day without
becoming the market. You can enter and exit a $5k–$50k position with negligible impact. Anomalies
that were arbitraged away in large caps decades ago (momentum continuation, post-event drift,
overreaction reversals) remain measurably stronger in the small/mid-cap segment precisely because
the players with the research budget can't harvest them at scale. Time horizon of 2–10 days also
sidesteps HFT entirely: the HFT firms *are* your counterparty for the entry fill, but they are not
competing for your alpha, which plays out over days.

### Inefficiency B — Post-Earnings Announcement Drift (PEAD), event-driven

PEAD is one of the oldest and most-replicated anomalies in the academic literature (Ball & Brown
1968; Bernard & Thomas 1989; still detectable in recent out-of-sample studies, concentrated in
smaller names): stocks that report a large positive earnings surprise tend to *continue drifting
up* for days-to-weeks after the announcement, because information diffuses slowly — analysts revise
estimates over days, funds rebalance over days, retail attention arrives over days. It persists in
small/mid caps for the capacity reasons in (A). It is event-driven, so you trade a handful of
well-defined moments instead of needing a continuous presence in the market — perfect for a
lightweight retail stack.

### Inefficiency C — Text/narrative processing speed at *human* timescales

Institutions have NLP desks, but their pipelines are tuned for large liquid names. For a
sub-$2B company, the earnings call transcript, the 8-K footnote, or the guidance language often
takes the market *days* to fully digest. A modern LLM reads a transcript in seconds and can grade
guidance quality, management tone shift, and one-off-vs-recurring earnings composition better than
a keyword model. This is not a latency edge — it's a *comprehension-per-dollar* edge that only
recently became available to individuals.

### Recommendation: combine B + C on the universe of A

**Strategy: LLM-filtered Post-Earnings Announcement Drift in small/mid-caps, multi-day swing
horizon.** PEAD supplies the statistical base rate (the quant edge); the LLM supplies a qualitative
filter that separates "clean beat with raised guidance" from "beat on a tax one-off with soft
guidance" — exactly the distinction that pure SUE-ranking systems miss and that takes a human
analyst 45 minutes per name. This is the single best fit for the constraints: no latency
requirement, capacity-constrained niche, discrete tradeable events, and a genuine use for the LLM
rather than LLM-as-decoration.

Rejected alternatives, for the record:
- **Statistical arbitrage / pairs trading:** viable but the edge is thin, spreads matter more,
  and institutions *do* compete here at every capacity level. Second choice, not first.
- **Intraday sentiment scalping on news:** loses to institutional NLP + colocation. This is the
  trap most retail "AI trading" projects fall into.
- **Options selling strategies:** positive expectancy is real but the tail risk profile is wrong
  for a first automated system.

---

## 2. The Logic — Exact Mechanics

### 2.1 Universe definition (recomputed weekly)

- US common stocks, primary listing, price > $5 (avoids microstructure junk and PDT-adjacent issues)
- Market cap $300M – $10B
- 20-day average dollar volume > $2M and > 40× your intended position size (your fill must be noise)
- Exclude: biotech pre-revenue names (binary FDA risk swamps PEAD), stocks with earnings-day
  options IV > 150% (the move is priced), recent IPOs (< 4 quarters of reporting history)

Expected universe: roughly 800–1,500 names; ~15–40 report earnings on any given day in season.

### 2.2 Data feeds

| Purpose | Source | Cost tier |
|---|---|---|
| Earnings calendar + estimates | Financial Modeling Prep, or Alpha Vantage, or Nasdaq API | $0–50/mo |
| EOD + intraday bars, corporate actions | Polygon.io or Alpaca Market Data | $0–200/mo |
| Transcripts / press releases | FMP transcripts endpoint, company IR RSS, EDGAR 8-K full-text | $0–50/mo |
| Execution + positions | Alpaca (simpler) or IBKR (better borrow/fills, more asset classes) | commission |
| Macro regime inputs | FRED API (rates, credit spreads), CBOE VIX | free |

### 2.3 Signal: the quantitative gate (deterministic, computed pre-open)

For every stock that reported earnings after yesterday's close or before today's open:

1. **Standardized Unexpected Earnings (SUE):**
   `SUE = (actual EPS − consensus EPS) / σ(last 8 quarters' surprises)`.
   Require `SUE ≥ +1.5` (long side). (A short-side mirror at `SUE ≤ −1.5` is optional and should
   be added only after the long side is live and stable — borrow costs and squeeze risk make
   shorts a phase-2 concern.)
2. **Revenue confirmation:** revenue surprise ≥ 0. Screens out pure EPS-engineering beats.
3. **Price reaction confirmation:** overnight gap between +2% and +12% vs. prior close. Below +2%
   the market disagrees with the "surprise"; above ~+12% the drift is statistically mostly spent
   and you're buying a blow-off.
4. **Volume confirmation (checked 30 min after open):** first-30-minute volume ≥ 3× the 20-day
   average for that window. Drift needs participation.
5. **Trend non-hostility:** price above its 200-day SMA, or gap carries it above. PEAD longs in
   downtrends underperform materially.

Stocks passing all five gates become **candidates**. Typical yield: 0–5 candidates/day in season.

### 2.4 Signal: the LLM qualitative gate (the hybrid edge)

For each candidate, the Brain (Section 3) receives the press release, transcript (if published),
and guidance table, and must return **structured JSON only**:

```json
{
  "ticker": "XYZ",
  "beat_quality": 0.0,          // 0–1: recurring-operations-driven vs one-off (tax, sale, accrual)
  "guidance_direction": "raised|maintained|lowered|none",
  "guidance_conviction": 0.0,   // 0–1: specificity and confidence of forward language
  "tone_shift": -1.0,           // -1..+1 vs prior quarter's call, hedging density, Q&A evasiveness
  "red_flags": ["..."],         // CFO departure, receivables ballooning, customer concentration...
  "one_line_thesis": "...",
  "confidence": 0.0             // LLM self-rating of evidence quality (transcript present? guidance explicit?)
}
```

**Trade gate:** `beat_quality ≥ 0.6` AND `guidance_direction ∈ {raised, maintained}` AND
`red_flags` empty-or-minor AND `confidence ≥ 0.5`. The LLM can only **veto or approve**
candidates that already passed the quantitative gate. It cannot originate trades, cannot size
them, and cannot touch execution. This containment is deliberate: LLM output is
non-deterministic, so it is used where non-determinism is tolerable (filtering) and excluded
where it is not (risk and execution).

Macro overlay (also from the Brain, one call per day): a `risk_state ∈ {normal, caution, off}`
flag from VIX level/term structure, credit spreads, and scheduled macro events (FOMC, CPI).
`caution` halves all new position sizes; `off` blocks new entries (existing positions run their
normal exits). The rules mapping macro inputs → risk_state are deterministic; the LLM only
summarizes the calendar and flags surprises, it does not set the state.

### 2.5 Entry

- Enter on day T+0 (the first regular session after the report) **at 10:00 ET**, after the
  volume-confirmation check — not at the open, where spread and volatility are worst.
- **Marketable limit order:** limit = bid + min(0.25 × ATR(14)_daily, 0.15% of price). Unfilled
  after 15 minutes → re-peg once; unfilled after 30 → cancel, skip the trade. Chasing is how
  backtest edges die in production.

### 2.6 Position sizing (volatility-normalized, fractional-Kelly-capped)

```
risk_per_trade   = 0.75% of account equity          (the amount lost if stop is hit)
stop_distance    = 2.0 × ATR(14, daily)
shares           = (equity × 0.0075) / stop_distance
position_cap     = min(10% of equity, 5% of the stock's 20-day avg dollar volume)
shares           = min(shares, position_cap / price)
```

- Max 6 concurrent positions; max 2 per GICS sector.
- Under `risk_state = caution`: risk_per_trade → 0.375%.
- **Circuit breakers (hard, enforced by the Reflexes, not configurable by the Brain):**
  - Daily portfolio loss ≥ 3% of equity → flatten nothing, but block all new entries until next day.
  - Peak-to-trough drawdown ≥ 12% → system halts, requires manual restart. If your backtest says
    this "can't happen," your backtest is wrong.

### 2.7 Exits (first trigger wins)

1. **Stop-loss:** close below entry − 2×ATR (evaluated on close, executed next open, to avoid
   intraday stop-hunts on thin names; use a hard intraday stop at 3×ATR as disaster insurance).
2. **Time stop:** close everything at T+8 trading days. PEAD is mostly consumed within ~2 weeks;
   overstaying converts an event trade into an unmanaged bet.
3. **Trailing take-profit:** once unrealized gain ≥ 2×ATR, trail a stop at highest-close − 1.5×ATR.
4. **Thesis invalidation:** guidance withdrawn, secondary offering announced, or material 8-K →
   Brain flags it in the daily review → exit next open.

Expected regime (to be validated, not assumed): ~45–55% win rate, average winner ≈ 1.6–2.2× average
loser, 100–250 trades/year concentrated in earnings seasons. The edge, if present, shows up in the
aggregate, not in any single trade.

---

## 3. The Architecture — "Hybrid AI": Brain + Reflexes

Principle: **the LLM thinks slowly once a day; a dumb, deterministic Python process acts quickly
all day.** They communicate only through a validated, signed file. The LLM is never in the
execution loop and has no broker credentials.

```
                        ┌──────────────────────────────────────────────┐
                        │  THE BRAIN  (batch, runs 06:30 ET, ~2 min)   │
                        │  LLM API (e.g. Claude via Anthropic API)     │
                        │                                              │
  earnings calendar ───▶│  1. macro digest  → risk_state (rule-mapped) │
  press releases ──────▶│  2. per-candidate transcript analysis        │
  transcripts ─────────▶│  3. open-position news review (invalidation) │
  8-K / IR feeds ──────▶│                                              │
  FRED / VIX ──────────▶│  output: signals_YYYY-MM-DD.json (schema-    │
                        │  validated, checksummed)                     │
                        └───────────────┬──────────────────────────────┘
                                        │  file drop (local disk / S3)
                                        ▼
        ┌───────────────────────────────────────────────────────────────┐
        │  THE REFLEXES  (long-running local Python process, asyncio)   │
        │                                                               │
        │  06:45  load + validate signal file (pydantic schema; if      │
        │         invalid/missing/stale → NO new trades today)          │
        │  09:30  monitor gaps, compute ATR sizing, volume check        │
        │  10:00  entry engine: marketable-limit state machine          │
        │  cont.  slippage manager: track fill vs decision price,       │
        │         kill entries if session slippage > 0.4% budget        │
        │  cont.  exit engine: stops / trails / time stops              │
        │  16:05  reconcile positions vs broker; write day log          │
        │                                                               │
        │  hard-coded, Brain-untouchable: circuit breakers, size caps,  │
        │  kill switch (file flag + CLI), order rate limiter            │
        └───────────────┬───────────────────────────────────────────────┘
                        │ REST/websocket
                        ▼
              Broker API (Alpaca / IBKR via ib_insync)
```

### 3.1 The Brain — design rules

- **Structured output only.** Every prompt demands JSON matching a published schema; the response
  is parsed with `pydantic` and *rejected* (not repaired by another LLM call loop) on failure —
  a malformed day is a no-trade day, not a guess day.
- **Grounding:** the prompt contains the full source documents. The LLM is instructed to cite the
  transcript line supporting each score, which measurably reduces hallucinated optimism and gives
  you an audit trail.
- **Determinism hygiene:** temperature 0, fixed prompt version string logged with every output,
  and each quarter's prompts are archived so you can re-run history when you change them.
- **Cost:** ~5 candidates × ~15k tokens + one macro call ≈ well under $1/day at current API pricing.
- **Prompt-injection surface:** transcripts and press releases are adversarial-ish inputs (IR
  departments write to persuade). Two mitigations: (1) the schema constrains output to scores, so
  the blast radius of a "persuasive" document is one bad filter decision on one capped position —
  never an execution action; (2) instruct the model to grade language *as evidence*, explicitly
  scoring promotional tone negatively.

### 3.2 The Reflexes — design rules

- Single Python process, `asyncio`, on a small always-on box (a $10/mo VPS or a mini-PC at home;
  latency to the broker is irrelevant at this horizon — reliability is what matters).
- **State machine per order:** `PENDING → SUBMITTED → PARTIAL → FILLED/CANCELLED`, persisted to
  SQLite after every transition. On crash/restart: reload state, reconcile against broker's
  open-orders and positions endpoints *before* doing anything else. The broker's answer wins.
- **Slippage management:** record decision price at signal time; every fill logs
  `slippage_bps = (fill − decision) / decision`. Rolling per-name and per-day stats; a name that
  repeatedly costs > 20 bps gets ejected from the universe. Daily slippage budget 0.4% of traded
  notional — exceeded → entries stop (exits never stop).
- **Idempotency:** every order carries a client-order-id derived from `(date, ticker, intent)`,
  so a crash-and-retry can never double-order.
- **Watchdog:** external cron (or the VPS provider's monitor) pings a heartbeat file; missed
  heartbeat → alert to your phone. The Reflexes also alert on: fill, stop-hit, circuit-breaker,
  reconciliation mismatch, stale signal file.
- **Kill switch:** touching a `HALT` file (or one CLI command) → cancel all open orders, block
  entries, optionally flatten. Test it monthly.

### 3.3 Why this split is the right architecture

- The failure modes are decorrelated: an LLM outage means "no new trades today" (safe); a Reflexes
  bug is caught by hard caps the Brain can't modify; a broker outage is handled by the
  reconciliation-first restart.
- Everything the Brain does is *auditable the next morning* because it is batch: you can read
  exactly why it vetoed a trade, with cited transcript lines.
- It is cheap: no streaming infra, no GPU, no colocation — one VPS, three data subscriptions, and
  pennies of LLM tokens.

---

## 4. Development Roadmap

Build order is chosen so that every phase produces something testable and the system earns trust
before it earns money. Do not reorder phases 5–8; skipping paper trading is how accounts die.

### Phase 0 — Foundations (week 1)
- [ ] Repo scaffold: `brain/`, `reflexes/`, `research/`, `common/` (shared pydantic schemas), `tests/`
- [ ] Broker account with API access (start Alpaca paper — free, instant); data subscriptions
- [ ] Secrets in environment/keychain, never in code; SQLite for state; structured JSON logging

### Phase 1 — Data layer (weeks 1–2)
- [ ] Ingest: daily bars, earnings calendar with consensus estimates, actuals, transcripts
- [ ] **Point-in-time discipline:** store *when* each datum became known (estimate as of T−1, not
      today's revised value). Look-ahead bias here silently fabricates the whole edge.
- [ ] Build 5+ years of historical universe + earnings events (survivorship-bias-free — include
      delisted tickers; this is why Polygon/FMP-grade data is worth paying for)

### Phase 2 — Backtest the quantitative core alone (weeks 3–5)
- [ ] Event-study backtester (custom ~500 lines, or `vectorbt`): for every historical event
      passing gates 1–5, simulate entry/exits/sizing exactly as specced, with conservative cost
      model (spread/2 + 10 bps impact + commissions)
- [ ] Walk-forward evaluation: tune nothing on the last 2 years; treat them as one-shot out-of-sample
- [ ] **Go/no-go:** quant core alone must show positive expectancy after costs (target: profit
      factor ≥ 1.3, max DD < 20% at spec sizing, ≥ 150 events in the OOS window). If it fails,
      stop here — the LLM layer cannot rescue a dead base signal, only refine a live one.

### Phase 3 — Brain v1 (weeks 5–7)
- [ ] Prompt + schema for earnings analysis; temperature 0; citation requirement
- [ ] Backtest the *filter*: run the Brain over historical transcripts for OOS-period events and
      measure whether LLM-approved events outperform LLM-vetoed events. This is the honest test of
      the hybrid premise. (Caveat to keep in mind: the model may have training-data familiarity
      with famous names/quarters; mitigate by weighting evaluation toward obscure small-caps.)
- [ ] Macro digest prompt + deterministic risk_state mapping
- [ ] **Go/no-go:** filter must improve expectancy or cut drawdown on OOS events; otherwise ship
      the pure quant system and keep the Brain in shadow mode

### Phase 4 — Reflexes v1 (weeks 7–10)
- [ ] Signal-file loader with schema validation + staleness check
- [ ] Order state machine + SQLite persistence + idempotent client-order-ids
- [ ] Entry engine (marketable-limit + re-peg logic), exit engine (stop/trail/time), sizing module
- [ ] Circuit breakers, kill switch, heartbeat, phone alerts
- [ ] Unit tests for every transition; chaos tests: kill -9 mid-order, feed it a corrupt signal
      file, replay a broker disconnect — reconciliation must survive all three

### Phase 5 — Integration + paper trading (weeks 10–18, minimum 2 earnings seasons ≈ but at
least 8 weeks of live paper fills)
- [ ] Full pipeline on Alpaca paper, running unattended
- [ ] Track: paper fills vs backtest assumptions (slippage model honest?), Brain JSON validity
      rate, crash/restart count, every alert fired
- [ ] Weekly review ritual: read every Brain rationale; you are auditing your analyst

### Phase 6 — Live, minimum size (months 4–6)
- [ ] Real money, 0.25% risk per trade (⅓ of spec), max 3 positions
- [ ] **Promotion criteria to full spec:** ≥ 30 live trades, realized slippage within 1.5× of
      model, zero reconciliation mismatches, zero manual interventions needed
- [ ] Keep a manual trade journal anyway — you're validating the *system*, including yourself

### Phase 7 — Full deployment + research loop (month 6+)
- [ ] Scale to spec sizing; add the short-side PEAD mirror if long-side live stats hold
- [ ] Quarterly: re-run walk-forward including new data; retire the strategy if OOS expectancy
      decays below costs for 2 consecutive quarters (edges die; plan the funeral in advance)
- [ ] Only then consider strategy #2 (e.g., the stat-arb sleeve) for diversification

### Standing rules
1. The backtest is always lying to you a little; the only question is how much. Costs +20%,
   expectancy −30% is the right haircut for planning.
2. Never let the Brain gain write-access to sizing, risk limits, or execution. Ever.
3. Every discretionary override of the system gets logged and reviewed — the system's worst enemy
   is its operator at 2 a.m.

---

*This document is an engineering/design blueprint, not investment advice. Past anomalies
(including PEAD) may not persist, and automated trading can lose money rapidly.*
