# SPDX-License-Identifier: GPL-2.0-or-later
"""Render public bilingual wiki and Pages documents from the admin guide source.
Usage: python3 tools/build-publishing.py /path/to/wiki /path/to/pages
"""
from pathlib import Path
import sys,json,runpy,shutil
r=Path(__file__).resolve().parents[1];d=json.loads((r/'docs/content.json').read_text());w=Path(sys.argv[1]);p=Path(sys.argv[2]);w.mkdir(exist_ok=True);p.mkdir(exist_ok=True)
u='https://github.com/deckerweb/gp-project-export-name';raw='https://raw.githubusercontent.com/deckerweb/gp-project-export-name/master/'
q=runpy.run_path(str(r/'tools/build-docs.py'))['quick_markdown']
nav={ 'en':[('Getting-started','Getting started'),('User-guide','Illustrated user guide'),('FAQ','FAQ by topic'),('Integrations','Integrations and advanced settings'),('Changelog','Changelog'),('Help-and-security','Help and security')], 'de':[('Erste-Schritte','Erste Schritte'),('Anleitung','Bebilderte Anleitung'),('FAQ-Deutsch','Fragen nach Themen'),('Integrationen','Integrationen und erweiterte Einstellungen'),('Aenderungsverlauf','Änderungsverlauf'),('Hilfe-und-Sicherheit','Hilfe und Sicherheit')]}
home='# GP Project Export Name\n\n![Practical filenames for every translation.]('+raw+'assets/brand/banner-en-1280x640.png)\n\n'
for lang,data in d.items():
 de=lang=='de';other='en' if de else 'de';names=nav[lang];home+='## '+('Deutsch' if de else 'English')+'\n\n**'+data['tagline']+'**\n\n'+data['about']+'\n\n'+data['requirements']+'\n\n'+(' · '.join('['+title+']('+name+')' for name,title in names))+'\n\n'
 def write(i,text):
  name,title=names[i];switch=nav[other][i][0];(w/(name+'.md')).write_text('# '+title+'\n\n['+('English' if de else 'Deutsch')+']('+switch+')\n\n'+text+'\n')
 write(0,data['about']+'\n\n'+data['requirements']+'\n\n'+data['start']+'\n\n'+data['migration']+'\n\n'+''.join('- '+x+'\n' for x in data['features'])+'\n['+names[1][1]+']('+names[1][0]+')')
 text=q(data['quickstart']);steps=data['quickstart']['steps']
 # Attach images to their exact shared-source step, preserving all instruction text.
 for i,step in enumerate(steps):
  image=['overview','project','preview','save','export'][i]
  needle=('   '+step['tip']+'\n\n') if step.get('tip') else ('   '+step['text']+'\n\n')
  caption=('GlotPress verwendet hier seine eigene Oberflächensprache.' if de and i==4 else step['title'])
  text=text.replace(needle,needle+'   !['+caption+']('+raw+'docs/screenshots/'+image+'-'+lang+'.png)\n\n',1)
 text+='\n### '+('Kurzanleitung jederzeit öffnen' if de else 'Open the quick guide at any time')+'\n\n!['+data['quickstart']['title']+']('+raw+'docs/screenshots/quick-guide-'+lang+'.png)\n'
 write(1,text)
 text=''
 for group in data['faq_groups']:
  text+='## '+group['title']+'\n\n'
  for index in group['items']:
   question,answer=data['faq_full'][index];text+='### '+question+'\n\n'+answer+'\n\n'
 write(2,text)
 text=''
 for key,title in [('rules','Namensmuster' if de else 'Filename patterns'),('preview','Vorschau' if de else 'Preview'),('projects','Projektsuche' if de else 'Project search'),('ownership','Zuständigkeit' if de else 'Responsibility'),('bulk','Externe Sammel-Downloads' if de else 'External bulk downloads'),('constant','Fester Sprachcode' if de else 'Fixed language code'),('transfer','Sichern und importieren' if de else 'Backup and import'),('reset','Zurücksetzen' if de else 'Reset'),('updates','Updates und Datenschutz' if de else 'Updates and privacy'),('data','Gespeicherte Daten' if de else 'Stored data')]:text+='## '+title+'\n\n'+data[key]+'\n\n'
 write(3,text);write(4,(r/('docs/CHANGES-de.md' if de else 'docs/CHANGES.md')).read_text().split('\n',1)[1])
 write(5,'[Issues / Fehler]('+u+'/issues) · [Discussions / Austausch]('+u+'/discussions)\n\n['+('Sicherheitsrichtlinie' if de else 'Security policy')+']('+u+'/blob/master/SECURITY'+('-de' if de else '')+'.md) · [Private vulnerability report / Vertrauliche Sicherheitsmeldung]('+u+'/security/advisories/new)\n\n'+('Keine Sicherheitsdetails in öffentlichen Issues veröffentlichen.' if de else 'Do not disclose security details in public issues.'))
