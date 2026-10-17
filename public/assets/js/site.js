const toggle = document.querySelector('.menu-toggle');
toggle?.addEventListener('click', () => { const open = toggle.getAttribute('aria-expanded') !== 'true'; toggle.setAttribute('aria-expanded', String(open)); document.querySelector('#navigation').classList.toggle('open', open); });
document.querySelectorAll('[data-amount]').forEach(button => button.addEventListener('click', () => { document.querySelector('#amount').value = button.dataset.amount; document.querySelectorAll('[data-amount]').forEach(b => b.setAttribute('aria-pressed', String(b === button))); }));
