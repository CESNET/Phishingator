<?php
  //
  // Soubor obsahující obecné funkce používané napříč zdrojovým kódem.
  //
  // @author Martin Šebela
  //

  /**
   * Automaticky importuje PHP třídy, které aplikace vyžaduje.
   *
   * @param string $className          Název třídy
   */
  function autoload_functions($className) {
    if (str_ends_with($className, 'Controller')) {
      require CORE_DOCUMENT_ROOT . '/' . CORE_DIR_CONTROLLERS . '/' . $className . '.php';
    }

    $modelClassLocation = CORE_DOCUMENT_ROOT . '/' . CORE_DIR_MODELS . '/' . $className . '.php';

    if (file_exists($modelClassLocation)) {
      require $modelClassLocation;
    }
  }


  /**
   * Upraví nastavení PHP dle požadované lokalizace.
   */
  function init_locales() {
    date_default_timezone_set(PHP_TIME_ZONE);
    mb_internal_encoding(PHP_MULTIBYTE_ENCODING);
  }


  /**
   * Nastaví zabezpečené HTTP hlavičky.
   */
  function init_http_security_headers() {
    header('X-Frame-Options: DENY');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: accelerometer=(); ambient-light-sensor=(); autoplay=(); battery=(); camera=(); display-capture=(); document-domain=(); encrypted-media=(); fullscreen=(); gamepad=(); geolocation=(); gyroscope=(); layout-animations=(); magnetometer=(); microphone=(); midi=(); payment=(); picture-in-picture=(); speaker-selection=(); usb=(); web-share=(); xr-spatial-tracking=()");
    header("Content-Security-Policy: default-src 'self'; upgrade-insecure-requests; script-src 'self' 'nonce-" . HTTP_HEADER_CSP_NONCE . "'; font-src 'self' data: fonts.gstatic.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; img-src 'self' data:");
  }


  /**
   * Nastaví a spustí zabezpečenou variantu SESSIONS (resp. COOKIES).
   */
  function init_secure_session_start() {
    ini_set('session.cookie_secure', true);
    ini_set('session.cookie_httponly', true);

    session_name('__Host-PHPSESSID');
    session_start();
  }


  /**
   * Rozdělí e-mailovou adresu na části a vrátí tu část, která je požadována.
   *
   * @param string $email              E-mail
   * @param null|string $requiredPart  Požadovaná část, která má být vrácena ("username" nebo "domain"),
   *                                   nebo NULL (tj. všechny části)
   * @return array|string|null         Požadovaná část (nebo části) e-mailu
   */
  function get_email_part($email, $requiredPart = null) {
    $returnPart = null;
    $symbol = '@';

    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    $emailParts = explode($symbol, $email);

    if (str_contains($email, $symbol)) {
      if ($requiredPart == 'username') {
        $returnPart = $emailParts[0] ?? null;
      }
      elseif ($requiredPart == 'domain') {
        $returnPart = $emailParts[1] ?? null;
      }
      elseif ($requiredPart == null) {
        $returnPart = $emailParts;
      }
    }

    return $returnPart;
  }


  /**
   * Vrátí název protokolu použitého v URL adrese.
   *
   * @param string $url                URL adresa
   * @return string                    Protokol
   */
  function get_protocol_from_url($url) {
    $protocol = '';

    if (!empty($url)) {
      $protocol = parse_url($url, PHP_URL_SCHEME);
    }

    return strtolower($protocol);
  }


  /**
   * Vrátí doménu včetně subdomén z konkrétní URL adresy.
   *
   * @param string $url                URL adresa
   * @return string                    Název domény včetně subdomén
   */
  function get_hostname_from_url($url) {
    $host = '';

    if (!empty($url)) {
      $host = parse_url($url, PHP_URL_HOST);
    }

    return $host;
  }


  /**
   * Vrátí název domény (second level domain) z konkrétní URL adresy.
   *
   * @param string $url                URL adresa
   * @return string|null               Název domény
   */
  function get_domain_from_url($url) {
    $domain = parse_url($url, PHP_URL_HOST);
    $secondLevelDomain = null;

    if ($domain != null) {
      $hostNames = explode('.', $domain);

      if (count($hostNames) >= 2) {
        $secondLevelDomain = $hostNames[count($hostNames) - 2] . '.' . $hostNames[count($hostNames) - 1];
      }
    }

    return $secondLevelDomain;
  }


  /**
   * Vrátí aktuální URL adresu včetně použitého protokolu (HTTP/HTTPS), cesty a případných parametrů.
   *
   * @return string                    Základní URL adresa
   */
  function get_current_url() {
    return (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  }


  /**
   * Ověří, zdali je předaný vstup číslo a pokud ano, vrátí jej.
   *
   * @param string $string             Testovaný vstup
   * @return bool|int                  Číslo nebo FALSE, pokud se nejedná o číslo
   */
  function get_number_from_get_string($string) {
    $number = false;

    if (isset($string) && is_numeric($string)) {
      $number = (int) $string;
    }

    return $number;
  }


  /**
   * Zformátuje číslo do českého formátu a případně ke kladnému číslu přidá znaménko "+".
   *
   * @param float|int $number          Číslo, které má být zformátováno
   * @param int $decimals              Počet desetinných míst (nepovinné)
   * @param bool $withPlusSign         TRUE, pokud má být před kladným číslem uvedeno i znaménko +, jinak FALSE (nepovinné)
   * @return string                    Zformátované číslo
   */
  function get_formatted_number($number, $decimals = 0, $withPlusSign = false) {
    $formattedNumber = number_format($number, $decimals, ',', ' ');

    if ($decimals > 0) {
      $formattedNumber = rtrim(rtrim($formattedNumber, '0'), ',');
    }

    if ($withPlusSign && $number > 0) {
      $formattedNumber = '+' . $formattedNumber;
    }

    return $formattedNumber;
  }


  /**
   * Rozdělí řetězec podle zadaného oddělovače, odstraní oddělovače
   * na jeho začátku a konci a vrátí výsledné položky jako pole.
   *
   * @param string $separator          Oddělovač položek
   * @param string $string             Řetězec s položkami
   * @return array                     Pole s jednotlivými položkami
   */
  function split_items($separator, $string) {
    $array = [];

    if (!empty($string)) {
      $array = explode($separator, trim($string, $separator));
    }

    return $array;
  }


  /**
   * Odstraní z řetězce symboly nových řádků a mezery na začátku a na konci řetězce.
   *
   * @param string $string             Upravovaný řetězec
   * @return string                    Řetězec zbavený symbolů nových řádků a mezer na začátku a konci řetězce
   */
  function remove_new_line_symbols($string) {
    return preg_replace('/\r\n|[\r\n]/', '', trim($string));
  }


  /**
   * Normalizuje konce řádků v textu na jednotný formát (\n).
   *
   * @param string $text             Vstupní text
   * @return string                  Text s normalizovanými konci řádků
   */
  function normalize_new_lines($text) {
    return str_replace(["\r\n", "\r"], "\n", $text);
  }


  /**
   * Nahradí v řetězci běžné mezery za pevné mezery.
   *
   * @param string $string             Upravovaný řetězec
   * @return string                    Upravený řetězec obsahující pevné mezery
   */
  function insert_nonbreaking_spaces($string) {
    return str_replace(' ', '&nbsp;', $string);
  }


  /**
   * Odstraní z řetězce všechny speciální znaky.
   *
   * @param string $string             Řetězec, ze kterého mají být odstraněny speciální znaky
   * @return string                    Řetězec bez speciálních symbolů
   */
  function remove_special_chars($string) {
    $string = str_replace(' ', '-', $string);
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

    return preg_replace('/-+/', '-', $string);
  }


  /**
   * Ověří, zdali IP adresa spadá do zadaného IP rozsahu.
   *
   * @param string $ip                 IP adresa
   * @param string $ipRange            IP rozsah
   * @return bool                      TRUE pokud IP adresa do IP rozsahu spadá, jinak FALSE
   */
  function is_ip_in_range($ip, $ipRange) {
    $inRange = false;

    list($subnet, $bits) = explode('/', $ipRange);

    if ($bits === null) {
      $bits = 32;
    }

    if ($bits >= 0 && $bits <= 32) {
      $ip = ip2long($ip);
      $subnet = ip2long($subnet);
      $mask = -1 << (32 - $bits);
      $subnet &= $mask;

      $inRange = ($ip & $mask) == $subnet;
    }

    return $inRange;
  }


  /**
   * Vrátí seznam domén organizace.
   *
   * @return string[]                  Seznam domén organizace
   */
  function get_organization_domains() {
    $domains = getenv('ORG_DOMAIN') ?: '';

    $domains = array_map('trim', explode(',', $domains));
    $domains = array_filter($domains, fn($d) => $d !== '');

    return $domains;
  }


  /**
   * Vrátí primární (první) doménu organizace.
   *
   * @return string|null               Primární doména nebo null, pokud žádná doména není definována
   */
  function get_organization_domain() {
    return get_organization_domains()[0] ?? null;
  }