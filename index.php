<?php
require __DIR__ . '/inc/lib.php';

$c = load_content();
$contacts = $c['contacts'] ?? [];
$texts = $c['texts'] ?? [];
$people = $c['people'] ?? [];
$services = $c['services'] ?? [];
$prices = $c['prices'] ?? [];
$steps = $c['steps'] ?? [];
$reviews = $c['reviews'] ?? [];
$faq = $c['faq'] ?? [];

$phone = $contacts['phone'] ?? '';
$tel = '+' . phone_digits($phone);
$address = $contacts['address'] ?? '';
$hours = $contacts['hours'] ?? '';
$insta = ltrim($contacts['instagram'] ?? '', '@');

$byId = [];
foreach ($people as $p) {
    $byId[$p['id']] = $p;
}
$face = fn(array $p): string => 'style="background-image:url(\'' . e($p['photo'] ?? '') . '\')"';
$firstName = fn(array $p): string => explode(' ', trim($p['name']))[0];

$waHello = wa_link($phone, 'Здравствуйте! Хочу записать ребёнка на консультацию.');
$waReview = wa_link($phone, 'Здравствуйте! Хочу оставить отзыв о центре «Наше Будущее»:');
$mapUrl = 'https://yandex.ru/maps/?text=' . rawurlencode($address);
$stepColors = ['peach', 'butter', 'mint', 'lilac'];

$siteData = ['wa' => phone_digits($phone), 'people' => []];
foreach ($people as $p) {
    $siteData['people'][$p['id']] = ['n' => $p['name'], 'r' => $p['about'] ?: $p['role'], 'p' => $p['poster'] ?? ''];
}
$schema = [
    '@context' => 'https://schema.org', '@type' => 'LocalBusiness',
    'name' => 'Наше Будущее — центр развития детей',
    'description' => $texts['hero_sub'] ?? '',
    'telephone' => $tel,
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressCountry' => 'RU'],
    'openingHours' => $hours,
    'sameAs' => $insta ? ['https://instagram.com/' . $insta] : [],
];
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Наше Будущее — центр развития детей в Гудермесе</title>
<meta name="description" content="Центр развития детей «Наше Будущее» в Гудермесе: логопед, дефектолог, детский психолог, нейропсихолог, АВА-терапия, сенсорная интеграция, остеопат, детский массаж. Запись: <?= e($phone) ?>.">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="Наше Будущее — центр развития детей в Гудермесе">
<meta property="og:description" content="<?= e($texts['hero_sub'] ?? '') ?>">
<?php
$siteBase = isset($_SERVER['HTTP_HOST'])
    ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/'
    : 'https://abdurahmanovzelimhan627-prog.github.io/nashe-budushee/';
?>
<meta property="og:site_name" content="Наше Будущее">
<meta property="og:url" content="<?= e($siteBase) ?>">
<meta property="og:image" content="<?= e($siteBase) ?>assets/share.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#FAFCFF">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%231D49B8'/%3E%3Cpath d='M12 4l2 5.400 5.700.4-4.400 3.600 1.500 5.600L12 15.800 7.200 19l1.500-5.600L4.300 9.800l5.700-.4z' fill='%23FFC93C'/%3E%3C/svg%3E">
<link rel="stylesheet" href="fonts/fonts.css">
<link rel="stylesheet" href="<?= asset('assets/site.css') ?>">
</head>
<body>
<?php readfile(__DIR__ . '/inc/sprite.html'); ?>

<header class="site-head">
  <div class="wrap head-row">
    <a class="logo" href="#top" aria-label="Наше Будущее — на главную"><img src="assets/logo.png" alt="Наше Будущее — центр развития детей" width="411" height="183"></a>
    <nav class="nav" aria-label="Разделы">
      <a href="#directions">Направления</a>
      <a href="#team">Специалисты</a>
      <a href="#prices">Цены</a>
      <a href="#reviews">Отзывы</a>
      <a href="#contacts">Контакты</a>
    </nav>
    <div class="head-cta">
      <a class="head-phone" href="tel:<?= e($tel) ?>"><?= e($phone) ?></a>
      <a class="btn btn-main" href="#contacts">Записаться</a>
      <button class="burger" id="burger" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="mobileMenu"><?= icon('menu') ?></button>
    </div>
  </div>
  <div class="mobile-menu" id="mobileMenu" hidden>
    <div class="wrap">
      <a href="#directions">Направления</a>
      <a href="#team">Специалисты</a>
      <a href="#prices">Цены</a>
      <a href="#reviews">Отзывы</a>
      <a href="#faq">Вопросы</a>
      <a href="#contacts">Контакты</a>
      <a class="btn btn-wa" href="<?= e($waHello) ?>" target="_blank" rel="noopener"><?= icon('wa', 'fill') ?>Написать в WhatsApp</a>
    </div>
  </div>
