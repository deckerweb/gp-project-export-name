# SPDX-License-Identifier: GPL-2.0-or-later
"""Render bilingual readmes, guides and portable web fragments from one source."""
from pathlib import Path
import html
import json
import re

ROOT = Path(__file__).resolve().parents[1]
DATA = json.loads((ROOT / 'docs/content.json').read_text())
esc = html.escape


def paragraphs(text):
    """Escape plain source paragraphs for standalone HTML output."""
    return ''.join('<p>' + esc(part) + '</p>' for part in text.split('\n\n'))


def quick_html(guide):
    """Render the same five steps as the admin modal, without admin-only links."""
    body = '<section class="ddw-gpe-quick-guide"><h2>' + esc(guide['title']) + '</h2>'
    body += paragraphs(guide['intro'])
    body += '<section class="example"><h3>' + esc(guide['example_title']) + '</h3><dl>'
    for part in ['before', 'after']:
        body += '<div><dt>' + esc(guide[part + '_label']) + '</dt><dd><code>' + esc(guide[part]) + '</code></dd></div>'
    body += '</dl>' + paragraphs(guide['example_note']) + '</section><ol class="steps" role="list">'
    for number, step in enumerate(guide['steps'], 1):
        body += '<li><span class="number" aria-hidden="true">' + str(number) + '.</span><div><h3>' + esc(step['title']) + '</h3>' + paragraphs(step['text'])
        if step.get('tip'):
            body += '<p class="tip">' + esc(step['tip']) + '</p>'
        body += '</div></li>'
    return body + '</ol><h3>' + esc(guide['optional_title']) + '</h3>' + paragraphs(guide['optional']) + '</section>'


def quick_markdown(guide):
    """Render the introductory steps as ordinary, accessible Markdown lists."""
    text = guide['intro'] + '\n\n### ' + guide['example_title'] + '\n\n'
    text += '- ' + guide['before_label'] + ': `' + guide['before'] + '`\n'
    text += '- ' + guide['after_label'] + ': `' + guide['after'] + '`\n\n' + guide['example_note'] + '\n\n'
    for number, step in enumerate(guide['steps'], 1):
        text += str(number) + '. **' + step['title'] + '**\n\n   ' + step['text'] + '\n\n'
        if step.get('tip'):
            text += '   ' + step['tip'] + '\n\n'
    return text + '**' + guide['optional_title'] + ':** ' + guide['optional'] + '\n\n'


def anchor(title):
    """Create GitHub-style section anchors for the fixed document headings."""
    return re.sub(r'[^\w\s-]', '', title.lower()).replace(' ', '-')


