/**
 * Cloudflare Worker — Gemma 4 AI gateway
 *
 * Secrets (set via wrangler CLI, never in code):
 *   GOOGLE_AI_KEY   — Google AI Studio API key
 *   WORKER_TOKEN    — (optional) bearer token clients must send
 */

const GEMMA_MODEL = 'gemma-4-31b-it';
const GOOGLE_AI_URL = `https://generativelanguage.googleapis.com/v1beta/models/${GEMMA_MODEL}:generateContent`;

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
      return json({ ok: true, model: GEMMA_MODEL });
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

    const contents = messages.map(m => ({
      role: m.role === 'assistant' ? 'model' : 'user',
      parts: [{ text: String(m.content) }],
    }));

    const googleRes = await fetch(`${GOOGLE_AI_URL}?key=${env.GOOGLE_AI_KEY}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ contents }),
    });

    if (!googleRes.ok) {
      const errText = await googleRes.text();
      return json({ error: `Google AI error ${googleRes.status}`, detail: errText }, googleRes.status);
    }

    const data = await googleRes.json();
    const reply = data.candidates?.[0]?.content?.parts?.[0]?.text ?? '';

    return json({ reply });
  },
};

function json(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json', ...CORS },
  });
}