</header>

<main id="top">
  <section class="hero">
    <div class="wrap hero-grid">
      <div class="hero-text">
        <span class="where"><?= icon('pin') ?><?= e($address) ?></span>
        <h1>Большие возможности <span class="nw">для каждого</span> <span class="mark">ребёнка<svg viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M3 13c40-9 78-10 118-5s86 8 176-3"/></svg></span></h1>
        <p class="hero-sub"><?= e($texts['hero_sub'] ?? '') ?></p>
        <div class="hero-actions">
          <a class="btn btn-main" href="#contacts">Записаться на приём</a>
          <a class="btn btn-soft" href="#prices">Смотреть цены</a>
        </div>
<?php if (!empty($texts['hero_note'])): ?>
        <div class="note hand"><?= e($texts['hero_note']) ?><small>Так мы работаем в «Нашем Будущем»</small></div>
<?php endif; ?>
      </div>
      <div class="collage collage-video">
        <video class="hero-video" autoplay muted loop playsinline preload="metadata" poster="<?= asset('assets/hero.jpg') ?>" aria-label="Занятия с детьми в центре «Наше Будущее»"><source src="<?= asset('assets/hero.mp4') ?>" type="video/mp4"></video>
        <span class="star s1" aria-hidden="true"><svg viewBox="0 0 24 24"><use href="#i-star"/></svg></span>
        <span class="star s2" aria-hidden="true"><svg viewBox="0 0 24 24"><use href="#i-star"/></svg></span>
      </div>
    </div>
  </section>

  <section class="section" id="directions">
    <div class="wrap">
      <div class="head">
        <span class="kicker hand">ведём приём</span>
        <h2>С чем к нам приходят</h2>
        <p>Каждый специалист работает по индивидуальному плану, составленному под задачи и темп конкретного ребёнка.</p>
      </div>
      <div class="dirs">
<?php foreach ($services as $s): ?>
        <article class="dir">
          <span class="dir-ico"><?= icon($s['icon'] ?: 'star') ?></span>
          <h3><?= e($s['title']) ?></h3>
          <p><?= e($s['text']) ?></p>
          <div class="dir-foot"><div class="dir-price"><?= e($s['price']) ?> <span><?= e($s['price_note']) ?></span></div>
<?php $who = array_filter(array_map(fn($id) => $byId[$id] ?? null, $s['people'] ?? [])); ?>
<?php if ($who): ?>
            <div class="who"><?php foreach ($who as $p): ?><button type="button" data-person="<?= e($p['id']) ?>"><span class="face" <?= $face($p) ?>></span><?= e($p['name']) ?></button><?php endforeach; ?></div>
<?php endif; ?>
          </div>
        </article>
<?php endforeach; ?>
        <article class="dir dir-ask">
          <h3>Не знаете, к кому идти?</h3>
          <p>Расскажите, что вас беспокоит. Подскажем, с какого специалиста начать.</p>
          <a class="btn" href="#contacts">Спросить нас</a>
        </article>
      </div>
    </div>
  </section>

<?php if ($people): ?>
  <svg class="wave" viewBox="0 0 1200 44" preserveAspectRatio="none" aria-hidden="true"><path d="M0 44V22c120-26 240 18 380 6s230-34 400-18 280 30 420 4v30z" fill="#E3EEFF"/></svg>
  <section class="section tint" id="team">
    <div class="wrap">
      <div class="head">
        <span class="kicker hand">давайте знакомиться</span>
        <h2>Кто будет рядом с вашим ребёнком</h2>
        <p>Нажмите на карточку, чтобы посмотреть, с чем работает специалист и как строит занятия.</p>
      </div>
      <div class="team">
