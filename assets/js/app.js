// Minimal JS (placeholder for enhancements)
document.addEventListener('click', (e) => {
  const target = e.target;
  if (!(target instanceof HTMLElement)) return;
  if (target.matches('[data-confirm]')) {
    const msg = target.getAttribute('data-confirm') || 'Are you sure?';
    if (!window.confirm(msg)) {
      e.preventDefault();
    }
  }
});

// Add smooth scroll behavior
document.addEventListener('DOMContentLoaded', () => {
  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Add loading states for buttons
  document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function() {
      if (!this.classList.contains('disabled')) {
        this.classList.add('loading');
        setTimeout(() => {
          this.classList.remove('loading');
        }, 1000);
      }
    });
  });
});

