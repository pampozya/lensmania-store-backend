/**
 * Cloudflare Worker — AI gateway (Gemma 4 + DeepSeek V4)
 *
 * Secrets (set via wrangler CLI, never in code):
 *   GOOGLE_AI_KEY   — Google AI Studio API key
 *   DEEPSEEK_API_KEY — DeepSeek platform API key
 *   WORKER_TOKEN    — (optional) bearer token clients must send
 *
 * POST /chat  body: { messages: [{role, content}], model?: "gemma"|"deepseek" }
 */

const GEMMA_MODEL = 'gemma-4-27b-it';
const GEMMA_URL   = `https://generativelanguage.googleapis.com/v1beta/models/${GEMMA_MODEL}:generateContent`;

const DEEPSEEK_MODEL = 'deepseek-v4';
const DEEPSEEK_URL   = 'https://api.deepseek.com/v1/chat/completions';

const CORS = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'POST, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization',
};

export default {
  async fetch(request, env) {
    if (request.method === 'OPTIONS') {
      return new Response(null, { status: 204, headers: CORS });
    }

    const url = new URL(request.url);

    if (url.pathname === '/health' && request.method === 'GET') {
      return json({ ok: true, models: { gemma: GEMMA_MODEL, deepseek: DEEPSEEK_MODEL } });
    }

    if (url.pathname !== '/chat' || request.method !== 'POST') {
      return new Response('Not Found', { status: 404, headers: CORS });
    }

    if (env.WORKER_TOKEN) {
      const auth = request.headers.get('Authorization') ?? '';
      if (auth !== `Bearer ${env.WORKER_TOKEN}`) {
        return new Response('Unauthorized', { status: 401, headers: CORS });
      }
    }

    let body;
    try {
      body = await request.json();
    } catch {
      return new Response('Bad Request: invalid JSON', { status: 400, headers: CORS });
    }

    const messages = body.messages;
    if (!Array.isArray(messages) || messages.length === 0) {
      return new Response('Bad Request: messages[] required', { status: 400, headers: CORS });
    }

    const model = body.model ?? 'gemma';

    if (model === 'deepseek') {
      return callDeepSeek(messages, env);
    }
    return callGemma(messages, env);
  },
};

async function callGemma(messages, env) {
  const contents = messages.map(m => ({
    role: m.role === 'assistant' ? 'model' : 'user',
    parts: [{ text: String(m.content) }],
  }));

  const res = await fetchWithRetry(
    `${GEMMA_URL}?key=${env.GOOGLE_AI_KEY}`,
    { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ contents }) }
  );

  if (!res.ok) {
    const errText = await res.text();
    return json({ error: `Gemma error ${res.status}`, detail: errText }, res.status);
  }

  const data = await res.json();
  const reply = data.candidates?.[0]?.content?.parts?.[0]?.text ?? '';
  return json({ reply, model: 'gemma' });
}

async function callDeepSeek(messages, env) {
  if (!env.DEEPSEEK_API_KEY) {
    return json({ error: 'DEEPSEEK_API_KEY secret not configured' }, 500);
  }

  const res = await fetchWithRetry(
    DEEPSEEK_URL,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${env.DEEPSEEK_API_KEY}`,
      },
      body: JSON.stringify({
        model: DEEPSEEK_MODEL,
        messages: messages.map(m => ({ role: m.role, content: String(m.content) })),
      }),
    }
  );

  if (!res.ok) {
    const errText = await res.text();
    return json({ error: `DeepSeek error ${res.status}`, detail: errText }, res.status);
  }

  const data = await res.json();
  const reply = data.choices?.[0]?.message?.content ?? '';
  return json({ reply, model: 'deepseek' });
}

async function fetchWithRetry(url, init, retries = 3) {
  let delay = 1000;
  for (let i = 0; i <= retries; i++) {
    const res = await fetch(url, init);
    if (res.ok || ![500, 503].includes(res.status) || i === retries) return res;
    await sleep(delay);
    delay *= 2;
  }
}

function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

function json(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json', ...CORS },
  });
}
