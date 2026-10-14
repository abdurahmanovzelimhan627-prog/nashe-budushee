(function () {
  var B = window.BOOT, app = document.getElementById('app');
  var C = B.content || {}, dirty = false, tab = 'services';

  // Маленький помощник для сборки элементов
  function h(tag, attrs) {
    var el = document.createElement(tag), i, k;
    for (k in attrs || {}) {
      if (k === 'text') el.textContent = attrs[k];
      else if (k.slice(0, 2) === 'on') el.addEventListener(k.slice(2), attrs[k]);
      else if (attrs[k] === true) el.setAttribute(k, '');
      else if (attrs[k] !== false && attrs[k] != null) el.setAttribute(k, attrs[k]);
    }
    for (i = 2; i < arguments.length; i++) {
      var kid = arguments[i];
      if (kid == null || kid === false) continue;
      (Array.isArray(kid) ? kid : [kid]).forEach(function (x) { if (x != null && x !== false) el.append(x); });
    }
    return el;
  }
  function icon(name) {
    var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg'), u = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    s.setAttribute('class', 'icon'); u.setAttribute('href', '#i-' + name); s.append(u);
    return s;
  }
  function api(action, body, isForm) {
    if (B.static) return window.STATIC_ADMIN.api(action, isForm ? body : (body || {}), isForm);
    var headers = { 'X-CSRF': B.csrf };
    if (!isForm) headers['Content-Type'] = 'application/json';
    return fetch('?action=' + action, { method: 'POST', headers: headers, body: isForm ? body : JSON.stringify(body || {}) })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) throw new Error(j.error || 'Что-то пошло не так. Попробуйте ещё раз.'); return j; }); });
  }

  /* ---------- Вход ---------- */
  function loginView() {
    var pass = h('input', { type: 'password', id: 'pass', autocomplete: 'current-password', placeholder: B.static ? 'Ключ доступа' : 'Пароль' });
    var msg = h('p', { class: 'msg err', hidden: true });
    var form = h('form', { class: 'login', onsubmit: function (e) {
      e.preventDefault();
      api('login', { password: pass.value }).then(function () { location.reload(); })
        .catch(function (er) { msg.textContent = er.message; msg.hidden = false; pass.select(); });
    } },
      h('img', { src: '../assets/logo.png', alt: 'Наше Будущее', width: 411, height: 183 }),
      h('h1', { text: 'Вход в админку' }),
      B.ready ? null : h('p', { class: 'msg err', text: 'Админка ещё не настроена: нет файла с паролем.' }),
      B.static ? h('p', { class: 'hint', text: 'Вставьте ключ доступа, который вам выдали. Он хранится только в этом браузере.' }) : null,
      h('label', { for: 'pass', text: B.static ? 'Ключ доступа' : 'Пароль' }), pass,
      B.error ? h('p', { class: 'msg err', text: B.error }) : null, msg,
      h('button', { class: 'btn main', type: 'submit', text: 'Войти' }));
    app.replaceChildren(form);
    pass.focus();
  }

  /* ---------- Поля ---------- */
  var uid = 0;
  function touch() { dirty = true; renderStatus(); }
  function field(label, control, hint) {
    var id = 'f' + (++uid);
    if (control.tagName === 'INPUT' || control.tagName === 'TEXTAREA' || control.tagName === 'SELECT') control.id = id;
    return h('div', { class: 'field' }, h('label', { for: id, text: label }), control, hint ? h('small', { text: hint }) : null);
  }
  function textInput(obj, key, placeholder, onchange) {
    return h('input', { type: 'text', value: obj[key] || '', placeholder: placeholder || '', oninput: function (e) { obj[key] = e.target.value; touch(); if (onchange) onchange(); } });
  }
  function areaInput(obj, key, rows) {
    var t = h('textarea', { rows: rows || 3, oninput: function (e) { obj[key] = e.target.value; touch(); } });
    t.value = obj[key] || '';
    return t;
  }
  function linesInput(obj, key) {
    var t = h('textarea', { rows: 4, oninput: function (e) { obj[key] = e.target.value.split('\n').map(function (x) { return x.trim(); }).filter(Boolean); touch(); } });
    t.value = (obj[key] || []).join('\n');
    return t;
  }
  function iconInput(obj, key) {
    var box = h('div', { class: 'icons', role: 'radiogroup' });
    Object.keys(B.icons).forEach(function (name) {
      var b = h('button', { type: 'button', title: B.icons[name], 'aria-label': B.icons[name], 'aria-pressed': String(obj[key] === name), onclick: function () {
        obj[key] = name; touch();
        box.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
      } }, icon(name));
      box.append(b);
    });
    return box;
  }
  function peopleInput(obj, key) {
    var box = h('div', { class: 'ticks' });
    if (!(C.people || []).length) box.append(h('small', { text: 'Сначала добавьте специалистов на вкладке «Специалисты».' }));
    (C.people || []).forEach(function (p) {
      var c = h('input', { type: 'checkbox', onchange: function () {
        var list = (obj[key] || []).filter(function (id) { return id !== p.id; });
        if (c.checked) list.push(p.id);
        obj[key] = list; touch();
      } });
      c.checked = (obj[key] || []).indexOf(p.id) >= 0;
      box.append(h('label', {}, c, ' ' + p.name));
    });
    return box;
  }
  function imageInput(obj, key, kind) {
    var box = h('div', { class: 'image ' + kind });
    function draw(note, isErr) {
      var file = h('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp', hidden: true, onchange: function () {
        if (!file.files[0]) return;
        var fd = new FormData(); fd.append('file', file.files[0]); fd.append('kind', kind);
        draw('Загружаю…');
        api('upload', fd, true).then(function (r) { obj[key] = r.path; touch(); draw('Фото загружено. Не забудьте нажать «Сохранить».'); })
          .catch(function (er) { draw(er.message, true); });
      } });
      box.replaceChildren(
        obj[key] ? h('img', { src: (B.static && window.STATIC_ADMIN.previews[obj[key]]) || '../' + obj[key], alt: '' }) : h('div', { class: 'empty', text: 'Нет фото' }),
        h('div', { class: 'image-actions' },
          h('button', { class: 'btn', type: 'button', text: obj[key] ? 'Заменить фото' : 'Загрузить фото', onclick: function () { file.click(); } }),
          obj[key] ? h('button', { class: 'btn ghost', type: 'button', text: 'Убрать', onclick: function () { obj[key] = ''; touch(); draw(); } }) : null,
          note ? h('small', { class: isErr ? 'err' : '', text: note }) : null),
        file);
    }
    draw();
    return box;
  }
  function rowsInput(obj, key) {
    var box = h('div', { class: 'rows' });
    function draw() {
      var rows = obj[key] = obj[key] || [];
      box.replaceChildren();
      rows.forEach(function (r, i) {
        box.append(h('div', { class: 'row' },
          h('input', { type: 'text', value: r.label || '', placeholder: 'Что', 'aria-label': 'Название строки', oninput: function (e) { r.label = e.target.value; touch(); } }),
          h('input', { type: 'text', value: r.value || '', placeholder: 'Цена', 'aria-label': 'Цена', oninput: function (e) { r.value = e.target.value; touch(); } }),
          h('button', { class: 'btn ghost small', type: 'button', 'aria-label': 'Удалить строку', text: '✕', onclick: function () { rows.splice(i, 1); touch(); draw(); } })));
      });
      box.append(h('button', { class: 'btn small', type: 'button', text: '+ Добавить строку', onclick: function () { rows.push({ label: '', value: '' }); touch(); draw(); } }));
    }
    draw();
    return box;
  }

  /* ---------- Вкладки ---------- */
  var TABS = [
    { id: 'services', anchor: 'directions', title: 'Услуги', list: 'services', noun: 'услугу', name: 'title',
      hint: 'Карточки в разделе «С чем к нам приходят». Эти же названия попадают в список «Специалист» в форме заявки.',
      blank: function () { return { title: '', text: '', icon: 'star', price: '', price_note: 'занятие', people: [] }; },
      fields: function (it, re) { return [
        field('Название', textInput(it, 'title', '', re)),
        field('Цена', textInput(it, 'price', '1 000 ₽')),
        field('Описание', areaInput(it, 'text', 2)),
        field('Подпись к цене', textInput(it, 'price_note', 'занятие'), 'Например: занятие, детский приём'),
        field('Значок', iconInput(it, 'icon')),
        field('Кто ведёт', peopleInput(it, 'people'))]; } },
    { id: 'prices', anchor: 'prices', title: 'Цены', list: 'prices', noun: 'карточку цен', name: 'title',
      hint: 'Карточки в разделе «Сколько стоят занятия». Первая карточка показывается широкой.',
      blank: function () { return { title: '', tags: [], rows: [{ label: 'Разовое занятие', value: '' }, { label: 'Абонемент', value: '' }] }; },
      fields: function (it, re) { return [
        field('Заголовок', textInput(it, 'title', '', re)),
        field('Что входит', linesInput(it, 'tags'), 'Каждое направление с новой строки'),
        field('Строки с ценами', rowsInput(it, 'rows'))]; },
      after: function () { return h('div', { class: 'card' }, field('Примечание под ценами', areaInput(C.texts, 'price_note', 2))); } },
    { id: 'people', anchor: 'team', title: 'Специалисты', list: 'people', noun: 'специалиста', name: 'name',
      hint: 'Первые трое показываются на первом экране сайта. Порядок меняется стрелками.',
      blank: function () { return { id: '', name: '', role: '', exp: '', about: '', photo: '', poster: '' }; },
      fields: function (it, re) { return [
        field('Фото лица', imageInput(it, 'photo', 'photo'), 'Лучше квадратное, лицо по центру'),
        field('Плакат, необязательно', imageInput(it, 'poster', 'poster'), 'Открывается по нажатию на карточку'),
        field('Имя и фамилия', textInput(it, 'name', '', re)),
        field('Должность', textInput(it, 'role')),
        field('Стаж, коротко', textInput(it, 'exp', '11 лет стажа')),
        field('Подпись в карточке', textInput(it, 'about'), 'Полная строка под плакатом')]; } },
    { id: 'reviews', anchor: 'reviews', title: 'Отзывы', list: 'reviews', noun: 'отзыв', name: 'alt',
      hint: 'Скриншоты отзывов. Перед публикацией убедитесь, что автор не против, или закройте имена на скриншоте.',
      blank: function () { return { img: '', alt: '' }; },
      fields: function (it) { return [
        field('Скриншот', imageInput(it, 'img', 'review')),
        field('О чём отзыв', areaInput(it, 'alt', 3), 'Короткое описание для поиска и незрячих. Отзыв без скриншота не сохранится.')]; } },
    { id: 'faq', anchor: 'faq', title: 'Вопросы', list: 'faq', noun: 'вопрос', name: 'q',
      hint: 'Раздел «Вопросы родителей». Первый вопрос показывается раскрытым.',
      blank: function () { return { q: '', a: '' }; },
      fields: function (it, re) { return [field('Вопрос', textInput(it, 'q', '', re)), field('Ответ', areaInput(it, 'a', 3))]; } },
    { id: 'steps', anchor: 'how', title: 'Шаги', list: 'steps', noun: 'шаг', name: 'title',
      hint: 'Раздел «Как проходит знакомство с центром». Лучше всего смотрятся 4 шага.',
      blank: function () { return { title: '', text: '' }; },
      fields: function (it, re) { return [field('Заголовок', textInput(it, 'title', '', re)), field('Текст', areaInput(it, 'text', 2))]; } },
    { id: 'contacts', anchor: 'contacts', title: 'Контакты и тексты', hint: 'Телефон используется и для звонков, и для всех кнопок «Написать» на сайте.',
      single: function () { return [
        h('div', { class: 'card grid' },
          field('Телефон', textInput(C.contacts, 'phone', '+7 (900) 000-00-00')),
          field('Часы работы', textInput(C.contacts, 'hours')),
          field('Адрес', textInput(C.contacts, 'address'))),
        h('div', { class: 'card' },
          field('Текст под главным заголовком', areaInput(C.texts, 'hero_sub', 3)),
          field('Жёлтая записка на первом экране', areaInput(C.texts, 'hero_note', 3)))]; } },
    { id: 'password', title: 'Пароль', hint: 'Смена пароля для входа в админку. Не короче 10 символов.', single: passwordForm }
  ];

  if (B.static) TABS = TABS.filter(function (t) { return t.id !== 'password'; });

  function passwordForm() {
    var oldP = h('input', { type: 'password', autocomplete: 'current-password' }), newP = h('input', { type: 'password', autocomplete: 'new-password' });
    var msg = h('p', { class: 'msg', hidden: true });
    return [h('form', { class: 'card', onsubmit: function (e) {
      e.preventDefault();
      api('password', { old: oldP.value, new: newP.value })
        .then(function () { msg.className = 'msg ok'; msg.textContent = 'Пароль изменён.'; msg.hidden = false; oldP.value = newP.value = ''; })
        .catch(function (er) { msg.className = 'msg err'; msg.textContent = er.message; msg.hidden = false; });
    } }, field('Старый пароль', oldP), field('Новый пароль', newP), msg, h('button', { class: 'btn main', type: 'submit', text: 'Сменить пароль' }))];
  }

  function listView(t) {
    var items = C[t.list] = C[t.list] || [], wrap = h('div', { class: 'list' });
    function move(i, d) { var j = i + d; if (j < 0 || j >= items.length) return; var x = items[i]; items[i] = items[j]; items[j] = x; touch(); draw(); }
    function draw() {
      wrap.replaceChildren();
      items.forEach(function (it, i) {
        var title = h('strong', {});
        function re() { title.textContent = (i + 1) + '. ' + (it[t.name] || 'Без названия').slice(0, 60); }
        re();
        var del = h('button', { class: 'btn ghost small', type: 'button', text: 'Удалить', onclick: function () {
          if (del.dataset.sure) { items.splice(i, 1); touch(); draw(); return; }
          del.dataset.sure = '1'; del.textContent = 'Точно удалить?'; del.classList.add('danger');
          setTimeout(function () { delete del.dataset.sure; del.textContent = 'Удалить'; del.classList.remove('danger'); }, 4000);
        } });
        wrap.append(h('section', { class: 'card' },
          h('header', {}, title, h('span', { class: 'tools' },
            h('button', { class: 'btn ghost small', type: 'button', 'aria-label': 'Поднять выше', text: '↑', disabled: i === 0, onclick: function () { move(i, -1); } }),
            h('button', { class: 'btn ghost small', type: 'button', 'aria-label': 'Опустить ниже', text: '↓', disabled: i === items.length - 1, onclick: function () { move(i, 1); } }),
            del)),
          h('div', { class: 'grid' }, t.fields(it, re))));
      });
      wrap.append(h('button', { class: 'btn add', type: 'button', text: '+ Добавить ' + t.noun, onclick: function () {
        items.push(t.blank()); touch(); draw();
        wrap.querySelectorAll('section.card')[items.length - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
      } }));
      if (t.after) wrap.append(t.after());
    }
    draw();
    return wrap;
  }

  /* ---------- Каркас ---------- */
  var statusEl, saveBtn, bodyEl, tabsEl;
  function renderStatus(text, cls) {
    if (!statusEl) return;
    statusEl.className = 'status ' + (cls || (dirty ? 'warn' : ''));
    statusEl.textContent = text || (dirty ? 'Есть несохранённые изменения' : 'Все изменения сохранены');
    saveBtn.disabled = !dirty;
  }
  function renderTab() {
    var t = TABS.filter(function (x) { return x.id === tab; })[0];
    tabsEl.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-selected', String(b.dataset.tab === tab)); });
    bodyEl.replaceChildren(h('p', { class: 'hint' }, t.hint + ' ', t.anchor ? h('a', { href: '../#' + t.anchor, target: '_blank', rel: 'noopener', text: 'Показать этот раздел на сайте' }) : null), t.list ? listView(t) : h('div', { class: 'list' }, t.single()));
  }
  function save() {
    saveBtn.disabled = true;
    renderStatus('Сохраняю…');
    api('save', { content: C }).then(function (r) {
      C = r.content; dirty = false; renderTab();
      renderStatus('Сохранено в ' + new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }) + (B.static ? '. На сайте изменения появятся через 1–3 минуты.' : '. Изменения уже на сайте.'), 'ok');
    }).catch(function (er) { renderStatus(er.message, 'err'); saveBtn.disabled = false; });
  }
  function mainView() {
    C.contacts = C.contacts || {}; C.texts = C.texts || {};
    statusEl = h('span', { class: 'status', role: 'status' });
    saveBtn = h('button', { class: 'btn main', type: 'button', text: 'Сохранить', onclick: save });
    tabsEl = h('nav', { class: 'tabs', role: 'tablist' }, TABS.map(function (t) {
      return h('button', { type: 'button', role: 'tab', 'data-tab': t.id, text: t.title, onclick: function () { tab = t.id; renderTab(); window.scrollTo(0, 0); } });
    }));
    bodyEl = h('main', {});
    app.replaceChildren(
      h('header', { class: 'bar' },
        h('div', { class: 'bar-row' },
          h('b', { text: 'Админка · Наше Будущее' }),
          h('span', { class: 'bar-links' },
            h('a', { href: '../', target: '_blank', rel: 'noopener', text: 'Открыть сайт' }),
            h('button', { class: 'link', type: 'button', text: 'Выйти', onclick: function () {
              if (dirty && !window.confirm('Есть несохранённые изменения. Выйти без сохранения?')) return;
              dirty = false; api('logout').then(function () { location.reload(); });
            } }))),
        h('div', { class: 'bar-row' }, statusEl, saveBtn),
        tabsEl),
      bodyEl);
    renderTab(); renderStatus();
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  if (B.authed) mainView(); else loginView();
})();
