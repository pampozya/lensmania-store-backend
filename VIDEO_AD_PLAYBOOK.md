# Cinematic Product-Ad Video Playbook (Higgsfield)

A repeatable process for turning **ChatGPT reference images + a Gemini script/analysis**
into a short cinematic product ad (like the DERMAQUAL / Skinfill Bacio reference).
Written from lessons learned building the 20s "Skinfill Bacio by Promoitalia" ad.

---

## 0. What you provide each time
1. **Reference video** (optional) — the ad you want to emulate the style of.
2. **Gemini analysis / script** — scene-by-scene breakdown OR a formatted script with:
   timecodes, VO lines (per language), audio/SFX cues, visual prompts per shot, text overlays.
3. **Model photos** — before/after sets (for identity) + any ChatGPT-generated shot stills.
4. **Product photos** — the real product art (box/vial), plus the exact on-screen product name.

---

## 1. Understand the reference first
- No system `ffmpeg` in the sandbox — install a bundled one: `pip install imageio-ffmpeg`.
- Extract frames (`fps=1/1.2`, scale 320w) and build 5x5 **montage grids** to view a whole
  video quickly via the image reader. Pull the audio too if needed.
- Identify the **structure/acts** (e.g. dark emotional act → bright hopeful/product act → logo).

## 2. Keyframes — the single biggest lesson
Choosing how to render the model into each scene is where quality is won or lost.

| Approach | Behavior | Verdict |
|---|---|---|
| `soul_2` + **single loose reference image** | **Over-copies** the reference photo — ignores scene/lighting/pose direction (gave bright yellow-dress studio shots when we asked for dark/upside-down). | ❌ Avoid for scene changes |
| `nano_banana_2` + reference + "replace the whole scene" prompt | Follows the scene, but **loses the person's likeness**. | ⚠️ Scene yes, identity no |
| **Train a Soul** (`show_characters action=train`, 5–20 photos, ~10 min) then `soul_2` + `soul_id` | Holds **identity across dramatically different scenes**. | ✅ Best for consistency |
| **User's own ChatGPT images** used directly as `start_image` | Often the **most realistic** and already art-directed. | ✅ Best for realism — prefer when provided |

**Rules of thumb**
- If the user supplies ChatGPT stills for a shot → use them directly (skip generation).
- For shots without a supplied still → use a **trained Soul** (not a single loose ref).
- Scene-change prompt pattern: *"Reimagine this exact person in a completely different
  scene: … replace the background and outfit entirely, remove [X] completely."*
- Product macro shots: `nano_banana_2` with the real product photo(s) as reference,
  prompt "no extra text added" so it doesn't invent packaging copy.

## 3. Animate keyframes → clips
- Model: `seedance_2_0`, `role: start_image`, `aspect_ratio: 9:16`, `mode: std`, `generate_audio: false`.
- Duration: match the shot's VO length + a little breathing room.
- **720p can degrade skin/faces.** For beauty/skin payoff shots use **1080p + minimal motion**
  ("very subtle head movement and a soft blink only") + skin-quality words
  ("flawless, smooth, glowing, natural, high-fidelity facial detail, no distortion").
- Skin can still be smoothed **naturally in the local edit** (gentle bilateral smooth on
  skin tones + slight sharpen — keep it subtle, not plastic).

## 4. Audio
- **Music bed:** `sonilo_music` (has `duration`). Describe the arc (e.g. dark ambient 0–9s →
  bright piano/strings 9–20s).
- **SFX:** `mirelo_text_to_audio` (has `duration`) — rumble, water ripple, whoosh, etc.
- **Voiceover:**
  - English → `text2speech_v2`, `variant: elevenlabs`, a preset `voice_id` (e.g. Vesper `c3204739-…`).
  - Arabic/Levantine → `inworld_text_to_speech`, voice `Nour (ar)`.
  - Use `...` and line breaks to control pacing/pauses.
- **Check VO durations vs shot windows.** Arabic ran long (line 3 = 7.4s vs a 5s shot);
  the English set fit cleanly. Extend clips or trim lines if they don't fit.

## 5. Cost control
- Always `get_cost: true` first. Rough guide (9:16):
  - `nano_banana_2` image ≈ 1–2 cr · `soul_2` image ≈ 1 cr
  - `seedance_2_0` 4s: 720p ≈ 18 cr · 1080p ≈ 36–45 cr
  - `sonilo_music` 20s ≈ 1.25 cr · SFX ≈ 0.5 cr · TTS line ≈ 0.3–2 cr

## 6. Final assembly — the hard constraint ⚠️
- **The sandbox is blocked by org policy from downloading Higgsfield's CDN (`*.cloudfront.net`).**
  So the assistant cannot pull generated clips/audio to edit them locally.
- **Higgsfield's server-side stitch tool (`explainer_video`) was unreliable** — hung
  `in_progress` indefinitely on multiple attempts.
- **Reliable path:** the user downloads the finished clips/audio from Higgsfield and
  **re-uploads them into the chat as attachments** (chat uploads *are* readable).
  Then the assistant does the whole edit locally with the bundled ffmpeg:
  stitch → transitions (white-flash on act change) → text overlays (product name, tagline)
  → end slate → VO + ducked music + SFX mix → export 9:16 MP4.

## 7. Misc lessons
- Use `media_upload_widget` for local files; the widget returns `media_id`s to pass on.
- Confirm the **exact on-screen product name** early (files may be named differently than
  the script — here files said "SKINFILL BACIO" while an early script said "Pacio Lips").
- Arabic subtitles (if ever used): render with the **Amiri** font +
  `arabic_reshaper` + `python-bidi` for correct RTL letter-joining. (Install
  `fonts-hosny-amiri`, `python3-bidi`; `pip install arabic-reshaper pillow`.)
- This is a **cloud session** — the user can close their laptop and continue from mobile.
- Background check-ins (`send_later`) are useful for polling long Higgsfield jobs.

---

## Reusable starter prompt for a NEW video
Paste this at the top of a fresh conversation, then attach your assets:

> I want to produce a short cinematic product-ad video with Higgsfield, following the
> process in `VIDEO_AD_PLAYBOOK.md`. Read that playbook first, then work through it.
>
> I will provide:
> - **ChatGPT images** for specific shots (use these directly as `start_image` — they're
>   the most realistic; don't regenerate them unless I ask).
> - A **Gemini script/description**: timecodes, VO lines, SFX/music cues, per-shot visual
>   prompts, and text overlays.
> - **Product photo(s)** and the **exact on-screen product name**.
>
> Please:
> 1. Confirm the shot list, durations, and exact product name before spending credits.
> 2. Preflight cost (`get_cost:true`) and show me the plan.
> 3. Generate: product macro shot(s), animate each keyframe with `seedance_2_0`
>    (1080p + minimal motion for any skin/beauty payoff shot), music (`sonilo_music`),
>    SFX (`mirelo_text_to_audio`), and VO (`text2speech_v2` elevenlabs / `inworld` for Arabic).
> 4. Remember you **can't download from Higgsfield's CDN** — when it's time to assemble,
>    give me clean one-per-line download links, I'll re-upload the files into the chat, and
>    you'll do the local ffmpeg edit (stitch, white-flash transition, text overlays, end
>    slate, natural skin-smoothing where needed, VO + ducked music + SFX mix) → one 9:16 MP4.
>
> Ask me for anything missing before you start.