(w/'Home.md').write_text(home)
(w/'_Sidebar.md').write_text('[GP Project Export Name](Home)\n\n'+''.join('## '+('Deutsch' if lang=='de' else 'English')+'\n\n'+''.join('- ['+title+']('+name+')\n' for name,title in nav[lang])+'\n' for lang in ['en','de']))
(w/'_Footer.md').write_text('**GP Project Export Name · Documentation / Dokumentation**\n\n[Wiki home / Wiki-Startseite](Home) · [Repository]('+u+') · [Downloads & Releases]('+u+'/releases)\n\n[Help & bugs / Hilfe & Fehler]('+u+'/issues) · [Discussions / Austausch]('+u+'/discussions) · [Security / Sicherheit]('+u+'/security/policy) · [Private report / Vertraulich melden]('+u+'/security/advisories/new)\n\n© 2016–2026 David Decker – DECKERWEB.  \nPlugin: [GPL v2 or later / GPL v2 oder höher](https://www.gnu.org/licenses/gpl-2.0.html).\n')
(p/'_config.yml').write_text('theme: jekyll-theme-cayman\ntitle: GP Project Export Name\ndescription: Practical filenames for every translation.\nshow_downloads: false\n')
css=p/'assets/css';css.mkdir(parents=True,exist_ok=True);(css/'style.scss').write_text('---\n---\n\n@import "{{ site.theme }}";\n.page-header{background-color:#063f49;background-image:linear-gradient(120deg,#063f49,#117b75)}\n.main-content h1,.main-content h2,.main-content h3,.main-content h4,.main-content h5,.main-content h6{font-weight:700;color:#08665f}\n.main-content img{max-width:100%;height:auto}.main-content a{color:#08665f}\n')
# Preserve the existing gh-pages URL and historical supporting files; index.md replaces old landing HTML.
if (p/'index.html').exists():(p/'index.html').unlink()
for lang,data in d.items():
 de=lang=='de';name='de.md' if de else 'index.md';text='---\nlayout: default\n---\n\n!['+data['tagline']+']('+raw+'assets/brand/banner-'+lang+'-1280x640.png)\n\n[English](./) · [Deutsch](de.html)\n\n'+data['about']+'\n\n'+data['requirements']+'\n\n[GitHub]('+u+') · [Wiki]('+u+'/wiki) · [Downloads & Releases]('+u+'/releases)\n\n'+q(data['quickstart'])+'\n!['+data['quickstart']['title']+']('+raw+'docs/screenshots/overview-'+lang+'.png)\n\n['+('Vollständige bebilderte Anleitung' if de else 'Full illustrated guide')+']('+u+'/wiki/'+nav[lang][1][0]+')\n\n© 2016–2026 David Decker – DECKERWEB. Plugin: GPL v2 or later.\n';(p/name).write_text(text)
print('Rendered 15 wiki files and bilingual Cayman Pages from shared content.')
