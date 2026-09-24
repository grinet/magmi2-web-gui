(() => {
    const root = document.documentElement;
    const updateThemeButtons = () => document.querySelectorAll('[data-theme-toggle]').forEach(button => {
        button.setAttribute('aria-label', root.dataset.theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
        button.setAttribute('aria-pressed', String(root.dataset.theme === 'dark'));
    });
    document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
        root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem('magmi-appearance', root.dataset.theme); } catch (_) {}
        updateThemeButtons();
    }));
    updateThemeButtons();
    const menu = document.querySelector('[data-nav-toggle]');
    const closeMenu = () => { document.body.classList.remove('nav-open'); menu?.setAttribute('aria-expanded', 'false'); };
    menu?.addEventListener('click', () => {
        const open = document.body.classList.toggle('nav-open');
        menu.setAttribute('aria-expanded', String(open));
        if (open) document.querySelector('.sidebar a')?.focus();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Tab' || !document.body.classList.contains('nav-open')) return;
        const items = [...document.querySelectorAll('.sidebar a, .sidebar button')];
        const first = items[0], last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
    document.querySelector('[data-nav-close]')?.addEventListener('click', closeMenu);
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && document.body.classList.contains('nav-open')) { closeMenu(); menu?.focus(); } });
    document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
        const field = document.getElementById(button.getAttribute('aria-controls'));
        if (!field) return;
        field.type = field.type === 'password' ? 'text' : 'password';
        button.textContent = field.type === 'password' ? 'Show' : 'Hide';
        button.setAttribute('aria-label', field.type === 'password' ? 'Show password' : 'Hide password');
    }));
    // Associate the existing form labels without changing submitted field names.
    document.querySelectorAll('.form-row, .profile-select-row, .copy-profile-row').forEach((row, index) => {
        const label = row.querySelector(':scope > label');
        const field = row.querySelector('input:not([type=hidden]), select, textarea');
        if (!label || !field || label.contains(field) || !label.textContent.trim()) return;
        field.id ||= `workspace-field-${index}`;
        label.htmlFor = field.id;
    });
    document.querySelectorAll('.plugin-header').forEach(header => {
        header.tabIndex = 0;
        header.setAttribute('role', 'button');
        const body = header.parentElement.querySelector('.plugin-body');
        if (body) {
            header.setAttribute('aria-controls', body.id);
            const sync = () => header.setAttribute('aria-expanded', String(body.style.display === 'block'));
            new MutationObserver(sync).observe(body, { attributes: true, attributeFilter: ['style'] });
            sync();
        }
        header.addEventListener('keydown', event => {
            if (event.target === header && ['Enter', ' '].includes(event.key)) { event.preventDefault(); header.click(); }
        });
    });
    document.querySelectorAll('.preview-table').forEach(table => {
        const wrap = document.createElement('div');
        wrap.className = 'table-scroll';
        wrap.tabIndex = 0;
        wrap.setAttribute('role', 'region');
        wrap.setAttribute('aria-label', 'Import data preview');
        table.before(wrap);
        wrap.appendChild(table);
    });
})();