<?php foreach ($people as $p): ?>
        <button class="person" type="button" data-person="<?= e($p['id']) ?>"><span class="face" <?= $face($p) ?>></span><strong><?= e($p['name']) ?></strong><span><?= e($p['role']) ?></span><?php if ($p['exp']): ?><em><?= e($p['exp']) ?></em><?php endif; ?><i>Подробнее <?= icon('arrow') ?></i></button>
<?php endforeach; ?>
      </div>
    </div>
  </section>
  <svg class="wave" viewBox="0 0 1200 44" preserveAspectRatio="none" aria-hidden="true" style="transform:scaleY(-1)"><path d="M0 44V22c120-26 240 18 380 6s230-34 400-18 280 30 420 4v30z" fill="#E3EEFF"/></svg>
<?php endif; ?>

<?php if ($steps): ?>
  <section class="section" id="how">
    <div class="wrap">
      <div class="head">
        <span class="kicker hand">без спешки</span>
        <h2>Как проходит знакомство с центром</h2>
      </div>
      <ol class="steps">
<?php foreach ($steps as $i => $st): ?>
        <li style="background:var(--<?= $stepColors[$i % 4] ?>)"><h3><?= e($st['title']) ?></h3><p><?= e($st['text']) ?></p></li>
<?php endforeach; ?>
      </ol>
    </div>
  </section>
<?php endif; ?>

  <section class="section tint" id="prices">
    <div class="wrap">
      <div class="head">
        <span class="kicker hand">честно и заранее</span>
        <h2>Сколько стоят занятия</h2>
        <p>Абонемент выгоднее разового занятия и удобен для регулярных курсов.</p>
      </div>
      <div class="prices">
<?php foreach ($prices as $i => $t): ?>
        <div class="ticket<?= $i === 0 ? ' wide' : '' ?>">
          <h3><?= e($t['title']) ?></h3>
<?php if (!empty($t['tags'])): ?>
          <ul class="chips"><?php foreach ($t['tags'] as $tag): ?><li><?= e($tag) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
          <dl class="sums"><?php foreach ($t['rows'] ?? [] as $r): ?><div><dt><?= e($r['label']) ?></dt><dd><?= e($r['value']) ?></dd></div><?php endforeach; ?></dl>
        </div>
<?php endforeach; ?>
      </div>
<?php if (!empty($texts['price_note'])): ?>
      <p class="price-note"><?= icon('info') ?><span><?= e($texts['price_note']) ?></span></p>
<?php endif; ?>
    </div>
  </section>

<?php if ($reviews): ?>
  <section class="section warm" id="reviews">
    <div class="wrap">
      <div class="head">
        <span class="kicker hand">из переписки с родителями</span>
        <h2>Что говорят родители</h2>
        <p>Настоящие сообщения и слова благодарности от родителей наших воспитанников. Оставили как есть.</p>
      </div>
      <p class="swipe hand">листайте вбок →</p>
      <div class="reviews">
<?php foreach ($reviews as $r): ?>
        <figure class="review"><img src="<?= e($r['img']) ?>" alt="<?= e($r['alt'] ?: 'Отзыв родителя о центре «Наше Будущее»') ?>"></figure>
<?php endforeach; ?>
      </div>
      <div class="review-cta">
        <p><strong>Хотите поделиться впечатлением?</strong>Ваш отзыв помогает другим родителям и радует наших специалистов.</p>
        <a class="btn btn-wa" href="<?= e($waReview) ?>" target="_blank" rel="noopener"><?= icon('wa', 'fill') ?>Оставить отзыв</a>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($faq): ?>
  <section class="section" id="faq">
    <div class="wrap">
      <div class="head">
        <span class="kicker hand">спрашивают чаще всего</span>
        <h2>Вопросы родителей</h2>
      </div>
      <div class="faq">
<?php foreach ($faq as $i => $f): ?>
        <details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($f['q']) ?></summary><p><?= e($f['a']) ?></p></details>
<?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

  <section class="section tint" id="contacts">
    <div class="wrap ask">
      <div class="ask-side">
        <span class="kicker hand">ждём вас</span>
        <h2>Запишите ребёнка на первую консультацию</h2>
        <p>Свяжитесь любым удобным способом. Подберём специалиста и время под расписание вашего ребёнка.</p>
        <ul class="contacts">
          <li><span class="ico"><?= icon('phone') ?></span><div><small>Телефон и WhatsApp</small><a href="tel:<?= e($tel) ?>"><?= e($phone) ?></a></div></li>
