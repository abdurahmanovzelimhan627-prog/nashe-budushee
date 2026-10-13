// Админка без сервера: читает и пишет data/content.json и фото прямо в репозиторий GitHub.
// Вместо пароля — личный ключ доступа GitHub (токен), он хранится только в браузере хозяина.
(function () {
  var OWNER = 'abdurahmanovzelimhan627-prog', REPO = 'nashe-budushee', BRANCH = 'main';
  var KEY = 'nb_gh_token';
  var API = 'https://api.github.com/repos/' + OWNER + '/' + REPO;
  var IMAGE_SIDE = { photo: 900, poster: 1400, review: 1200 };
  var MAX_UPLOAD = 12 * 1024 * 1024;
  var ICONS = {
    speech: 'Речь', abc: 'Буквы', puzzle: 'Пазл', heart: 'Сердце', bulb: 'Лампочка', grid: 'Плитки',
    blocks: 'Кубики', swing: 'Движение', leaf: 'Лист', pulse: 'Пульс', hand: 'Ладонь', star: 'Звезда'
  };

  var token = '', sha = '', previews = {};
  try { token = localStorage.getItem(KEY) || ''; } catch (e) { token = ''; }

  function fail(message) { var e = new Error(message); return e; }

  function gh(path, opts) {
    opts = opts || {};
    var headers = { 'Accept': 'application/vnd.github+json', 'Authorization': 'Bearer ' + token, 'X-GitHub-Api-Version': '2022-11-28' };
    if (opts.body) headers['Content-Type'] = 'application/json';
    return fetch(API + path, { method: opts.method || 'GET', headers: headers, body: opts.body ? JSON.stringify(opts.body) : undefined, cache: 'no-store' })
      .then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (j) {
          if (r.ok) return j;
          if (r.status === 401) throw fail('Ключ доступа не подошёл или закончился его срок. Выйдите и введите новый ключ.');
          if (r.status === 403) throw fail('У этого ключа нет права менять сайт. Создайте ключ с доступом «Contents: Read and write».');
          if (r.status === 404) throw fail('Ключ не видит сайт. Проверьте, что он создан для репозитория nashe-budushee.');
          if (r.status === 409 || r.status === 422) throw fail('Кто-то уже изменил сайт. Обновите страницу и повторите правки.');
          throw fail('GitHub ответил ошибкой ' + r.status + '. Попробуйте ещё раз через минуту.');
        });
      }, function () { throw fail('Нет связи с GitHub. Проверьте интернет и попробуйте ещё раз.'); });
  }

  // base64 <-> Unicode
  function b64encodeUtf8(str) {
    var bytes = new TextEncoder().encode(str), s = '', i;
    for (i = 0; i < bytes.length; i += 0x8000) s += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
    return btoa(s);
  }
  function b64decodeUtf8(b64) {
    var bin = atob(b64.replace(/\s/g, '')), bytes = new Uint8Array(bin.length), i;
    for (i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
    return new TextDecoder().decode(bytes);
  }

  /* ---------- Чистка содержимого (как в admin/index.php) ---------- */
  function str(v, max) { return (typeof v === 'string' || typeof v === 'number') ? String(v).trim().slice(0, max || 2000) : ''; }
  function list(v, max) { return Array.isArray(v) ? v.slice(0, max || 60) : []; }
  function img(v) { return typeof v === 'string' && /^uploads\/[A-Za-z0-9_.-]+\.(jpe?g|png|webp)$/.test(v) ? v : ''; }
  function rid() { var a = new Uint8Array(4); crypto.getRandomValues(a); return Array.prototype.map.call(a, function (x) { return ('0' + x.toString(16)).slice(-2); }).join(''); }

  function cleanContent(inp) {
    var out = { contacts: {}, texts: {}, people: [], services: [], prices: [], steps: [], reviews: [], faq: [] };
    var c = inp.contacts || {}, t = inp.texts || {}, ids = {};
    ['phone', 'instagram', 'address', 'hours'].forEach(function (k) { out.contacts[k] = str(c[k], 200); });
    ['hero_sub', 'hero_note', 'price_note'].forEach(function (k) { out.texts[k] = str(t[k], 600); });
    list(inp.people).forEach(function (p) {
      var name = str(p && p.name, 120); if (!name) return;
      var id = str(p.id, 40);
      if (!/^[a-z0-9_-]{1,40}$/.test(id) || ids[id]) id = 'p' + rid();
      ids[id] = true;
      out.people.push({ id: id, name: name, role: str(p.role, 200), exp: str(p.exp, 80), about: str(p.about, 400), photo: img(p.photo), poster: img(p.poster) });
    });
    list(inp.services).forEach(function (s) {
      var title = str(s && s.title, 120); if (!title) return;
      var icon = str(s.icon, 20);
      out.services.push({
        title: title, text: str(s.text, 400), icon: ICONS[icon] ? icon : 'star',
        price: str(s.price, 40), price_note: str(s.price_note, 80),
        people: list(s.people, 10).filter(function (id) { return typeof id === 'string' && ids[id]; })
      });
    });
    list(inp.prices, 20).forEach(function (p) {
      var title = str(p && p.title, 120); if (!title) return;
      var rows = [];
      list(p.rows, 6).forEach(function (r) {
        var label = str(r && r.label, 60), value = str(r && r.value, 40);
        if (label || value) rows.push({ label: label, value: value });
      });
      var tags = list(p.tags, 12).map(function (x) { return str(x, 60); }).filter(Boolean);
      out.prices.push({ title: title, tags: tags, rows: rows });
    });
    list(inp.steps, 8).forEach(function (s) {
      var title = str(s && s.title, 120);
      if (title) out.steps.push({ title: title, text: str(s.text, 400) });
    });
    list(inp.reviews).forEach(function (r) {
      var im = img(r && r.img);
      if (im) out.reviews.push({ img: im, alt: str(r.alt, 300) });
    });
    list(inp.faq, 30).forEach(function (f) {
      var q = str(f && f.q, 200);
      if (q) out.faq.push({ q: q, a: str(f.a, 1000) });
    });
    return out;
  }

  /* ---------- Загрузка фото ---------- */
  function resizeToJpeg(file, side) {
    if (file.size > MAX_UPLOAD) return Promise.reject(fail('Файл больше 12 МБ. Выберите фото поменьше.'));
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return Promise.reject(fail('Подходят только фото JPG, PNG или WEBP.'));
    var open = window.createImageBitmap
      ? createImageBitmap(file, { imageOrientation: 'from-image' })
      : new Promise(function (res, rej) { var i = new Image(); i.onload = function () { res(i); }; i.onerror = rej; i.src = URL.createObjectURL(file); });
    return open.then(function (bmp) {
      var w = bmp.width, h = bmp.height, k = Math.min(1, side / Math.max(w, h));
      var cw = Math.max(1, Math.round(w * k)), ch = Math.max(1, Math.round(h * k));
      var cv = document.createElement('canvas'); cv.width = cw; cv.height = ch;
      var cx = cv.getContext('2d'); cx.fillStyle = '#fff'; cx.fillRect(0, 0, cw, ch); cx.drawImage(bmp, 0, 0, cw, ch);
      return new Promise(function (res, rej) { cv.toBlob(function (b) { b ? res(b) : rej(fail('Не получилось обработать фото.')); }, 'image/jpeg', 0.84); });
    }, function () { throw fail('Не получилось прочитать фото. Попробуйте другой файл.'); });
  }
  function blobToBase64(blob) {
    return new Promise(function (res, rej) {
      var fr = new FileReader();
      fr.onload = function () { res(String(fr.result).split(',')[1]); };
      fr.onerror = function () { rej(fail('Не получилось прочитать фото.')); };
      fr.readAsDataURL(blob);
    });
  }
  function today() { var d = new Date(); return ('' + d.getFullYear()).slice(2) + ('0' + (d.getMonth() + 1)).slice(-2) + ('0' + d.getDate()).slice(-2); }

  function upload(fd) {
    var file = fd.get('file'), kind = fd.get('kind');
    if (!IMAGE_SIDE[kind] || !file) return Promise.reject(fail('Файл не загрузился. Попробуйте ещё раз.'));
    var name = kind + '_' + today() + '_' + rid() + '.jpg', path = 'uploads/' + name;
    return resizeToJpeg(file, IMAGE_SIDE[kind]).then(function (blob) {
      return blobToBase64(blob).then(function (b64) {
        return gh('/contents/' + path, { method: 'PUT', body: { message: 'Фото из админки: ' + name, content: b64, branch: BRANCH } })
          .then(function () { previews[path] = URL.createObjectURL(blob); return { path: path }; });
      });
    });
  }

  /* ---------- Действия, которые зовёт admin.js ---------- */
  function save(body) {
    var clean = cleanContent(body.content || {});
    var json = JSON.stringify(clean, null, 4) + '\n';
    return gh('/contents/data/content.json', { method: 'PUT', body: { message: 'Обновление содержимого из админки', content: b64encodeUtf8(json), sha: sha, branch: BRANCH } })
      .then(function (r) { sha = r.content.sha; return { content: clean }; });
  }
  function login(body) {
    var t = String(body.password || '').trim();
    if (!t) return Promise.reject(fail('Вставьте ключ доступа.'));
    token = t;
    return gh('').then(function (repo) {
      if (!repo.permissions || !repo.permissions.push) { token = ''; throw fail('У этого ключа нет права менять сайт.'); }
      try { localStorage.setItem(KEY, t); } catch (e) { /* без запоминания тоже работает до закрытия вкладки */ }
      return { ok: true };
    }).catch(function (e) { token = ''; throw e; });
  }
  function logout() {
    try { localStorage.removeItem(KEY); } catch (e) { /* ничего */ }
    token = '';
    return Promise.resolve({ ok: true });
  }

  window.STATIC_ADMIN = {
    previews: previews,
    api: function (action, body, isForm) {
      if (action === 'login') return login(body);
      if (action === 'logout') return logout();
      if (action === 'save') return save(body);
      if (action === 'upload') return upload(body);
      return Promise.reject(fail('Это действие здесь недоступно.'));
    },
    cleanContent: cleanContent
  };

  /* ---------- Запуск ---------- */
  function start(boot) {
    window.BOOT = boot;
    var s = document.createElement('script');
    s.src = 'admin.js?v=2';
    document.body.append(s);
  }
  var boot = { static: true, authed: false, ready: true, icons: ICONS, content: null };
  if (!token) { start(boot); return; }
  gh('/contents/data/content.json?ref=' + BRANCH).then(function (f) {
    sha = f.sha;
    boot.content = JSON.parse(b64decodeUtf8(f.content));
    boot.authed = true;
    start(boot);
  }).catch(function (e) {
    token = ''; boot.authed = false; boot.error = e.message;
    start(boot);
  });
})();
