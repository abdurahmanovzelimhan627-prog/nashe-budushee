(function () {
  var SITE = window.SITE || { wa: '', people: {} };
  var WA = 'https://wa.me/' + SITE.wa + '?text=';
  var $ = function (id) { return document.getElementById(id); };

  // Мобильное меню
  var burger = $('burger'), menu = $('mobileMenu');
  function setMenu(open) { menu.hidden = !open; burger.setAttribute('aria-expanded', String(open)); }
  burger.addEventListener('click', function () { setMenu(menu.hidden); });
  menu.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });

  // Видео на первом экране: без автозапуска, если в системе отключена анимация
  var heroVideo = document.querySelector('.hero-video');
  if (heroVideo && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    heroVideo.removeAttribute('autoplay');
    heroVideo.pause();
    heroVideo.controls = true;
  }

  // Карточка специалиста
  var sheet = $('sheet');
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-person]');
    if (!b) return;
    var p = SITE.people[b.dataset.person];
    if (!p) return;
    var img = $('sheetImg');
    img.hidden = !p.p;
    if (p.p) { img.src = p.p; img.alt = p.n + '. ' + p.r; }
    $('sheetName').textContent = p.n;
    $('sheetRole').textContent = p.r;
    $('sheetWa').href = WA + encodeURIComponent('Здравствуйте! Хочу записаться к специалисту: ' + p.n + '.');
    if (sheet.showModal) { sheet.showModal(); sheet.scrollTop = 0; } else { sheet.setAttribute('open', ''); }
  });
  $('sheetClose').addEventListener('click', function () { sheet.close(); });
  sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });

  // Заявка в мессенджер
  var link = $('waLink'), err = $('formErr');
  function waText() {
    var lines = ['Здравствуйте! Хочу записаться в центр «Наше Будущее».'];
    var name = $('reqName').value.trim(), phone = $('reqPhone').value.trim();
    var spec = $('reqSpecialist').value, note = $('reqComment').value.trim();
    if (name) lines.push('Имя: ' + name);
    if (phone) lines.push('Телефон: ' + phone);
    if (spec) lines.push('Специалист: ' + spec);
    if (note) lines.push('Комментарий: ' + note);
    if ($('reqPolicy').checked) lines.push('С политикой конфиденциальности ознакомлен(а): да');
    if ($('reqAgree').checked) lines.push('Согласие на обработку персональных данных: да');
    if ($('reqAds').checked) lines.push('Согласие на сообщения об акциях и новостях: да');
    return encodeURIComponent(lines.join('\n'));
  }
  function refresh() {
    var ok = $('reqPolicy').checked && $('reqAgree').checked;
    $('reqFields').disabled = !ok;
    link.classList.toggle('is-off', !ok);
    link.setAttribute('aria-disabled', String(!ok));
    link.href = ok ? WA + waText() : '#contacts';
  }
  function fail(text, field) {
    err.textContent = text;
    err.hidden = false;
    field.focus();
  }
  $('requestForm').addEventListener('input', function () { refresh(); err.hidden = true; });
  $('requestForm').addEventListener('submit', function (e) { e.preventDefault(); });
  link.addEventListener('click', function (e) {
    if (!$('reqPolicy').checked || !$('reqAgree').checked) {
      e.preventDefault();
      fail('Отметьте две обязательные галочки вверху формы.', $('reqPolicy').checked ? $('reqAgree') : $('reqPolicy'));
      return;
    }
    if (!$('reqName').value.trim() || !$('reqPhone').value.trim()) {
      e.preventDefault();
      fail('Укажите, пожалуйста, имя и телефон.', $('reqName').value.trim() ? $('reqPhone') : $('reqName'));
      return;
    }
    refresh();
  });
  refresh();
})();
