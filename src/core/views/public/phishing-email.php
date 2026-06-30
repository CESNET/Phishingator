<div class="container mt-5">
  <div class="row pb-5">
    <div class="col-lg-12">
      <h2 class="mb-3"><?= t('educational_site.intro.title') ?></h2>
      <p class="par-intro"><?= t('educational_site.intro.par_1') ?></p>
      <?php if ($campaign_status != 'ended'): ?>
      <p class="par-intro"><?= t('educational_site.intro.par_2') ?></p>
      <?php endif; ?>
      <?php if ($volunteer): ?>
      <p class="par-intro"><?= t('educational_site.intro.volunteer.yes') ?></p>
      <?php else: ?>
      <p class="par-intro"><?= t('educational_site.intro.volunteer.no') ?></p>
      <?php endif; ?>
    </div>

    <div class="col-lg-4 mt-4 mt-lg-0 d-flex align-items-center justify-content-end">
      <a href="<?= WEB_BASE_URL ?>/co-je-to-phishing" target="_blank" class="btn btn-primary with-icon" role="button">
        <?= t('educational_site.intro.button') ?>
      </a>
    </div>
  </div>

  <div class="pt-5 pb-4 mb-3 border-top">
    <h3 class="mb-4"><?= t('educational_site.email.title') ?></h3>

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
            <div class="col-md-3"><strong><?= t('educational_site.email.headers.from') ?></strong></div>
            <div class="col"><?= $_email['sender'] ?></div>
          </div>
          <div class="row">
            <div class="col-md-3"><strong><?= t('educational_site.email.headers.subject') ?></strong></div>
            <div class="col"><?= $_email['subject'] ?></div>
          </div>
          <div class="row">
            <div class="col-md-3"><strong><?= t('educational_site.email.headers.to') ?></strong></div>
            <div class="col"><?= $email['recipient_email'] ?></div>
          </div>
          <div class="row">
            <div class="col-md-3"><strong><?= t('educational_site.email.headers.date') ?></strong></div>
            <div class="col"><?= $email['datetime_sent_formatted'] ?></div>
          </div>
          <hr>
          <div class="row">
            <div class="col">
              <?= $_email['body'] ?>
            </div>
          </div>
        </div>
        <div class="phishing-sign bg-danger" title="<?= t('educational_site.email.sign.title') ?>">
          <span data-feather="alert-triangle"></span>
          <?= t('educational_site.email.sign.name') ?>
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
            <span class="badge badge-pill badge-dark"><?= ($i + 1) ?>.&nbsp;<?= t('educational_site.signs.title') ?></span>
            <?= $indication['title'] ?>
          </h5>
          <p class="card-text"><?= nl2br($indication['description']) ?></p>
          <?php if (!empty($indication['expression'])): ?>
          <div class="clearfix">
            <button type="button" id="indication-<?= $indication['id_indication'] ?>-btn" class="btn btn-sm btn-info float-right mark-indication" data-indication="<?= $indication['id_indication'] ?>">
              <span data-feather="chevron-up"></span>
              <span><?= t('educational_site.signs.button') ?></span>
            </button>
          </div>
          <?php endif; ?>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <h3 class="mb-4"><?= t('educational_site.website.title') ?></h3>

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
              <?php if ($website['http']): ?><span data-feather="alert-triangle"></span>&nbsp;<?= t('educational_site.website.window.http') ?> | <?php else: ?><span data-feather="lock"></span><?php endif; ?>
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
        <img src="<?= $website['image_src'] ?>" alt="<?= t('educational_site.website.screenshot') ?>">
        <div class="screenshot-shadow"></div>
      </div>
      <div class="phishing-sign bg-danger" title="<?= t('educational_site.website.sign.title') ?>">
        <span data-feather="alert-triangle"></span>
        <?= t('educational_site.website.sign.name') ?>
      </div>
    </div>
  </div>

  <?php $i = 1; ?>
  <div class="card-columns pb-5 mb-5 border-bottom text-dark">
    <div id="indication-url-text" class="card bg-light cursor-pointer mark-indication" data-indication="url">
      <a href="#indication-url" class="anchor-link">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge badge-pill badge-dark"><?= $i++; ?>.&nbsp;<?= t('educational_site.signs.title') ?></span>
            <?= t('educational_site.website.signs.fraudulent_url.title') ?>
          </h5>
          <p class="card-text"><?= t('educational_site.website.signs.fraudulent_url.description') ?></p>
          <?php if (!empty($website['indication'])): ?>
          <p class="card-text"><?= $website['indication'] ?></p>
          <?php endif; ?>
          <div class="clearfix">
            <button type="button" id="indication-url-btn" class="btn btn-sm btn-info float-right">
              <span data-feather="chevron-up"></span>
              <span><?= t('educational_site.signs.button') ?></span>
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
            <span class="badge badge-pill badge-dark"><?= $i++; ?>.&nbsp;<?= t('educational_site.signs.title') ?></span>
            <?= t('educational_site.website.signs.http_url.title') ?>
          </h5>
          <p class="card-text">
            <?= t('educational_site.website.signs.http_url.description.par-1') ?>
          </p>
          <p class="card-text">
            <?= t('educational_site.website.signs.http_url.description.par-2') ?>
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
            <span class="badge badge-pill badge-dark"><?= $i++; ?>.&nbsp;<?= t('educational_site.signs.title') ?></span>
            <?= t('educational_site.website.signs.cloned.title') ?>
          </h5>
          <p class="card-text">
            <?= t('educational_site.website.signs.cloned.description.par-1') ?>
          </p>
          <p class="card-text">
            <?= t('educational_site.website.signs.cloned.description.par-2') ?>
          </p>
        </div>
      </a>
    </div>
    <?php endif; ?>
  </div>

  <footer>
    <div class="row">
      <div class="col">
        <h3><?= t('educational_site.footer.title') ?></h3>
        <ul class="mb-lg-5">
          <li><?= t('educational_site.footer.list.item_1') ?></li>
          <li><?= t('educational_site.footer.list.item_2') ?></li>
        </ul>
      </div>

      <div class="col-lg-3 footer-logo">
        <a href="https://www.cesnet.cz" target="_blank">
          <img src="/assets/images/logo-cesnet.svg" alt="Logo &ndash; CESNET, zájmové sdružení právnických osob">
        </a>
      </div>
    </div>
  </footer>
</div>