for language, data in DATA.items():
    german = language == 'de'
    suffix = '-de' if german else ''
    other = '' if german else '-de'
    labels = (['Kurzvorstellung', 'Auf einen Blick', 'Erste Schritte', 'Kurzanleitung', 'Namensmuster und Platzhalter', 'Dateinamen prüfen', 'Zuständiges Namensplugin', 'Sammel-Downloads', 'Fester Sprachcode', 'Updates und Datenschutz', 'Wechsel von der Altversion', 'Gespeicherte Daten', 'Projekte suchen', 'Einstellungen sichern und importieren', 'Einstellungen zurücksetzen', 'Fragen nach Themen', 'Änderungsverlauf'] if german else ['About', 'At a glance', 'Getting started', 'Quick guide', 'Filename patterns and placeholders', 'Check filenames', 'Responsible naming plugin', 'Bulk downloads', 'Fixed language code', 'Updates and privacy', 'Moving from the old version', 'Stored data', 'Find projects', 'Settings backup and import', 'Reset settings', 'FAQ by topic', 'Changelog'])
    topics = list(zip(labels[4:15], [data[key] for key in ['rules', 'preview', 'ownership', 'bulk', 'constant', 'updates', 'migration', 'data', 'projects', 'transfer', 'reset']]))
    language_link = '<a href="guide' + other + '.html">' + ('English' if german else 'Deutsch') + '</a>'
    footer = '<footer><p>© 2016–2026 David Decker – DECKERWEB · ' + ('Plugin-Lizenz: GPL v2 oder höher.' if german else 'Plugin license: GPL v2 or later.') + '</p><p>' + ('Gemeinsame Komponenten: ' if german else 'Shared components: ') + 'deckerweb Library 1.1.0 / Updater 2.2.1, GPL-2.0-or-later. <a href="https://github.com/deckerweb/gp-project-export-name">GitHub</a></p></footer>'

    def page(body):
        """Wrap local, fully escaped documentation in a portable page."""
        return '<!doctype html><html lang="' + language + '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>GP Project Export Name</title><link rel="stylesheet" href="guide.css"></head><body><main>' + body + footer + '</main></body></html>'

    intro = '<h1>' + esc(data['title']) + '</h1><p>' + esc(data['tagline']) + '</p>' + paragraphs(data['about']) + paragraphs(data['requirements'])
    intro += '<p>' + language_link + '</p>'
    body = intro + '<h2>' + labels[1] + '</h2><ul>' + ''.join('<li>' + esc(item) + '</li>' for item in data['features']) + '</ul>'
    body += '<h2>' + labels[2] + '</h2>' + paragraphs(data['start']) + quick_html(data['quickstart'])
    markdown = '# ' + data['title'] + '\n\n[' + ('English' if german else 'Deutsch') + '](' + ('../README.md' if german else 'docs/README-de.md') + ')\n\n'
    banner = ('../' if german else '') + 'assets/brand/banner-' + language + '-1280x640.png'
    markdown += '![' + data['tagline'] + '](' + banner + ')\n\n**2.0.0** · WordPress 6.4+ · PHP 8.1+ · GlotPress 4.0+\n\n'
    markdown += '[' + ('Bebilderte Anleitung' if german else 'Illustrated guide') + '](https://github.com/deckerweb/gp-project-export-name/wiki/' + ('Anleitung' if german else 'User-guide') + ') · [Wiki](https://github.com/deckerweb/gp-project-export-name/wiki)\n\n'
    markdown += '## ' + labels[0] + '\n\n' + data['about'] + '\n\n' + data['requirements'] + '\n\n'
    prefix = '' if german else 'docs/'
    markdown += '[' + labels[3] + '](' + prefix + 'quickstart' + suffix + '.html) · [' + ('Vollständige Anleitung und FAQ' if german else 'Full guide and FAQ') + '](' + prefix + 'guide' + suffix + '.html)\n\n'
    markdown += ' · '.join('[' + title + '](#' + anchor(title) + ')' for title in labels[1:]) + '\n\n'
    markdown += '## ' + labels[1] + '\n\n' + ''.join('- ' + item + '\n' for item in data['features']) + '\n## ' + labels[2] + '\n\n' + data['start'] + '\n\n## ' + labels[3] + '\n\n' + quick_markdown(data['quickstart'])
    for title, text in topics:
        body += '<h2 id="' + anchor(title) + '">' + esc(title) + '</h2>' + paragraphs(text)
        markdown += '## ' + title + '\n\n' + text + '\n\n'
    body += '<h2>' + labels[15] + '</h2>'
    questions = data.get('faq_full', data['faq'])
    for group in data.get('faq_groups', [{'title': labels[15], 'items': list(range(len(questions)))}]):
        body += '<h3>' + esc(group['title']) + '</h3>'
        for index in group['items']:
            question, answer = questions[index]
            body += '<h4>' + esc(question) + '</h4>' + paragraphs(answer)
    markdown += '## Screenshots\n\n'
    for image, title in [('overview', 'Einstellungsseite und Kurzanleitung' if german else 'Settings and quick guide'), ('preview', 'Dateinamen vor dem Speichern prüfen' if german else 'Check filenames before saving')]:
        markdown += '![' + title + '](' + prefix + 'screenshots/' + image + '-' + language + '.png)\n\n'
    markdown += '## ' + labels[15] + '\n\n'
    for question, answer in data['faq']:
        markdown += '### ' + question + '\n\n' + answer + '\n\n'
    markdown += '[' + ('Alle Fragen nach Themen' if german else 'All FAQs by topic') + '](' + prefix + 'guide' + suffix + '.html)\n\n## ' + labels[16] + '\n\n'
    history = ''
    changes = '# ' + labels[16] + '\n\n'
    for version, date, categories in data['history']:
        history += '<h3>' + esc(version) + ' · ' + esc(date) + '</h3>'
        entry = version + ' · ' + date + '\n\n'
        markdown += '### ' + entry
        changes += '## ' + entry
        for category, items in categories.items():
            history += '<h4>' + esc(category) + '</h4><ul>' + ''.join('<li>' + esc(item) + '</li>' for item in items) + '</ul>'
            lines = ''.join('- **' + category + ':** ' + item + '\n' for item in items) + '\n'
            markdown += lines
            changes += lines
    body += '<h2>' + labels[16] + '</h2>' + history
    markdown += '## ' + ('Projekt und Hilfe' if german else 'Project and help') + '\n\n' + ('2016 von David Decker – DECKERWEB für einen eigenen GlotPress-Ablauf entwickelt.' if german else 'Originally created in 2016 by David Decker – DECKERWEB for a personal GlotPress workflow.') + '\n\n[GitHub](https://github.com/deckerweb/gp-project-export-name) · [Issues](https://github.com/deckerweb/gp-project-export-name/issues) · [' + ('Sicherheitsrichtlinie' if german else 'Security policy') + '](' + ('../SECURITY-de.md' if german else 'SECURITY.md') + ')\n\nCopyright © 2016–2026 David Decker – DECKERWEB. ' + ('Plugin-Lizenz: GPL v2 oder höher.' if german else 'Plugin license: GPL v2 or later.') + '\n\nLibrary 1.1.0 / Updater 2.2.1: David Decker – DECKERWEB, GPL-2.0-or-later.\n'
    markdown += '\n[Ko-fi](https://ko-fi.com/deckerweb) · [Buy Me a Coffee](https://buymeacoffee.com/daveshine) · [PayPal](https://paypal.me/deckerweb)\n'
    markdown = markdown.replace('](' + prefix + 'quickstart' + suffix + '.html)', '](https://github.com/deckerweb/gp-project-export-name/wiki/' + ('Anleitung' if german else 'User-guide') + ')').replace('](' + prefix + 'guide' + suffix + '.html)', '](https://github.com/deckerweb/gp-project-export-name/wiki/' + ('FAQ-Deutsch' if german else 'FAQ') + ')')
    (ROOT / ('docs/README-de.md' if german else 'README.md')).write_text(markdown)
    (ROOT / 'docs' / ('guide' + suffix + '.html')).write_text(page(body))
    (ROOT / 'docs' / ('changelog' + suffix + '.html')).write_text(page('<h1>' + labels[16] + '</h1>' + history))
    quick = quick_html(data['quickstart'])
    (ROOT / 'docs' / ('quickstart' + suffix + '.html')).write_text(page('<h1>GP Project Export Name</h1><p><a href="quickstart' + other + '.html">' + ('English' if german else 'Deutsch') + '</a></p>' + quick + '<p><a href="guide' + suffix + '.html">' + esc(data['quickstart']['docs_label']) + '</a></p>'))
    (ROOT / 'docs' / ('quickstart-fragment' + suffix + '.html')).write_text(quick)
    (ROOT / 'docs' / ('QUICKSTART' + suffix + '.md')).write_text('# ' + data['quickstart']['title'] + '\n\n[' + ('English' if german else 'Deutsch') + '](QUICKSTART' + other + '.md)\n\n' + quick_markdown(data['quickstart']))
    (ROOT / 'docs' / ('CHANGES' + suffix + '.md')).write_text(changes)

