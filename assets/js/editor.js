// File: /var/www/redreporter2/assets/js/editor.js
// Handles risk‑rating select colour and Markdown editor setup

document.addEventListener('DOMContentLoaded', () => {
  // 1) Colour‑code the risk‑rating dropdown border
  const riskSelect = document.getElementById('risk_rating');
  if (riskSelect) {
    const updateRiskClass = () => {
      ['critical','high','medium','low','informational'].forEach(level =>
        riskSelect.classList.remove(`risk-${level}`)
      );
      riskSelect.classList.add(`risk-${riskSelect.value.toLowerCase()}`);
    };
    riskSelect.addEventListener('change', updateRiskClass);
    updateRiskClass();
  }

  // 2) Initialise Markdown editors (using SimpleMDE) and sync before form submit
  if (window.SimpleMDE) {
    const descEl = document.getElementById('description');
    const remEl  = document.getElementById('remediation');
    const form   = document.getElementById('template-form');
    if (descEl && remEl && form) {
      const descEditor = new SimpleMDE({
        element: descEl,
        spellChecker: false,
        status: false
      });
      const remEditor = new SimpleMDE({
        element: remEl,
        spellChecker: false,
        status: false
      });

      form.addEventListener('submit', () => {
        descEditor.codemirror.save();
        remEditor.codemirror.save();
      });
    }
  }
});
