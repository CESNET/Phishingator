<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="utf-8">

  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Phishingator je systém pro rozesílání cvičných phishingových zpráv, jehož cílem je naučit uživatele odhalovat reálný phishing.">
  <meta name="keywords" content="phishingator, cvičný phishing, sociální inženýrství, kyberbezpečnost">
  <meta name="author" content="Martin Šebela">

  <meta property="og:url" content="<?= WEB_URL ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Phishingator">
  <meta property="og:description" content="Cvičný phishing na českých univerzitách">

  <title>Phishingator · Cvičný phishing na českých univerzitách</title>

  <link rel="icon" type="image/png" href="/favicon.png">

  <link rel="stylesheet" href="/<?= CORE_DIR_EXTENSIONS ?>/bootstrap-4.6.2/bootstrap.min.css">
  <link rel="stylesheet" href="/style-intro.css?20260410">
</head>
<body>
  <header>
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <a href="/" class="navbar-brand shifted">
          <h1>
            <span data-feather="anchor" class="logo"></span>
            Phishingator
          </h1>
        </a>
      </div>
    </nav>
  </header>

  <?php $this->controller->displayView(); ?>

  <script src="/<?= CORE_DIR_EXTENSIONS ?>/jquery-3.7.1.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
  <script src="/<?= CORE_DIR_EXTENSIONS ?>/bootstrap-4.6.2/bootstrap.bundle.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
  <script src="/js.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
  <script src="/<?= CORE_DIR_EXTENSIONS ?>/feather.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
  <script nonce="<?= HTTP_HEADER_CSP_NONCE ?>">
    feather.replace()
  </script>
</body>
</html>