DOCUMENT_STYLE = '''body{font:16px/1.65 system-ui,sans-serif;color:#1d2327;background:#f0f0f1;margin:0}main{box-sizing:border-box;max-width:880px;margin:32px auto;padding:32px;background:white}a{color:#135e96}h2{margin-top:2em}code{font-family:monospace;overflow-wrap:anywhere}a:focus-visible{outline:2px solid #2271b1;outline-offset:3px}footer{margin-top:3em;padding-top:1em;border-top:1px solid #c3c4c7;font-size:14px}@media(max-width:600px){main{padding:20px;margin:0}}'''
QUICK_STYLE = '''.ddw-gpe-quick-guide{line-height:1.65;overflow-wrap:anywhere}.ddw-gpe-quick-guide code{font-family:monospace;overflow-wrap:anywhere}.ddw-gpe-quick-guide .example{background:#f4f8fc;border:1px solid #c5d9ed;border-radius:8px;padding:18px;margin:20px 0 28px}.ddw-gpe-quick-guide dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.ddw-gpe-quick-guide dt{font-size:14px;color:#50575e}.ddw-gpe-quick-guide dd{margin:6px 0}.ddw-gpe-quick-guide .steps{list-style:none;margin:0;padding:0}.ddw-gpe-quick-guide .steps>li{display:grid;grid-template-columns:40px minmax(0,1fr);gap:14px;margin:0 0 24px}.ddw-gpe-quick-guide .number{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border:2px solid #2271b1;border-radius:50%;color:#135e96;background:#f0f6fc;font-weight:600;box-sizing:border-box}.ddw-gpe-quick-guide h3{margin:0 0 8px}.ddw-gpe-quick-guide .tip{border-left:3px solid #72aee6;padding-left:12px;color:#50575e}@media(max-width:600px){.ddw-gpe-quick-guide dl{grid-template-columns:1fr}.ddw-gpe-quick-guide .steps>li{gap:10px}}@media(forced-colors:active){.ddw-gpe-quick-guide .number,.ddw-gpe-quick-guide .example{border-color:CanvasText}}'''
(ROOT / 'docs/guide.css').write_text(DOCUMENT_STYLE + QUICK_STYLE)
(ROOT / 'docs/quickstart.css').write_text(QUICK_STYLE)

