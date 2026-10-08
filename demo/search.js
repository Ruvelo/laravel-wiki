// Answers the demo's search page in the browser, following the server's rules:
// an exact title goes straight to the page, title matches rank above body matches.
(() => {
    const params = new URLSearchParams(location.search);
    const query = (params.get('q') || '').trim();
    if (query === '') return;

    const slugify = (text) => text.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
    const pages = window.WIKI_INDEX;

    const exact = pages.find((page) => page.slug === slugify(query));
    if (exact && !params.has('all')) {
        location.replace(exact.url);
        return;
    }

    const needle = query.toLowerCase();
    const inTitle = (page) => page.title.toLowerCase().includes(needle);
    const hits = pages
        .filter((page) => inTitle(page) || page.body.toLowerCase().includes(needle))
        .sort((a, b) => inTitle(b) - inTitle(a) || a.title.localeCompare(b.title));

    const el = (tag, attrs = {}, text = '') => {
        const node = Object.assign(document.createElement(tag), attrs);
        if (text) node.textContent = text;
        return node;
    };

    const snippet = (body) => {
        const at = body.toLowerCase().indexOf(needle);
        if (at < 0) return '';
        const start = Math.max(0, at - 90);
        const end = Math.min(body.length, at + needle.length + 90);
        return (start > 0 ? '…' : '') + body.slice(start, end).replace(/\s+/g, ' ').trim() + (end < body.length ? '…' : '');
    };

    document.title = `Search: ${query} · ${document.title.split(' · ').pop()}`;
    document.querySelector('.wiki-search input').value = query;

    const main = document.querySelector('.wiki-main');
    main.replaceChildren();

    const head = el('div', { className: 'wiki-head' });
    head.append(el('h1', {}, `Results for “${query}”`));
    main.append(head);

    if (hits.length === 0) {
        main.append(el('p', {}, 'Nothing matches.'));
        return;
    }

    const list = el('ul', { className: 'wiki-list' });
    for (const page of hits) {
        const item = el('li');
        item.append(el('a', { href: page.url }, page.title), el('small', {}, `edited ${page.edited}`));
        const text = snippet(page.body);
        if (text) {
            const span = el('span', { className: 'wiki-snippet' });
            const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const pattern = new RegExp('(' + escaped + ')', 'giu');
            text.split(pattern).forEach((part, i) => span.append(i % 2 ? el('mark', {}, part) : document.createTextNode(part)));
            item.append(span);
        }
        list.append(item);
    }
    main.append(list);
})();
