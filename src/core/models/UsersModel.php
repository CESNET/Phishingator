<?php
  /**
   * Třída sloužící k získávání informací o konkrétních uživatelích,
   * k manuálnímu přidávání nových uživatelů, k úpravě těch existujících a dalším
   * souvisejícím operacím.
   *
   * @author Martin Šebela
   */
  class UsersModel extends FormModel {
    /**
     * @var int         ID skupiny, do které bude uživatel zařazen.
     */
    public $idUserGroup;

    /**
     * @var string      Unikátní řetězec, na základě kterého je uživatel identifikován na podvodných stránkách.
     */
    public $url;

    /**
     * @var string      Uživatelské jméno.
     */
    public $username;

    /**
     * @var string      E-mail uživatele.
     */
    public $email;

    /**
     * @var int|null    Informace o tom, zdali chce uživatel dobrovolně přijímat cvičné e-maily (hodnota 1), jinak NULL.
     */
    public $recieveEmail;

    /**
     * @var int|null    Maximální počet e-mailů, které chce uživatel obdržet (pokud je dobrovolníkem) nebo NULL.
     */
    public $emailLimit;


    /**
     * Nastaví výchozí hodnoty některých atributů pro nového uživatele.
     */
    public function __construct() {
      $this->recieveEmail = NULL;
      $this->emailLimit = NULL;
    }


    /**
     * Připraví nového uživatele v závislosti na vyplněných datech a vrátí ho formou pole.
     *
     * @return array                   Pole obsahující data o uživateli.
     */
    private function makeUser() {
      $user = null;

      $ldap = new LdapModel();

      // Získání preferovaného e-mailu (pro případ aliasů) uživatele z LDAP.
      $userEmail = $ldap->getPreferredEmailByEmail($this->email);

      if (!empty($userEmail)) {
        $user['email'] = $userEmail;

        // Přepsání uživatelem zadaného e-mailu za preferovaný e-mail z LDAP.
        $this->email = $userEmail;

        $user['username'] = $ldap->getUsernameByEmail($user['email']);
        $user['departments'] = $ldap->getGroupsByUsername($user['username'], false);

        $user['id_user_group'] = $this->idUserGroup;

        // Výjimka pro případ, kdy se registruje nový uživatel - v takovém případě
        // je do databáze nutné zapsat nikoliv NULL hodnoty, ale hodnoty nastavené
        // v configu aplikace během registrace uživatele.
        if ($this->recieveEmail !== NULL && $this->emailLimit !== NULL) {
          $user['recieve_email'] = $this->recieveEmail;
          $user['email_limit'] = $this->emailLimit;
        }
      }

      $ldap->close();

      return $user;
    }


    /**
     * Uloží do instance a zároveň vrátí (z databáze) informace o uživateli na základě jeho ID.
     *
     * @param int $id                  ID uživatele
     * @param bool $getFullname        TRUE, pokud se mají z LDAP vrátit další data (jméno a příjmení) o uživateli, jinak FALSE (výchozí)
     * @return array                   Pole s informacemi o uživateli
     */
    public function getUser($id, $getFullname = false) {
      $user = Database::querySingle('
              SELECT `id_user`, `id_user_group`, `url`, `username`, `email`, `departments`
              FROM `phg_users`
              WHERE `id_user` = ?
              AND `visible` = 1
      ', $id);

      if ($getFullname && isset($user['username'])) {
        // Získání jména a příjmení uživatele z LDAP.
        $user = array_merge($user, UsersModel::getUserFullname($user['username']));
      }

      $this->dbRecordData = $user;

      return $user;
    }


    /**
     * Vrátí informace o uživateli na základě jeho uživatelského jména.
     *
     * @param string $username         Uživatelské jméno.
     * @return mixed                   Pole s informacemi o uživateli.
     */
    public static function getUserByUsername($username) {
      return Database::querySingle('
              SELECT `id_user`, `id_user_group`, `username`, `email`, `inactive`
              FROM `phg_users`
              WHERE `username` = ?
              AND `visible` = 1
      ', $username);
    }


    /**
     * Vrátí informace o uživateli na základě jeho e-mailu.
     *
     * @param string $email            E-mail uživatele.
     * @return mixed                   Pole s informacemi o uživateli.
     */
    public static function getUserByEmail($email) {
      return Database::querySingle('
              SELECT `id_user`, phg_users.id_user_group, `url`, `username`, `email`, `departments`, `recieve_email`, `email_limit`, `value` AS `role`
              FROM `phg_users`
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              WHERE `email` = ?
              AND phg_users.visible = 1
      ', $email);
    }


    /**
     * Vrátí informace o uživateli na základě jeho identifikátoru na podvodných stránkách.
     *
     * @param string $url              Identifikátor uživatele na podvodných stránkách.
     * @return mixed                   Pole s informacemi o uživateli.
     */
    public static function getUserByURL($url) {
      return Database::querySingle('
              SELECT `id_user`, `id_user_group`, `url`, `username`, `email`, `departments`
              FROM `phg_users`
              WHERE `url` = ?
              AND `visible` = 1
      ', $url);
    }


    /**
     * Vrátí ID uživatele z databáze, popř. založí a vrátí jeho ID.
     *
     * @param string $email            E-mail uživatele
     * @return int|null                ID uživatele, nebo NULL
     * @throws UserError
     */
    public static function getOrCreateUserByEmail($email) {
      // Ověření, zdali již uživatel v databázi existuje.
      $user = self::getUserByEmail($email);
      $idUser = $user['id_user'] ?? null;

      // Pokud se uživatele nepodařilo v databázi podle e-mailu nalézt, je možné, že jde o jeho e-mailový alias.
      if ($idUser === null) {
        $ldap = new LdapModel();

        // Získání preferovaného e-mailu uživatele z LDAP na základě předaného e-mailu (např. e-mailového aliasu).
        $prefferedEmail = $ldap->getPreferredEmailByEmail($email);

        if (!empty($prefferedEmail)) {
          $user = UsersModel::getUserByEmail($prefferedEmail);
          $idUser = $user['id_user'] ?? null;

          // Pokud uživatel nebyl v databázi nalezen ani na základě preferovaného e-mailu, vytvořit ho.
          if ($idUser === null) {
            $newUser = new UsersModel();

            $newUser->email = $prefferedEmail;
            $newUser->username = $ldap->getUsernameByEmail($prefferedEmail);

            // Pokud se podařilo z LDAPu k preferovanému e-mailu získat uživatelské jméno,
            // dojde k registraci nového uživatele v rámci založení kampaně.
            if (!empty($newUser->username)) {
              Logger::info('Registering a new user when creating a phishing campaign.', $email);

              $newUser->dbTableName = 'phg_users';

              $newUser->idUserGroup = NEW_USER_BY_CAMPAIGN_DEFAULT_GROUP_ID;
              $newUser->recieveEmail = NEW_USER_BY_CAMPAIGN_PARTICIPATION;
              $newUser->emailLimit = NEW_USER_BY_CAMPAIGN_PARTICIPATION_EMAILS_LIMIT;

              $newUser->insertUser();

              $idUser = Database::getLastInsertId();
            }
            else {
              Logger::error('Failed to retrieve a new users username from LDAP while creating a phishing campaign.', $email);
            }
          }
        }
        else {
          Logger::error('Failed to retrieve a new users email from LDAP while creating a phishing campaign.', $email);
        }
      }

      return $idUser;
    }


    /**
     * Projde data a upraví uživatelská jména do podoby určené pro výpis do GUI, a to dle konfigurace.
     *
     * @param array $data              Data, ve kterých bude docházet k nalezení a případné úpravě uživatelského jména
     * @return mixed                   Data obsahující uživatelské jméno v podobě dle konfigurace
     */
    public static function setUsernamesByConfig($data) {
      if (USER_PREFER_EMAIL) {
        if (is_array($data[0])) {
          foreach ($data as $key => $record) {
            $data[$key] = self::replaceUsernameWithEmailUsername($record);
          }
        }
        else {
          $data = self::replaceUsernameWithEmailUsername($data);
        }
      }

      return $data;
    }


    /**
     * Nahradí v datech uživatelské jméno za uživatelské jméno z e-mailu (bez doménové části).
     *
     * @param array $record            Data obsahující uživatelské jméno a e-mail
     * @return mixed                   Původní data s případně pozměněným uživatelským jménem
     */
    private static function replaceUsernameWithEmailUsername($record) {
      if (isset($record['username']) && isset($record['email'])) {
        $emailUsername = get_email_part($record['email'], 'username');

        if ($emailUsername != null) {
          $record['username'] = $emailUsername;
        }
      }

      return $record;
    }


    /**
     * Vrátí maximální počet cvičných podvodných e-mailů, které chce uživatel obdržet a informaci o tom, zdali
     * je uživatel zapojen do dobrovolného přijímání těchto e-mailů.
     *
     * @param int $id                  ID uživatele
     * @return mixed                   Pole s informacemi
     */
    public static function getUserEmailLimit($id) {
      return Database::querySingle('
              SELECT `recieve_email`, `email_limit`
              FROM `phg_users`
              WHERE `id_user` = ?
              AND `visible` = 1
      ', $id);
    }


    /**
     * Vrátí oprávnění konkrétního uživatele.
     *
     * @param int $id                  ID uživatele
     * @return int|null                Pole s informacemi
     */
    public static function getUserRole($id) {
      $data = Database::querySingle('
              SELECT `value`
              FROM `phg_users`
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              WHERE phg_users.id_user = ?
              AND phg_users.visible = 1
      ', $id);

      if ($data != null) {
        return $data['value'];
      }

      return null;
    }


    /**
     * Vrátí počet všech uživatelů s možností filtrování.
     *
     * @param int|null $onlyVolunteers Pokud 1, budou vypisováni pouze dobrovolně registrovaní (nepovinný parametr).
     * @param int|null $groupFilter    Filtrování záznamů na základě ID konkrétní skupiny (nepovinný parametr).
     * @param string|null $emailFilter Filtrování záznamů podle e-mailu uživatele (nepovinný parametr).
     * @param int|null $permFilter     Filtrování záznamů podle oprávnění uživatele (nepovinný parametr).
     * @return mixed                   Počet všech uživatelů vyhovujících zvoleným podmínkám.
     */
    public static function getUsersCount($onlyVolunteers = null, $groupFilter = null, $emailFilter = null, $permFilter = null) {
      $query = 'SELECT COUNT(*) FROM `phg_users` WHERE `visible` = 1';
      $args = [];

      /* Pokud je specifikovaný filtr na oprávnění (je třeba změnit celý dotaz kvůli klíči v jiné
         tabulce - tzn. tato podmínka musí být jako první před ostatními filtry). */
      if ($permFilter) {
        $query = '
                SELECT COUNT(*)
                FROM `phg_users`
                JOIN `phg_users_groups`
                ON phg_users.id_user_group = phg_users_groups.id_user_group
                AND `role` = ?
                WHERE phg_users.visible = 1';
        $args[] = $permFilter;
      }

      /* Pokud mají byl vybráni pouze dobrovolníci. */
      if ($onlyVolunteers) {
        $query .= ' AND `recieve_email` = 1';
      }

      /* Pokud je specifikována skupina. */
      if ($groupFilter) {
        $query .= ' AND phg_users.id_user_group = ?';
        $args[] = $groupFilter;
      }

      /* Pokud je specifikovaný filtr na e-mail. */
      if ($emailFilter) {
        $query .= ' AND `email` LIKE ?';
        $args[] = '%' . $emailFilter . '%';
      }

      return Database::queryCount($query, $args);
    }


    /**
     * Vrátí podrobný seznam všech uživatelů s možností filtrování.
     *
     * @param int|null $from           Od jakého záznamu (pořadí) vypisovat uživatele (nepovinný parametr).
     * @param int|null $countRecords   Maximální počet záznamů (nepovinný parametr).
     * @param int|null $onlyVolunteers Pokud 1, budou vypisováni pouze dobrovolně registrovaní (nepovinný parametr).
     * @param int|null $groupFilter    Filtrování záznamů na základě ID konkrétní skupiny (nepovinný parametr).
     * @param string|null $emailFilter Filtrování záznamů podle e-mailu uživatele (nepovinný parametr).
     * @param int|null $permFilter     Filtrování záznamů podle oprávnění uživatele (nepovinný parametr).
     * @return mixed                   Pole uživatelů.
     */
    public function getUsers($from = null, $countRecords = null, $onlyVolunteers = null, $groupFilter = null, $emailFilter = null, $permFilter = null) {
      $query = '
              SELECT phg_users.id_user, phg_users.id_by_user, phg_users.id_user_group, `username`, `email`, `recieve_email`, `email_limit`, phg_users.date_added,
              phg_users_groups.name AS `name`,
              `id_user_role`, `value`,
              DATE_FORMAT(phg_users.date_added, "%e. %c. %Y %k:%i") AS `date_added_formatted`,
              MAX(login_datetime) AS `date_login`, DATE_FORMAT(MAX(login_datetime), "%e. %c. %Y %k:%i") AS `date_login_formatted`,
              MAX(date_participation) AS `date_participation`, DATE_FORMAT(MAX(date_participation), "%e. %c. %Y") AS `date_participation_formatted`
              FROM `phg_users`
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              LEFT JOIN `phg_users_login_log`
              ON phg_users.id_user = phg_users_login_log.id_user
              LEFT JOIN `phg_users_participation_log`
              ON phg_users.id_user = phg_users_participation_log.id_user
              WHERE phg_users.visible = 1
              ' . (($onlyVolunteers) ? 'AND `recieve_email` = 1' : '') . '
              ' . (($groupFilter) ? 'AND phg_users.id_user_group = ?' : '') . '
              ' . (($emailFilter) ? 'AND `email` LIKE ?' : '') . '
              ' . (($permFilter) ? 'AND `id_user_role` = ?' : '') . '
              GROUP BY phg_users.id_user
              ORDER BY phg_users.id_user DESC';
      $args = [];

      /* Pokud je specifikována skupina. */
      if ($groupFilter) {
        $args[] = $groupFilter;
      }

      /* Pokud je specifikovaný filtr na e-mail. */
      if ($emailFilter) {
        $args[] = '%' . $emailFilter . '%';
      }

      /* Pokud je specifikovaný filtr na oprávnění. */
      if ($permFilter) {
        $args[] = $permFilter;
      }

      /* Pokud je specifikován limit vypisovaných záznamů. */
      if (!is_null($from) && !is_null($countRecords)) {
        $query .= ' LIMIT ?, ?';

        $args[] = $from;
        $args[] = $countRecords;
      }

      $result = Database::queryMulti($query, $args);

      // Zjištění dalších podrobností o každém uživateli.
      if ($result != null) {
        $ldap = new LdapModel();

        foreach ($result as $key => $user) {
          // Zjištění jména a příjmení uživatele z LDAP.
          $result[$key]['person_name'] = $ldap->getFullnameByUsername($user['username']);

          // Informace o tom, zdali se uživatel registroval dobrovolně nebo administrátorem/správcem testů v rámci kampaně.
          $result[$key]['voluntary'] = ($user['id_by_user'] == null) ? 1 : 0;
          $result[$key]['voluntary_registration'] = ($user['id_by_user'] == null) ? 'dobrovolně' : 'z kampaně';
          $result[$key]['voluntary_registration_color'] = ($user['id_by_user'] == null) ? MSG_CSS_SUCCESS : MSG_CSS_DEFAULT;

          // Informace o posledním přihlášení uživatele.
          $result[$key]['date_login_formatted'] = (!is_null($user['date_login'])) ? $user['date_login_formatted'] : 'zatím nikdy';

          // Zjištění barvy dle oprávnění uživatele.
          $result[$key]['group_color'] = UserGroupsModel::getColorGroupRole($user['value']);

          // Zjištění počtu přijatých cvičných phishingů, které uživatel obdržel.
          $result[$key]['recieved_emails_count'] = RecievedEmailModel::getCountOfRecievedPhishingEmails($user['id_user']);

          // Informace o dobrovolnosti.
          $result[$key]['recieve_email_text'] = $user['recieve_email'] ? 'ano' : 'ne';
          $result[$key]['recieve_email_color'] = $user['recieve_email'] ? MSG_CSS_SUCCESS : MSG_CSS_DEFAULT;

          // Zjištění úspěšnosti při odhalování phishingu včetně barevného zvýraznění.
          $result[$key]['success_rate'] = ($result[$key]['recieved_emails_count'] > 0) ? StatsModel::getUserSuccessRate($user['id_user']) : 0;
          $result[$key]['success_rate_color'] = StatsModel::getUserSuccessRateColor(($result[$key]['recieved_emails_count'] > 0) ? $result[$key]['success_rate'] : null);

          // Informace o maximálním limitu cvičných e-mailů.
          $result[$key]['email_limit'] = (!is_null($user['email_limit'])) ? $user['email_limit'] : 0;
          $result[$key]['email_limit_formatted'] = (!is_null($user['email_limit'])) ? $user['email_limit'] : 'žádný';
        }

        $ldap->close();
      }

      return $result;
    }


    /**
     * Vrátí detailní informace o konkrétním uživateli.
     *
     * @param int $id                  ID uživatele
     * @return mixed                   Pole s informacemi
     */
    public static function getUserDetail($id) {
      $user = Database::querySingle('
              SELECT phg_users.id_user, phg_users.id_by_user, `recieve_email`, `email_limit`,
              DATE_FORMAT(phg_users.date_added, "%e. %c. %Y %k:%i") AS `date_added`,
              DATE_FORMAT(MAX(login_datetime), "%e. %c. %Y %k:%i") AS `date_login`,
              DATE_FORMAT(MAX(date_participation), "%e. %c. %Y") AS `date_participation`
              FROM `phg_users`
              LEFT JOIN `phg_users_login_log`
              ON phg_users.id_user = phg_users_login_log.id_user
              LEFT JOIN `phg_users_participation_log`
              ON phg_users.id_user = phg_users_participation_log.id_user
              WHERE phg_users.id_user = ?
              AND phg_users.visible = 1
              GROUP BY phg_users.id_user
              ORDER BY phg_users.id_user DESC', $id);

      if ($user != null) {
        // Zjištění, zdali se uživatel registroval dobrovolně nebo administrátorem/správcem testů v rámci kampaně.
        $user['voluntary_registration'] = ($user['id_by_user'] == null) ? 'dobrovolně' : 'z kampaně';
        $user['voluntary_registration_color'] = ($user['id_by_user'] == null) ? MSG_CSS_SUCCESS : MSG_CSS_DEFAULT;

        // Informace o posledním přihlášení uživatele.
        $user['date_login'] = (!is_null($user['date_login'])) ? $user['date_login'] : 'zatím nikdy';

        // Zjištění počtu přijatých cvičných phishingů, které uživatel obdržel.
        $user['recieved_emails_count'] = RecievedEmailModel::getCountOfRecievedPhishingEmails($user['id_user']);

        // Informace o dobrovolnosti.
        $user['recieve_email_text'] = $user['recieve_email'] ? 'ano' : 'ne';
        $user['recieve_email_color'] = $user['recieve_email'] ? MSG_CSS_SUCCESS : MSG_CSS_DEFAULT;

        // Zjištění úspěšnosti při odhalování phishingu včetně barevného zvýraznění.
        $user['success_rate'] = ($user['recieved_emails_count'] > 0) ? StatsModel::getUserSuccessRate($user['id_user']) : 0;
        $user['success_rate_color'] = StatsModel::getUserSuccessRateColor(($user['recieved_emails_count'] > 0) ? $user['success_rate'] : null);

        // Informace o maximálním limitu cvičných e-mailů.
        $user['email_limit'] = (!is_null($user['email_limit'])) ? $user['email_limit'] : 'žádný';
      }

      return $user;
    }


    /**
     * Vrátí jméno a příjmení uživatele dle jeho uživatelského jména.
     *
     * @param string $username         Uživatelské jméno
     * @param LdapModel $ldap          Instance třídy (nepovinné)
     * @return array                   Pole obsahující jméno a příjmení uživatele
     */
    public static function getUserFullname($username, $ldap = null) {
      $ldapConnected = false;
      $user = [];

      // Pokud zatím není LDAP spojení navázáno...
      if ($ldap == null) {
        $ldap = new LdapModel();
        $ldapConnected = true;
      }

      $user['firstname'] = $ldap->getFirstnameByUsername($username) ?? '';
      $user['surname'] = $ldap->getSurnameByUsername($username) ?? '';
      $user['fullname'] = $ldap->getFullnameByUsername($username) ?? '';

      if (empty($user['fullname'])) {
        $user['fullname'] = $user['firstname'] . ' ' . $user['surname'];
      }

      if ($ldapConnected) {
        $ldap->close();
      }

      return $user;
    }


    /**
     * Vrátí počet všech aktivních, nesmazaných uživatelů (případně do konkrétního roku).
     *
     * @param int $year                Maximální rok, do kterého se bude počet uživatelů zjišťovat [nepovinné]
     * @return mixed                   Počet aktivních uživatelů
     */
    public static function getCountOfActiveUsers($year = []) {
      $yearQuery = (!is_array($year) && is_numeric($year)) ? 'AND YEAR(`date_added`) <= ?' : '';

      return Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_users`
              WHERE `visible` = 1
              ' . $yearQuery
      , $year);
    }


    /**
     * Vrátí počet všech dobrovolníků – uživatelů, kteří jsou přihlášeni
     * k odebírání cvičných phishingových zpráv (případně do konkrétního roku).
     *
     * @param int $year                Maximální rok, do kterého se bude počet dobrovolníků zjišťovat [nepovinné]
     * @return mixed                   Počet dobrovolníků
     */
    public static function getCountOfVolunteers($year = null) {
      if ($year != null && is_numeric($year)) {
        $volunteers = Database::queryMulti('
                SELECT DISTINCT email
                FROM `phg_users`
                LEFT JOIN `phg_users_participation_log`
                ON phg_users.id_user = phg_users_participation_log.id_user
                WHERE
                (`recieve_email` = 1 AND `inactive` = 0 AND `visible` = 1 AND `logged` = 1 AND YEAR(`date_participation`) <= ? AND YEAR(`date_added`) <= ?)
                OR
                (`recieve_email` = 1 AND `inactive` = 0 AND `visible` = 1 AND YEAR(`date_added`) <= ?);
        ', [$year, $year, $year]);

        $countVolunteers = count($volunteers);
      }
      else {
        $countVolunteers = Database::queryCount('SELECT COUNT(*) FROM `phg_users` WHERE `recieve_email` = 1 AND `inactive` = 0 AND `visible` = 1');
      }

      return $countVolunteers;
    }


    /**
     * Vrátí seznam všech uživatelů se zvoleným oprávněním.
     *
     * @param int $permission          Oprávnění, které mají mít vybraní uživatelé (hodnota 0/1/2).
     * @param bool $onlyUsernamesArray Nepovinný parametr - při TRUE vrátí pouze pole s uživatelskými jmény, která
     *                                  požadovanému oprávnění vyhovují, při FALSE (výchozí) vrátí i více podrobností.
     * @return array                   Pole obsahující informace o uživatelích, kteří mají požadované oprávnění.
     */
    public static function getUsersByPermission($permission, $onlyUsernamesArray = false) {
      $data = Database::queryMulti('
              SELECT `id_user`, `username`, `email`
              FROM `phg_users`
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              JOIN `phg_users_roles`
              ON phg_users_groups.role = phg_users_roles.id_user_role
              WHERE phg_users.visible = 1
              AND `value` = ?
      ', $permission);

      if ($onlyUsernamesArray) {
        $usernames = [];

        foreach ($data as $record) {
          $usernames[] = $record['email'];
        }

        return $usernames;
      }

      return $data;
    }


    /**
     * Vrátí pole uživatelů, kteří jsou ve zvolené skupině.
     *
     * @param int $idGroup             ID skupiny
     * @return mixed                   Pole uživatelů
     */
    public static function getUsersByGroup($idGroup) {
      return Database::queryMulti('
              SELECT `id_user`, `id_user_group`, `url`, `username`, `email`
              FROM `phg_users`
              WHERE `id_user_group` = ?
              AND `visible` = 1
      ', $idGroup);
    }


    /**
     * Změní zvolenému uživateli skupinu na jinou.
     *
     * @param int $idUser              ID uživatele
     * @param int $idGroup             ID skupiny
     * @return int                     Výsledek změny
     */
    public static function changeUserGroup($idUser, $idGroup) {
      Logger::info(
        'Change the user group of a user.',
        ['id_user' => $idUser, 'id_group' => $idGroup]
      );

      return Database::update(
        'phg_users',
        ['id_user_group' => $idGroup],
        'WHERE `id_user` = ? AND `visible` = 1',
        $idUser
      );
    }


    /**
     * Vrátí informaci o tom, zdali je zvolený řetězec používaný jiným uživatelem.
     *
     * @param string $url              Řetězec k identifikaci uživatele na podvodných stránkách.
     * @return mixed                   1 pokud je již používán, jinak 0.
     */
    private function existUserUrl($url) {
      return Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_users`
              WHERE `url` = ?
      ', $url);
    }


    /**
     * Vygeneruje náhodný řetězec.
     *
     * @return string                  Vygenerovaný náhodný řetězec
     */
    private function generateRandomString() {
      return bin2hex(openssl_random_pseudo_bytes(USER_ID_WEBSITE_LENGTH / 2));
    }


    /**
     * Vrátí unikátní náhodný řetězec, který slouží k identifikaci uživatele na podvodných stránkách
     * a žádný jiný uživatel jej zatím nevyužívá.
     *
     * @return string                  Vygenerovaný náhodný řetězec
     */
    public function generateUserUrl() {
      $this->url = $this->generateRandomString();

      while ($this->existUserUrl($this->url) > 0) {
        $this->url = $this->generateRandomString();
      }

      return $this->url;
    }


    /**
     * Vloží do databáze nového uživatele.
     *
     * @return bool                    TRUE, pokud došlo k úspěšnému vložení uživatele do databáze
     * @throws UserError
     */
    public function insertUser() {
      $user = $this->makeUser();

      // Pokud se podařilo získat informace o uživateli z LDAP, vytvořit záznam v databázi.
      if ($user !== null) {
        $this->isEmailUnique();

        $user['url'] = $this->generateUserUrl();
        $user['id_by_user'] = PermissionsModel::getUserId();
        $user['date_added'] = date('Y-m-d H:i:s');

        Logger::info('New user added.', $user);

        Database::insert($this->dbTableName, $user);
      }
      else {
        Logger::error('Failed to retrieve user data from LDAP during user creation.', $user);

        throw new UserError('Žádný uživatel s tímto e-mailem neexistuje.', MSG_ERROR);
      }

      return true;
    }


    /**
     * Upraví zvoleného uživatele.
     *
     * @param int $id                  ID uživatele
     * @throws UserError
     */
    public function updateUser($id) {
      $user = $this->makeUser();

      $this->isEmailUnique($id);

      Logger::info('User modified.', $user);

      Database::update(
        $this->dbTableName,
        $user,
        'WHERE `id_user` = ? AND `visible` = 1',
        $id
      );
    }


    /**
     * Aktualizuje e-mail a členství ve skupině u všech aktivních uživatelů v databázi na základě dat v LDAP.
     * Pokud se e-mail v LDAP nepodaří dohledat, dojde k automatické deaktivaci uživatele v databázi.
     */
    public static function synchronizeUsers() {
      $ldapModel = new LdapModel(false);

      if ($ldapModel->connect()) {
        // Získání seznamu všech aktivních uživatelů.
        $users = Database::queryMulti('SELECT `id_user`, `username`, `email`, `departments` FROM `phg_users` WHERE `inactive` = 0 AND `visible` = 1');

        foreach ($users as $user) {
          // Zjištění aktuálního e-mailu uživatele z LDAP.
          $ldapEmail = $ldapModel->getEmailByUsername($user['username']);

          // Pokud je e-mail z LDAP prázdný, pak již uživatel není v organizaci, a je možné ho ve Phishingatoru označit za neaktivního.
          if (empty($ldapEmail)) {
            Database::update(
              'phg_users', ['inactive' => 1], 'WHERE `id_user` = ?', $user['id_user']
            );

            Logger::info(
              'Deactivated user account based on the current data in LDAP.',
              [$user['id_user'], $user['email'], $ldapEmail]
            );
          }
          else {
            // Pokud se e-mail z LDAP neshoduje s tím, který je uložen ve Phishingatoru, provést aktualizaci.
            if ($user['email'] != $ldapEmail) {
              Database::update(
                'phg_users', ['email' => $ldapEmail], 'WHERE `id_user` = ?', $user['id_user']
              );

              Logger::info(
                'Updated the users email based on the current data in LDAP.',
                [$user['id_user'], $user['email'], $ldapEmail]
              );
            }

            // Zjištění aktuálního členství uživatele ve skupinách z LDAP.
            $ldapGroups = $ldapModel->getGroupsByUsername($user['username'], false);

            // Pokud se členství ve skupině z LDAP neshoduje s tím, které je uloženo ve Phishingatoru, provést aktualizaci.
            if ($user['departments'] != $ldapGroups) {
              Database::update(
                'phg_users', ['departments' => $ldapGroups], 'WHERE `id_user` = ?', $user['id_user']
              );

              Logger::info(
                'Updated the users group based on the current data in LDAP.',
                [$user['id_user'], $user['departments'], $ldapGroups]
              );
            }
          }
        }

        $ldapModel->close();
      }
    }


    /**
     * Odstraní (resp. deaktivuje) uživatele z databáze.
     *
     * @param int $id                  ID uživatele
     * @throws UserError
     */
    public function deleteUser($id) {
      if ($id == PermissionsModel::getUserId()) {
        throw new UserError('Uživatel nemůže smazat sám sebe.', MSG_ERROR);
      }

      $result = Database::update(
        'phg_users',
        ['visible' => 0],
        'WHERE `id_user` = ? AND `visible` = 1',
        $id
      );

      if ($result == 0) {
        Logger::warning('Attempt to delete a non-existent user.', $id);

        throw new UserError('Záznam vybraný ke smazání neexistuje.', MSG_ERROR);
      }

      Logger::info('User deleted.', $id);
    }


    /**
     * Zkontroluje uživatelský vstup (atributy třídy), který se bude zapisovat do databáze.
     *
     * @throws UserError
     */
    public function validateData() {
      $this->isEmailEmpty();
      $this->isEmailTooLong();
      $this->isEmailValid();
      $this->isEmailInAllowedDomains();

      $this->isGroupEmpty();
      $this->existsGroup();
    }


    /**
     * Ověří, zdali byl vyplněn e-mail uživatele.
     *
     * @throws UserError
     */
    private function isEmailEmpty() {
      if (empty($this->email)) {
        throw new UserError('Není vyplněn e-mail uživatele.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný e-mail uživatele není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isEmailTooLong() {
      if (mb_strlen($this->email) > $this->inputsMaxLengths['email']) {
        throw new UserError('E-mail uživatele je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je zadaný e-mail uživatele validní.
     *
     * @throws UserError
     */
    private function isEmailValid() {
      if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
        throw new UserError('E-mail uživatele je v nesprávném formátu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný e-mail uživatele vede na některou z povolených domén.
     *
     * @throws UserError
     */
    private function isEmailInAllowedDomains() {
      if (!PhishingEmailModel::isEmailInAllowedDomains($this->email)) {
        throw new UserError('E-mail uživatele vede na nepovolenou doménu.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný e-mail již v databázi Phishingatoru nepoužívá
     * jiný aktivní uživatel (tzn. jestli je e-mail unikátní).
     *
     * @param int|null $idUser         ID uživatele, u kterého se e-mail testuje (nepovinné)
     * @throws UserError
     */
    private function isEmailUnique($idUser = null) {
      $ldap = new LdapModel();

      $username = $ldap->getUsernameByEmail($this->email);
      $user = $this->getUserByUsername($username);

      $ldap->close();

      // V případě, že byl v databázi nalezen aktivní uživatel se stejným uživatelským jménem
      // a nejedná se o uživatele, u kterého se e-mail testuje, zobrazit chybovou hlášku.
      if ((!empty($user) && $user['inactive'] == 0) && ($idUser != $user['id_user'] || $idUser == null)) {
        throw new UserError('Toto uživatelské jméno již používá jiný uživatel.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je vybrána skupina, do které bude uživatel spadat.
     *
     * @throws UserError
     */
    private function isGroupEmpty() {
      if (empty($this->idUserGroup) || !is_numeric($this->idUserGroup)) {
        throw new UserError('Není vybrána skupina, pod kterou bude uživatel spadat.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali existuje vybraná skupina, do které bude uživatel spadat.
     *
     * @throws UserError
     */
    private function existsGroup() {
      $userGroupsModel = new UserGroupsModel();

      if (empty($userGroupsModel->getUserGroup($this->idUserGroup))) {
        throw new UserError('Vybraná skupina neexistuje.', MSG_ERROR);
      }
    }
  }