# The directory-format readme remains reproducible, although no directory release is made.
d = DATA['en']
readme = '=== GP Project Export Name ===\nContributors: daveshine\nTags: glotpress, translations, export\nRequires at least: 6.4\nTested up to: 7.1.3\nRequires PHP: 8.1\nStable tag: 2.0.0\nLicense: GPLv2 or later\nLicense URI: https://www.gnu.org/licenses/gpl-2.0.html\n\nChoose recognizable GlotPress download names, check language variants and follow a five-step guide.\n\n== Description ==\n\n' + d['about'] + '\n\n' + d['requirements'] + '\n\n' + ''.join('* ' + item + '\n' for item in d['features']) + '\n' + d['updates'] + '\n\n== Installation ==\n\n' + d['start'] + '\n\n== Quick guide ==\n\n'
for number, step in enumerate(d['quickstart']['steps'], 1):
    readme += str(number) + '. ' + step['title'] + '\n' + step['text'] + '\n\n'
readme += '== Frequently Asked Questions ==\n\n'
for question, answer in d['faq']:
    readme += '= ' + question + ' =\n' + answer + '\n\n'
readme += 'Full documentation and FAQ: docs/guide.html and docs/guide-de.html in this package.\n\n== Changelog ==\n\n'
for version, date, categories in d['history']:
    readme += '= ' + version + ' =\n'
    for category, items in categories.items():
        readme += ''.join('* ' + category + ': ' + item + '\n' for item in items)
    readme += '\n'
(ROOT / 'readme.txt').write_text(readme)
