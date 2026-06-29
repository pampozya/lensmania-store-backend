// ── Configuration ────────────────────────────────────────────────
// Replace WORKER_URL with your deployed Cloudflare Worker URL.
// Set WORKER_TOKEN only if you set the WORKER_TOKEN secret in wrangler.
const WORKER_URL   = 'https://gemma-worker.pampozya.workers.dev';
const WORKER_TOKEN = ''; // leave empty if you skipped the optional token

// ── State ─────────────────────────────────────────────────────────
const history = []; // { role: 'user'|'assistant', content: string }
let currentModel = 'gemma';

const MODEL_LABELS = {
  gemma:    'Gemma 4',
  llama:    'Llama 3.3',
  deepseek: 'DeepSeek V4',
};

const MODEL_DESCRIPTIONS = {
  gemma:    'Powered by Google Gemma 4 — ask anything.',
  llama:    'Powered by Llama 3.3 via Cloudflare AI — ask anything.',
  deepseek: 'Powered by DeepSeek V4 — ask anything.',
};

// ── DOM refs ──────────────────────────────────────────────────────
const messagesEl  = document.getElementById('messages');
const emptyState  = document.getElementById('empty-state');
const textarea    = document.getElementById('input');
const sendBtn     = document.getElementById('send-btn');
const clearBtn    = document.getElementById('clear-btn');
const badge       = document.querySelector('.badge');
const modelBtns   = document.querySelectorAll('.model-btn');

// ── Rendering ─────────────────────────────────────────────────────
function appendMessage(role, content, isTyping = false) {
  emptyState?.remove();

  const wrap = document.createElement('div');
  wrap.className = `msg ${role}${isTyping ? ' typing' : ''}`;

  const avatar = document.createElement('div');
  avatar.className = 'avatar';
  avatar.textContent = role === 'user' ? 'U' : 'AI';

  const bubble = document.createElement('div');
  bubble.className = 'bubble';
  if (!isTyping) bubble.textContent = content;

  wrap.appendChild(avatar);
  wrap.appendChild(bubble);
  messagesEl.appendChild(wrap);
  messagesEl.scrollTop = messagesEl.scrollHeight;
  return wrap;
}

// ── Send ──────────────────────────────────────────────────────────
async function send() {
  const text = textarea.value.trim();
  if (!text || sendBtn.disabled) return;

  textarea.value = '';
  autoResize();
  sendBtn.disabled = true;

  history.push({ role: 'user', content: text });
  appendMessage('user', text);

  const typingEl = appendMessage('ai', '', true);

  try {
    const headers = { 'Content-Type': 'application/json' };
    if (WORKER_TOKEN) headers['Authorization'] = `Bearer ${WORKER_TOKEN}`;

    const res = await fetch(`${WORKER_URL}/chat`, {
      method: 'POST',
      headers,
      body: JSON.stringify({ messages: history, model: currentModel }),
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: res.statusText }));
      throw new Error(err.error ?? `HTTP ${res.status}`);
    }

    const data = await res.json();
    const reply = data.reply ?? '(empty response)';

    history.push({ role: 'assistant', content: reply });
    typingEl.classList.remove('typing');
    typingEl.querySelector('.bubble').textContent = reply;
  } catch (err) {
    typingEl.classList.remove('typing');
    typingEl.querySelector('.bubble').textContent = `Error: ${err.message}`;
    typingEl.classList.add('error');
    // roll back the user message so they can retry
    history.pop();
  } finally {
    sendBtn.disabled = false;
    messagesEl.scrollTop = messagesEl.scrollHeight;
    textarea.focus();
  }
}

// ── Clear conversation ────────────────────────────────────────────
function clearChat() {
  history.length = 0;
  messagesEl.innerHTML = `
    <div id="empty-state" class="empty-state">
      <div class="empty-icon">✦</div>
      <h2>Lensmania AI</h2>
      <p>${MODEL_DESCRIPTIONS[currentModel]}</p>
    </div>`;
}

// ── Auto-resize textarea ──────────────────────────────────────────
function autoResize() {
  textarea.style.height = 'auto';
  textarea.style.height = Math.min(textarea.scrollHeight, 160) + 'px';
}

// ── Events ────────────────────────────────────────────────────────
textarea.addEventListener('input', autoResize);

textarea.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    send();
  }
});

sendBtn.addEventListener('click', send);
clearBtn.addEventListener('click', clearChat);

// ── Model selector ────────────────────────────────────────────────
modelBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    currentModel = btn.dataset.model;
    modelBtns.forEach(b => b.classList.toggle('active', b === btn));
    badge.textContent = MODEL_LABELS[currentModel];
  });
});

// ── Service worker ────────────────────────────────────────────────
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('./sw.js').catch(() => {});
}
