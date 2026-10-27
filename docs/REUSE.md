# Reusing the introductory guide

[Deutsch](REUSE-de.md)

The five-step guide is maintained in the `quickstart` sections of `content.json`. The plugin reads these sections for its admin modal. Run `python3 tools/build-docs.py` from the repository to update the other formats after editing the source.

- `quickstart.html` / `quickstart-de.html`: standalone local guides, using `guide.css`.
- `QUICKSTART.md` / `QUICKSTART-de.md`: Markdown for GitHub or other documentation.
- `quickstart-fragment.html` / `quickstart-fragment-de.html`: HTML sections for embedding in a web page. Use `quickstart.css` for the numbered circles and layout. Its styles are scoped to `.ddw-gpe-quick-guide`; they do not reset the website’s body or main layout.
- `guide.html` / `guide-de.html`: the full guide with topic-based FAQs, also generated from `content.json`.

The web fragments contain no scripts, admin links, account data or image dependencies. Choose the appropriate language fragment and place it within the page’s content. It starts with an h2 heading and uses h3 step headings. The admin-only links to settings are deliberately omitted from the portable formats.

Change the shared source rather than editing generated copies independently. Informal and formal German use the same neutral instructional text. The generated files are prepared content; generating them does not publish or update a website or GitHub wiki.
