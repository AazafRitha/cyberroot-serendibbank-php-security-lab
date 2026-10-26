(() => {
  const widget = document.querySelector('.chat-widget');
  if (!widget) return;
  const panel = widget.querySelector('.chat-panel');
  const toggle = widget.querySelector('[data-chat-toggle]');
  const close = widget.querySelector('[data-chat-close]');
  const messages = widget.querySelector('[data-chat-messages]');
  const form = widget.querySelector('[data-chat-form]');
  const input = form.elements.message;
  const submit = form.querySelector('button[type=submit]');
  let csrf = '';
  let loaded = false;

  const append = (role, text, action = null) => {
    const bubble = document.createElement('div');
    bubble.className = `chat-message chat-message-${role === 'customer' ? 'customer' : 'assistant'}`;
    bubble.textContent = text;
    if (action && typeof action.url === 'string' && action.url.startsWith('/') && !action.url.startsWith('//')) {
      const link = document.createElement('a');
      link.href = action.url;
      link.textContent = action.label || 'Continue';
      bubble.append(link);
    }
    messages.append(bubble);
    messages.scrollTop = messages.scrollHeight;
  };

  const load = async () => {
    if (loaded || location.protocol === 'file:') return;
    try {
      const response = await fetch('/chat', { credentials: 'same-origin' });
      if (!response.ok) throw new Error('Chat could not be loaded');
      const data = await response.json();
      csrf = data.csrf;
      for (const item of data.messages || []) append(item.role, item.text, item.action);
      loaded = true;
    } catch (_) {
      append('assistant', 'Chat is temporarily unavailable. Please visit the help centre.');
    }
  };

  const open = () => {
    panel.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    load();
    input.focus();
  };
  const hide = () => {
    panel.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.focus();
  };
  toggle.addEventListener('click', () => panel.hidden ? open() : hide());
  close.addEventListener('click', hide);
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !panel.hidden) hide(); });
  document.querySelectorAll('[data-chat-open]').forEach((button) => button.addEventListener('click', open));

  const previewReply = (message) => {
    const text = message.toLowerCase();
    if (text.includes('card')) return { text: 'Compare debit and credit cards, then apply from online banking.', action: { label: 'Explore cards', url: 'cards.html' } };
    if (text.includes('branch')) return { text: 'Find a nearby branch or ATM using the location finder.', action: { label: 'Find a location', url: 'locations.html' } };
    return { text: 'I can help with accounts, cards, transfers, branches and support.', action: { label: 'Visit help centre', url: 'support.html' } };
  };

  const send = async (message) => {
    const cleaned = message.trim();
    if (!cleaned || cleaned.length > 500) return;
    append('customer', cleaned);
    input.value = '';
    submit.disabled = true;
    try {
      if (location.protocol === 'file:') {
        const reply = previewReply(cleaned);
        append('assistant', reply.text, reply.action);
        return;
      }
      if (!csrf) await load();
      const body = new URLSearchParams({ _csrf: csrf, message: cleaned });
      const response = await fetch('/chat', { method: 'POST', credentials: 'same-origin', body });
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Please try again.');
      append('assistant', data.reply.text, data.reply.action);
    } catch (error) {
      append('assistant', error.message || 'Please try again.');
    } finally {
      submit.disabled = false;
      input.focus();
    }
  };
  form.addEventListener('submit', (event) => { event.preventDefault(); send(input.value); });
  widget.querySelectorAll('[data-chat-suggestion]').forEach((button) => button.addEventListener('click', () => send(button.dataset.chatSuggestion)));
})();
