# Crypto Hybrid-AI Swing System — Binance Futures Design Blueprint

**Companion to** `retail-ai-trading-system.md` (equity PEAD system). Same architectural
philosophy — deterministic Reflexes, batch LLM Brain with veto-only power — re-derived for
Binance USDT-M perpetual futures, 4H/1D timeframe, 1–3 trades/day, 3–5x max leverage.

**Honest disclaimer:** leveraged crypto perps are one of the fastest legal ways to lose money.
Every number below is a starting spec to be validated in backtest and on testnet, not a promise.
Momentum edges in crypto decay fast and regime-shift violently; the risk framework is the product,
the entry signal is a parameter.

---

## 1. The Crypto Retail Moat — "Contextual Momentum"

### Why this niche is defensible for a retail trader

- **You correctly refuse the fee trap.** On Binance USDT-M, taker fee ~4–5 bps per side. A
  hundred round-trips a day at 3x leverage is ~2.4%+ of notional daily bled to fees before
  slippage — that game belongs to market makers with rebates. At 1–3 trades/day targeting
  3–5% moves, fees are ~2–3% of the *profit target* instead of a multiple of it.
- **The 4H/1D timeframe is where systematic crypto flow is thinnest.** HFT owns the ms-to-minutes
  book; funds running billions can't be bothered with the capacity of a $50k–$500k swing book on
  alt perps. Multi-hour momentum after structural breaks persists because the marginal
  participant on that horizon is a discretionary human reacting late.
- **Crypto uniquely publishes its positioning.** Funding rates, open interest, and liquidation
  data are public in real time. In equities you infer crowding; in perps you *read* it. A
  breakout occurring while funding is negative (shorts paying longs) means the crowd is leaning
  the wrong way and the move has squeeze fuel. This is the core of "contextual" momentum:
  **price signal + positioning context + narrative context.**
- **The narrative layer is where the LLM earns its keep.** Crypto moves on scheduled, public,
  text-shaped catalysts that pure TA is blind to: token unlock cliffs, FOMC/CPI prints, exchange
  incidents, regulatory headlines, protocol upgrades/exploits. A breakout 48h before a 5% supply
  unlock is statistically a different trade than the same chart without one. Institutions have
  analysts for this; you have a Brain call that costs cents.

### The strategy in one sentence

Trade 4H structural breakouts on the top-15 volume USDT perps **only when** positioning
(funding/OI) says the crowd is offside **and** the Brain finds no scheduled catalyst or news
regime that explains the move away or is about to reverse it.

### What the LLM filters (fakeout taxonomy)

| Fakeout type | Detection input given to the Brain |
|---|---|
| Unlock-front-running pump | Token unlock calendar (Tokenomist/CryptoRank-style data): size %, date, recipient type (team/VC vs ecosystem) |
| Macro head-fake | Economic calendar: FOMC, CPI, NFP within 24h → breakouts into these events are coin-flips |
| Exchange/legal shock | Curated news headlines (CoinDesk/The Block RSS + exchange status pages) last 48h |
| Listing/delisting pop | Binance announcements feed (new perp listings spike then mean-revert hard) |
| Exploit/depeg contagion | Same news feed; anything mentioning the token's protocol, bridge, or major holder |
| Pure narrative froth | Brain grades whether the move has an identifiable driver at all; driverless vertical moves on low-cap alts score low |

The Brain never reads charts and never predicts price. It answers one question per candidate:
*"Is there contextual information that makes this breakout untrustworthy?"* — APPROVE / VETO,
with cited evidence.

---

## 2. The Logic & Risk Management

### 2.1 Universe (recomputed daily, 00:00 UTC)

- Top 15 USDT-M perps by 30-day median daily volume, **excluding** perps listed < 60 days
  (post-listing dynamics are their own regime) and anything with 30-day median |funding| >
  0.15%/8h (chronically broken basis = untradeable carry).
- BTC and ETH always included; they anchor the regime filter.

### 2.2 Quantitative triggers (computed on 4H bar close only — never intra-bar)

**LONG setup — all conditions on the same closed 4H bar:**

