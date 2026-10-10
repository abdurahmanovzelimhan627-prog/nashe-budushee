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

for name in ('index.html', os.path.join('artifact', 'page.html')):
    print(name, round(os.path.getsize(os.path.join(ROOT, name)) / 1024), 'KB')
