<?php
  /**
   * Třída sloužící k získávání informací o podvodných webových stránkách,
   * k přidávání nových podvodných webových stránek, k úpravě těch existujících
   * a k dalším souvisejícím operacím.
   *
   * @author Martin Šebela
   */
  class PhishingWebsiteModel extends FormModel {
    /**
     * @var string      Název podvodné webové stránky.
     */
    protected $name;

    /**
     * @var string      URL adresa podvodné webové stránky, na které bude dostupná.
     */
    protected $url;

    /**
     * @var int         ID šablony, která se na webové stránce zobrazí.
     */
    protected $idTemplate;

    /**
     * @var string      Název služby, ke které se uživatel na podvodné stránce přihlašuje.
     */
    protected $serviceName;

    /**
     * @var int         Proměnná uchovávající stav o tom, zdali je podvodná webová stránka dostupná pro ostatní
     *                  uživatele (1 pokud ano, jinak 0).
     */
    protected $active;


    /**
     * Načte a zpracuje předaná data.
     *
     * @param array $data              Vstupní data
     * @return void
     * @throws UserError               Výjimka platná tehdy, pokud CSRF token neodpovídá původně vygenerovanému.
     */
    public function load($data) {
      parent::load($data);

      $this->active = (empty($this->active) ? 0 : 1);
    }


    /**
     * Připraví novou podvodnou webovou stránku v závislosti na vyplněných datech a vrátí ji formou pole.
     *
     * @return array                   Pole obsahující data o podvodné stránce.
     */
    private function makePhishingWebsite() {
      return [
        'name' => $this->name,
        'url' => $this->makeWebsiteUrl($this->url),
        'id_template' => $this->idTemplate,
        'service_name' => $this->serviceName,
        'active' => $this->active
      ];
    }


    /**
     * Uloží do instance a zároveň vrátí (z databáze) informace o zvolené podvodné webové stránce.
     *
     * @param int $id                  ID podvodné stránky
     * @return array                   Pole obsahující informace o podvodné webové stránce.
     */
    public function getPhishingWebsite($id) {
      $this->dbRecordData = Database::querySingle('
              SELECT `id_website`, `name`, `url`, `service_name`, `id_template`, `active`
              FROM `phg_websites`
              WHERE `id_website` = ?
              AND `visible` = 1
      ', $id);

      if (!empty($this->dbRecordData)) {
        $this->dbRecordData['status'] = $this->getPhishingWebsiteStatus($this->dbRecordData['url']);

        $this->dbRecordData['active_campaigns_count'] = $this->getPhishingWebsiteCampaignCount($this->dbRecordData['id_website'], true);
        $this->dbRecordData['used_campaigns_count'] = $this->getPhishingWebsiteCampaignCount($this->dbRecordData['id_website']);
      }

      return $this->dbRecordData;
    }


    /**
     * Vrátí informace o konkrétní podvodné stránce na základě její URL adresy.
     *
     * @param string $url              URL adresa podvodné stránky (včetně protokolu)
     * @return mixed                   Pole obsahující informace o podvodné webové stránce.
     */
    public static function getPhishingWebsiteByUrl($url) {
      return Database::querySingle('
              SELECT `id_website`, `service_name`
              FROM `phg_websites`
              WHERE `url` = ?
              AND `visible` = 1
      ', [$url]);
    }


    /**
     * Vrátí informace o konkrétní podvodné stránce, a to na základě personalizované URL adresy
     * (tj. včetně identifikátoru uživatele).
     *
     * @param string $url              URL adresa podvodné strnáky
     * @param string $userUrl          Řetězec identifikující uživatele na podvodné stránce
     * @return mixed                   Data o podvodné stránce
     */
    public static function getPhishingWebsiteByPersonalizedUrl($url, $userUrl) {
      $previewToken = $_GET[ACT_PREVIEW] ?? '';

      // Úprava URL adresy do původní podoby (odstranění identifikátoru uživatele, odstranění parametru pro náhled).
      $url = str_replace(
        [$userUrl . USER_ID_WEBSITE_SUFFIX, $userUrl, '&' . ACT_PREVIEW . '=' . $previewToken],
        [VAR_RECIPIENT_URL, VAR_RECIPIENT_URL, ''],
        $url
      );

      $website = PhishingWebsiteModel::getPhishingWebsiteByUrl($url);

      // Pokud se nepodařilo zjistit informace o podvodné stránce a mělo by se jednat o stránku
      // běžící na protokolu HTTP, ale prohlížeč předal adresu s HTTPS (např. z důvodu HSTS),
      // načíst informace o stránce i pro variantu s HTTPS.
      $https = 'https://';

      if (!$website && mb_substr($url, 0, mb_strlen($https)) == $https) {
        $website = PhishingWebsiteModel::getPhishingWebsiteByUrl(
          str_replace($https, 'http://', $url)
        );
      }

      return $website;
    }


    /**
     * Vrátí seznam všech podvodných webových stránek z databáze.
     *
     * @return mixed                   Pole podvodných webových stránek a informace o každé z nich.
     */
    public static function getPhishingWebsites() {
      $result = Database::queryMulti('
              SELECT `id_website`, phg_websites.id_by_user, phg_websites.name, phg_websites.url, `active`, phg_websites.date_added,
              phg_websites_templates.name AS template_name,
              `username`, `email`,
              DATE_FORMAT(phg_websites.date_added, "%e. %c. %Y") AS date_added_formatted
              FROM `phg_websites`
              JOIN `phg_websites_templates`
              ON phg_websites.id_template = phg_websites_templates.id_website_template
              JOIN `phg_users`
              ON phg_websites.id_by_user = phg_users.id_user
              WHERE phg_websites.visible = 1
              ORDER BY `id_website` DESC
      ');

      foreach ($result as $key => $website) {
        $urlProtocol = get_protocol_from_url($website['url']);

        $result[$key]['url_protocol'] = $urlProtocol;
        $result[$key]['url_protocol_color'] = self::getColorURLProtocol($urlProtocol);
        $result[$key]['url'] = mb_substr($website['url'], mb_strlen($urlProtocol));

        $status = self::getPhishingWebsiteStatus($website['url']);
        $statusText = self::getPhishingWebsiteStatusText($status, $website['active']);

        $result[$key]['status'] = $status;
        $result[$key]['status_text'] = $statusText['text'];
        $result[$key]['status_color'] = $statusText['color'];
      }

      return UsersModel::setUsernamesByConfig($result);
    }


    /**
     * Vrátí z databáze seznam podvodných webových stránek, které jsou skutečně dostupné
     * pro ostatní uživatele (případně v konkrétním roce).
     *
     * @param int $year                Zkoumaný rok (nepovinné)
     * @return mixed                   Pole aktivních podvodných stránek a informace o každé z nich
     */
    public static function getActivePhishingWebsites($year = []) {
      $yearQuery = (!is_array($year) && is_numeric($year)) ? 'AND YEAR(`date_added`) = ?' : '';

      $result = Database::queryMulti('
              SELECT `id_website`, `id_template`, `name`, `url`
              FROM `phg_websites`
              WHERE `active` = 1
              AND `visible` = 1
              ' . $yearQuery . '
              ORDER BY `id_website` DESC
      ', $year);

      foreach ($result as $key => $website) {
        $result[$key]['status'] = self::getPhishingWebsiteStatus($website['url']);

        if ($result[$key]['status'] != 0) {
          unset($result[$key]);
        }
      }

      return $result;
    }


    /**
     * Vrátí počet kampaní, ke kterým je přiřazena daná podvodná webová stránka.
     *
     * @param int $id                   ID podvodné stránky
     * @param bool $onlyActiveCampaigns TRUE, pokud se mají uvažovat pouze aktivní (běžící) kampaně, jinak FALSE (výchozí)
     * @return int                      Počet kampaní
     */
    public static function getPhishingWebsiteCampaignCount($id, $onlyActiveCampaigns = false) {
      if ($onlyActiveCampaigns) {
        $query = 'AND TIMESTAMP(`date_active_since`, `time_active_since`) <= NOW() AND TIMESTAMP(`date_active_to`, `time_active_to`) >= NOW()';
      }
      else {
        $query = '';
      }

      return Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_campaigns`
              WHERE `id_website` = ?
              AND `visible` = 1
              ' . $query,
        $id);
    }


    /**
     * Vrátí informace o konkrétní šabloně podvodné webové stránky z databáze.
     *
     * @param int $id                  ID šablony podvodné stránky
     * @return mixed                   Pole s informacemi o šabloně
     */
    public static function getPhishingWebsiteTemplate($id) {
      return Database::querySingle('
              SELECT `server_dir`, `cloned`
              FROM `phg_websites_templates`
              WHERE `id_website_template` = ?
              AND `visible` = 1
      ', $id);
    }


    /**
     * Vrátí seznam všech šablon podvodných webových stránek z databáze.
     *
     * @return mixed                   Pole šablon podvodných stránek a informace o každé z nich
     */
    public function getPhishingWebsitesTemplates() {
      return Database::queryMulti('
              SELECT `id_website_template`, `name`
              FROM `phg_websites_templates`
              WHERE `visible` = 1
              ORDER BY `id_website_template` DESC
      ');
    }


    /**
     * Vloží do databáze novou podvodnou webovou stránku.
     *
     * @return void
     * @throws UserError
     */
    public function insertPhishingWebsite() {
      $phishingWebsite = $this->makePhishingWebsite();

      $phishingWebsite['id_by_user'] = PermissionsModel::getUserId();
      $phishingWebsite['date_added'] = date('Y-m-d H:i:s');

      $this->isURLUnique();

      PhishingWebsiteConfigs::generateNewConfig($this->url, $this->idTemplate);

      Database::insert($this->dbTableName, $phishingWebsite);

      Logger::info('New phishing website added.', $phishingWebsite);
    }


    /**
     * Upraví zvolenou podvodnou webovou stránku.
     *
     * @param int $id                  ID podvodné stránky
     * @return void
     * @throws UserError
     */
    public function updatePhishingWebsite($id) {
      $phishingWebsite = $this->makePhishingWebsite();

      $this->isURLUnique($id);

      $originalWebsite = new PhishingWebsiteModel();
      $originalWebsite = $originalWebsite->getPhishingWebsite($id);

      // Změny v konfiguračních souborech provádět pouze tehdy, pokud byla, nebo pokud má být podvodná stránka aktivní
      // (u neaktivních stránek nedojde ke zbytečnému uzamčení konfiguračního souboru pro stránky se stejnou doménou).
      if ($this->active !== 0 || $originalWebsite['active'] !== 0) {
        // Pokud byla změněna URL adresa (doména/path – resp. část před GET argumenty), šablona podvodné stránky
        // nebo jde o (de)aktivaci stránky, je nutné upravit konfigurační soubor. Pokud konfigurační soubor
        // neexistuje, je třeba ho dodatečně vytvořit.
        if (strtok($originalWebsite['url'], '?') !== strtok($this->url, '?') || $originalWebsite['id_template'] != $this->idTemplate || $originalWebsite['active'] !== $this->active || !file_exists(PhishingWebsiteConfigs::getConfigPath($this->url))) {
          // Pokud se má stránka aktivovat (přidat do konfiguračního souboru), je třeba do konfigurace předat ID šablony.
          $idTemplate = ($this->active !== 0) ? $this->idTemplate : null;

          // (Sub)doména se nijak nezměnila – přegeneruje se aktuální konfigurační soubor tak,
          // že se odstraní původní záznam editované stránky a přidá nový s aktuálním nastavením.
          if (get_hostname_from_url($originalWebsite['url']) == get_hostname_from_url($this->url)) {
            PhishingWebsiteConfigs::generateNewConfig($this->url, $idTemplate, $id);
          }
          else {
            // (Sub)doména se změnila – přegeneruje se aktuální konfigurační soubor
            // a z původního konfiguračního souboru je nutné záznam smazat.
            PhishingWebsiteConfigs::generateNewConfig($this->url, $idTemplate);
            PhishingWebsiteConfigs::generateNewConfig($originalWebsite['url'], null, $id);
          }
        }
      }

      Logger::info('Phishing website modified.', $phishingWebsite);

      Database::update(
        $this->dbTableName,
        $phishingWebsite,
        'WHERE `id_website` = ? AND `visible` = 1',
        $id
      );
    }


    /**
     * Odstraní (resp. deaktivuje) zvolenou podvodnou webovou stránku.
     *
     * @param int $id                  ID podvodné stránky
     * @return void
     * @throws UserError
     */
    public function deletePhishingWebsite($id) {
      if ($this->getPhishingWebsiteCampaignCount($id) != 0) {
        throw new UserError('Nelze smazat podvodnou stránku, která je svázána s nějakou existující kampaní.', MSG_ERROR);
      }

      $website = $this->getPhishingWebsite($id);

      // Pokud byla podvodná stránka aktivní, je třeba zároveň odstranit její záznam v konfiguračním souboru.
      if ($website['active'] === 1) {
        PhishingWebsiteConfigs::generateNewConfig($website['url'], null, $id);
      }

      $result = Database::update(
        'phg_websites',
        ['visible' => 0],
        'WHERE `id_website` = ? AND `visible` = 1',
        $id
      );

      if ($result == 0) {
        Logger::info('Attempt to delete a non-existent phishing website.', $id);

        throw new UserError('Záznam vybraný ke smazání neexistuje.', MSG_ERROR);
      }

      Logger::info('Phishing website deleted.', $id);
    }


    /**
     * Duplikuje podvodnou stránku.
     *
     * @param int $id                  ID podvodné stránky, která se má duplikovat
     */
    public function duplicatePhishingWebsite($id) {
      $website = $this->getPhishingWebsite($id);

      if (!empty($website)) {
        $duplicatedWebsite = [
          'id_by_user' => PermissionsModel::getUserId(),
          'id_template' => $this->dbRecordData['id_template'],
          'name' => $this->dbRecordData['name'] . ' (kopie)',
          'url' => $this->dbRecordData['url'] . ' (kopie)',
          'service_name' => $this->dbRecordData['service_name'],
          'date_added' => date('Y-m-d H:i:s'),
          'active' => 0
        ];

        Logger::info('Phishing website duplicated.', $duplicatedWebsite);

        Database::insert('phg_websites', $duplicatedWebsite);
      }
    }


    /**
     * Ověří, zdali v databázi existuje podvodná stránka se stejnou (sub)doménou.
     *
     * @param string $websiteUrl       URL podvodné stránky
     * @param string $websiteId        ID podvodné stránky
     * @return bool                    TRUE, pokud existuje podvodná stránka se stejnou doménou, jinak FALSE
     */
    private function existsWebsiteWithSameHostname($websiteUrl, $websiteId) {
      $websites = $this->getPhishingWebsites();
      $websiteHostname = get_hostname_from_url($websiteUrl);

      $found = false;

      foreach ($websites as $anotherWebsite) {
        if ($anotherWebsite['id_website'] == $websiteId) {
          continue;
        }

        if (get_hostname_from_url($anotherWebsite['url_protocol'] . $anotherWebsite['url']) == $websiteHostname) {
          $found = true;
        }
      }

      return $found;
    }


    /**
     * Vrátí screenshot podvodné stránky.
     *
     * @param int $id                  ID podvodné stránky
     * @return void
     */
    public function getPhishingWebsiteScreenshot($id) {
      $website = $this->getPhishingWebsite($id);

      if ($website != null) {
        $template = self::getPhishingWebsiteTemplate($website['id_template']);

        $name = $template['server_dir'] . '/' . PHISHING_WEBSITE_SCREENSHOT_FILENAME;
        $fp = fopen($name, 'rb');

        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($name));

        fpassthru($fp);
        exit();
      }
    }


    /**
     * Vrátí CSS třídu k odlišení protokolu podvodné webové stránky.
     *
     * @param string $urlProtocol      Protokol (HTTPS/HTTP) podvodné stránky
     * @return string                  Název CSS třídy
     */
    public static function getColorURLProtocol($urlProtocol) {
      $color = MSG_CSS_DEFAULT;

      if ($urlProtocol == 'https') {
        $color = MSG_CSS_SUCCESS;
      }
      elseif ($urlProtocol == 'http') {
        $color = MSG_CSS_ERROR;
      }

      return $color;
    }


    /**
     * Vygeneruje a vrátí URL s parametry pro zobrazení náhledu podvodné stránky.
     *
     * @param int $idWebsite           ID podvodné stránky
     * @param int $idUser              ID uživatele, který chce zobrazit náhled podvodné stránky
     * @return string|null             URL pro náhled podvodné stránky nebo NULL
     * @throws UserError
     */
    public static function getPreviewLink($idWebsite, $idUser) {
      $previewLink = null;

      // Zjištění, zdali není v databázi pro daného uživatele ještě nějaký aktivní token pro náhled podvodné stránky.
      $access = Database::querySingle(
        'SELECT `hash` FROM `phg_websites_preview` WHERE `id_website` = ? AND `id_user` = ? AND `date_active_to` > now()',
        [$idWebsite, $idUser]
      );

      // Zjištění informací o uživateli, který náhled podvodné stránky požaduje.
      $usersModel = new UsersModel();
      $user = $usersModel->getUser($idUser);

      // Zjištění informací o podvodné stránce, jejíž náhled má být zobrazen.
      $model = new PhishingWebsiteModel();
      $website = $model->getPhishingWebsite($idWebsite);

      // Pokud podvodná stránka existuje a je nastavena jako skutečně aktivní na webovém serveru.
      if ($user != null && $website != null && $website['active'] == 1 && PhishingWebsiteConfigs::isConfigReady($website['url'])) {
        // Pokud v databázi není žádný aktivní token pro přístup na náhled podvodné stránky, vygenerovat nový.
        if (empty($access)) {
          $activeSince = date('Y-m-d H:i:s');
          $activeTo = date('Y-m-d H:i:s', time() + PHISHING_WEBSITE_PREVIEW_TOKEN_VALIDITY_S);
          $token = bin2hex(openssl_random_pseudo_bytes(PHISHING_WEBSITE_PREVIEW_TOKEN_LENGTH_B));

          $access = [
            'id_website' => $idWebsite,
            'id_user' => $idUser,
            'hash' => $token,
            'date_active_since' => $activeSince,
            'date_active_to' => $activeTo
          ];

          Database::insert('phg_websites_preview', $access);
        }
        else {
          // Jinak použít již existující token.
          $token = $access['hash'];
        }

        $previewLink = self::makeWebsiteUrl($website['url'], WebsitePrependerModel::makeUserWebsiteId(PHISHING_WEBSITE_PREVIEW_ID, $user['url'])) . '&' . ACT_PREVIEW . '=' . $token;
      }

      return $previewLink;
    }


    /**
     * Vrátí aktuální stav podvodné domény.
     *
     * @param string $url              URL adresa podvodné stránky (bez protokolu)
     * @return int                     Aktuální stav
     * @throws UserError
     */
    public static function getPhishingWebsiteStatus($url) {
      $status = 0;

      $websiteHostname = get_hostname_from_url($url);
      $instanceHost = gethostbyname(get_hostname_from_url(WEB_URL));

      // U podvodné domény chybí v DNS záznam typu A směrovaný na Phishingator.
      if (gethostbyname($websiteHostname) != $instanceHost && gethostbyname(get_domain_from_url($url)) != $instanceHost) {
        $status = 1;
      }
      // Chybí záznam o doméně v proxy Phishingatoru.
      elseif (!self::isDomainRegisteredInProxy($websiteHostname)) {
        $status = 2;
      }
      // Probíhá změna konfigurace podvodné stránky na dané doméně.
      elseif (!PhishingWebsiteConfigs::isConfigReady($url)) {
        $status = 3;
      }

      return $status;
    }


    /**
     * Vrátí aktuální stav podvodné stránky v textové podobě.
     *
     * @param int $status              Aktuální stav
     * @param int $active              Stav nasazení podvodné stránky na webovém serveru
     * @return string[]                Aktuální stav v textové podobě společně s barvou
     */
    private static function getPhishingWebsiteStatusText($status, $active) {
      $message = [
        'text' => '',
        'color' => ''
      ];

      if ($active == 1) {
        if ($status == 0) {
          $message['text'] = 'aktivní';
          $message['color'] = MSG_CSS_SUCCESS;
        }
        elseif ($status == 1) {
          $message['text'] = 'chybné DNS';
          $message['color'] = MSG_CSS_ERROR;
        }
        elseif ($status == 2) {
          $message['text'] = 'nedokončené směrování';
          $message['color'] = MSG_CSS_WARNING;
        }
        elseif ($status == 3) {
          $message['text'] = 'probíhají změny';
          $message['color'] = MSG_CSS_DEFAULT;
        }
      }
      elseif ($active == 0) {
        $message['text'] = 'neaktivní';
        $message['color'] = MSG_CSS_DEFAULT;
      }

      return $message;
    }


    /**
     * Ověří, zdali je doména zaregistrována v proxy Phishingatoru.
     *
     * @param string $website          Doména (bez protokolu)
     * @return bool                    TRUE, pokud byla nalezena v konfiguraci proxy, jinak FALSE
     */
    public static function isDomainRegisteredInProxy($website) {
      $registered = false;

      $website = mb_strtolower($website);
      $proxyDomains = self::getDomainsRegisteredInProxy();

      if (!empty($proxyDomains)) {
        $domain = get_domain_from_url('https://' . $website);
        $subdomain = mb_substr($website, 0, -mb_strlen($domain) - 1);

        foreach ($proxyDomains as $proxyDomain) {
          // Existující konkrétní subdoména v konfiguraci proxy.
          if (!empty($subdomain) && $proxyDomain == $subdomain . '.' . $domain) {
            $registered = true;
          }
          // Existující konkrétní doména v konfiguraci proxy.
          elseif ($proxyDomain == $domain) {
            $registered = true;
          }

          if ($registered) {
            break;
          }
        }
      }

      return $registered;
    }


    /**
     * Vrátí pole všech (sub)domén, které jsou registrované v proxy Phishingatoru
     * a které mohou být použity jako podvodné domény ve phishingových kampaních.
     *
     * @return array|string[]          Pole podvodných domén a subdomén
     */
    public static function getDomainsRegisteredInProxy() {
      $proxyDomains = [];

      if (getenv('FRAUDULENT_HOSTS') !== null) {
        $proxyDomains = explode(',', mb_strtolower(getenv('FRAUDULENT_HOSTS')));
      }

      return $proxyDomains;
    }


    /**
     * Sestaví, případně upraví URL adresu na podvodnou stránku a nahradí
     * proměnnou pro identifikaci uživatele předaným obsahem.
     *
     * @param string $websiteUrl       URL adresa podvodné stránky včetně proměnné pro identifikaci uživatele
     * @param string $varReplace       Obsah, který má být nahrazen za proměnnou pro identifikaci uživatele (nepovinné)
     * @return string                  Sestavená URL adresa na podvodnou stránku
     */
    public static function makeWebsiteUrl($websiteUrl, $varReplace = null) {
      $websiteUrl = Controller::decodeHtmlEntities($websiteUrl);

      $hostname = get_hostname_from_url($websiteUrl);
      $afterHostnamePosition = mb_strpos($websiteUrl, $hostname) + mb_strlen($hostname);

      // Přidání lomítka za hostname.
      if (isset($websiteUrl[$afterHostnamePosition]) && $websiteUrl[$afterHostnamePosition] != '/') {
        $websiteUrl = substr_replace($websiteUrl, '/', $afterHostnamePosition, 0);
      }

      // Zjištění znaku, který je těsně před proměnnou.
      $varPosition = mb_strpos($websiteUrl, VAR_RECIPIENT_URL);
      $symbolBeforeVar = $websiteUrl[$varPosition - 1] ?? '';

      if (in_array($symbolBeforeVar, ['=', '?', '&'])) {
        $symbol = '';
      }
      elseif (parse_url($websiteUrl, PHP_URL_QUERY) == null || $symbolBeforeVar == '/') {
        $symbol = '?';
      }
      else {
        $symbol = '&';
      }

      // Případné doplnění chybějícího symbolu před proměnnou, aby se jednalo o GET parametr.
      if (!empty($symbol)) {
        $websiteUrl = substr_replace($websiteUrl, $symbol, $varPosition, 0);
      }

      // Případné přidání lomítka před první argument.
      $firstArgPosition = mb_strpos($websiteUrl, '?');

      if ($websiteUrl[$firstArgPosition - 1] != '/') {
        $websiteUrl = substr_replace($websiteUrl, '/', $firstArgPosition, 0);
      }

      // Nahrazení proměnné předaným obsahem (pokud byl předán jako argument).
      if ($varReplace != null) {
        $websiteUrl = str_replace(VAR_RECIPIENT_URL, $varReplace, $websiteUrl);
      }

      return Controller::escapeOutput($websiteUrl);
    }


    /**
     * Zkontroluje uživatelský vstup (atributy třídy), který se bude zapisovat do databáze.
     *
     * @throws UserError
     */
    public function validateData() {
      $this->isNameEmpty();
      $this->isNameTooLong();

      $this->isURLEmpty();
      $this->isURLTooLong();
      $this->isURLValid();
      $this->isURLValidDNSRecord();
      $this->isURLSubdomainValid();
      $this->isURLPathValid();
      $this->isURLArgValid();

      $this->containURLUserIdentifierVariable();
      $this->checkCountURLUserIdentifierVariable();
      $this->isURLUserIdentifierVariableValid();

      $this->isTemplateEmpty();
      $this->existTemplate();

      $this->isServiceNameTooLong();

      $this->isWebsiteDeactivable();
    }


    /**
     * Ověří, zdali byl vyplněn název podvodné webové stránky.
     *
     * @return void
     * @throws UserError
     */
    private function isNameEmpty() {
      if (empty($this->name)) {
        throw new UserError('Není vyplněn název podvodné stránky.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný název podvodné webové stránky není příliš dlouhý.
     *
     * @return void
     * @throws UserError
     */
    private function isNameTooLong() {
      if (mb_strlen($this->name) > $this->inputsMaxLengths['name']) {
        throw new UserError('Název podvodné stránky je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byla vyplněna URL adresa podvodné webové stránky.
     *
     * @return void
     * @throws UserError
     */
    private function isURLEmpty() {
      if (empty($this->url)) {
        throw new UserError('Není vyplněna URL adresa podvodné stránky.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaná URL adresa podvodné webové stránky není příliš dlouhá.
     *
     * @return void
     * @throws UserError
     */
    private function isURLTooLong() {
      if (mb_strlen($this->url) > $this->inputsMaxLengths['url']) {
        throw new UserError('URL adresa podvodné stránky je příliš dlouhá.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je zadaná URL adresa podvodné webové stránky validní.
     *
     * @return void
     * @throws UserError
     */
    private function isURLValid() {
      if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
        throw new UserError('URL adresa podvodné stránky je v nesprávném formátu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je u zadané (sub)domény v DNS nasměrován A záznam na instanci Phishingatoru.
     *
     * @return void
     * @throws UserError
     */
    private function isURLValidDNSRecord() {
      if (!in_array(mb_strtolower(get_domain_from_url($this->url)), PhishingWebsiteModel::getDomainsRegisteredInProxy()) &&
           gethostbyname(get_hostname_from_url($this->url)) != gethostbyname(get_hostname_from_url(WEB_URL)) && $this->active == 1) {
        throw new UserError('U zadané domény (popř. subdomény) není v DNS nasměrován záznam typu A na IP adresu serveru, kde běží Phishingator.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali subdoména zadaná cesta v URL adrese podvodné stránky neobsahuje nepovolené znaky.
     *
     * @return void
     * @throws UserError
     */
    private function isURLSubdomainValid() {
      $hostname = get_hostname_from_url($this->url);
      $domain = get_domain_from_url('https://' . $hostname);
      $subdomain = mb_substr($hostname, 0, -mb_strlen($domain) - 1);

      if (!empty($subdomain) && preg_match(PHISHING_WEBSITE_SUBDOMAINS_REGEXP, $subdomain, $matches)) {
        throw new UserError('Zadaná subdoména obsahuje nepovolený znak (' . implode(',', $matches) . ').', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaná cesta v URL adrese podvodné stránky neobsahuje nepovolené znaky a výrazy.
     *
     * @return void
     * @throws UserError
     */
    private function isURLPathValid() {
      $path = parse_url(str_replace(VAR_RECIPIENT_URL, 'var', $this->url), PHP_URL_PATH);

      if (!empty($path) && (str_contains($path, './') || str_contains($path, '//'))) {
        throw new UserError('Adresářová cesta v URL adrese podvodné stránky nemůže obsahovat výraz //, ../ a podobné pro přecházení mezi adresáři.', MSG_ERROR);
      }
      elseif (!empty($path) && preg_match('/[^A-Za-z0-9\/._-]/', $path, $matches)) {
        throw new UserError('Adresářová cesta v URL adrese podvodné stránky obsahuje nepovolený znak (' . implode(',', $matches) . ').', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali GET argumenty u zadané URL adresy podvodné stránky neobsahují nepovolené znaky a výrazy.
     *
     * @return void
     * @throws UserError
     */
    private function isURLArgValid() {
      $query = parse_url(str_replace(VAR_RECIPIENT_URL, 'var', $this->url), PHP_URL_QUERY);

      if (!empty($query) && preg_match('/[^A-Za-z0-9._?=&-]/', $query, $matches)) {
        throw new UserError('Argumenty v URL adrese podvodné stránky obsahují nepovolený znak (' . implode(',', $matches) . ').', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je v zadané URL adrese obsažena proměnná, která bude obsahovat identifikátor uživatele.
     *
     * @return void
     * @throws UserError
     */
    private function containURLUserIdentifierVariable() {
      if (mb_strpos($this->url, VAR_RECIPIENT_URL) === false) {
        throw new UserError(
          'V argumentech URL adresy chybí použití proměnné "' . VAR_RECIPIENT_URL . '" pro identifikaci uživatele.',
          MSG_ERROR
        );
      }
    }


    /**
     * Ověří, zdali se v zadané URL adrese vícekrát nevyskytuje proměnná, která bude obsahovat identifikátor uživatele.
     *
     * @return void
     * @throws UserError
     */
    private function checkCountURLUserIdentifierVariable() {
      if (substr_count($this->url, VAR_RECIPIENT_URL) > 1) {
        throw new UserError(
          'V argumentech URL adresy se nemůže proměnná "' . VAR_RECIPIENT_URL . '" vyskytovat vícekrát.',
          MSG_ERROR
        );
      }
    }


    /**
     * Ověří, zdali je v zadané URL adrese správně umístěna proměnná, která bude obsahovat identifikátor uživatele.
     *
     * @return void
     * @throws UserError
     */
    private function isURLUserIdentifierVariableValid() {
      $variablePosition = mb_strpos($this->url, VAR_RECIPIENT_URL) + mb_strlen(VAR_RECIPIENT_URL);

      if (isset($this->url[$variablePosition])) {
        if ($this->url[$variablePosition] != '&') {
          throw new UserError(
            'Za proměnnou "' . VAR_RECIPIENT_URL . '" může následovat pouze další argument (tj. znak &), nebo nic dalšího.',
            MSG_ERROR
          );
        }
      }
    }


    /**
     * Ověří, zdali zadanou URL adresu nepoužívá jiná podvodná webová stránka (tzn. jestli je unikátní).
     *
     * @param int $idWebsite           ID podvodné stránky
     * @return void
     * @throws UserError
     */
    private function isURLUnique($idWebsite = 0) {
      $websites = $this->getPhishingWebsites();

      foreach ($websites as $website) {
        if (strtok($this->makeWebsiteUrl($website['url_protocol'] . $website['url']), '?') == strtok($this->makeWebsiteUrl($this->url), '?') && $website['id_website'] != $idWebsite) {
          throw new UserError('Zadanou URL adresu již používá jiná podvodná stránka.', MSG_ERROR);
        }
      }
    }


    /**
     * Ověří, zdali byla vybrána šablona podvodné webové stránky.
     *
     * @return void
     * @throws UserError
     */
    private function isTemplateEmpty() {
      if (empty($this->idTemplate) || !is_numeric($this->idTemplate)) {
        throw new UserError('Není vybrána šablona stránky.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali vybraná šablona podvodné webové stránky existuje.
     *
     * @return void
     * @throws UserError
     */
    private function existTemplate() {
      if (empty($this->getPhishingWebsiteTemplate($this->idTemplate))) {
        throw new UserError('Vybraná šablona stránky neexistuje.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný název služby není příliš dlouhý.
     *
     * @return void
     * @throws UserError
     */
    private function isServiceNameTooLong() {
      if (mb_strlen($this->serviceName) > $this->inputsMaxLengths['service-name']) {
        throw new UserError('Název služby vypisovaný do šablony je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali lze zvolenou podvodnou stránku deaktivovat, aniž by byly ovlivněny některé existující kampaně.
     *
     * @return void
     * @throws UserError
     */
    private function isWebsiteDeactivable() {
      if ($this->active == 0 && !empty($this->dbRecordData['id_website']) && self::getPhishingWebsiteCampaignCount($this->dbRecordData['id_website'], true) != 0) {
        throw new UserError('Nelze deaktivovat podvodnou stránku, která je právě využívána v probíhající kampani.', MSG_ERROR);
      }
    }
  }
