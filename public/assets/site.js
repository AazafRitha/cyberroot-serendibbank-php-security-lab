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
