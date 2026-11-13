document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  document.getElementById('sidebarToggle')?.addEventListener('click', () => sidebar.classList.toggle('show'));
  const canvas = document.getElementById('categoryChart');
  const data = window.categoryChartData || [];
  if (canvas && window.Chart) {
    new Chart(canvas, { type: 'bar', data: { labels: data.map(item => item.category_name), datasets: [{ data: data.map(item => item.total), backgroundColor: '#8a1c2d', borderRadius: 7, borderSkipped: false }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { displayColors: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, border: { display: false } } } } });
  }
});
