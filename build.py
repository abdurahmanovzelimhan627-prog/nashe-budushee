"""Сборка сайта: подставляет картинки из assets/ в src/page.html.

Запуск:  python3 build.py
Результат:
  index.html           — готовый файл для хостинга (один файл, всё внутри)
  artifact/page.html   — та же страница без обёртки, для публикации на claude.ai
"""
import base64, os, re

ROOT = os.path.dirname(os.path.abspath(__file__))
ASSETS = os.path.join(ROOT, 'assets')

HEAD = '''<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="description" content="Центр развития детей «Наше Будущее» в Гудермесе: логопед, дефектолог, детский психолог, нейропсихолог, АВА-терапия, сенсорная интеграция, остеопат, детский массаж. Запись: +7 (938) 019-90-80.">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="Наше Будущее — центр развития детей в Гудермесе">
<meta property="og:description" content="Логопеды, дефектологи, психологи и другие специалисты помогают детям говорить, учиться и уверенно расти.">
<meta name="theme-color" content="#FAFCFF">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%231D49B8'/%3E%3Cpath d='M12 4l2 5.400 5.700.4-4.400 3.600 1.500 5.600L12 15.800 7.200 19l1.500-5.600L4.300 9.800l5.700-.4z' fill='%23FFC93C'/%3E%3C/svg%3E">
'''


def data_uri(name):
    path = os.path.join(ASSETS, name)
    mime = 'image/png' if name.endswith('.png') else 'image/jpeg'
    with open(path, 'rb') as f:
        return 'data:%s;base64,%s' % (mime, base64.b64encode(f.read()).decode())


def fill(m):
    kind, key = m.group(1), m.group(2)
    if kind == 'LOGO':
        return data_uri('logo.png')
    return data_uri({'FACE': 'face_%s.jpg', 'POSTER': 'poster_%s.jpg', 'REVIEW': 'review_%s.jpg'}[kind] % key)


with open(os.path.join(ROOT, 'src', 'page.html'), encoding='utf-8') as f:
    page = f.read()

page = re.sub(r'__(LOGO|FACE|POSTER|REVIEW)_?([a-z0-9]*)__', fill, page)
head_part, body_part = page.split('<!--BODY-->')

os.makedirs(os.path.join(ROOT, 'artifact'), exist_ok=True)
with open(os.path.join(ROOT, 'artifact', 'page.html'), 'w', encoding='utf-8') as f:
    f.write(head_part + body_part)

full_title = head_part.replace('<title>Наше Будущее</title>', '<title>Наше Будущее — центр развития детей в Гудермесе</title>')
with open(os.path.join(ROOT, 'index.html'), 'w', encoding='utf-8') as f:
    f.write(HEAD + full_title + '</head>\n<body>\n' + body_part + '\n</body>\n</html>\n')

# Хостинг: шрифты лежат рядом с сайтом, без обращения к серверам Google
import shutil
FONT_LINKS = re.compile(r'<link rel="preconnect"[^>]*>\n<link rel="preconnect"[^>]*>\n<link rel="stylesheet" href="https://fonts.googleapis.com[^>]*>')
index_path = os.path.join(ROOT, 'index.html')
with open(index_path, encoding='utf-8') as f:
    html = f.read()
assert FONT_LINKS.search(html)
html = FONT_LINKS.sub('<link rel="stylesheet" href="fonts/fonts.css">', html)
with open(index_path, 'w', encoding='utf-8') as f:
    f.write(html)

# Юридические страницы
LEGAL_CSS = '''<style>
  :root { --paper:#FAFCFF; --card:#FFFFFF; --ink:#15244D; --ink-soft:#4A5779; --blue:#1D49B8; --orange-ink:#B8420A; --line:rgba(21,36,77,.13); }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Nunito','Segoe UI',system-ui,sans-serif; font-size: 17px; line-height: 1.65; -webkit-font-smoothing: antialiased; }
  .top { border-bottom: 1px solid var(--line); background: var(--card); }
  .top div, main, footer { max-width: 780px; margin: 0 auto; padding-inline: 20px; }
  .top div { display: flex; align-items: center; justify-content: space-between; gap: 16px; min-height: 76px; }
  .top img { height: 54px; width: auto; display: block; }
  .top a.back { font-weight: 800; color: var(--blue); text-decoration: none; }
  main { padding-block: 40px 56px; }
  h1 { font-size: clamp(28px, 6vw, 40px); line-height: 1.15; font-weight: 900; margin: 0 0 8px; text-wrap: balance; }
  h2 { font-size: 21px; font-weight: 800; margin: 34px 0 10px; }
  p { margin: 0 0 12px; }
  ul { margin: 0 0 12px; padding-left: 22px; }
  li { margin-bottom: 6px; }
  .rev { color: var(--ink-soft); font-size: 15px; margin-bottom: 26px; }
  a { color: var(--blue); }
  footer { padding-block: 22px 40px; border-top: 1px solid var(--line); font-size: 14.5px; color: var(--ink-soft); display: flex; flex-wrap: wrap; gap: 8px 22px; }
  footer a { color: var(--ink-soft); }
</style>'''
LEGAL = {'privacy.html': 'Политика конфиденциальности', 'consent.html': 'Согласие на обработку персональных данных', 'ads-consent.html': 'Согласие на рекламные сообщения'}
for fname, title in LEGAL.items():
    with open(os.path.join(ROOT, 'src', 'legal', fname), encoding='utf-8') as f:
        body = f.read()
    links = ''.join('<a href="%s">%s</a>' % (n, t) for n, t in LEGAL.items() if n != fname)
    doc = ('<!DOCTYPE html>\n<html lang="ru">\n<head>\n<meta charset="UTF-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n'
           '<title>%s — Наше Будущее</title>\n<link rel="stylesheet" href="fonts/fonts.css">\n%s\n</head>\n<body>\n'
           '<header class="top"><div><a href="index.html" aria-label="На главную"><img src="assets/logo.png" alt="Наше Будущее" width="411" height="183"></a>'
           '<a class="back" href="index.html">← На сайт</a></div></header>\n<main>\n%s</main>\n'
           '<footer><span>© 2026 Центр «Наше Будущее»</span>%s</footer>\n</body>\n</html>\n') % (title, LEGAL_CSS, body, links)
    with open(os.path.join(ROOT, fname), 'w', encoding='utf-8') as f:
        f.write(doc)

for name in ('index.html', os.path.join('artifact', 'page.html')):
    print(name, round(os.path.getsize(os.path.join(ROOT, name)) / 1024), 'KB')