1. **Structure:** close crosses **above the 50-bar SMA(4H)** after ≥ 10 consecutive bars below
   it (a genuine regime cross, not chop around a flat MA). Additionally the 50-SMA slope over
   the last 10 bars must be ≥ 0 or the close must also clear the highest high of the last 20
   bars — one of the two, so you're buying either a turn or a range break, not a dead-cat.
2. **Volume:** bar volume ≥ **3× the 20-bar average** (your "200% spike" = +200% vs average).
   Measured in quote (USDT) volume, not contracts.
3. **Positioning fuel:** current funding rate ≤ **0** (shorts paying longs), or funding ≤
   +0.01%/8h *and* funding has declined for 3 consecutive intervals. Negative funding into an
   upside break = trapped shorts.
4. **Participation:** open interest on the breakout bar up ≥ 2% vs 24h ago — new money, not
   just short-covering churn (covering alone shows OI *falling*; those breaks fade).
5. **Regime filter:** BTC 1D close above its own 50-day SMA, OR the candidate *is* BTC/ETH.
   Alt longs in a BTC downtrend are structurally poisoned — this single filter removes most
   catastrophic alt-long losers.

**SHORT setup — mirror with asymmetries respected:**

1. Close crosses **below the 50-bar SMA(4H)** after ≥ 10 bars above, with 50-SMA slope ≤ 0 or
   a 20-bar low break.
2. Volume ≥ 3× 20-bar average.
3. Funding ≥ **+0.03%/8h** (longs crowded and paying) or OI at a 30-day high with funding > 0 —
   over-leveraged longs are the fuel for downside cascades.
4. OI up ≥ 2% vs 24h (new shorts pressing, not just longs closing).
5. Regime: BTC 1D below its 50-day SMA, or the candidate is BTC/ETH. **Shorts are phase-2:**
   go live long-only first; shorts inherit squeeze risk and funding costs that deserve their own
   live evidence before enabling.

Expected candidate rate with these gates: **0–3 per day** across 15 symbols. If a backtest shows
10/day, the gates are too loose — tighten volume or structure thresholds, don't celebrate.

### 2.3 The Brain gate (per candidate, before entry)

Candidates are queued; the Reflexes calls the Brain (Section 3) and receives schema-validated
JSON. Trade only on `verdict = "APPROVE"` **and** `confidence ≥ 0.6`. Missing/invalid/stale
Brain output = **no trade** (fail-closed), never "trade anyway."

### 2.4 Position sizing — leverage is an output, not an input

```
risk_per_trade      = 0.75% of account equity     # what you lose if the hard stop is hit
stop_distance       = 1.5 × ATR(14, 4H)           # in price terms
position_notional   = (equity × 0.0075) / (stop_distance / entry_price)
leverage_implied    = position_notional / equity
HARD CAP: leverage_implied ≤ 3   (spec) … ≤ 5 absolute ceiling; if the formula wants more,
                                  the trade is skipped, NOT taken at cap with a wider stop.
```

- **Isolated margin, one-way mode, per-symbol margin = position_notional / 3** so that even a
  stop-gap-through has bounded damage and no cross-margin contagion.
- **Liquidation buffer rule:** computed liquidation price must be ≥ 3× stop_distance beyond the
  stop. If not (thin margin / high vol), skip. Your stop must always fire long before Binance's.
- Max **3 concurrent positions**, max 1 per symbol, max 2 in the same direction on correlated
  alts (30-day correlation to each other > 0.8 counts as one slot).
- **Fee/funding awareness:** projected cost = 2× taker fee + expected funding over intended hold
  (use current rate × hold). If projected cost > 25% of the 1R profit target, skip — this is the
  quantified version of your "fee trap" instinct.

### 2.5 Trade management via WebSockets (the dynamic part)

All management runs in the Reflexes off three streams: `kline_4h` (bar closes), `markPrice@1s`
(stop/trail logic + funding), and the **user-data stream** (fills, margin calls; keepalive ping
every 30 min, listenKey refresh handled with retry).

