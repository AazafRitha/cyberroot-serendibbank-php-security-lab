const menuToggle = document.querySelector('[data-menu-toggle]');
const primaryNav = document.querySelector('#primary-navigation');
if (menuToggle && primaryNav) {
  document.documentElement.classList.add('has-menu');
  const header = menuToggle.closest('.site-header');
  const menuMedia = matchMedia('(max-width: 900px)');
  const setMenuOpen = (open) => {
    const expanded = menuMedia.matches && open;
    header.classList.toggle('menu-open', expanded);
    menuToggle.setAttribute('aria-expanded', String(expanded));
    menuToggle.setAttribute('aria-label', expanded ? 'Close menu' : 'Open menu');
    menuToggle.querySelector('.menu-toggle-text').textContent = expanded ? 'Close' : 'Menu';
    primaryNav.inert = menuMedia.matches && !expanded;
    if (menuMedia.matches && !expanded) primaryNav.setAttribute('aria-hidden', 'true');
    else primaryNav.removeAttribute('aria-hidden');
  };
  menuToggle.addEventListener('click', () => setMenuOpen(!header.classList.contains('menu-open')));
  primaryNav.addEventListener('click', (event) => {
    if (event.target.closest('a')) setMenuOpen(false);
  });
  document.addEventListener('click', (event) => {
    if (header.classList.contains('menu-open') && !header.contains(event.target)) setMenuOpen(false);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && header.classList.contains('menu-open')) {
      setMenuOpen(false);
      menuToggle.focus();
    }
  });
  menuMedia.addEventListener('change', () => setMenuOpen(false));
  setMenuOpen(false);
}

const themeToggle = document.querySelector('[data-theme-toggle]');
if (themeToggle) {
  const updateThemeToggle = () => {
    const dark = document.documentElement.dataset.theme === 'dark';
    themeToggle.setAttribute('aria-pressed', String(dark));
    themeToggle.setAttribute('aria-label', `Switch to ${dark ? 'light' : 'dark'} mode`);
    themeToggle.querySelector('[data-theme-label]').textContent = dark ? 'Light mode' : 'Dark mode';
    themeToggle.querySelector('[data-theme-icon]').textContent = dark ? '☀' : '☾';
  };
  themeToggle.addEventListener('click', () => {
    const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = nextTheme;
    try { localStorage.setItem('serendib-theme', nextTheme); } catch { /* Theme still changes for this page. */ }
    updateThemeToggle();
  });
  updateThemeToggle();
}

document.querySelectorAll('[data-filter-group]').forEach((group) => {
  const name = group.dataset.filterGroup;
  const list = document.querySelector(`[data-filter-items="${name}"]`);
  if (!list) return;

  const buttons = Array.from(group.querySelectorAll('[data-filter]'));
  const items = Array.from(list.querySelectorAll('[data-category]'));
  const search = name === 'locations' ? document.querySelector('[data-location-search]') : null;
  const empty = document.querySelector(`[data-filter-empty="${name}"]`);
  let category = 'all';

  const update = () => {
    const query = search ? search.value.trim().toLocaleLowerCase() : '';
    let visible = 0;
    items.forEach((item) => {
      const matchesCategory = category === 'all' || item.dataset.category === category;
      const matchesQuery = !query || (item.dataset.search || '').toLocaleLowerCase().includes(query);
      item.hidden = !(matchesCategory && matchesQuery);
      if (!item.hidden) visible += 1;
    });
    if (empty) empty.hidden = visible !== 0;
  };

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      category = button.dataset.filter;
      buttons.forEach((other) => {
        const active = other === button;
        other.classList.toggle('is-active', active);
        other.setAttribute('aria-pressed', String(active));
      });
      update();
    });
  });

  if (search) search.addEventListener('input', update);
});

const mapFrame = document.querySelector('[data-location-map]');
if (mapFrame) {
  const title = document.querySelector('[data-map-title]');
  const largerMap = document.querySelector('[data-map-link]');
  document.querySelectorAll('[data-map-location]').forEach((button) => {
    button.addEventListener('click', () => {
      const lat = Number(button.dataset.mapLat);
      const lon = Number(button.dataset.mapLon);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;
      const bbox = [lon - 0.02, lat - 0.02, lon + 0.02, lat + 0.02].map((n) => n.toFixed(4)).join(',');
      const name = button.dataset.mapLocation || 'Selected location';
      title.textContent = name;
      mapFrame.title = `Map of the ${name} area`;
      mapFrame.src = `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${encodeURIComponent(`${lat},${lon}`)}`;
      largerMap.href = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=14/${lat}/${lon}`;
      document.querySelector('#location-map').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

const cardProduct = document.querySelector('[data-card-product]');
if (cardProduct) {
  const debitFields = document.querySelector('[data-debit-fields]');
  const creditFields = document.querySelector('[data-credit-fields]');
  const updateCardFields = () => {
    const debit = cardProduct.value === 'Serendib Debit';
    const credit = cardProduct.value === 'Serendib Credit';
    debitFields.hidden = !debit;
    creditFields.hidden = !credit;
    debitFields.querySelector('select').required = debit;
    creditFields.querySelectorAll('select, input').forEach((field) => { field.required = credit; });
  };
  cardProduct.addEventListener('change', updateCardFields);
  updateCardFields();
}

const lookupForm = document.querySelector('[data-account-lookup]');
if (lookupForm) {
  const result = document.querySelector('[data-account-lookup-result]');
  lookupForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const accountNumber = new FormData(lookupForm).get('account_number');
    result.textContent = 'Checking account…';
    try {
      const response = await fetch(`/api/v1/beneficiaries/lookup?account_number=${encodeURIComponent(accountNumber)}`);
      const payload = await response.json();
      result.textContent = response.ok
        ? `${payload.beneficiary.account_holder} · ${payload.beneficiary.account_type} · ${payload.beneficiary.currency}`
        : payload.error || 'Account could not be found.';
    } catch {
      result.textContent = 'Account lookup is temporarily unavailable.';
    }
  });
}

const cooldown = document.querySelector('[data-login-cooldown]');
if (cooldown) {
  let remaining = Number(cooldown.dataset.loginCooldown) || 0;
  const time = cooldown.querySelector('[data-login-cooldown-time]');
  const update = () => {
    const minutes = Math.floor(remaining / 60);
    const seconds = String(remaining % 60).padStart(2, '0');
    time.textContent = `${minutes}:${seconds}`;
    if (remaining <= 0) {
      clearInterval(timer);
      cooldown.textContent = 'You can try signing in again.';
    }
    remaining = Math.max(0, remaining - 1);
  };
  const timer = setInterval(update, 1000);
  update();
}
