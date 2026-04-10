<!DOCTYPE html>
<html lang="cs">
  <head>
    <meta charset="utf-8">

    <title><?= $html_title ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Phishingator je systém pro rozesílání cvičných phishingových zpráv, jehož cílem je naučit uživatele odhalovat reálný phishing.">
    <meta name="author" content="Martin Šebela">

    <link rel="icon" type="image/png" href="/favicon.png">

    <link href="/<?= CORE_DIR_EXTENSIONS ?>/bootstrap-4.6.2/bootstrap.min.css" rel="stylesheet">
    <link href="/style.css?20260410" rel="stylesheet">
  </head>

  <body>
    <header class="navbar navbar-expand navbar-dark sticky-top bg-dark flex-column flex-md-row p-0 shadow">
      <a href="/" class="navbar-brand col-md-4 col-lg-3 col-xl-2 mr-0">
        <span data-feather="anchor"></span>
        <h1 class="h6 d-inline">Phishingator <small>v<?= WEB_VERSION ?></small></h1>
      </a>

      <button class="navbar-toggler d-inline d-md-none border-0 text-white" type="button" data-toggle="collapse" data-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="navbar-nav-scroll text-light d-none d-xl-block">
        <ul class="navbar-nav bd-navbar-nav flex-row pl-4">
          <li class="nav-item">
            Systém pro rozesílání cvičných phishingových zpráv
          </li>
        </ul>
      </div>

      <ul class="navbar-nav bd-navbar-nav flex-sm-row px-3 ml-md-auto">
        <li class="nav-item">
          <p class="nav-link text-white">
            <span data-feather="user"></span>
            <?= get_email_part($_SESSION['user']['email'], 'username') ?>
          </p>
        </li>
        <?php if ($userPermission <= PERMISSION_TEST_MANAGER): ?>
        <li class="nav-item dropdown">
          <a href="#" id="switchRoleDropdown" class="nav-link dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="d-sm-none">Role</span>
            <span class="d-none d-sm-inline badge badge-<?= $userRoleColor ?>"><?= $userRoleText ?></span>
          </a>
          <div class="dropdown-menu" aria-labelledby="switchRoleDropdown">
            <?php foreach ($rolesMenu as $role): ?>
            <a href="/portal/?<?= ACT_SWITCH_ROLE . '=' . $role['url'] ?>" class="dropdown-item<?php if ($userRole == $role) echo ' active'; ?>">
              <?= $role['name'] ?>
            </a>
            <?php endforeach; ?>
          </div>
        </li>
        <?php endif; ?>
        <li class="nav-item ml-md-4">
          <a href="/portal/?logout" class="nav-link text-white">
            <span data-feather="log-out"></span>
            Odhlásit
          </a>
        </li>
      </ul>
    </header>

    <div class="container-fluid">
      <div class="row">
        <nav id="menu" class="col-16 col-md-4 col-lg-3 col-xl-2 d-md-block bg-light sidebar collapse">
          <div class="position-sticky pt-1 sidebar-sticky">
            <ul class="nav flex-column">
              <?php foreach ($menu as $name => $section): ?>
              <li class="nav-item">
                <a href="/portal/<?= $section['url'] ?? ''; ?>" class="nav-link<?php if ($currentSection == $section['url'] || (empty($currentSection) && empty($section['url']))) echo ' active'; ?>">
                  <span data-feather="<?= $section['icon'] ?>"></span>
                  <?= $name ?>
                  <?php if ($currentSection == $section['url']): ?><span class="sr-only">(vybráno)</span><?php endif; ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
              <span>Nápověda</span>
              <a href="https://github.com/CESNET/Phishingator/blob/main/MANUAL.md" target="_blank" aria-label="Nápověda">
                <span data-feather="help-circle" class="align-text-bottom"></span>
              </a>
            </h6>

            <ul class="nav flex-column mb-2">
              <li class="nav-item">
                <a href="/portal/help/about-phishing" class="nav-link<?php if ($currentSection == 'help' && $currentAction == 'about-phishing') echo ' active'; ?>">
                  <span class="align-text-bottom" data-feather="anchor"></span>
                  Jak poznat phishing
                </a>
              </li>
              <?php if ($userRole <= PERMISSION_TEST_MANAGER): ?>
              <li class="nav-item">
                <a href="/portal/help/principles-phishing" class="nav-link<?php if ($currentSection == 'help' && $currentAction == 'principles-phishing') echo ' active'; ?>">
                  <span class="align-text-bottom" data-feather="file-text"></span>
                  Jak připravit phishing
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </div>
        </nav>

        <main class="col-md-12 col-lg-13 col-xl-14 ml-sm-auto p-4">
          <?php $this->controller->displayHeader(); ?>

          <?php foreach ($messages as $message): ?>
          <div class="alert alert-<?= $message['type'] ?>" role="alert">
            <?= $message['message'] ?>
          </div>
          <?php endforeach; ?>

          <?php $this->controller->displayView(); ?>
        </main>

        <a href="#" class="d-none btn-top-page">
          <span data-feather="chevron-up"></span>
        </a>
      </div>
    </div>

    <script src="/<?= CORE_DIR_EXTENSIONS ?>/jquery-3.7.1.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
    <script src="/<?= CORE_DIR_EXTENSIONS ?>/bootstrap-4.6.2/bootstrap.bundle.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
    <script src="/<?= CORE_DIR_EXTENSIONS ?>/feather.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

    <script src="/portal/js.js?20260221" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
    <script src="/js.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
    <script nonce="<?= HTTP_HEADER_CSP_NONCE ?>">feather.replace();</script>

    <?php if ($userRole == PERMISSION_ADMIN && ($currentSection == 'phishing-emails' || $currentSection == 'phishing-websites') && ($currentAction == ACT_NEW || $currentAction == ACT_EDIT)): ?>
    <link href="/<?= CORE_DIR_EXTENSIONS ?>/jquery-highlighttextarea/jquery.highlighttextarea.min.css" rel="stylesheet">
    <script src="/<?= CORE_DIR_EXTENSIONS ?>/jquery-highlighttextarea/jquery.highlighttextarea.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

    <?php if ($currentSection == 'phishing-emails'): ?>
    <script nonce="<?= HTTP_HEADER_CSP_NONCE ?>">
      $('#phishing-email-sender-email').highlightTextarea({
        words: ['<?= VAR_RECIPIENT_EMAIL ?>']
      });
    </script>
    <?php elseif ($currentSection == 'phishing-websites'): ?>
    <script nonce="<?= HTTP_HEADER_CSP_NONCE ?>">
      $('#phishing-website-url').highlightTextarea({
        words: ['%id%']
      });
    </script>
    <?php endif; ?>

    <?php endif; ?>

    <footer class="pr-2 text-right text-light bg-dark">
      <small>&copy;&nbsp;Martin Šebela, 2019&ndash;2025</small>
    </footer>
  </body>
</html>