**Entry (on APPROVE):** limit order at breakout-bar close ± 0.05%, GTC for 30 minutes. Filled →
proceed; not filled and price ran > 0.5×ATR away → cancel and skip (chasing converts a 1.8R
trade into a 1.1R trade — the math dies).

**Immediately on fill — placed on the exchange, never only in local memory:**
- `STOP_MARKET` reduce-only at entry − 1.5×ATR (long). Exchange-side, `workingType=MARK_PRICE`
  (mark price triggers resist scam-wicks on the last-price feed).
- `TAKE_PROFIT_MARKET` reduce-only at entry + 2.5 × (1.5×ATR) ≈ **2.5R**, which at typical top-15
  4H ATRs lands in your 3–5% price-move target zone.

**Dynamic rules (Reflexes, evaluated on mark-price ticks, executed by amending the exchange stop):**
1. **Break-even move:** at +1.0R unrealized, amend stop to entry + fees (≈ entry × 1.001).
   The trade can no longer lose. This is your "trail to break-even."
2. **ATR trail:** at +1.5R, trail stop at (highest mark since entry) − 1.5×ATR(current, recomputed
   each 4H close). Trail only moves toward profit, never back.
3. **Partial de-risk (optional, test in backtest first):** at +2R, close 50% at market, let the
   trail run the remainder. Reduces variance, costs some expectancy — a temperament choice.
4. **Time stop:** if neither stop nor TP hit within **18 4H-bars (3 days)**, exit at market. A
   breakout that goes nowhere for 3 days has failed quietly; funding is bleeding you meanwhile.
5. **Funding guard:** if funding flips against the position and cumulative paid funding > 0.5R,
   tighten the time stop to the next 4H close that isn't in profit ≥ 0.5R.

**Portfolio circuit breakers (hard-coded in Reflexes, Brain cannot touch):**
- Day realized+unrealized loss ≥ 2.5% equity → no new entries until next 00:00 UTC.
- Peak-to-trough drawdown ≥ 10% → full halt, cancel all entry orders (exits stay live), manual
  restart required.
- 3 consecutive losing trades on one symbol → symbol suspended 7 days.
- WebSocket dead > 60s or clock drift > 1s vs server time → block entries; exits keep working via
  REST polling fallback (5s interval) until the stream recovers.

---

## 3. The Architecture — Hybrid AI Stack

```
                    ┌─────────────────────────────────────────────────┐
                    │  THE BRAIN — LLM via API (batch + on-demand)    │
                    │  NO exchange keys. NO sizing. NO execution.     │
                    │                                                 │
 unlock calendar ──▶│  daily 00:15 UTC: macro/news digest →           │
 econ calendar ────▶│    risk_state ∈ {normal, caution, off}          │
 news RSS ─────────▶│    (mapped by DETERMINISTIC rules from LLM's    │
 Binance annc. ────▶│     extracted facts — LLM extracts, code decides)│
                    │  on-demand (per breakout candidate):            │
                    │    verdict: APPROVE | VETO                      │
                    │    confidence, reasons[], cited_evidence[]      │
                    │  output → signals/YYYY-MM-DD/*.json             │
                    └───────────────┬─────────────────────────────────┘
                                    │ schema-validated JSON files (pydantic),
                                    │ checksummed, staleness-checked (>2h old = invalid)
                                    ▼
        ┌──────────────────────────────────────────────────────────────┐
        │  THE REFLEXES — local Python, asyncio, single process        │
        │  python-binance (or ccxt) REST + websockets                  │
        │                                                              │
        │  scanner.py    4H-close job: compute SMA/ATR/volume/funding/ │
        │                OI gates → candidate queue                    │
        │  brain_client.py  sends candidate dossier, awaits JSON,      │
        │                validates; timeout 120s → auto-VETO           │
        │  executor.py   entry limit orders, exchange-side SL/TP,      │
        │                idempotent newClientOrderId = hash(date,      │
        │                symbol, side, bar_ts) — crash-safe, no dupes  │
        │  manager.py    mark-price stream: BE move, ATR trail,        │
        │                time stop, funding guard                      │
        │  risk.py       sizing, leverage cap, liq-buffer check,       │
        │                circuit breakers  ← Brain has zero write path │
        │  state: SQLite (orders, positions, trades, Brain verdicts);  │
        │  reconcile-vs-exchange FIRST on every restart — the          │
        │  exchange's answer always wins                               │
        │  ops: heartbeat file + cron watchdog, Telegram alerts,       │
        │  HALT file kill switch (cancel entries, keep exits)          │
        └───────────────┬──────────────────────────────────────────────┘
                        │ REST + WebSocket (HMAC keys, trade-only)
                        ▼
                Binance USDT-M Futures  (testnet first: testnet.binancefuture.com)
```

