<div class="container mt-5">
  <div class="row pb-5">
    <div class="col-lg-12">
      <h2 class="mb-3">Právě jste absolvovali <strong>cvičný phishing</strong></h2>
      <p class="par-intro">E-mail, který jste obdrželi, byl <strong>cvičný podvodný e-mail</strong> napodobující skutečný phishing. Jeho cílem bylo ukázat Vám <strong>typické znaky</strong>, podle kterých můžete skutečný podvodný e-mail poznat.</p>
      <?php if ($campaign_status != 'ended'): ?>
      <p class="par-intro">Kdyby se jednalo o&nbsp;skutečný phishing, <strong>útočníci</strong> by v&nbsp;tuto chvíli již velmi pravděpodobně <strong>znali údaje, které jste vyplnili na podvodné stránce</strong>.</p>
      <?php endif; ?>
      <?php if ($volunteer): ?>
      <p class="par-intro"><strong>Děkujeme</strong>, že máte zájem <strong>vzdělávat se</strong> v&nbsp;oblasti <strong>phishingu</strong>. Nastavení <strong>dobrovolného odebírání</strong> cvičných phishingů můžete změnit po <a href="/portal">přihlášení</a> do Phishingatoru.</p>
      <?php else: ?>
      <p class="par-intro">V&nbsp;rámci projektu <a href="<?= WEB_BASE_URL ?>">Phishingator</a> se můžete <a href="/portal">přihlásit</a> k&nbsp;dobrovolnému odebírání <strong>cvičných</strong> phishingů, které Vám názorně ukáží, <strong>na co se v&nbsp;e-mailu zaměřit</strong> a&nbsp;<strong>jak rozpoznat skutečný phishing a&nbsp;nepodlehnout mu</strong>.</p>
      <?php endif; ?>
    </div>

    <div class="col-lg-4 mt-4 mt-lg-0 d-flex align-items-center justify-content-end">
      <a href="<?= WEB_BASE_URL ?>/co-je-to-phishing" target="_blank" class="btn btn-primary with-icon" role="button">
        Více informací
      </a>
    </div>
  </div>

  <div class="pt-5 pb-4 mb-3 border-top">
    <h3 class="mb-4">Jak bylo možné <strong>phishing</strong> rozpoznat z&nbsp;<strong>e-mailu</strong></h3>

    <div class="window-wrapper">
      <div class="window">
        <div class="container header">
          <div class="row">
            <div class="col">
              <div class="traffic-lights-menu">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
            <div class="col">
              <div class="bar-menu">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        </div>
        <div class="container content">
          <div class="row">
            <div class="col-md-3"><strong>Od:</strong></div>
            <div class="col"><?= $_email['sender'] ?></div>
          </div>
          <div class="row">
            <div class="col-md-3"><strong>Předmět:</strong></div>
            <div class="col"><?= $_email['subject'] ?></div>
          </div>
          <div class="row">
            <div class="col-md-3"><strong>Komu:</strong></div>
            <div class="col"><?= $email['recipient_email'] ?></div>
          </div>
          <div class="row">
            <div class="col-md-3"><strong>Datum:</strong></div>
            <div class="col"><?= $email['datetime_sent_formatted'] ?></div>
          </div>
          <hr>
          <div class="row">
            <div class="col">
              <?= $_email['body'] ?>
            </div>
          </div>
        </div>
        <div class="phishing-sign bg-danger" title="Podvodný e-mail">
          <span data-feather="alert-triangle"></span>
          Phishing
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($email['indications'])): ?>
  <div class="card-columns pb-5 mb-5 border-bottom text-dark">
    <?php foreach ($email['indications'] as $i => $indication): ?>
    <div id="indication-<?= $indication['id_indication'] ?>-text" class="card bg-light cursor-pointer mark-indication" data-indication="<?= $indication['id_indication'] ?>">
      <a href="#indication-<?= $indication['id_indication'] ?>" class="anchor-link">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge badge-pill badge-dark"><?= ($i + 1) ?>.&nbsp;indicie</span>
            <?= $indication['title'] ?>
          </h5>
          <p class="card-text"><?= $indication['description'] ?></p>

          <div class="clearfix">
            <button type="button" id="indication-<?= $indication['id_indication'] ?>-btn" class="btn btn-sm btn-info float-right mark-indication" data-indication="<?= $indication['id_indication'] ?>">
              <span data-feather="chevron-up"></span>
              <span>Označit</span>
            </button>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <h3 class="mb-4">Jak bylo možné <strong>phishing</strong> rozpoznat <strong>na stránce</strong></h3>

  <div class="window-wrapper mb-5">
    <div class="window">
      <div class="container header">
        <div class="row">
          <div class="col col-2">
            <div class="traffic-lights-menu">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
          <div class="col title">
            <div class="url<?php if ($website['http']): ?> http<?php endif; ?>">
              <?php if ($website['http']): ?><span data-feather="alert-triangle"></span>&nbsp;Nezabezpečeno | <?php else: ?><span data-feather="lock"></span><?php endif; ?>
              <?= $website['url_before_domain'] ?><a href="#indication-url-text" id="indication-url" class="indication anchor-link mark-indication" data-indication="url"><span class="indication-link"><?= $website['domain'] ?></span><span class="icons"><span class="icon" data-feather="alert-triangle"></span><span class="icon" data-feather="arrow-up-left"></span></span></a><?= $website['url_after_domain'] ?>
            </div>
          </div>
          <div class="col col-1">
            <div class="bar-menu">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>
      </div>
      <div class="container content screenshot">
        <img src="<?= $website['image_src'] ?>" alt="Screenshot podvodné stránky">
        <div class="screenshot-shadow"></div>
      </div>
      <div class="phishing-sign bg-danger" title="Podvodný web">
        <span data-feather="alert-triangle"></span>
        Phishing
      </div>
    </div>
  </div>

  <?php $i = 1; ?>
  <div class="card-columns pb-5 mb-5 border-bottom text-dark">
    <div id="indication-url-text" class="card bg-light cursor-pointer mark-indication" data-indication="url">
      <a href="#indication-url" class="anchor-link">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge badge-pill badge-dark"><?= $i++; ?>.&nbsp;indicie</span>
            Špatná adresa stránky
          </h5>
          <p class="card-text">Snaha o&nbsp;napodobení adresy stránky &ndash; jedná se o&nbsp;typický trik útočníků, kdy se snaží využít překlepu nebo malé nesrovnalosti v&nbsp;adrese.
          <?php if (!empty($website['indication'])): ?>
          <p class="card-text"><?= $website['indication'] ?></p>
          <?php endif; ?>

          <div class="clearfix">
            <button type="button" id="indication-url-btn" class="btn btn-sm btn-info float-right">
              <span data-feather="chevron-up"></span>
              <span>Označit</span>
            </button>
          </div>
        </div>
      </a>
    </div>

    <?php if ($website['http']): ?>
    <div class="card bg-light cursor-pointer mark-indication">
      <a href="#indication-http" class="anchor-link">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge badge-pill badge-dark"><?= $i++; ?>.&nbsp;indicie</span>
            Nezabezpečeno
          </h5>
          <p class="card-text">
            Adresa stránky začíná zkratkou protokolu <abbr title="Hypertext Transfer Protocol" class="font-weight-bold">HTTP</abbr>, vždy je ale nutné pro přihlašování přistupovat na stránku se zabezpečeným protokolem se zkratkou <abbr title="Hypertext Transfer Protocol Secure" class="font-weight-bold">HTTPS</abbr> (<strong>S</strong> ve zkratce znamená <i>secure</i>, tedy zabezpečeno).
          </p>
          <p class="card-text">
            Vedle adresy může být také vidět popisek <strong class="d-inline-block">&bdquo;<span data-feather="alert-triangle"></span>&nbsp;Nezabezpečeno&ldquo;</strong> &ndash; to znamená, že všechno, co na webu děláte a&nbsp;zadáte, může kdokoliv sledovat.
          </p>
        </div>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($website['cloned']): ?>
    <div class="card bg-light cursor-pointer mark-indication">
      <a href="#indication-cloned" class="anchor-link">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge badge-pill badge-dark"><?= $i++; ?>.&nbsp;indicie</span>
            Zkopírovaný vzhled
          </h5>
          <p class="card-text">
            Útočníci jsou schopni vzhled stránek zkopírovat do posledního detailu. Podvodná (falešná) přihlašovací stránka se může chovat i&nbsp;<strong>vypadat úplně stejně</strong> jako ta správná (legitimní).
          </p>
          <p class="card-text">
            Vždy si tedy především zkontrolujte adresu, na které stránka je.
          </p>
        </div>
      </a>
    </div>
    <?php endif; ?>
  </div>

  <footer>
    <div class="row">
      <div class="col">
        <h3>Důležité informace</h3>
        <ul class="mb-lg-5">
          <li>Zadané heslo nebylo <strong>nikde uloženo</strong>. Doporučujeme ale, abyste si ho změnili.</li>
          <li>Praktické phishingové testy probíhají ve spolupráci s&nbsp;Vaším IT oddělením.</li>
        </ul>
      </div>

      <div class="col-lg-3 footer-logo">
        <a href="https://www.cesnet.cz" target="_blank">
          <img src="/img/logo-cesnet.svg" alt="Logo &ndash; CESNET, zájmové sdružení právnických osob">
        </a>
      </div>
    </div>
  </footer>
</div>