(() => {
    const buttons = Array.from(document.querySelectorAll('[data-history-target]'));
    if (buttons.length !== 2) return;

    const panels = buttons.map(button => document.getElementById(button.dataset.historyTarget));
    if (panels.some(panel => !panel)) return;

    function show(target) {
        buttons.forEach((button, index) => {
            const selected = button.dataset.historyTarget === target;
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', String(selected));
            panels[index].hidden = !selected;
        });
    }

    const params = new URLSearchParams(window.location.search);
    show(params.has('paymentPage') || window.location.hash === '#member-payment-history'
        ? 'member-payment-history'
        : 'member-attendance-history');

    buttons.forEach(button => button.addEventListener('click', () => {
        show(button.dataset.historyTarget);
        history.replaceState(null, '', window.location.pathname + window.location.search + '#' + button.dataset.historyTarget);
    }));
})();