### Containment guarantees (explicit, per your requirement)

1. **The LLM has APPROVE/VETO power only.** It cannot originate a trade (only the scanner's
   quantitative gates create candidates), cannot size, cannot set stops, cannot amend orders,
   and its JSON schema literally has no fields for quantity, leverage, or price. A schema with
   no vocabulary for an action is stronger than a prompt instruction not to take it.
2. **The LLM never sees the API keys.** Keys live only in the Reflexes' environment
   (env vars / OS keychain), the Brain runs as a separate process/module whose only I/O is
   text-in, JSON-out. Nothing from the Brain is ever interpolated into an exchange call except
   the boolean derived from `verdict`.
3. **Fail-closed everywhere:** invalid JSON, timeout, stale file, dead news feed → VETO, not
   retry-until-approve. A silent Brain means a quiet day, not a blind trade.
4. **Prompt-injection posture:** news headlines and announcements are untrusted input. Blast
   radius of a malicious/manipulative headline = one wrongly-approved or wrongly-vetoed
   candidate at 0.75% risk — annoying, not fatal. The Brain is instructed to treat promotional
   language as negative evidence and must cite sources for every claim in `reasons[]`.

### Binance-specific key hygiene (do this before writing any code)

- API key: **enable futures trading only — withdrawals disabled** (this is a checkbox; verify it).
- **IP whitelist** the key to your VPS/home IP.
- Separate read-only key for research/backtesting scripts.
- `recvWindow` 5000ms, sync clock via server-time endpoint on startup and hourly (HMAC requests
  fail on drift, and drift also breaks your bar-close timing).

---

## 4. Step-by-Step Python Roadmap

Ordered so that risk management exists before strategy, and testnet evidence exists before
capital. Each phase has an exit criterion; don't advance without it.

### Phase 0 — Secure plumbing (days 1–3)
- [ ] Repo: `reflexes/` (scanner, executor, manager, risk), `brain/`, `common/schemas.py`
      (pydantic models shared by both), `research/`, `tests/`
- [ ] Binance **testnet** account + key; mainnet *read-only* key for real market data
- [ ] `connectivity.py`: server-time sync check, account balance fetch, one testnet market order
      placed and cancelled — prove the HMAC path end-to-end
- [ ] Secrets via env vars; `.env` in `.gitignore` from commit #1
- **Exit criterion:** clean testnet order round-trip, clock drift < 500ms, keys confirmed
  withdrawal-disabled and IP-locked

### Phase 1 — Data layer (week 1)
- [ ] `klines.py`: paginated 4H/1D history fetch for top-15 universe, ≥ 3 years where available,
      stored in SQLite/Parquet; incremental updater
- [ ] Funding-rate history and open-interest history fetchers (Binance provides both)
- [ ] Universe builder (30-day median volume ranking, listing-age filter)
- [ ] Indicator module: SMA, ATR(14), rolling volume stats — **computed on closed bars only**,
      with unit tests against known values
- **Exit criterion:** for any symbol/date you can reproduce the exact gate inputs (SMA, ATR,
  volume ratio, funding, OI delta) that would have been known at that bar close — point-in-time
  discipline, no look-ahead

### Phase 2 — Risk engine BEFORE strategy (week 2)
- [ ] `risk.py`: sizing formula, leverage cap, liq-buffer calculator, fee+funding cost model,
      circuit-breaker state machine — all pure functions, exhaustively unit-tested
- [ ] Property tests: no input (price, ATR, equity) may ever produce leverage > cap, risk >
      0.75%, or a stop inside the liquidation buffer
