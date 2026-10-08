// The landing page's live editor: a pocket-sized version of the package's
// renderer. [[Links]] to pages that exist in the demo wiki turn blue and open
// them; anything else turns red, exactly as the package does it.
(() => {
    const DEMO = 'demo/';
    const EXISTING = ['Home', 'Onboarding', 'How we work', 'Deploy guide', 'Service map', 'Glossary', 'Incident reviews', 'Incident 14 September', 'Café crème'];

    const slugify = (text) => text.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
    const existing = new Set(EXISTING.map(slugify));
    const escape = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    const inline = (text) => escape(text)
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/\*([^*]+)\*/g, '<em>$1</em>')
        .replace(/\[\[([^\[\]|\n]+?)(?:\|([^\[\]\n]+?))?\]\]/g, (match, target, label) => {
            const slug = slugify(target.trim());
            if (!slug) return match;
            const known = existing.has(slug);
            return `<a href="${DEMO}${encodeURIComponent(slug)}/"${known ? '' : ' class="new"'}>${(label || target).trim()}</a>`;
        });

    const render = (markdown) => {
        const html = [];
        let list = null;
        let paragraph = [];

        const flushParagraph = () => {
            if (paragraph.length) html.push(`<p>${inline(paragraph.join(' '))}</p>`);
            paragraph = [];
        };
        const closeList = () => {
            if (list) html.push('</ul>');
            list = null;
        };

        for (const line of markdown.split('\n')) {
            const heading = line.match(/^(#{1,2})\s+(.*)$/);
            const item = line.match(/^\s*[-*]\s+(?:\[( |x)\]\s+)?(.*)$/i);

            if (heading) {
                flushParagraph(); closeList();
                html.push(`<h${heading[1].length}>${inline(heading[2])}</h${heading[1].length}>`);
            } else if (item) {
                flushParagraph();
                if (!list) { html.push('<ul>'); list = true; }
                const task = item[1] !== undefined;
                const box = task ? `<input type="checkbox" disabled${item[1].toLowerCase() === 'x' ? ' checked' : ''}>` : '';
                html.push(`<li${task ? ' class="task"' : ''}>${box}${inline(item[2])}</li>`);
            } else if (line.trim() === '') {
                flushParagraph(); closeList();
            } else {
                closeList();
                paragraph.push(line.trim());
            }
        }
        flushParagraph(); closeList();

        return html.join('\n');
    };

    const source = document.querySelector('[data-source]');
    const output = document.querySelector('[data-rendered]');
    if (source && output) {
        const update = () => { output.innerHTML = render(source.value); };
        source.addEventListener('input', update);
        update();
    }

    for (const button of document.querySelectorAll('[data-copy]')) {
        button.addEventListener('click', async () => {
            await navigator.clipboard.writeText(button.dataset.copy);
            button.textContent = 'Copied';
            button.dataset.done = '';
            setTimeout(() => { button.textContent = 'Copy'; delete button.dataset.done; }, 1600);
        });
    }

    // Docs: mark the section in view in the sidebar.
    const links = [...document.querySelectorAll('.docs nav a')];
    if (links.length && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                links.forEach((a) => a.toggleAttribute('aria-current', a.hash === `#${entry.target.id}`));
            }
        }, { rootMargin: '0px 0px -70% 0px' });
        document.querySelectorAll('.doc h2[id]').forEach((h) => observer.observe(h));
    }
})();
