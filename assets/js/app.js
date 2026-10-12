// JavaScript Interactivity for VSM Search Engine
document.addEventListener('DOMContentLoaded', () => {

  // 0. Theme Toggle Switcher (Gelap / Terang)
  const themeToggle = document.getElementById('themeToggle');
  const themeLabel = document.getElementById('themeLabelText');

  function updateThemeUI(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('stki_theme', theme);
    if (themeLabel) {
      themeLabel.textContent = (theme === 'dark') ? 'Terang' : 'Gelap';
    }
  }

  // Inisialisasi status tombol tema
  const currentTheme = localStorage.getItem('stki_theme') || 'dark';
  updateThemeUI(currentTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const activeTheme = document.documentElement.getAttribute('data-theme') || 'dark';
      const newTheme = (activeTheme === 'dark') ? 'light' : 'dark';
      updateThemeUI(newTheme);
    });
  }

  // 1. Tabbed Switcher for Dataset Input
  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabPanes = document.querySelectorAll('.tab-pane');

  if (tabButtons.length > 0 && tabPanes.length > 0) {
    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-tab');

        // Deactivate all
        tabButtons.forEach(b => b.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));

        // Activate target
        btn.classList.add('active');
        const targetPane = document.getElementById(targetId);
        if (targetPane) {
          targetPane.classList.add('active');
        }
      });
    });
  }

  // 2. Shortcut '/' to focus search input
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
      const searchInput = document.querySelector('.search-input');
      if (searchInput) {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
      }
    }
  });

  // 3. Live Filter for Table Rows
  const tableFilterInput = document.getElementById('tableFilterInput');
  const docsTable = document.querySelector('.data-table tbody');
  if (tableFilterInput && docsTable) {
    tableFilterInput.addEventListener('input', () => {
      const q = tableFilterInput.value.toLowerCase().trim();
      const rows = docsTable.querySelectorAll('tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }

  // 4. Drag & Drop Visual feedback for CSV File Input
  const fileInput = document.getElementById('csv_file');
  if (fileInput) {
    const dropZone = fileInput.closest('.form-group');
    if (dropZone) {
      ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
          e.preventDefault();
          dropZone.style.borderColor = 'var(--primary-light)';
        }, false);
      });
      ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
          e.preventDefault();
          dropZone.style.borderColor = 'var(--border-subtle)';
        }, false);
      });
    }
  }

  // 5. Back to Top Button
  const backToTopBtn = document.getElementById('backToTopBtn');
  if (backToTopBtn) {
    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
});