- **Exit criterion:** you can prove, from tests alone, that no trade the system constructs can
  lose more than ~1% of equity under a 2× stop-gap scenario

### Phase 3 — Backtest the quant core alone (weeks 3–5)
- [ ] Event-driven backtester over Phase-1 data: gates → entry → exchange-realistic exits
      (stop/TP/BE/trail/time), **charging taker fees both sides, funding every 8h held, and
      slippage = spread + 5 bps** (crypto fill quality is good on top-15, but model it anyway)
- [ ] Walk-forward: tune on 2021–2023, hold out 2024–present untouched, one shot
- [ ] Sensitivity sweep: results must survive ±20% perturbation of every threshold (SMA length,
      volume multiple, funding cutoffs). An edge that lives on exact parameter values is noise.
- **Go/no-go:** OOS profit factor ≥ 1.4 after all costs, max DD ≤ 15% at spec sizing, ≥ 80 OOS
  trades. **If the quant core fails, stop — the Brain filters a real edge, it cannot mint one.**

### Phase 4 — Brain v1 (weeks 5–6)
- [ ] Feed collectors: unlock calendar, econ calendar, news RSS, Binance announcements
- [ ] Candidate-dossier prompt + strict JSON schema; temperature 0; citation requirement;
      versioned prompts logged with every verdict
- [ ] **Backtest the filter:** replay historical candidates with the news/unlock data that
      existed *at that time* (archive feeds from now on — you can't scrape the past's news
      cleanly, so this validation improves with age). Measure: do VETOed trades underperform
      APPROVEd ones? If not, Brain stays in shadow mode and the quant system ships alone.
- **Exit criterion:** ≥ 95% schema-valid responses; filter shows non-negative OOS value

### Phase 5 — Reflexes live plumbing on TESTNET (weeks 6–9)
- [ ] WebSocket manager: kline + markPrice + user-data streams, auto-reconnect with backoff,
      listenKey keepalive, REST-polling fallback for exits
- [ ] Executor with idempotent client order IDs; exchange-side SL/TP placement atomically after
      fill (and a reconciler that repairs a naked position if the process died between fill and
      stop placement — this exact gap is where accounts die; test it explicitly with kill -9)
- [ ] Manager: BE move, trail amendments, time stop, funding guard
- [ ] Chaos drills: kill -9 mid-entry, mid-trail, between fill and stop; drop the network 5 min;
      feed a corrupt Brain JSON; every drill must end with reconciled, protected positions
- **Exit criterion:** 2+ weeks unattended on testnet, zero unprotected-position incidents, zero
  duplicate orders, every alert channel proven

### Phase 6 — Live, minimum size (month 3+)
- [ ] Mainnet with **0.25% risk/trade, leverage cap 2×, max 2 positions, long-only**
- [ ] Compare live fills/funding/slippage vs backtest assumptions weekly; read every Brain
      verdict — you are auditing an analyst, not worshipping an oracle
- **Promotion to spec (0.75% / 3x / 3 positions):** ≥ 30 live trades, realized costs ≤ 1.5× modeled,
  zero reconciliation mismatches, zero manual rescues

### Phase 7 — Full spec + research loop (month 4+)
- [ ] Enable shorts only after long-side live stats hold through at least one BTC drawdown ≥ 15%
- [ ] Monthly: re-run walk-forward with new data; quarterly parameter-drift review
- [ ] Pre-committed retirement rule: two consecutive quarters of OOS expectancy below total costs
      → strategy is retired, not "tweaked until it backtests well again"

### Standing rules
1. Haircut every backtest: costs +20%, expectancy −30%, drawdown ×1.5. Plan capital around the
   haircut, not the headline.
2. The Brain never gains a write path to risk, sizing, or execution — re-verify this invariant
   in code review every time either component changes.
3. Every manual override is logged and reviewed weekly. The system's biggest tail risk is its
   operator at 3 a.m. during a 12% BTC candle.

---

*Engineering blueprint, not investment advice. Leveraged perpetual futures can lose more than
the margin allocated to a position; past patterns (including funding-conditioned momentum) may
not persist.*
