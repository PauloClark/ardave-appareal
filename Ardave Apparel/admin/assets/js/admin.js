document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.admin-sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
  }

  const chartEls = document.querySelectorAll('[data-admin-chart]');
  chartEls.forEach(canvas => {
    const config = JSON.parse(canvas.dataset.chartConfig || '{}');
    if (!config.type || !config.data) return;
    new Chart(canvas, config);
  });
});
