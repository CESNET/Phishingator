<?php
  /**
   * Třída řešící přihlašování, registraci, oprávnění uživatelů v systému a získávání informací
   * o přihlášeném uživateli.
   *
   * @author Martin Šebela
   */
  class PermissionsModel {
    /**
     * Registruje do Phishingatoru nového uživatele na základě jeho identity získané z SSO.
     *
     * @param string $identity         Identita uživatele
     * @return bool                    TRUE, pokud se registrace nového uživatele podaří, jinak FALSE
     */
    private function register($identity) {
      $registrated = false;
      $user = new UsersModel();

      // Identita (e-mail) získaná z SSO.
      if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
        Logger::info('Voluntary new user registration.', $identity);

        $user->dbTableName = 'phg_users';

        $user->email = $identity;
        $user->idUserGroup = NEW_USER_DEFAULT_GROUP_ID;
        $user->recieveEmail = NEW_USER_PARTICIPATION;
        $user->emailLimit = NEW_USER_PARTICIPATION_EMAILS_LIMIT;

        try {
          $registrated = $user->insertUser();

          if ($registrated) {
            $this->login($identity);
          }
        }
        catch (Exception $e) {
          $registrated = false;
        }
      }

      return $registrated;
    }


    /**
     * Vrátí primární identitu (při existenci více identit) uživatele z SSO,
     * na základě které bude uživatel identifikován.
     *
     * @param string $identity         Identita/identity uživatele získané z SSO
     * @return string|null             Identita, kterou bude Phishingator používat pro identifikaci daného uživatele
     */
    private function getUserIdentity($identity) {
      $primaryIdentity = null;

      $serverIdentities = explode(';', $identity);
      $externalIdentities = explode(',', $_SERVER['OIDC_CLAIM_voperson_external_id'] ?? '');
      $identities = array_merge($serverIdentities, $externalIdentities);

      // U uživatele s více identitami použít tu, která už je evidována jako primární v databázi Phishingatoru z LDAP.
      foreach ($identities as $email) {
        $user = UsersModel::getUserByEmail($email);

        if (!empty($user)) {
          $primaryIdentity = $email;
          break;
        }
      }

      if ($primaryIdentity == null) {
        // Pokud se nepodařilo v databázi primární identitu uživatele nalézt, ověřit v LDAP, zdali nejde o e-mailový alias.
        $ldap = new LdapModel();

        foreach ($identities as $email) {
          $ldapPreferredEmail = $ldap->getPreferredEmailByEmail($email);

          if (!empty($ldapPreferredEmail)) {
            $primaryIdentity = $ldapPreferredEmail;
            break;
          }
        }

        $ldap->close();

        // Pokud se identitu uživatele nepodařilo v databázi, ani v LDAP dohledat, použít jako primární tu první z nich.
        if ($primaryIdentity == null && isset($identities[0]) && filter_var($identities[0], FILTER_VALIDATE_EMAIL)) {
          $primaryIdentity = $identities[0];
        }
      }

      return $primaryIdentity;
    }


    /**
     * Ověří, zdali je identita získaná z federativního SSO z organizace, pro kterou byla instance nasazena.
     *
     * @param string $identity         Identita uživatele
     * @return bool                    TRUE, pokud je uživatel z organizace, jinak FALSE
     */
    private function isRemoteUserFromOrganization($identity) {
      $identityDomain = get_domain_from_url('https://' . get_email_part($identity, 'domain'));
      $organizationDomains = get_organization_domains();

      return in_array($identityDomain, $organizationDomains);
    }


    /**
     * Přihlásí uživatele do systému, přičemž pokud se jedná o první přihlášení uživatele do systému
     * (tedy typicky po automatické registraci), dojde k přesměrování do sekce, kde si lze navolit účast
     * v programu.
     *
     * @param string $identity         Identita uživatele
     */
    public function login($identity) {
      $identity = $this->getUserIdentity($identity);

      // Ověření, zdali se podařilo získat nějakou identitu z SSO.
      if ($identity == null) {
        Logger::error('Failed to retrieve user identity from SSO.');

        echo 'Nepodařilo se získat Vaši identitu z SSO. Kontaktujte, prosím, administrátora.';
        exit();
      }

      // Ověření, zdali je získaná identita skutečně z dané organizace
      // a nejedná se o validní přihlášení, ale pro jinou organizaci.
      if (!$this->isRemoteUserFromOrganization($identity)) {
        Logger::error('The user identity provided by SSO does not match this Phishingator instance.', $identity);

        echo 'Jste přihlášeni identitou "' . Controller::escapeOutput($identity). '", která nespadá do organizace ' . Controller::escapeOutput(get_organization_domain()) . '. Odhlaste se, prosím, a přihlaste správnou identitou.';
        exit();
      }

      $user = UsersModel::getUserByEmail($identity);

      // Pokud je již uživatel registrován...
      if (!empty($user)) {
        $_SESSION['user']['id'] = $user['id_user'];
        $_SESSION['user']['email'] = $user['email'];

        // Nejvyšší oprávnění, které si může uživatel vybrat (v rámci funkce změny role).
        $_SESSION['user']['permission'] = $user['role'];

        // Aktuálně zvolená role v systému.
        $_SESSION['user']['role'] = $user['role'];

        // Vygenerování CSRF tokenu pro přihlášenou relaci.
        $_SESSION['csrf_token'] = $this->generateCsrfToken($user['id_user']);

        // Další případná nastavení.
        PermissionsModel::setUserSetting('blur-identities', CAMPAIGN_STATS_BLUR_IDENTITIES);

        session_regenerate_id();

        // Zjištění, zdali se jedná o uživatele, který se zatím do Phishingatoru nikdy v minulosti nepřihlásil.
        $countUserLogins = $this->getCountOfUserLogins($user['id_user']);

        // Zaznamenat přihlášení uživatele do databáze (pokud se nejedná o testovacího uživatele - sondu).
        if ($user['username'] != TEST_USERNAME) {
          $this->logLogin($user['id_user']);
        }

        // Pokud se jedná o první přihlášení uživatele, přesměrovat ho do sekce "Moje účast v programu".
        if ($countUserLogins == 0) {
          header('Location: ' . WEB_URL . '/portal/my-participation');
          exit();
        }
      }
      else {
        $registrated = $this->register($identity);

        if (!$registrated) {
          Logger::error('Failed to retrieve data about user from LDAP during registration.', [$identity, $_SERVER['REMOTE_USER'], $_SERVER['OIDC_CLAIM_voperson_external_id']]);

          echo 'Nepodařilo se získat informace o Vaší identitě (neznámá identita "' . Controller::escapeOutput($identity). '"). Kontaktujte, prosím, administrátora.';
          exit();
        }
      }
    }


    /**
     * Ověří, zdali má uživatel podle databáze stále aktivní konto a zdali
     * nedošlo během jeho přihlášené relace ke změně přidělených oprávnění.
     *
     * @return void
     */
    public static function checkUserValidity() {
      $user = UsersModel::getUserByEmail(self::getUserName());

      if (!empty($user)) {
        // Pokud byla uživateli během jeho relace odebrána práva, snížit mu je i v přihlášené relaci.
        if (self::getUserPermission() != $user['role']) {
          $_SESSION['user']['permission'] = $user['role'];

          if (self::getUserRole() < $user['role']) {
            $_SESSION['user']['role'] = $user['role'];
          }
        }
      }
      else {
        // Pokud bylo uživateli konto smazáno, provést obratem jeho odhlášení.
        self::logout();
      }
    }


    /**
     * Vytvoří v databázi nový záznam o úspěšném přihlášení uživatele do systému.
     *
     * @param int $idUser              ID uživatele
     */
    private function logLogin($idUser) {
      $ip = WebsitePrependerModel::getClientIp();

      Logger::info('Successful user login.', [$idUser, $ip]);

      $record = [
        'id_user' => $idUser,
        'login_datetime' => date('Y-m-d H:i:s'),
        'ip' => $ip
      ];

      Database::insert('phg_users_login_log', $record);
    }


    /**
     * Vrátí počet přihlášení konkrétního uživatele do systému.
     *
     * @param int $idUser              ID uživatele
     * @return mixed                   Celkový počet přihlášení
     */
    private function getCountOfUserLogins($idUser) {
      return Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_users_login_log`
              WHERE `id_user` = ?
      ', $idUser);
    }


    /**
     * Přepne roli aktuálně přihlášeného uživatele na základě jeho oprávnění.
     *
     * @param string $newRole          Identifikátor požadované role
     * @return void
     */
    public static function switchRole($newRole) {
      $userPermission = self::getUserPermission();

      if ($userPermission === PERMISSION_ADMIN) {
        $allowedRoles = [PERMISSION_ADMIN_URL, PERMISSION_TEST_MANAGER_URL, PERMISSION_USER_URL];
      }
      elseif ($userPermission === PERMISSION_TEST_MANAGER) {
        $allowedRoles = [PERMISSION_TEST_MANAGER_URL, PERMISSION_USER_URL];
      }
      elseif ($userPermission === PERMISSION_USER) {
        $allowedRoles = [PERMISSION_USER_URL];
      }
      else {
        $allowedRoles = [];
      }

      // Ověření požadované role.
      if (is_string($newRole) && in_array($newRole, $allowedRoles, true)) {
        self::setUserRole($newRole);
      }
      else {
        Logger::warning(
          'Unauthorized attempt to change the user role.',
          [
            'id_user' => self::getUserId(),
            'new_role' => $newRole
          ]
        );
      }
    }


    /**
     * Změní přihlášenému uživateli roli.
     *
     * @param int $newRole             Identifikátor role
     */
    private static function setUserRole($newRole) {
      // Pole možných oprávnění v systému.
      $roles = [
        PERMISSION_ADMIN_URL => PERMISSION_ADMIN,
        PERMISSION_TEST_MANAGER_URL => PERMISSION_TEST_MANAGER,
        PERMISSION_USER_URL => PERMISSION_USER
      ];

      // Pokud bylo požadované oprávnění nalezeno, tak jej změnit.
      if (array_key_exists($newRole, $roles)) {
        $_SESSION['user']['role'] = $roles[$newRole];
      }
    }


    /**
     * Vrátí textový název role.
     *
     * @return string|null             Název role, kterou má právě uživatel vybranou.
     */
    public static function getUserRoleText() {
      switch (self::getUserRole()) {
        case PERMISSION_ADMIN: return PERMISSION_ADMIN_TEXT;
        case PERMISSION_TEST_MANAGER: return PERMISSION_TEST_MANAGER_TEXT;
        case PERMISSION_USER: return PERMISSION_USER_TEXT;
      }

      return null;
    }


    /**
     * Vygeneruje a vrátí unikátní CSRF token pro konkrétního uživatele.
     *
     * @param int $idUser              ID uživatele
     * @return string                  Vygenerovaný CSRF token
     */
    private function generateCsrfToken($idUser) {
      return hash_hmac('sha512', microtime() . $idUser, CSRF_KEY);
    }


    /**
     * Vrátí CSRF token.
     *
     * @return mixed|null              CSRF token nebo NULL
     */
    public static function getCsrfToken() {
      return $_SESSION['csrf_token'] ?? null;
    }


    /**
     * Vrátí e-mailové omezení (na příjemce, které může uživatel vybírat v kampaních)
     * pro právě přihlášeného uživatele.
     *
     * @return mixed|null              Pole e-mailových omezení nebo NULL
     */
    public static function getUserEmailRestrictions() {
      $result = Database::querySingle('
              SELECT `emails_restrictions`
              FROM `phg_users`
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              WHERE `id_user` = ?
      ', self::getUserId());

      return $result['emails_restrictions'] ?? null;
    }


    /**
     * Vrátí řetězec povolených LDAP skupin pro právě přihlášeného uživatele.
     *
     * @return mixed|null              Řetězec povolených LDAP skupin nebo NULL
     */
    public static function getUserAllowedLdapGroups() {
      $result = Database::querySingle('
              SELECT `ldap_groups`
              FROM `phg_users`
              JOIN `phg_users_groups`
              ON phg_users.id_user_group = phg_users_groups.id_user_group
              WHERE `id_user` = ?
      ', self::getUserId());

      return $result['ldap_groups'] ?? null;
    }


    /**
     * Odhlásí právě přihlášeného uživatele ze systému.
     */
    public static function logout() {
      unset($_SESSION['user']);
      session_destroy();
    }


    /**
     * Vrátí ID právě přihlášeného uživatele.
     *
     * @return int|null                ID uživatele nebo NULL
     */
    public static function getUserId() {
      return $_SESSION['user']['id'] ?? null;
    }


    /**
     * Vrátí identitu právě přihlášeného uživatele.
     *
     * @return string|null                Uživatelské jméno uživatele nebo NULL
     */
    public static function getUserName() {
      return $_SESSION['user']['email'] ?? null;
    }


    /**
     * Vrátí nejvyšší možné oprávnění, které má přihlášený uživatel.
     *
     * @return int|null                Číslo oprávnění nebo NULL
     */
    public static function getUserPermission() {
      return $_SESSION['user']['permission'] ?? null;
    }


    /**
     * Vrátí aktuálně zvolenou roli právě přihlášeného uživatele.
     *
     * @return int|null                Číslo role nebo NULL
     */
    public static function getUserRole() {
      return $_SESSION['user']['role'] ?? null;
    }


    /**
     * Vrátí nastavení konkrétní položky pro právě přihlášeného uživatele.
     *
     * @param string $key              Položka k nastavení
     * @return mixed|null              Hodnota položky v nastavení
     */
    public static function getUserSetting($key) {
      return $_SESSION['user']['settings'][$key] ?? null;
    }


    /**
     * Nastaví konkrétní položku nastavení pro právě přihlášeného uživatele.
     *
     * @param string $key              Položka k nastavení
     * @param mixed $value             Hodnota položky v nastavení
     * @return void
     */
    public static function setUserSetting($key, $value) {
      $_SESSION['user']['settings'][$key] = $value;
    }
  }
