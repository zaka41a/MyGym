// Active link auto (si tu oublies la classe .active)
(function() {
  const links = document.querySelectorAll('.navbar a');
  const here = location.pathname.split('/').pop() || 'index.html';
  links.forEach(a => {
    const href = a.getAttribute('href');
    if (href === here) a.classList.add('active');
  });
})();

// Validation simple du formulaire de contact
(function() {
  const form = document.getElementById('contactForm');
  if (!form) return;

  const msg = document.getElementById('formMsg');

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const name = (data.get('name') || '').trim();
    const email = (data.get('email') || '').trim();
    const message = (data.get('message') || '').trim();

    if (!name || !email || !message) {
      msg.textContent = 'Please fill all fields.';
      msg.style.color = '#ffb3b3';
      return;
    }
    // Ici tu pourrais faire un fetch POST vers ton backend PHP
    msg.textContent = 'Thanks! Your message has been sent.';
    msg.style.color = '#8cff98';
    form.reset();
  });
})();