<?php if ($insta): ?>
          <li><span class="ico"><?= icon('insta') ?></span><div><small>Instagram</small><a href="https://instagram.com/<?= e($insta) ?>" target="_blank" rel="noopener"><?= e($insta) ?></a></div></li>
<?php endif; ?>
          <li><span class="ico"><?= icon('pin') ?></span><div><small>Адрес</small><a href="<?= e($mapUrl) ?>" target="_blank" rel="noopener"><?= e($address) ?></a></div></li>
          <li><span class="ico"><?= icon('clock') ?></span><div><small>Часы работы</small><b><?= e($hours) ?></b></div></li>
        </ul>
      </div>
      <form class="form" id="requestForm" novalidate>
        <p class="form-step">Сначала отметьте согласия, после этого откроются поля заявки.</p>
        <label class="check"><input type="checkbox" id="reqPolicy"><span>Ознакомлен(а) с <a href="privacy.html" target="_blank">политикой конфиденциальности</a></span></label>
        <label class="check"><input type="checkbox" id="reqAgree"><span>Согласен(на) на <a href="consent.html" target="_blank">обработку персональных данных</a></span></label>
        <label class="check"><input type="checkbox" id="reqAds"><span>Согласен(на) получать <a href="ads-consent.html" target="_blank">сообщения об акциях и новостях центра</a>. Необязательно</span></label>
        <fieldset class="form-fields" id="reqFields" disabled>
        <div class="form-row">
          <div class="field"><label for="reqName">Ваше имя</label><input type="text" id="reqName" autocomplete="name" placeholder="Как к вам обращаться"></div>
          <div class="field"><label for="reqPhone">Телефон</label><input type="tel" id="reqPhone" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__"></div>
        </div>
        <div class="field"><label for="reqSpecialist">Специалист</label>
          <select id="reqSpecialist">
            <option value="">Подберите сами</option>
<?php foreach ($services as $s): ?>
            <option><?= e($s['title']) ?></option>
<?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label for="reqComment">Комментарий, если хотите</label><textarea id="reqComment" rows="3" placeholder="Возраст ребёнка, удобное время, ваш вопрос"></textarea></div>
        </fieldset>
        <p class="form-err" id="formErr" role="alert" hidden>Укажите, пожалуйста, имя и телефон.</p>
        <a class="btn btn-wa" id="waLink" href="#contacts" target="_blank" rel="noopener"><?= icon('wa', 'fill') ?>Отправить в WhatsApp</a>
        <p class="form-hint">Откроется WhatsApp с готовым сообщением, останется нажать «Отправить». Сайт ничего не сохраняет: ваши данные попадают только в это сообщение.</p>
      </form>
    </div>
  </section>
</main>

<footer class="foot">
  <div class="wrap foot-row">
    <img src="assets/logo.png" alt="Наше Будущее" width="411" height="183" loading="lazy">
    <span class="hand">Сегодня · Завтра · Всегда</span>
    <span>© <?= date('Y') ?> Центр «Наше Будущее», <?= e($address) ?></span>
  </div>
  <nav class="wrap" aria-label="Документы"><div class="foot-legal">
    <a href="privacy.html">Политика конфиденциальности</a>
    <a href="consent.html">Согласие на обработку персональных данных</a>
    <a href="ads-consent.html">Согласие на рекламные сообщения</a>
  </div></nav>
</footer>

<a class="wa-float" href="<?= e($waHello) ?>" target="_blank" rel="noopener" aria-label="Написать в WhatsApp"><?= icon('wa', 'fill') ?><span>Написать</span></a>

<dialog class="sheet" id="sheet" aria-labelledby="sheetName">
  <button class="sheet-close" id="sheetClose" type="button" aria-label="Закрыть">&times;</button>
  <img id="sheetImg" alt="" hidden>
  <div class="sheet-body">
    <strong id="sheetName"></strong>
    <span id="sheetRole"></span>
    <a class="btn btn-wa" id="sheetWa" href="#contacts" target="_blank" rel="noopener"><?= icon('wa', 'fill') ?>Записаться к специалисту</a>
  </div>
</dialog>

<script type="application/ld+json"><?= json_encode($schema, $jsonFlags) ?></script>
<script>window.SITE = <?= json_encode($siteData, $jsonFlags) ?>;</script>
<script src="<?= asset('assets/site.js') ?>"></script>
</body>
</html>
