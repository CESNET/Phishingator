<?php
  /**
   * Třída sloužící k získávání informací o založených kampaních,
   * k přidávání nových kampaní, k úpravě těch existujících
   * a k dalším souvisejícím operacím.
   *
   * @author Martin Šebela
   */
  class CampaignModel extends FormModel {
    /**
     * @var string      Název kampaně.
     */
    public $name;

    /**
     * @var int         ID podvodného e-mailu, který se bude v dané kampani rozesílat.
     */
    public $idEmail;

    /**
     * @var int         ID podvodné stránky, která bude v dané kampani přístupná z podvodného e-mailu.
     */
    public $idWebsite;

    /**
     * @var int         ID akce, která se stane po odeslání formuláře na podvodné stránce.
     */
    public $idOnsubmit;

    /**
     * @var int         ID požadavku souvisejícího s kampaní ze systému pro správu požadavků.
     */
    public $idTicket;

    /**
     * @var string      Čas, od kterého dojde k odesílání podvodných e-mailů.
     */
    public $timeActiveSince;

    /**
     * @var string      Čas, do kterého bude kampaň aktivní a podvodná stránka dostupná.
     */
    public $timeActiveTo;

    /**
     * @var string      Datum, od kterého bude kampaň aktivní (tzn. začnou se odesílat podvodné e-maily a podvodná
     *                  stránka se stane přístupnou přes speciální identifikátor.
     */
    public $dateActiveSince;

    /**
     * @var string      Datum, do kterého bude přístupná podvodná stránka a do kterého tedy budou sbírána data
     *                  z této podvodné stránky.
     */
    public $dateActiveTo;

    /**
     * @var array       Seznam příjemců.
     */
    public $recipients;

    /**
     * @var int         Proměnná uchovávající informaci o tom, zdali má po ukončení kampaně
     *                  dojít k odeslání notifikací příjemcům kampaně.
     */
    public $sendUsersNotification;

    /**
     * @var string       Proměnná uchovávající informaci o tom, v jakých jazycích mám být
     *                   uživatelská notifikace po ukončení kampaně odeslána.
     */
    public $usersNotificationLanguage;


    /**
     * Načte a zpracuje předaná data.
     *
     * @param array $data              Vstupní data
     * @throws UserError               Výjimka platná tehdy, pokud CSRF token neodpovídá původně vygenerovanému.
     */
    public function load($data) {
      parent::load($data);

      if (isset($_POST[$this->formPrefix . 'recipients'])) {
        $this->recipients = preg_split('/\r\n|[\r\n]/', $_POST[$this->formPrefix . 'recipients']);

        if (count($this->recipients) == 1 && empty($this->recipients[0])) {
          $this->recipients = '';
        }
      }
      else {
        $this->recipients = '';
      }

      $this->sendUsersNotification = (empty($this->sendUsersNotification) ? 0 : 1);

      if (is_array($this->usersNotificationLanguage)) {
        $this->usersNotificationLanguage = implode(',', $this->usersNotificationLanguage);
      }
      else {
        $this->usersNotificationLanguage = '';
      }
    }


    /**
     * Připraví novou kampaň v závislosti na vyplněných datech a vrátí ji formou pole.
     *
     * @return array                   Pole obsahující data o kampani.
     */
    private function makeCampaign() {
      $this->idTicket = (empty($this->idTicket) ? NULL : $this->idTicket);

      return [
        'id_email' => $this->idEmail,
        'id_website' => $this->idWebsite,
        'id_onsubmit' => $this->idOnsubmit,
        'id_ticket' => $this->idTicket,
        'name' => $this->name,
        'time_active_since' => $this->timeActiveSince,
        'time_active_to' => $this->timeActiveTo,
        'date_active_since' => $this->dateActiveSince,
        'date_active_to' => $this->dateActiveTo,
        'send_users_notification' => $this->sendUsersNotification,
        'users_notification_language' => $this->usersNotificationLanguage
      ];
    }


    /**
     * Uloží do instance a zároveň vrátí (z databáze) informace o zvolené kampani.
     *
     * @param int $id                  ID kampaně
     * @return array|null              Pole obsahující informace o kampani nebo NULL, pokud uživatel k dané kampani
     *                                 nemá právo.
     */
    public function getCampaign($id) {
      if ($this->isCampaignInUserGroup($id) !== true) {
        return null;
      }

      $this->dbRecordData = Database::querySingle('
              SELECT `id_campaign`, `id_by_user`, `id_email`, `id_website`, `id_onsubmit`, `id_ticket`, `name`, `date_active_since`, `date_active_to`, `send_users_notification`, `users_notification_language`,
              `time_active_since`, DATE_FORMAT(time_active_since, "%H:%i") AS `time_active_since`,
              `time_active_to`, DATE_FORMAT(time_active_to, "%H:%i") AS `time_active_to`,
              TIMESTAMP(date_active_since, time_active_since) AS `datetime_active_since`,
              TIMESTAMP(date_active_to, time_active_to) AS `datetime_active_to`
              FROM `phg_campaigns`
              WHERE `id_campaign` = ?
              AND `visible` = 1
      ', $id);

      if (!empty($this->dbRecordData)) {
        $this->dbRecordData['status'] = $this->getCampaignStatus($this->dbRecordData['datetime_active_since'], $this->dbRecordData['datetime_active_to']);
        $this->dbRecordData['users_notification_language'] = NotificationsModel::parseNotificationLanguages($this->dbRecordData['users_notification_language']);
      }

      return $this->dbRecordData;
    }


    /**
     * Ověří, zdali je zvolená kampaň vytvořená uživatelem nebo jinými uživateli ve stejné uživatelské skupině
     * a má-li tedy uživatel právo k ní přistupovat (netýká se administrátorů).
     *
     * @param int $idCampaign          ID kampaně
     * @return bool                    TRUE pokud uživatel právo k přístupu ke kampani má, jinak FALSE.
     */
    private static function isCampaignInUserGroup($idCampaign) {
      $model = new UsersModel();
      $user = $model->getUser(PermissionsModel::getUserId());
      $userPermission = PermissionsModel::getUserPermission();

      // Spouštění z CRONu.
      if (empty($user)) {
        return true;
      }

      // Uživatel je přihlášen a vstupuje na vzdělávací stránku s informacemi o kampani, ve které byl mezi příjemci.
      if ($userPermission == PERMISSION_USER) {
        return CampaignModel::isUserRecipient($idCampaign, $user['id_user']) == 1;
      }

      $campaign = Database::querySingle('
              SELECT phg_users.id_user_group
              FROM `phg_campaigns`
              JOIN `phg_users`
              ON phg_campaigns.id_by_user = phg_users.id_user
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              WHERE `id_campaign` = ?
              AND phg_users_roles.value >= ?
              AND phg_campaigns.visible = 1
      ', [$idCampaign, $userPermission]);

      if (empty($campaign) || $userPermission != PERMISSION_ADMIN
          && $campaign['id_user_group'] != $user['id_user_group']) {
        return false;
      }

      return true;
    }


    /**
     * Vrátí detailní informace o konkrétní kampani.
     *
     * @param int $id                  ID kampaně
     * @param bool $replaceUsernames   TRUE (výchozí) pokud má dojít k nahrazení uživatelských jmen podle konfigurace
     * @return array|null              Pole s informacemi o kampani nebo NULL, pokud uživatel k dané kampani nemá právo
     */
    public static function getCampaignDetail($id, $replaceUsernames = true) {
      if (self::isCampaignInUserGroup($id) !== true) {
        return null;
      }

      $result = Database::querySingle('
              SELECT `id_campaign`, phg_campaigns.id_by_user, phg_campaigns.id_email, phg_campaigns.id_website, phg_campaigns.id_ticket, phg_campaigns.name,
              `time_active_since`, `time_active_to`, `date_active_since`, `date_active_to`, phg_campaigns.date_added,
              `username`, `email`,
              phg_emails.name AS `email_name`, phg_emails.sender_name, phg_emails.sender_email, `subject`, `body`, `html`,
              phg_websites.name AS `website_name`, phg_websites.url AS `url`,
              `server_dir`,
              DATE_FORMAT(phg_campaigns.date_added, "%e. %c. %Y") AS `date_added`,
              DATE_FORMAT(date_active_since, "%e. %c. %Y") AS `date_active_since_formatted`,
              DATE_FORMAT(date_active_to, "%e. %c. %Y") AS `date_active_to_formatted`,
              DATE_FORMAT(time_active_since, "%k:%i") AS `time_active_since`,
              DATE_FORMAT(time_active_to, "%k:%i") AS `time_active_to`,
              TIMESTAMP(date_active_since, time_active_since) AS `datetime_active_since`,
              TIMESTAMP(date_active_to, time_active_to) AS `datetime_active_to`
              FROM `phg_campaigns`
              JOIN `phg_users`
              ON phg_campaigns.id_by_user = phg_users.id_user
              JOIN `phg_emails`
              ON phg_campaigns.id_email = phg_emails.id_email
              JOIN `phg_websites`
              ON phg_campaigns.id_website = phg_websites.id_website
              JOIN `phg_websites_templates`
              ON phg_websites.id_template = phg_websites_templates.id_website_template
              WHERE `id_campaign` = ?
              AND phg_campaigns.visible = 1
      ', $id);

      // Zjištění dalších podrobností o kampani.
      if (!empty($result)) {
        $urlProtocol = get_protocol_from_url($result['url']);

        $result['url_protocol'] = $urlProtocol;
        $result['url_protocol_color'] = PhishingWebsiteModel::getColorURLProtocol($urlProtocol);
        $result['url'] = mb_substr($result['url'], mb_strlen($urlProtocol));

        $result['count_recipients'] = self::getCountOfRecipients($result['id_campaign']);
        $result['sent_emails'] = RecievedEmailModel::getCountOfSentEmailsInCampaign($result['id_campaign']);

        $status = self::getCampaignStatus($result['datetime_active_since'], $result['datetime_active_to'], true);
        $statusText = self::getCampaignStatusText($status);

        $result['status'] = $status;
        $result['status_text'] = $statusText['text'];
        $result['status_color'] = $statusText['color'];
      }

      if ($replaceUsernames) {
        $result = UsersModel::setUsernamesByConfig($result);
      }

      return $result;
    }


    /**
     * Vrátí seznam všech kampaní z databáze.
     *
     * @return mixed                   Pole kampaní a informace o každé z nich.
     */
    public function getCampaigns() {
      $model = new UsersModel();
      $user = $model->getUser(PermissionsModel::getUserId());
      $userPermission = PermissionsModel::getUserPermission();

      $result = Database::queryMulti('
              SELECT `id_campaign`, phg_campaigns.id_by_user, phg_campaigns.id_email, phg_campaigns.id_website, phg_campaigns.id_ticket, phg_campaigns.name, `time_active_since`, `time_active_to`, `date_active_since`, `date_active_to`, phg_campaigns.date_added,
              `username`, `email`, `id_user_role`, phg_users_roles.value,
              phg_users.id_user_group,
              phg_emails.name AS `email_name`,
              phg_websites.name AS `website_name`, phg_websites.url,
              DATE_FORMAT(phg_campaigns.date_added, "%e. %c. %Y") AS `date_added_formatted`,
              DATE_FORMAT(date_active_since, "%e. %c. %Y") AS `date_active_since_formatted`,
              DATE_FORMAT(date_active_to, "%e. %c. %Y") AS `date_active_to_formatted`,
              DATE_FORMAT(time_active_since, "%H:%i") AS `time_active_since_formatted`,
              DATE_FORMAT(time_active_to, "%H:%i") AS `time_active_to_formatted`,
              TIMESTAMP(date_active_since, time_active_since) AS `datetime_active_since`,
              TIMESTAMP(date_active_to, time_active_to) AS `datetime_active_to`
              FROM `phg_campaigns`
              JOIN `phg_users`
              ON phg_campaigns.id_by_user = phg_users.id_user
              JOIN `phg_emails`
              ON phg_campaigns.id_email = phg_emails.id_email
              JOIN `phg_websites`
              ON phg_campaigns.id_website = phg_websites.id_website
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              WHERE phg_users_roles.value >= ?
              AND phg_campaigns.visible = 1
              ORDER BY `id_campaign` DESC
      ', $userPermission);

      foreach ($result as $key => $campaign) {
        if ($userPermission != PERMISSION_ADMIN && $campaign['id_user_group'] != $user['id_user_group']) {
          unset($result[$key]);
          continue;
        }

        $urlProtocol = get_protocol_from_url($campaign['url']);

        $result[$key]['url_protocol'] = $urlProtocol;
        $result[$key]['url_protocol_color'] = PhishingWebsiteModel::getColorURLProtocol($urlProtocol);
        $result[$key]['url'] = mb_substr($campaign['url'], mb_strlen($urlProtocol));

        $result[$key]['role_color'] = UserGroupsModel::getColorGroupRole($campaign['value']);

        $result[$key]['count_recipients'] = $this->getCountOfRecipients($campaign['id_campaign']);

        $status = self::getCampaignStatus($campaign['datetime_active_since'], $campaign['datetime_active_to'], true);
        $statusText = self::getCampaignStatusText($status);

        $result[$key]['status'] = $status;
        $result[$key]['status_text'] = $statusText['text'];
        $result[$key]['status_color'] = $statusText['color'];
      }

      return UsersModel::setUsernamesByConfig($result);
    }


    /**
     * Vrátí seznam kampaní, které byly vytvořeny uživatelem nebo uživateli v určité skupině.
     *
     * @param int $idUser              ID uživatele, vůči kterému se seznam kampaní zjišťuje.
     * @return array                   Pole ID kampaní.
     */
    public static function getIdCampaignsInUserGroup($idUser) {
      $idCampaigns = [];

      $model = new UsersModel();
      $user = $model->getUser($idUser);
      $userPermission = PermissionsModel::getUserPermission();

      $result = Database::queryMulti('
              SELECT `id_campaign`, phg_users.id_user_group
              FROM `phg_campaigns`
              JOIN `phg_users`
              ON phg_campaigns.id_by_user = phg_users.id_user
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              WHERE phg_users_roles.value >= ?
              AND phg_campaigns.visible = 1
      ', $userPermission);

      /* Ověření, zdali má uživatel k dané kampani přístup v rámci uživatelské skupiny, do které je zařazen. */
      foreach ($result as $campaign) {
        if (!($userPermission != PERMISSION_ADMIN && $campaign['id_user_group'] != $user['id_user_group'])) {
          $idCampaigns[] = $campaign['id_campaign'];
        }
      }

      return $idCampaigns;
    }


    /**
     * Vrátí seznam právě aktivních kampaní (případně kampaní aktivních v daném roce).
     *
     * @param int $year                Rok (nepovinné)
     * @return mixed                   Pole aktivních kampaní
     */
    public static function getActiveCampaigns($year = []) {
      if (!empty($year) && is_numeric($year)) {
        $query = 'YEAR(`date_added`) = ?';
      }
      else {
        $query = 'TIMESTAMP(`date_active_since`, `time_active_since`) <= NOW() AND TIMESTAMP(`date_active_to`, `time_active_to`) >= NOW()';
      }

      return Database::queryMulti('
              SELECT `id_campaign`
              FROM `phg_campaigns`
              WHERE ' . $query . '
              AND `visible` = 1
      ', $year);
    }


    /**
     * Ověří, zdali je daná kampaň aktivní.
     *
     * @param int $idCampaign          ID kampaně
     * @return bool                    TRUE, pokud je kampaň aktivní, jinak FALSE
     */
    private static function isCampaignActive($idCampaign) {
      $campaing = Database::querySingle('
              SELECT `id_campaign`
              FROM `phg_campaigns`
              WHERE `id_campaign` = ?
              AND TIMESTAMP(`date_active_since`, `time_active_since`) <= NOW() AND TIMESTAMP(`date_active_to`, `time_active_to`) >= NOW()
              AND `visible` = 1
      ', $idCampaign);

      return !empty($campaing['id_campaign']);
    }


    /**
     * Vrátí seznam všech nově (k dnešnímu dni) přidaných kampaní.
     *
     * @return mixed                   Pole obsahující informace o nově přidaných kampaní.
     */
    public static function getNewAddedCampaigns() {
      return Database::queryMulti('
              SELECT `id_campaign`,
              `username`,
              DATE_FORMAT(phg_campaigns.date_added, "%e. %c. %Y (%k:%i)") AS `date_added`
              FROM `phg_campaigns`
              JOIN `phg_users`
              ON phg_campaigns.id_by_user = phg_users.id_user
              WHERE DATE(phg_campaigns.date_added) = CURDATE()
              AND phg_campaigns.visible = 1
      ');
    }


    /**
     * Vrátí seznam všech kampaní, které již skončily.
     *
     * @return mixed                   Pole obsahující informace o všech kampaních, které již skončily
     */
    public static function getFinishedCampaigns() {
      $result = Database::queryMulti('
              SELECT `id_campaign`, phg_campaigns.id_by_user, `date_active_to`, `send_users_notification`, `users_notification_language`,
              `username`, `email`,
              DATE_FORMAT(phg_campaigns.date_added, "%e. %c. %Y (%k:%i)") AS `date_added`,
              DATE_FORMAT(date_active_to, "%e. %c. %Y") AS `date_active_to`
              FROM `phg_campaigns`
              JOIN `phg_users`
              ON phg_campaigns.id_by_user = phg_users.id_user
              WHERE NOW() > TIMESTAMP(`date_active_to`, `time_active_to`)
              AND phg_campaigns.visible = 1
      ');

      if (!empty($result)) {
        foreach ($result as $key => $campaign) {
          $result[$key]['users_notification_language'] = NotificationsModel::parseNotificationLanguages($campaign['users_notification_language']);
        }
      }

      return $result;
    }


    /**
     * Vrátí rok, kdy byla založena první cvičná phishingová kampaň,
     * která zároveň nebyla smazána. Pokud nedojde k nalezení žádné kampaně,
     * vrátí se aktuální rok.
     *
     * @return int                     Rok
     */
    public static function getYearFirstActiveCampaign() {
      $campaing = Database::querySingle('
              SELECT `id_campaign`, YEAR(`date_added`) AS `date_added_year`
              FROM `phg_campaigns`
              WHERE `visible` = 1
              ORDER BY `id_campaign`
              LIMIT 1
      ');

      return $campaing['date_added_year'] ?? date('Y');
    }


    /**
     * Vrátí seznam všech kampaní, které mají vyplněn ID požadavku
     * souvisejícího s kampaní ze systému pro správu požadavků.
     *
     * @return array|false             Pole s daty o kampaních
     */
    public static function getCampaignsWithTicketId() {
      return Database::queryMulti('
              SELECT `id_campaign`
              FROM `phg_campaigns`
              WHERE `id_ticket` IS NOT NULL
              AND `visible` = 1
      ');
    }


    /**
     * Vrátí počet příjemců v konkrétní kampani, nebo v množině kampaní nebo ve všech kampaních.
     *
     * @param int|array|null $idCampaign ID kampaně nebo pole ID kampaní nebo NULL pro všechny kampaně.
     * @return int                       Počet příjemců v kampani
     */
    public static function getCountOfRecipients($idCampaign = null) {
      /* Zjištění počtu příjemců pro konkrétní kampaň. */
      if (!is_array($idCampaign) && $idCampaign != null) {
        return count(self::getCampaignRecipients($idCampaign));
      }
      else {
        /* Zjištění počtu příjemců pro množinu kampaní nebo pro všechny kampaně. */
        $recipientsCount = 0;

        $recipients = Database::queryMulti('
              SELECT phg_campaigns_recipients.id_campaign, `id_user`, `signed`
              FROM `phg_campaigns_recipients`
              JOIN phg_campaigns
              ON phg_campaigns.id_campaign = phg_campaigns_recipients.id_campaign
              WHERE date_active_since <= CURDATE()
              AND phg_campaigns.visible = 1
              ORDER BY `id_recipient`
        ');

        foreach ($recipients as $recipient) {
          /* Pokud ID kampaně neodpovídá některému z ID v množině kampaní a jsou požadováni příjemci v takové množině
             kampaní, pak záznam přeskočit. */
          if (is_array($idCampaign) && !in_array($recipient['id_campaign'], $idCampaign)) {
            continue;
          }

          /* Rozlišení toho, zdali se uživatel do kampaně přihlásil nebo se z ní odhlásil. */
          if ($recipient['signed'] == 1) {
            $recipientsCount += 1;
          }
          else {
            $recipientsCount -= 1;
          }
        }

        return $recipientsCount;
      }
    }


    /**
     * Vrátí seznam příjemců konkrétní kampaně.
     *
     * @param int $idCampaign          ID kampaně
     * @param bool $returnString       TRUE pokud vrátít data formou řetězce s oddělovačem, FALSE pokud vracet formou pole (výchozí).
     * @param bool $all                TRUE pokud vrátit více podrobností, jinak FALSE (výchozí).
     * @return array|string            Seznam e-mailů v podobě podle druhého parametru metody.
     */
    public static function getCampaignRecipients($idCampaign, $returnString = false, $all = false) {
      $recipientsArray = [];
      $recipientsString = '';

      $result = Database::queryMulti('
              SELECT phg_campaigns_recipients.id_user, `signed`, `sign_date`,
              `username`, `email`
              FROM `phg_campaigns_recipients`
              JOIN `phg_users`
              ON phg_campaigns_recipients.id_user = phg_users.id_user
              WHERE `id_recipient`
              IN (
                SELECT MAX(`id_recipient`) FROM `phg_campaigns_recipients` WHERE `id_campaign` = ? GROUP BY `id_user`
              )
              AND `signed` = 1
        ', $idCampaign);

      if ($all) {
        return $result;
      }

      foreach ($result as $recipient) {
        $recipientsArray[] = $recipient['email'];
        $recipientsString .= $recipient['email'] . CAMPAIGN_EMAILS_DELIMITER;
      }

      return !$returnString ? $recipientsArray : $recipientsString;
    }


    /**
     * Vrátí seznam všech příjemců, kteří jsou dobrovolně zaregistrováni k odebírání cvičných
     * phishingových zpráv. První parametr předává metodě pole příjemců, kteří mají být označeni
     * jako vybraní (tzn. jsou již součástí kampaně).
     *
     * @param array $allRecipients          Pole příjemců, kteří jsou již součástí kampaně.
     * @return mixed                        Pole dobrovolných příjemců s informacemi o každém z nich.
     */
    public static function getVolunteersRecipients($allRecipients) {
      // Seznam administrátorů a správců testů pro odlišení barvou.
      $adminUsers = UsersModel::getUsersByPermission(PERMISSION_ADMIN, true);
      $testManagerUsers = UsersModel::getUsersByPermission(PERMISSION_TEST_MANAGER, true);

      $result = Database::queryMulti('
            SELECT `id_user`, `email`, `email_limit`
            FROM `phg_users`
            WHERE `recieve_email` = 1
            AND (`email_limit` > 0
            OR `email_limit` IS NULL)
            AND `visible` = 1
            ORDER BY `email`
      ');

      if ($result != null) {
        $ldap = new LdapModel();

        foreach ($result as $key => $recipient) {
          $username = get_email_part($recipient['email'], 'username');
          $domain = get_email_part($recipient['email'], 'domain');

          // Pokud se nepodaří nalézt uživatelské jméno dobrovolníka v LDAPu, nezobrazovat jej mezi dobrovolníky.
          if ($ldap->getUsernameByEmail($recipient['email']) == null) {
            unset($result[$key]);
            continue;
          }

          $result[$key]['username'] = $username;
          $result[$key]['domain'] = $domain;

          // Ověření, zdali je daný uživatel vyplněn mezi příjemci.
          $result[$key]['checked'] = in_array($recipient['email'], $allRecipients);

          // Určení barvy uživatele na základě oprávnění ve Phishingatoru.
          $result[$key]['color'] = UserGroupsModel::getColorGroupRoleByUsername(
            $recipient['email'], ['admin' => $adminUsers, 'testmanager' => $testManagerUsers]
          );
        }

        $ldap->close();
      }

      return $result;
    }


    /**
     * Vrátí seznam skupin se všemi jejich potenciálními příjemci z LDAP s tím,
     * že první parametr metodě předává pole příjemců, které uživatel již mezi
     * příjemce zahrnul.
     *
     * @param array $allRecipients          Pole příjemců, kteří jsou již součástí kampaně.
     * @param string $allowedLdapGroups     Názvy povolených skupin v LDAP, které mají být vypisovány.
     * @param string|null $emailRestriction E-mailové omezení uživatele na konkrétní sadu e-mailů - takové e-maily
     *                                      nebudou uživateli ani vypisovány (nepovinný parametr).
     * @return array                        Seznam skupin s příjemci z LDAP.
     */
    public static function getLdapRecipients($allRecipients, $allowedLdapGroups, $emailRestriction = null) {
      $ldapGroups = [];

      // Seznam administrátorů a správců testů pro odlišení barvou.
      $adminUsers = UsersModel::getUsersByPermission(PERMISSION_ADMIN, true);
      $testManagerUsers = UsersModel::getUsersByPermission(PERMISSION_TEST_MANAGER, true);

      if (!empty($allowedLdapGroups)) {
        $ldapModel = new LdapModel();
        $ldapAllowedGroups = explode(LDAP_GROUPS_DELIMITER, $allowedLdapGroups);

        foreach ($ldapAllowedGroups as $group) {
          $group = remove_new_line_symbols($group);

          if (empty($group)) {
            continue;
          }

          // Seznam uživatelů v dané LDAP skupině.
          $usersInGroup = $ldapModel->getUsersEmailsInGroup($group);

          if (!empty($usersInGroup)) {
            foreach ($usersInGroup as $key => $user) {
              $username = get_email_part($user, 'username');
              $domain = get_email_part($user, 'domain');

              // Ověření, zdali je e-mail mezi těmi povolenými (pokud je v oprávnění na sadu e-mailů nějaká restrikce).
              if (empty($user) /*|| ($emailRestriction != null && strpos($emailRestriction, '@' . $domain) === false)*/) {
                unset($usersInGroup[$key]);
                continue;
              }

              // Ověření, zdali je daný uživatel vyplněn mezi příjemci.
              $checked = in_array($user, $allRecipients);

              // Určení barvy uživatele na základě jeho oprávnění.
              $color = UserGroupsModel::getColorGroupRoleByUsername(
                $user, ['admin' => $adminUsers, 'testmanager' => $testManagerUsers]
              );

              $usersInGroup[$key] = [
                'email' => $user, 'username' => $username, 'domain' => $domain,
                'color' => $color, 'checked' => $checked
              ];
            }

            // Ověření, zdali má vůbec smysl skupinu uvažovat, pokud by došlo k tomu, že na základě uživatelova
            // oprávnění na sadu e-mailů nevyhovuje žádný z e-mailů.
            if (count($usersInGroup) > 0) {
              $ldapGroups[$group] = $usersInGroup;
            }
          }
        }

        $ldapModel->close();
      }

      return $ldapGroups;
    }


    /**
     * Ověří, zdali je konkrétní uživatel mezi příjemci dané kampaně.
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idUser              ID uživatele
     * @return mixed                   1 pokud je uživatel mezi příjemci, jinak 0 nebo NULL.
     */
    public static function isUserRecipient($idCampaign, $idUser) {
      return Database::queryCount('
              SELECT COUNT(*), MAX(`sign_date`)
              FROM `phg_campaigns_recipients`
              WHERE `id_campaign` = ?
              AND `id_user` = ?
              AND `signed` = 1
      ', [$idCampaign, $idUser]);
    }


    /**
     * Vloží do databáze novou kampaň.
     */
    public function insertCampaign() {
      $campaign = $this->makeCampaign();

      $campaign['id_by_user'] = PermissionsModel::getUserId();
      $campaign['date_added'] = date('Y-m-d H:i:s');

      Logger::info('New phishing campaign added.', $campaign);

      Database::insert($this->dbTableName, $campaign);

      // Zjištění ID nově vložené kampaně.
      $campaign['id'] = Database::getLastInsertId();

      // Odstranění případných duplicit ze seznamu příjemců.
      $uniqueRecipients = array_unique($this->recipients);

      // Vkládání vybraných příjemců ke kampani.
      foreach ($uniqueRecipients as $recipientEmail) {
        $this->insertRecipient($campaign['id'], $recipientEmail);
      }
    }


    /**
     * Přidá uživatele mezi příjemce u dané kampaně.
     *
     * @param int $idCampaign          ID kampaně
     * @param string $email            E-mail příjemce
     * @throws UserError
     */
    public function insertRecipient($idCampaign, $email) {
      if (!empty($email)) {
        $idUser = UsersModel::getOrCreateUserByEmail($email);

        // Pokud se uživatele podařilo dohledat, popř. založit, přidat jej mezi příjemce.
        if ($idUser !== null) {
          // Ověření, zdali již uživatel není mezi příjemci.
          if (self::isUserRecipient($idCampaign, $idUser) === 0) {
            $recipient = [
              'id_campaign' => $idCampaign,
              'id_user' => $idUser,
              'id_sign_by_user' => PermissionsModel::getUserId(),
              'sign_date' => date('Y-m-d H:i:s'),
              'signed' => 1
            ];

            Logger::info('New recipient added to the phishing campaign.', [$idCampaign, $email]);

            Database::insert('phg_campaigns_recipients', $recipient);
          }
        }
        else {
          Logger::error('Failed to add new recipient to the phishing campaign.', [$idCampaign, $email]);
        }
      }
    }


    /**
     * Odebere uživatele ze seznamu příjemců u dané kampaně.
     *
     * @param int $idCampaign          ID kampaně
     * @param string $email            E-mail příjemce
     */
    public function unsignRecipient($idCampaign, $email) {
      if (!empty($email)) {
        $user = UsersModel::getUserByEmail($email);

        if (!empty($user['id_user'])) {
          $recipient = [
            'id_campaign' => $idCampaign,
            'id_user' => $user['id_user'],
            'id_sign_by_user' => PermissionsModel::getUserId(),
            'sign_date' => date('Y-m-d H:i:s'),
            'signed' => 0
          ];

          Logger::info('Recipient removed from the phishing campaign.', [$idCampaign, $email]);

          Database::insert('phg_campaigns_recipients', $recipient);
        }
        else {
          Logger::error('Failed to remove recipient from the phishing campaign.', [$idCampaign, $email]);
        }
      }
    }


    /**
     * Upraví zvolenou kampaň v databázi.
     *
     * @param int $id ID kampaně
     * @throws UserError
     */
    public function updateCampaign($id) {
      $campaign = $this->makeCampaign();

      Logger::info('Phishing campaign modified.', $campaign);

      Database::update(
        $this->dbTableName,
        $campaign,
        'WHERE `id_campaign` = ?',
        $id
      );

      // Aktuální seznam příjemců kampaně.
      $currentRecipientsArray = $this->getCampaignRecipients($id);

      // Odstranění případných duplicit ze seznamu příjemců.
      $uniqueRecipients = array_unique($this->recipients);

      // Seznam příjemců, kteří v databázi přebývají (oproti vyplněnému seznamu).
      $recipientsToUnsign = array_udiff($currentRecipientsArray, $uniqueRecipients, 'strcasecmp');

      // Seznam příjemců, kteří v databázi nejsou (oproti vyplněnému seznamu).
      $recipientsToSign = array_udiff($uniqueRecipients, $currentRecipientsArray, 'strcasecmp');

      // Odhlášení smazaných příjemců.
      foreach ($recipientsToUnsign as $recipient) {
        $this->unsignRecipient($id, $recipient);
      }

      // Přihlášení nových příjemců.
      foreach ($recipientsToSign as $recipient) {
        $this->insertRecipient($id, $recipient);
      }
    }


    /**
     * Odstraní (resp. deaktivuje) kampaň z databáze.
     *
     * @param int $id                  ID uživatele
     * @throws UserError
     */
    public function deleteCampaign($id) {
      if ($this->isCampaignActive($id)) {
        throw new UserError('Nelze smazat kampaň, která je právě aktivní.', MSG_ERROR);
      }

      $result = Database::update(
        'phg_campaigns',
        ['visible' => 0],
        'WHERE `id_campaign` = ? AND `visible` = 1',
        $id
      );

      if ($result == 0) {
        Logger::warning('Attempt to delete a non-existent phishing campaign.', $id);

        throw new UserError('Záznam vybraný ke smazání neexistuje.', MSG_ERROR);
      }

      Logger::info('Phishing campaign deleted.', $id);
    }


    /**
     * Ukončí zvolenou kampaň.
     *
     * @param int $id                  ID kampaně
     * @throws UserError
     */
    public static function stopCampaign($id) {
      if (!self::isCampaignActive($id)) {
        throw new UserError('Nelze zastavit kampaň, která není aktivní.', MSG_ERROR);
      }

      $currenteDatetime = [
        'date_active_to' => date('Y-m-d'),
        'time_active_to' => date('H:i:s')
      ];

      Database::update(
        'phg_campaigns',
        $currenteDatetime,
        'WHERE `id_campaign` = ? AND `visible` = 1',
        $id
      );

      Logger::info('Phishing campaign stopped.', $id);
    }


    /**
     * Změní (zneguje) nastavení u konkrétního záznamu pro hlášení phishingu.
     *
     * @param int $idCapturedData      ID záznamu, u kterého má dojít ke změně
     */
    public static function setUserPhishReport($idCapturedData) {
      $data = Database::querySingle('
              SELECT `reported`
              FROM `phg_captured_data`
              WHERE `id_captured_data` = ?
      ', $idCapturedData);

      if (isset($data['reported'])) {
        $lastValue = ($data['reported'] == 1) ? 0 : 1;

        Database::update(
          'phg_captured_data',
          ['reported' => $lastValue],
          'WHERE `id_captured_data` = ?',
          $idCapturedData
        );
      }
    }


    /**
     * Vrátí všechny zaznamenané akce, které uživatelé provedli na podvodné stránce přiřazené ke konkrétní kampani.
     *
     * @param int $idCampaign          ID kampaně
     * @param bool $orderAsc           TRUE, pokud mají být akce řazeny od nejstarší, jinak FALSE (výchozí)
     * @param bool $replaceUsernames   TRUE (výchozí) pokud má dojít k nahrazení uživatelských jmen podle konfigurace
     * @return mixed                   Pole zaznamenaných akcí
     */
    public static function getCapturedDataInCampaign($idCampaign, $orderAsc = false, $replaceUsernames = true) {
      $records = Database::queryMulti('
              SELECT `id_captured_data`, phg_captured_data.id_user, `used_email`, `used_group`, `visit_datetime`, `ip`, `browser_fingerprint`, `data_json`, `reported`,
              DATE_FORMAT(visit_datetime, "%e. %c. %Y %k:%i:%s") AS `visit_datetime_formatted`,
              DATE_FORMAT(CONVERT_TZ(`visit_datetime`, @@session.time_zone, "+00:00"), "%Y-%m-%dT%TZ") AS `visit_datetime_utc`,
              `name`, `css_color_class`,
              `username`, `email`
              FROM `phg_captured_data`
              JOIN `phg_captured_data_actions`
              ON phg_captured_data.id_action = phg_captured_data_actions.id_action
              JOIN `phg_users`
              ON phg_captured_data.id_user = phg_users.id_user
              WHERE `id_campaign` = ?
              AND phg_captured_data.id_action != ?
              ORDER BY `id_captured_data` ' . ((!$orderAsc) ? 'DESC' : '')
      , [$idCampaign, CAMPAIGN_NO_REACTION_ID]);

      // Dekódování uživatelského jména a hesla zadaného do podvodné stránky z JSON.
      foreach ($records as $key => $record) {
        $capturedCredentials = (!empty($record['data_json'])) ? json_decode($record['data_json'], true) : [];

        $records[$key]['filled_username'] = $capturedCredentials['username'] ?? '';
        $records[$key]['filled_password'] = $capturedCredentials['password'] ?? '';
      }

      if ($replaceUsernames) {
        $records = UsersModel::setUsernamesByConfig($records);
      }

      return $records;
    }


    /**
     * Vrátí reakce uživatelů na phishing pro konkrétní kampaň.
     *
     * @param int $idCampaign          ID kampaně
     * @param bool $replaceUsernames   TRUE (výchozí) pokud má dojít k nahrazení uživatelských jmen podle konfigurace
     * @return mixed                   Reakce uživatelů
     */
    public static function getUsersResponsesInCampaign($idCampaign, $replaceUsernames = true) {
      $records = Database::queryMulti('
              SELECT
                userWorstAction.id_captured_data, userWorstAction.id_user, userWorstAction.used_email, userWorstAction.used_group, userWorstAction.id_action, userWorstAction.reported,
                userEduSiteVisit.edusite_visit_datetime, userEduSiteVisit.edusite_visit_datetime_utc,
                phg_captured_data_actions.name,
                `username`, `email`
              FROM `phg_captured_data_actions`
              JOIN
                (SELECT MAX(id_captured_data) AS `id_captured_data`, `id_user`, `used_email`, `used_group`, MAX(phg_captured_data.id_action) AS `id_action`, MAX(reported) AS `reported`
                 FROM `phg_captured_data`
                 WHERE `id_campaign` = ?
                 GROUP BY `id_user`) userWorstAction
              ON phg_captured_data_actions.id_action = userWorstAction.id_action
              LEFT JOIN
                (SELECT MIN(`id_captured_data`), `id_user`, `visit_datetime` AS `edusite_visit_datetime`,
                 DATE_FORMAT(CONVERT_TZ(`visit_datetime`, @@session.time_zone, "+00:00"), "%Y-%m-%dT%TZ") AS `edusite_visit_datetime_utc`
                 FROM `phg_captured_data_end`
                 WHERE `id_campaign` = ?
                 GROUP BY `id_user`) userEduSiteVisit
              ON userWorstAction.id_user = userEduSiteVisit.id_user
              JOIN `phg_users`
              ON userWorstAction.id_user = phg_users.id_user
              ORDER BY userWorstAction.used_group, `username`
          ', [$idCampaign, $idCampaign]);

      if ($replaceUsernames) {
        $records = UsersModel::setUsernamesByConfig($records);
      }

      return $records;
    }


    /**
     * Vrátí seznam uživatelů (resp. kompromitovaných účtů), kteří v dané kampani
     * podlehli cvičnému phishingu a skončili ve stavu "zadání platných údajů".
     *
     * @param int $idCampaign          ID kampaně
     * @param bool $replaceUsernames   TRUE (výchozí) pokud má dojít k nahrazení uživatelských jmen podle konfigurace
     * @return mixed                   Seznam kompromitovaných účtů
     */
    public static function getCompromisedUsersInCampaign($idCampaign, $replaceUsernames = true) {
      $records = Database::queryMulti('
              SELECT `id_captured_data`, `used_email`, `used_group`, `visit_datetime`, `reported`,
              DATE_FORMAT(CONVERT_TZ(`visit_datetime`, @@session.time_zone, "+00:00"), "%Y-%m-%dT%TZ") AS `visit_datetime_utc`,
              TIMESTAMPDIFF(SECOND, TIMESTAMP(phg_campaigns.date_active_since, phg_campaigns.time_active_since), `visit_datetime`) AS `seconds_since_campaign_start`,
              SEC_TO_TIME(TIMESTAMPDIFF(SECOND, TIMESTAMP(phg_campaigns.date_active_since, phg_campaigns.time_active_since), `visit_datetime`)) AS `time_since_campaign_start`,
              `username`
              FROM `phg_captured_data`
              JOIN (
                  SELECT `id_user`, MIN(`id_captured_data`) AS `first_id_captured_data`
                  FROM `phg_captured_data`
                  WHERE `id_campaign` = ?
                  AND `id_action` = ?
                  GROUP BY `id_user`
              ) `first_captured_data` ON phg_captured_data.id_captured_data = first_captured_data.first_id_captured_data
              JOIN `phg_campaigns`
              ON phg_captured_data.id_campaign = phg_campaigns.id_campaign
              JOIN `phg_users`
              ON phg_captured_data.id_user = phg_users.id_user
              WHERE phg_captured_data.id_campaign = ?
              AND phg_captured_data.id_action = ?
              ORDER BY `id_captured_data`
          ', [$idCampaign, CAMPAIGN_VALID_CREDENTIALS_ID, $idCampaign, CAMPAIGN_VALID_CREDENTIALS_ID]);

      if ($replaceUsernames) {
        $records = UsersModel::setUsernamesByConfig($records);
      }

      return $records;
    }


    /**
     * Vrátí všechny záznamy o navštívení stránky o absolvování phishingu pro konkrétní kampaň.
     *
     * @param int $idCampaign          ID kampaně
     * @return array                   Pole zaznamenaných akcí
     */
    public static function getCapturedDataTestPage($idCampaign) {
      $data = null;
      $db_data = Database::queryMulti('
              SELECT `id_user`, `visit_datetime`,
              DATE_FORMAT(visit_datetime, "%e. %c. %Y %k:%i:%s") AS `visit_datetime`
              FROM `phg_captured_data_end`
              WHERE `id_campaign` = ?
              GROUP BY `id_user`
      ', $idCampaign);

      foreach ($db_data as $record) {
        $data[$record['id_user']] = $record['visit_datetime'];
      }

      return $data;
    }


    /**
     * Vrátí souhrnnou statistiku pro kampaň, a to buď na základě dat s reakcemi uživatelů,
     * nebo na základě ID kampaně, podle které se reakce uživatelů získají.
     *
     * @param array $data              Reakce uživatelů v kampani (nepovinné)
     * @param int $idCampaign          ID kampaně, podle které se reakce uživatelů získají (nepovinné)
     * @return array                   Pole se statistikou uživatelů v dané kampani
     */
    public static function getCampaignSummaryStats($data = null, $idCampaign = null) {
      $actions = self::getCampaignActions();

      if ($data === null) {
        $data = self::getUsersResponsesInCampaign($idCampaign);
      }

      $stats = [];

      $countData = count($data);
      $countUserEdusiteVisits = 0;
      $countUserReports = 0;

      // Předpřipravení dat o akcích, ke kterým u kampaně může dojít.
      foreach ($actions as $action) {
        $stats[$action['id_action']] = [
          'name' => $action['name'],
          'css_color_class' => $action['css_color_class'],
          'value' => 0
        ];
      }

      // Zjištění počtu výskytu jednotlivých akcí uživatelů.
      foreach ($data as $record) {
        $idAction = $record['id_action'];

        if (isset($stats[$idAction])) {
          $stats[$idAction]['value']++;
        }

        if (!empty($record['edusite_visit_datetime'])) {
          $countUserEdusiteVisits++;
        }

        if (!empty($record['reported'])) {
          $countUserReports++;
        }
      }

      // Doplnění dat o zbývajících akcích.
      if (isset($stats[CAMPAIGN_VISIT_EDUCATIONAL_SITE_ID])) {
        $stats[CAMPAIGN_VISIT_EDUCATIONAL_SITE_ID]['value'] = $countUserEdusiteVisits;
      }

      if (isset($stats[CAMPAIGN_REPORT_PHISHING_ID])) {
        $stats[CAMPAIGN_REPORT_PHISHING_ID]['value'] = $countUserReports;
      }

      // Výpočet procent pro každou akci.
      foreach ($stats as $action => $value) {
        $stats[$action]['percentage'] = $countData > 0 ? get_formatted_number(round($value['value'] / $countData * 100, 1), 1) : 0;
      }

      return $stats;
    }


    /**
     * Vrátí data o možných akcích, ke kterým může v kampani dojít.
     *
     * @return array                   Data o akcích v kampani
     */
    public static function getCampaignActions() {
      return Database::queryMulti('
        SELECT `id_action`, `name`, `hex_color`, `css_color_class`
        FROM `phg_captured_data_actions`
      ');
    }


    /**
     * Přidá do databáze záznam o tom, že uživatel v dané kampani zatím žádným způsobem nereagoval.
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idUser              ID uživatele
     * @param string $usedEmail        E-mail uživatele použitý v kampani
     * @param string $usedGroup        Skupina, která uživateli během kampaně náleží
     */
    public static function insertNoReactionRecord($idCampaign, $idUser, $usedEmail, $usedGroup) {
      $record = [
        'id_campaign' => $idCampaign,
        'id_user' => $idUser,
        'id_action' => CAMPAIGN_NO_REACTION_ID,
        'used_email' => $usedEmail,
        'used_group' => $usedGroup
      ];

      Database::insert('phg_captured_data', $record);
    }


    /**
     * Vrátí (z databáze) informace o akci, která se stane po odeslání formuláře na podvodné stránce.
     *
     * @param int $id                  ID akce po odeslání formuláře
     * @return mixed                   Informace o zvolené akci
     */
    public static function getWebsiteAction($id) {
      return Database::querySingle('
              SELECT `id_onsubmit`, `name`, `url`
              FROM `phg_campaigns_onsubmit`
              WHERE `id_onsubmit` = ?
              AND `visible` = 1
      ', $id);
    }


    /**
     * Vrátí (z databáze) seznam všech možných akcí, které se mohou stát na podvodné stránce při odeslání formuláře.
     *
     * @return mixed                   Pole možných akcí.
     */
    public function getWebsiteActions() {
      return Database::queryMulti('
              SELECT `id_onsubmit`, `name`, `url`
              FROM `phg_campaigns_onsubmit`
              WHERE `visible` = 1
              ORDER BY `id_onsubmit`
      ');
    }


    /**
     * Vrátí nejhorší možnou reakci uživatele, kterou mohl ve vybrané kampani provést.
     * Pokud na kampaň nereagoval, výsledek bude akce "bez reakce".
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idUser              ID uživatele
     * @return array|null              Pole s informacemi o reakci uživatele
     */
    public static function getUserResponse($idCampaign, $idUser) {
      $result = null;

      if ($idCampaign && $idUser) {
        $result = Database::querySingle('
              SELECT phg_captured_data.id_captured_data, phg_captured_data.id_action,
              `name`, `css_color_class`
              FROM `phg_captured_data`
              JOIN `phg_captured_data_actions`
              ON phg_captured_data.id_action = phg_captured_data_actions.id_action
              WHERE `id_campaign` = ?
              AND `id_user` = ?
              ORDER BY phg_captured_data.id_action DESC
              LIMIT 1
        ', [$idCampaign, $idUser]);

        /* Pokud uživatel nijak nereagoval, vrátit jako výsledek informace o akci "bez reakce". */
        if (empty($result['id_action'])) {
          $result = Database::querySingle('
              SELECT `id_action`, `name`, `css_color_class`
              FROM `phg_captured_data_actions`
              WHERE `id_action` = ?
          ', CAMPAIGN_NO_REACTION_ID);
        }
      }

      return $result;
    }


    /**
     * Vrátí aktuální stav kampaně podle předaného data a času startu a konce.
     *
     * @param string $datetimeActiveSince Datum a čas startu kampaně
     * @param string $datetimeActiveTo    Datum a čas konce kampaně
     * @param bool $withTodayStatus       TRUE, pokud se má uvažovat i stav "dnes startuje" / "dnes končí", jinak FALSE (výchozí)
     * @return string                     Aktuální stav
     */
    public static function getCampaignStatus($datetimeActiveSince, $datetimeActiveTo, $withTodayStatus = false) {
      $activeSince = strtotime($datetimeActiveSince);
      $activeTo = strtotime($datetimeActiveTo);

      $todayStart = strtotime('today');
      $todayEnd = strtotime('tomorrow');

      $now = time();

      // Kampaň zatím nezačala.
      if ($now < $activeSince) {
        $status = 'waiting';
      }
      // Kampaň probíhá.
      elseif ($now < $activeTo) {
        $status = 'running';
      }
      // Kampaň již skončila.
      else {
        $status = 'ended';
      }

      if ($withTodayStatus) {
        // Kampaň dnes začíná (tj. ještě neprobíhá).
        if ($status == 'waiting' && $activeSince >= $todayStart && $activeSince < $todayEnd) {
          $status = 'starting';
        }
        // Kampaň dnes končí (tj. probíhá, ale dnes je poslední den).
        elseif ($status == 'running' && $activeTo >= $todayStart && $activeTo < $todayEnd) {
          $status = 'ending';
        }
      }

      return $status;
    }


    /**
     * Vrátí aktuální stav kampaně v textové podobě.
     *
     * @param string $status           Aktuální stav
     * @return string[]                Aktuální stav v textové podobě společně s barvou
     */
    public static function getCampaignStatusText($status) {
      $message = [];

      switch ($status) {
        case 'waiting':
          $message['text'] = 'čeká na zahájení';
          $message['color'] = MSG_CSS_WARNING;
          break;

        case 'starting':
          $message['text'] = 'dnes startuje';
          $message['color'] = MSG_CSS_ERROR;
          break;

        case 'running':
          $message['text'] = 'probíhá';
          $message['color'] = MSG_CSS_SUCCESS;
          break;

        case 'ending':
          $message['text'] = 'dnes končí';
          $message['color'] = MSG_CSS_ERROR;
          break;

        default:
          $message['text'] = 'ukončena';
          $message['color'] = MSG_CSS_DEFAULT;
          break;
      }

      return $message;
    }


    /**
     * Ověří, zdali je daný čas ve správném formátu.
     *
     * @param string $time             Testovaný čas
     * @return bool                    TRUE pokud je čas ve správném formátu, jinak FALSE
     */
    private function isTimeValid($time) {
      return preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $time) === 1;
    }


    /**
     * Ověří, zdali zadané datum existuje a odpovídá formátu YYYY-MM-DD.
     *
     * @param string $date             Testované datum
     * @return bool                    TRUE pokud je datum validní, jinak FALSE
     */
    private function isDateValid($date) {
      $valid = false;
      $dateParts = explode(DATE_DELIMETER, $date);

      if (count($dateParts) == 3) {
        $valid = checkdate($dateParts[1], $dateParts[2], $dateParts[0]);
      }

      return $valid;
    }


    /**
     * Zkontroluje uživatelský vstup (atributy třídy), který se bude zapisovat do databáze.
     *
     * @throws UserError
     */
    public function validateData() {
      $this->isNameEmpty();
      $this->isNameTooLong();

      $this->isTicketValid();

      $this->isPhishingEmailEmpty();
      $this->existPhishingEmail();

      $this->isPhishingWebsiteEmpty();
      $this->existPhishingWebsite();

      $this->isOnSubmitActionEmpty();
      $this->existOnSubmitAction();

      $this->isEmptyDateActiveSince();
      $this->isDateActiveSinceValid();

      $this->isEmptyTimeSince();
      $this->isTimeSinceValid();

      $this->isEmptyTimeTo();
      $this->isTimeToValid();

      $this->isEmptyDateActiveTo();
      $this->isDateActiveToValid();

      $this->isDateActiveSinceGreaterThanDateActiveTo();
      $this->isTimeActiveSinceGreaterThanTimeActiveTo();
      $this->isDatetimeActiveSinceDifferentThanDatetimeActiveTo();
      $this->isDatetimeActiveSinceInPast();
      $this->isDatetimeActiveToInPast();
      $this->isDatetimeDiffAtLeastMinutes();

      $this->isNotificationLanguageEmpty();
      $this->existNotificationLanguage();

      $this->isRecipientsEmpty();
      $this->isRecipientsValid();
    }


    /**
     * Ověří, zdali byl vyplněn název kampaně.
     *
     * @throws UserError
     */
    private function isNameEmpty() {
      if (empty($this->name)) {
        throw new UserError('Není vyplněn název kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný název kampaně není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isNameTooLong() {
      if (mb_strlen($this->name) > $this->inputsMaxLengths['name']) {
        throw new UserError('Název kampaně je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je zadané číslo lístku (ticketu) s kampaní validní.
     *
     * @throws UserError
     */
    private function isTicketValid() {
      if (!empty($this->idTicket) && (!is_numeric($this->idTicket) || $this->idTicket < 1)) {
        throw new UserError('Zadané číslo lístku s kampaní není validní.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vybrán rozesílaný podvodný e-mail.
     *
     * @throws UserError
     */
    private function isPhishingEmailEmpty() {
      if (empty($this->idEmail) || !is_numeric($this->idEmail)) {
        throw new UserError('Není vybrán rozesílaný podvodný e-mail.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali vybraný rozesílaný podvodný e-mail existuje.
     *
     * @throws UserError
     */
    private function existPhishingEmail() {
      $model = new PhishingEmailModel();

      if (empty($model->getPhishingEmail($this->idEmail))) {
        throw new UserError('Vybraný rozesílaný podvodný e-mail neexistuje.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byla vybrána podvodná webová stránka.
     *
     * @throws UserError
     */
    private function isPhishingWebsiteEmpty() {
      if (empty($this->idWebsite) || !is_numeric($this->idWebsite)) {
        throw new UserError('Není vybrána podvodná webová stránka.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali vybraná podvodná stránka existuje.
     *
     * @throws UserError
     */
    private function existPhishingWebsite() {
      $model = new PhishingWebsiteModel();

      if (empty($model->getPhishingWebsite($this->idWebsite))) {
        throw new UserError('Vybraná podvodná webová stránka neexistuje.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byla vybrána akce, která se stane po odeslání formuláře na podvodné stránce.
     *
     * @throws UserError
     */
    private function isOnSubmitActionEmpty() {
      if (empty($this->idOnsubmit) || !is_numeric($this->idOnsubmit)) {
        throw new UserError('Není vybrána akce, která se stane po odeslání formuláře.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali vybraná akce, která se stane po odeslání formuláře na podvodné stránce, existuje.
     *
     * @throws UserError
     */
    private function existOnSubmitAction() {
      if (empty($this->getWebsiteAction($this->idOnsubmit))) {
        throw new UserError('Vybraná akce, která se stane po odeslání formuláře, neexistuje.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vyplněn čas, ve kterém dojde k zahájení kampaně.
     *
     * @throws UserError
     */
    private function isEmptyTimeSince() {
      if (empty($this->timeActiveSince)) {
        throw new UserError('Není vyplněn čas zahájení kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je vyplněný čas zahájení kampaně ve správném formátu.
     *
     * @throws UserError
     */
    private function isTimeSinceValid() {
      if (!$this->isTimeValid($this->timeActiveSince)) {
        throw new UserError('Čas zahájení kampaně je v nesprávném formátu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vyplněn čas, ve kterém dojde k ukončení kampaně.
     *
     * @throws UserError
     */
    private function isEmptyTimeTo() {
      if (empty($this->timeActiveTo)) {
        throw new UserError('Není vyplněn čas ukončení kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je vyplněný čas ukončení kampaně ve správném formátu.
     *
     * @throws UserError
     */
    private function isTimeToValid() {
      if (!$this->isTimeValid($this->timeActiveTo)) {
        throw new UserError('Čas ukončení kampaně je v nesprávném formátu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali bylo vyplněno datum zahájení kampaně.
     *
     * @throws UserError
     */
    private function isEmptyDateActiveSince() {
      if (empty($this->dateActiveSince)) {
        throw new UserError('Není vyplněno datum zahájení kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je vyplněné datum zahájení kampaně ve správném formátu.
     *
     * @throws UserError
     */
    private function isDateActiveSinceValid() {
      if (!$this->isDateValid($this->dateActiveSince)) {
        throw new UserError('Datum zahájení kampaně je v nesprávném formátu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali bylo vyplněno datum ukončení kampaně.
     *
     * @throws UserError
     */
    private function isEmptyDateActiveTo() {
      if (empty($this->dateActiveTo)) {
        throw new UserError('Není vyplněno datum ukončení kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je vyplněné datum konce kampaně ve správném formátu.
     *
     * @throws UserError
     */
    private function isDateActiveToValid() {
      if (!$this->isDateValid($this->dateActiveTo)) {
        throw new UserError('Datum ukončení kampaně je v nesprávném formátu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je datum zahájení kampaně dříve než datum ukončení kampaně.
     *
     * @throws UserError
     */
    private function isDateActiveSinceGreaterThanDateActiveTo() {
      if (strtotime($this->dateActiveSince) > strtotime($this->dateActiveTo)) {
        throw new UserError('Datum zahájení kampaně nemůže být později než datum ukončení kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je při stejném datu čas zahájení kampaně dřívější než čas jejího ukončení.
     *
     * @throws UserError
     */
    private function isTimeActiveSinceGreaterThanTimeActiveTo() {
      if ($this->dateActiveSince == $this->dateActiveTo && strtotime($this->dateActiveSince . ' ' . $this->timeActiveSince) > strtotime($this->dateActiveTo . ' ' . $this->timeActiveTo)) {
        throw new UserError('Čas zahájení kampaně nemůže být ve stejném dni později než čas ukončení kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali není datum a čas zahájení kampaně shodné s datem a časem ukončení kampaně.
     *
     * @throws UserError
     */
    private function isDatetimeActiveSinceDifferentThanDatetimeActiveTo() {
      if (strtotime($this->dateActiveSince . ' ' . $this->timeActiveSince) == strtotime($this->dateActiveTo . ' ' . $this->timeActiveTo)) {
        throw new UserError('Datum a čas zahájení kampaně nemůže být stejné jako datum a čas ukončení kampaně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali není datum a čas zahájení kampaně z minulosti.
     *
     * @throws UserError
     */
    private function isDatetimeActiveSinceInPast() {
      $datetimeSinceTimestamp = strtotime($this->dateActiveSince . ' ' . $this->timeActiveSince);

      if (isset($this->dbRecordData['id_campaign'])) {
        $originalDatetime = strtotime($this->dbRecordData['date_active_since'] . ' ' . $this->dbRecordData['time_active_since']);

        if ($datetimeSinceTimestamp < $originalDatetime) {
          throw new UserError('Datum a čas zahájení kampaně nesmí být starší než původně nastavené datum zahájení.', MSG_ERROR);
        }
      }
      else {
        $now = time();

        if ($datetimeSinceTimestamp < $now) {
          throw new UserError('Datum a čas zahájení kampaně nesmí být v minulosti.', MSG_ERROR);
        }
      }
    }


    /**
     * Ověří, zdali není datum a čas ukončení kampaně z minulosti.
     *
     * @throws UserError
     */
    private function isDatetimeActiveToInPast() {
      $datetimeToTimestamp = strtotime($this->dateActiveTo . ' ' . $this->timeActiveTo);

      if (isset($this->dbRecordData['id_campaign'])) {
        $datetimeSinceTimestamp = strtotime($this->dbRecordData['date_active_since'] . ' ' . $this->dbRecordData['time_active_since']);

        if ($datetimeToTimestamp < $datetimeSinceTimestamp) {
          throw new UserError('Datum a čas ukončení kampaně nesmí být dříve nastavené datum zahájení.', MSG_ERROR);
        }
      }
      else {
        $now = time();

        if ($datetimeToTimestamp < $now) {
          throw new UserError('Datum a čas ukončení kampaně nesmí být v minulosti.', MSG_ERROR);
        }
      }
    }


    /**
     * Ověří, zdali je mezi datem a časem zahájení kampaně a datem a časem ukončení kampaně nějaký minimální čas.
     *
     * @throws UserError
     */
    private function isDatetimeDiffAtLeastMinutes($minutes = 10) {
      $datetimeSinceTimestamp = strtotime($this->dateActiveSince . ' ' . $this->timeActiveSince);
      $datetimeToTimestamp = strtotime($this->dateActiveTo . ' ' . $this->timeActiveTo);

      if (($datetimeToTimestamp - $datetimeSinceTimestamp) < $minutes * 60) {
        throw new UserError('Rozdíl mezi časem zahájení a časem ukončení kampaně musí být alespoň ' . $minutes . ' minut.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vybrán jazyk, ve kterém bude odeslána uživatelská notifikace
     * o absolvování cvičného phishingu (resp. ukončení kampaně).
     *
     * @throws UserError
     */
    private function isNotificationLanguageEmpty() {
      if ($this->sendUsersNotification && empty($this->usersNotificationLanguage)) {
        throw new UserError('Není vybrán jazyk uživatelské notifikace o ukončení kampaně!', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali vybraný jazyk uživatelské notifikace o absolvování
     * cvičného phishingu (resp. ukončení kampaně) existuje.
     *
     * @throws UserError
     */
    private function existNotificationLanguage() {
      if ($this->sendUsersNotification && !empty($this->usersNotificationLanguage)) {
        $allowedLanguages = array_keys(NotificationsModel::getUserNotificationLanguages());
        $selectedLanguages = NotificationsModel::parseNotificationLanguages($this->usersNotificationLanguage);

        foreach ($selectedLanguages as $language) {
          if (!in_array($language, $allowedLanguages, true)) {
            throw new UserError('Vybraný jazyk uživatelské notifikace o ukončení kampaně neexistuje!', MSG_ERROR);
          }
        }
      }
    }


    /**
     * Ověří, zdali kampaň obsahuje alespoň jednoho příjemce.
     *
     * @throws UserError
     */
    private function isRecipientsEmpty() {
      if (empty($this->recipients)) {
        throw new UserError('Není vyplněn seznam příjemců.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali jsou e-maily všech zadaných příjemců ve správném formátu, zdali e-maily vedou na povolenou doménu
     * a jestli má uživatel povolení k tomu, aby danému uživateli e-mail rozeslal.
     *
     * @throws UserError
     */
    private function isRecipientsValid() {
      //$allowedDomains = PermissionsModel::getUserEmailRestrictions();
      //$allowedDomainsArray = explode(LDAP_GROUPS_DELIMITER, $allowedDomains);

      foreach ($this->recipients as $email) {
        if (empty($email)) continue;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          throw new UserError('E-mail "' . $email . '" zadaný v seznamu příjemců je v nesprávném formátu.', MSG_ERROR);
        }

        if (!PhishingEmailModel::isEmailInAllowedDomains($email)) {
          throw new UserError('E-mail "' . $email . '" zadaný v seznamu příjemců vede na nepovolenou doménu.', MSG_ERROR);
        }

        /*
        if (!empty($allowedDomains)) {
          if ($this->strposa('@' . getEmailPart($email, 'domain'), $allowedDomainsArray) === false) {
            throw new UserError('Záznam "' . $email . '" zadaný v seznamu příjemců vede na doménu, která není v rámci oprávnění povolena.', MSG_ERROR);
          }

          // Kontrola e-mailu vůči LDAP.
          $ldap = new LdapModel();
          $username = getEmailPart($email, 'username');

          // Získání e-mailu uživatele z LDAP.
          $ldapEmail = $ldap->getEmailByUsername($username);

          // Ověření, jaký e-mail je v LDAPu oproti restrikcím.
          if ($this->strposa('@' . getEmailPart($ldapEmail, 'domain'), $allowedDomainsArray) === false) {
            throw new UserError('Záznam "' . $email . '" zadaný v seznamu příjemců vede na doménu, která není v rámci oprávnění povolena.', MSG_ERROR);
          }
        }
        */
      }
    }
  }