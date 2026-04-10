<?php
  /**
   * Třída sloužící k získávání informací o podvodných e-mailech,
   * k přidávání nových podvodných e-mailů, k úpravě těch existujících, k jejich personalizaci
   * a k dalším souvisejícím operacím.
   *
   * @author Martin Šebela
   */
  class PhishingEmailModel extends FormModel {
    /**
     * @var string      Název podvodného e-mailu.
     */
    protected $name;

    /**
     * @var int         Proměnná uchovávající informaci o tom, zdali je podvodný e-mail skryt pro správce testů.
     */
    protected $hidden;

    /**
     * @var string      Jméno odesílatele podvodného e-mailu.
     */
    protected $senderName;

    /**
     * @var string      E-mail odesílatele podvodného e-mailu.
     */
    protected $senderEmail;

    /**
     * @var string      Předmět podvodného e-mailu.
     */
    protected $subject;

    /**
     * @var string      Tělo podvodného e-mailu.
     */
    protected $body;

    /**
     * @var int         Proměnná uchovávající informaci o tom, zdali se jedná o e-mail v HTML formátu.
     */
    protected $html;


    /**
     * Načte a zpracuje předaná data.
     *
     * @param array $data              Vstupní data
     * @throws UserError               Výjimka platná tehdy, pokud CSRF token neodpovídá původně vygenerovanému.
     */
    public function load($data) {
      parent::load($data);

      $this->html = (empty($this->html) ? 0 : 1);
      $this->hidden = (empty($this->hidden) ? 0 : 1);
    }


    /**
     * Připraví nový podvodný e-mail v závislosti na vyplněných datech a vrátí ho formou pole.
     *
     * @return array                   Pole obsahující data o podvodném e-mailu.
     */
    public function makePhishingEmail() {
      return [
        'name' => $this->name,
        'sender_name' => $this->senderName,
        'sender_email' => $this->senderEmail,
        'subject' => $this->subject,
        'body' => $this->body,
        'html' => $this->html,
        'hidden' => $this->hidden
      ];
    }


    /**
     * Uloží do instance a zároveň vrátí (z databáze) informace o zvoleném podvodném e-mailu.
     *
     * @param int $id                  ID e-mailu
     * @return array                   Pole obsahující informace o podvodném e-mailu.
     */
    public function getPhishingEmail($id) {
      $whereFilter = (PermissionsModel::getUserRole() == PERMISSION_TEST_MANAGER) ? 'AND `hidden` = 0' : '';

      $this->dbRecordData = Database::querySingle('
              SELECT `id_email`, `name`, `sender_name`, `sender_email`, `subject`, `body`, `html`, `hidden`
              FROM `phg_emails`
              WHERE `id_email` = ?
              AND `visible` = 1
              ' . $whereFilter
      , $id);

      return $this->dbRecordData;
    }


    /**
     * Vrátí seznam všech podvodných e-mailů z databáze.
     *
     * @return mixed                   Pole e-mailů a informace o každém z nich.
     */
    public static function getPhishingEmails() {
      $whereFilter = (PermissionsModel::getUserRole() == PERMISSION_TEST_MANAGER) ? 'AND `hidden` = 0' : '';

      $records = Database::queryMulti('
              SELECT `id_email`, phg_emails.id_by_user, `name`, `subject`, phg_emails.date_added, `html`, `hidden`,
              `username`, `email`,
              DATE_FORMAT(phg_emails.date_added, "%e. %c. %Y") AS date_added_formatted
              FROM `phg_emails`
              JOIN `phg_users`
              ON phg_emails.id_by_user = phg_users.id_user
              WHERE phg_emails.visible = 1
              ' . $whereFilter . '
              ORDER BY `id_email` DESC
      ');

      return UsersModel::setUsernamesByConfig($records);
    }


    /**
     * Vrátí počet kampaní, ke kterým je přiřazený daný podvodný e-mail.
     *
     * @param int $id                  ID e-mailu
     * @return int                     Počet kampaní
     */
    private function getCountOfUsePhishingEmail($id) {
      return Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_campaigns`
              WHERE `id_email` = ?
      ', $id);
    }


    /**
     * Vrátí počet kampaní, ve kterých je použit podvodný e-mail uživatelem, jehož oprávnění je správců testů.
     *
     * @param int $id                  ID e-mailu
     * @return int                     Počet kampaní
     */
    private function getCountOfUsePhishingEmailByTestManager($id) {
      return Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_campaigns`
              JOIN `phg_users`
              ON phg_campaigns.id_by_user = phg_users.id_user
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              WHERE phg_campaigns.id_email = ?
              AND phg_campaigns.visible = 1
              AND phg_users_roles.value = ?
      ', [$id, PERMISSION_TEST_MANAGER]);
    }


    /**
     * Vloží do databáze nový podvodný e-mail a v případě úspěchu vrátí jeho ID.
     *
     * @return int                     ID nově vloženého podvodného e-mailu
     */
    public function insertPhishingEmail() {
      $email = $this->makePhishingEmail();

      $email['id_by_user'] = $_SESSION['user']['id'];
      $email['date_added'] = date('Y-m-d H:i:s');

      Logger::info('New phishing email added.', $email);

      Database::insert($this->dbTableName, $email);

      return Database::getLastInsertId();
    }


    /**
     * Upraví zvolený e-mail.
     *
     * @param int $id                  ID e-mailu
     * @throws UserError
     */
    public function updatePhishingEmail($id) {
      $email = $this->makePhishingEmail();

      $this->isPhishingEmailUsedByTestManager($id);

      Logger::info('Phishing email modified.', $email);

      Database::update(
        $this->dbTableName,
        $email,
        'WHERE `id_email` = ? AND `visible` = 1',
        $id
      );
    }


    /**
     * Odstraní (resp. deaktivuje) podvodný e-mail z databáze.
     *
     * @param int $id                  ID podvodného e-mailu
     * @throws UserError
     */
    public function deletePhishingEmail($id) {
      if ($this->getCountOfUsePhishingEmail($id) != 0) {
        throw new UserError(
          'Nelze smazat podvodný e-mail, který je svázán s nějakou existující kampaní.', MSG_ERROR
        );
      }

      $result = Database::update(
        'phg_emails',
        ['visible' => 0],
        'WHERE `id_email` = ? AND `visible` = 1',
        $id
      );

      if ($result == 0) {
        Logger::warning('Attempt to delete a non-existent phishing email.', $id);

        throw new UserError('Záznam vybraný ke smazání neexistuje.', MSG_ERROR);
      }

      Logger::info('Phishing email deleted.', $id);
    }


    /**
     * Duplikuje podvodný e-mail včetně souvisejících indicií.
     *
     * @param int $id                  ID podvodného e-mailu, který se má duplikovat
     */
    public function duplicatePhishingEmail($id) {
      $email = $this->getPhishingEmail($id);

      $incicationModel = new EmailIndicationsModel();
      $indications = EmailIndicationsModel::getEmailIndications($id);

      if (!empty($email)) {
        $duplicatedEmail = [
          'id_by_user' => PermissionsModel::getUserId(),
          'name' => $this->dbRecordData['name'] . ' (kopie)',
          'sender_name' => $this->dbRecordData['sender_name'],
          'sender_email' => $this->dbRecordData['sender_email'],
          'subject' => $this->dbRecordData['subject'],
          'body' => $this->dbRecordData['body'],
          'html' => $this->dbRecordData['html'],
          'hidden' => $this->dbRecordData['hidden'],
          'date_added' => date('Y-m-d H:i:s')
        ];

        Logger::info('Phishing email duplicated.', $duplicatedEmail);

        Database::insert('phg_emails', $duplicatedEmail);

        $idEmail = Database::getLastInsertId();

        // Duplikace i všech původních indicií.
        if ($idEmail !== false) {
          foreach ($indications as $indication) {
            $incicationModel->duplicateEmailIndication($indication['id_indication'], $idEmail);
          }
        }
      }
    }


    /**
     * Vrátí pole proměnných, které se mohou vyskytovat v těle e-mailu.
     *
     * @return array                   Pole proměnných
     */
    public static function getEmailBodyVariables() {
      return [
        VAR_RECIPIENT_USERNAME, VAR_RECIPIENT_EMAIL,
        VAR_RECIPIENT_FIRSTNAME, VAR_RECIPIENT_SURNAME, VAR_RECIPIENT_FULLNAME,
        VAR_DATE_CZ, VAR_DATE_EN,
        VAR_URL
      ];
    }


    /**
     * Vrátí pole proměnných, které se mohou vyskytovat v hlavičkách e-mailu.
     *
     * @return array                   Pole proměnných
     */
    public static function getEmailHeaderVariables() {
      return [
        VAR_INDICATION_SENDER_NAME, VAR_INDICATION_SENDER_EMAIL,
        VAR_INDICATION_SUBJECT
      ];
    }


    /**
     * Vrátí seznam domén, ze kterých mohou pocházet e-maily příjemců.
     *
     * @param bool $returnString       TRUE, pokud má být seznam vrácen jako řetězec (nepovinné)
     * @return string[]|string         Pole (výchozí) nebo řetězec se seznamem povolených domén
     */
    public static function getAllowedEmailDomains($returnString = false) {
      $domains = explode(',', strtolower(EMAILS_ALLOWED_DOMAINS));
      $domains = array_map('trim', $domains);

      if ($returnString) {
        $domainsString = '';
        $separator = '/';

        foreach ($domains as $domain) {
          $domainsString .= '<code>' . Controller::escapeOutput($domain) . '</code>' . $separator;
        }

        $domains = trim($domainsString, $separator);
      }

      return $domains;
    }


    /**
     * Vrátí informaci, zdali zadaný e-mail vede na některou z povolených (sub)domén.
     *
     * @param string $email            E-mail, který se kontroluje
     * @return bool                    TRUE pokud je e-mail z povolené (sub)domény, jinak FALSE
     */
    public static function isEmailInAllowedDomains($email) {
      $allowedDomains = self::getAllowedEmailDomains();

      $email = strtolower($email);
      $emailParts = explode('@', $email, 2);
      $emailDomain = $emailParts[1] ?? '';

      $allowed = false;

      foreach ($allowedDomains as $allowedDomain) {
        // Pokud jsou u domény povoleny i všechny její subdomény.
        if (str_starts_with($allowedDomain, '*.')) {
          $domain = substr($allowedDomain, 2);

          $match = $emailDomain === $domain || str_ends_with($emailDomain, '.' . $domain);
        }
        else {
          $match = $emailDomain === $allowedDomain;
        }

        if ($match) {
          $allowed = true;
          break;
        }
      }

      return $allowed;
    }


    /**
     * Vrátí zformátovanou hlavičku jména odesílatele e-mailu v závislosti
     * na tom, která data byla o odesílateli vyplněna.
     *
     * @param string $senderEmail      E-mail odesílatele
     * @param string|bool $senderName  Jméno odesílatele e-mailu nebo FALSE (výchozí), pokud není uvedeno
     * @return string                  Zformátovaná hlavička odesílatele e-mailu
     */
    private static function formatEmailSenderNameHeader($senderEmail, $senderName = false) {
      return (($senderName) ? $senderName . ' &lt;' . $senderEmail . '&gt;' : $senderEmail);
    }


    /**
     * Personalizuje odesílatele e-mailu tak, aby byl jako odesílatel
     * uveden e-mail příjemce (pokud tomu tak má být).
     *
     * @param string $senderEmail      E-mail odesílatele
     * @param string $userEmail        E-mail příjemce
     * @return string                  Personalizovaný e-mail odesílatele
     */
    private static function personalizeEmailSender($senderEmail, $userEmail) {
      return str_replace(VAR_RECIPIENT_EMAIL, $userEmail, $senderEmail);
    }


    /**
     * Vrátí personalizované tělo e-mailu vůči zvolenému uživateli.
     *
     * @param string $body                   Tělo e-mailu
     * @param array|null $user               Pole s daty o uživateli, vůči kterému bude tělo e-mailu personalizováno (nepovinné)
     * @param string|null $url               URL podvodné stránky (nepovinné)
     * @param int|null $idCampaign           ID kampaně (nepovinné)
     * @param string|null $datetimeEmailSent Datum a čas, kdy došlo k odeslání e-mailu pro doplnění proměnné (nepovinné)
     * @return string                        Personalizované tělo e-mailu
     */
    private static function personalizeEmailBody($body, $user = null, $url = null, $idCampaign = null, $datetimeEmailSent = null) {
      $variables = [];

      // Nahrazení proměnné pro odkaz na podvodnou stránku za personalizovaný odkaz.
      if (!empty($url) && $user !== null && !empty($idCampaign)) {
        $variables[VAR_URL] = PhishingWebsiteModel::makeWebsiteUrl($url, WebsitePrependerModel::makeUserWebsiteId($idCampaign, $user['url']));
      }
      else {
        $variables[VAR_URL] = VAR_URL_PREVIEW;
      }

      // Nahrazení proměnných s datem.
      if ($datetimeEmailSent !== null) {
        $timestamp = strtotime($datetimeEmailSent);
      }
      else {
        $timestamp = time();
      }

      $variables[VAR_DATE_CZ] = date(VAR_DATE_CZ_FORMAT, $timestamp);
      $variables[VAR_DATE_EN] = date(VAR_DATE_EN_FORMAT, $timestamp);

      // Nahrazení proměnných týkajících se uživatele.
      if ($user !== null) {
        $variables = array_merge($variables, [
          VAR_RECIPIENT_USERNAME  => $user['username'] ?? '',
          VAR_RECIPIENT_EMAIL     => $user['email'] ?? '',
          VAR_RECIPIENT_FIRSTNAME => $user['firstname'] ?? '',
          VAR_RECIPIENT_SURNAME   => $user['surname'] ?? '',
          VAR_RECIPIENT_FULLNAME  => $user['fullname'] ?? '',
        ]);
      }

      return str_replace(array_keys($variables), array_values($variables), $body);
    }


    /**
     * Vrátí tělo e-mailu zprocované pro výpis včetně sanitizace a případného vyznačení indicií, promměných a odkazů.
     *
     * @param string $body             Tělo e-mailu
     * @param array $indications       Pole obsahující všechny indicie, které mají být v těle e-mailu zvýrazněny
     * @param bool $highlightVariables TRUE, pokud má dojít ke zvýraznění proměnných, jinak FALSE (výchozí)
     * @param bool $isHtml             TRUE, pokud se jedná o HTML e-mail, jinak FALSE
     * @return false|string
     * @throws DOMException
     */
    private static function renderEmailBodyHtml($body, $indications = [], $highlightVariables = false, $isHtml = false) {
      $body = normalize_new_lines($body);

      // Pokud je tělo e-mailu v plain textu, převést ho do HTML pro následné zpracování.
      if (!$isHtml) {
        $body = nl2br(Controller::escapeOutput($body));
      }

      return EmailDomProcessor::processEmailBodyHtml($body, $indications, $highlightVariables);
    }


    /**
     * Vrátí normalizované tělo e-mailu zpracované pro výpis v plain textu.
     *
     * @param string $body             Tělo e-mailu
     * @return string                  Normalizované tělo e-mailu v plain textu
     */
    private static function renderEmailBodyPlain($body) {
      $body = normalize_new_lines($body);

      $body = strip_tags($body);
      $body = Controller::decodeHtmlEntities($body);

      return trim($body);
    }


    /**
     * Připraví a vrátí personalizovaný e-mail (včetně hlaviček) a případně vyznačí indicie, proměnné a odkazy.
     *
     * @param array $email                 Pole s daty o e-mailu
     * @param array|null $user             Pole s daty o uživateli, nebo NULL, pokud se nemají informace o uživateli do e-mailu vkládat
     * @param bool|array $applyIndications TRUE (výchozí), pokud mají být k e-mailu zahrnuty i indicie pro jeho rozpoznání, jinak FALSE
     * @param bool $highlightVariables     TRUE, pokud se mají v těle e-mailu vyznačit proměnné, jinak FALSE (výchozí)
     * @param bool $htmlOutput             TRUE (výchozí), pokud je cílem e-mail vypsat do HTML, jinak FALSE
     * @return array                       Personalizovaný e-mail
     * @throws DOMException
     */
    public static function preparePhishingEmail($email, $user, $applyIndications = true, $highlightVariables = false, $htmlOutput = true) {
      $indications = [];

      // Personalizace hlaviček e-mailu.
      $email = self::personalizeEmailHeader($email, $user);

      // Získání indicií, pokud mají být v e-mailu zvýrazněny.
      if ($applyIndications) {
        $indications = EmailIndicationsModel::getEmailIndications($email['id_email']);
        $email['indications'] = $indications;

        $email = EmailDomProcessor::applyHeaderIndications($email, $indications);
      }

      // Dodatečné ošetření hlaviček e-mailu pro případ, že některé z nich neprošly DOM sanitizací při zvýrazňování indicií.
      $email = self::escapeEmailHeaders($email);
      unset($email['_dom_processed_headers']);

      // Formátování hlavičky jména odesílatele podle toho, zdali bylo jméno vyplněno.
      $email['sender'] = self::formatEmailSenderNameHeader($email['sender_email'], $email['sender_name']);

      // Personalizace těla e-mailu.
      $email['body'] = self::processEmailBody($email, $user, $indications, $highlightVariables, $htmlOutput);

      return $email;
    }


    /**
     * Vrátí personalizované hlavičky e-mailu.
     *
     * @param array $email             Pole s daty o e-mailu
     * @param array $user              Pole s daty o uživateli, který bude příjemcem e-mailu
     * @return array                   Pole s daty o e-mailu s personalizovanými hlavičkami
     */
    private static function personalizeEmailHeader($email, $user) {
      if (isset($user['email'])) {
        // Nahrazení proměnné odesílatele za e-mail uživatele (pokud byla proměnná použita).
        $email['sender_email'] = self::personalizeEmailSender($email['sender_email'], $user['email']);

        // Vložení příjemce.
        $email['recipient_email'] = $user['email'];
      }

      return $email;
    }


    /**
     * Vrátí e-mail, ve které escapuje všechny e-mailové hlavičky.
     *
     * @param array $email             Pole s daty o e-mailu
     * @return array                   Pole s daty o e-mailu s escapovanými hlavičkami
     */
    private static function escapeEmailHeaders($email) {
      $headers = ['sender_email', 'sender_name', 'subject'];

      foreach ($headers as $header) {
        if (!isset($email[$header])) {
          continue;
        }

        // Ověření, zdali je hlavička zvýrazněna jako indicie (prošla přes DOM kontrolu) – pak byl text již escapován.
        if (!empty($email['_dom_processed_headers'][$header])) {
          continue;
        }

        $email[$header] = Controller::escapeOutput($email[$header]);
      }

      return $email;
    }


    /**
     * Vrátí sanitizované a personalizované tělo e-mailu s případným zvýrazněním indicií a proměnných.
     *
     * @param array $email             Pole s daty o e-mailu
     * @param array $user              Pole s daty o uživateli, který bude příjemcem e-mailu
     * @param array $indications       Pole s daty o indiciích
     * @param bool $highlightVariables TRUE, pokud se mají v těle e-mailu vyznačit proměnné, jinak FALSE (výchozí)
     * @param bool $htmlOutput         TRUE (výchozí), pokud je cílem e-mail vypsat do HTML, jinak FALSE
     * @return string                  Personalizované tělo e-mailu
     * @throws DOMException
     */
    private static function processEmailBody($email, $user, $indications, $highlightVariables, $htmlOutput) {
      if (!empty($email['body'])) {
        // Sanitizace HTML, doplnění indicií, proměnných.
        $body = $htmlOutput
          ? self::renderEmailBodyHtml($email['body'], $indications, $highlightVariables, $email['html'])
          : self::renderEmailBodyPlain($email['body']);

        // Personalizace těla e-mailu.
        if (isset($user['email'])) {
          $body = self::personalizeEmailBody(
            $body, $user, $email['url'] ?? null, $email['id_campaign'] ?? null, $email['date_sent'] ?? null
          );
        }

        // Pokud nemá jít o HTML výstup, dodatečně z těla e-mailu odstranit HTML entity
        // (z důvodu jejich možného výskytu v URL adrese po personalizaci).
        if (!$htmlOutput) {
          $body = Controller::decodeHtmlEntities($body);
        }
      }
      else {
        $body = $email['body'] ?? '';
      }

      return $body;
    }


    /**
     * Zkontroluje uživatelský vstup (atributy třídy), který se bude zapisovat do databáze.
     *
     * @throws UserError
     */
    public function validateData() {
      $this->isNameEmpty();
      $this->isNameTooLong();

      $this->isSenderNameTooLong();

      $this->isSenderEmailEmpty();
      $this->isSenderEmailTooLong();
      $this->isSenderEmailValid();
      $this->isSenderEmailDomainValid();

      $this->isSubjectEmpty();
      $this->isSubjectTooLong();

      $this->isBodyEmpty();
      $this->isBodyTooLong();
      $this->containBodyPhishingWebsiteVariable();
    }


    /**
     * Ověří, zdali byl vyplněn název e-mailu.
     *
     * @throws UserError
     */
    private function isNameEmpty() {
      if (empty($this->name)) {
        throw new UserError('Není vyplněn název e-mailu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný název e-mailu není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isNameTooLong() {
      if (mb_strlen($this->name) > $this->inputsMaxLengths['name']) {
        throw new UserError('Název e-mailu je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadané jméno odesílatele e-mailu není příliš dlouhé.
     *
     * @throws UserError
     */
    private function isSenderNameTooLong() {
      if (mb_strlen($this->senderName) > $this->inputsMaxLengths['sender-name']) {
        throw new UserError('Jméno odesílatele je příliš dlouhé.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vyplněn e-mail odesílatele.
     *
     * @throws UserError
     */
    private function isSenderEmailEmpty() {
      if (empty($this->senderEmail)) {
        throw new UserError('Není vyplněn e-mail odesílatele.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný e-mail odesílatele není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isSenderEmailTooLong() {
      if (mb_strlen($this->senderEmail) > $this->inputsMaxLengths['sender-email']) {
        throw new UserError('E-mail odesílatele je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je zadaný e-mail odesílatele validní.
     *
     * @throws UserError
     */
    private function isSenderEmailValid() {
      if (!filter_var($this->senderEmail, FILTER_VALIDATE_EMAIL) && $this->senderEmail != VAR_RECIPIENT_EMAIL) {
        throw new UserError('E-mail odesílatele je v nesprávném formátu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali existuje doména použitá v zadaném e-mailu odesílatele.
     *
     * @throws UserError
     */
    private function isSenderEmailDomainValid() {
      $domain = get_email_part($this->senderEmail, 'domain');

      if (gethostbyname($domain) == $domain) {
        throw new UserError('Doména použitá v e-mailu odesílatele neexistuje.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vyplněn předmět e-mailu.
     *
     * @throws UserError
     */
    private function isSubjectEmpty() {
      if (empty($this->subject)) {
        throw new UserError('Není vyplněn předmět e-mailu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný předmět e-mailu není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isSubjectTooLong() {
      if (mb_strlen($this->subject) > $this->inputsMaxLengths['subject']) {
        throw new UserError('Předmět e-mailu je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali bylo vyplněno tělo e-mailu.
     *
     * @throws UserError
     */
    private function isBodyEmpty() {
      if (empty($this->body)) {
        throw new UserError('Není vyplněno tělo e-mailu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadané tělo e-mailu není příliš dlouhé.
     *
     * @throws UserError
     */
    private function isBodyTooLong() {
      if (mb_strlen($this->body) > $this->inputsMaxLengths['body']) {
        throw new UserError('Obsah e-mailu je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je v zadaném těle e-mailu obsažena proměnná, která bude obsahovat odkaz na podvodnou stránku.
     *
     * @throws UserError
     */
    private function containBodyPhishingWebsiteVariable() {
      if (mb_strpos($this->body, VAR_URL) === false) {
        throw new UserError(
          'V těle e-mailu chybí použití proměnné "' . VAR_URL . '" pro umístění odkazu na podvodnou stránku.',
          MSG_ERROR
        );
      }
    }


    /**
     * Ověří, zdali administrátor nechce skrýt e-mail, který je již použit v některé z kampaní
     * (některým ze správců testů).
     *
     * @param int $idEmail             ID e-mailu
     * @throws UserError
     */
    private function isPhishingEmailUsedByTestManager($idEmail) {
      if ($this->hidden == 1 && $this->getCountOfUsePhishingEmailByTestManager($idEmail) > 0) {
        throw new UserError(
          'Nelze skrýt e-mail, který je použit v existující kampani některým ze správců testů.',MSG_ERROR
        );
      }
    }
  }
