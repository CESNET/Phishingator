<?php
  /**
   * Třída sloužící k ověření správnosti uživatelem zadaných
   * přihlašovacích údajů na podvodných stránek.
   *
   * @author Martin Šebela
   */
  class CredentialsTesterModel {
    /**
     * @var string      Uživatelské jméno
     */
    private static $username;

    /**
     * @var string      Heslo uživatele
     */
    private static $password;


    /**
     * Ověří vůči zvolené autentizační metodě, zdali jsou zadané přihlašovací údaje platné.
     *
     * @param string $username         Identita uživatele (uživatelské jméno nebo e-mail)
     * @param string $password         Heslo uživatele
     * @param string $methods          Metoda (popř. různé metody) použitá k ověření platnosti přihlašovacích údajů, při
     *                                 nevyplnění bude použita metoda nastavená v konfiguračním souboru (nepovinné)
     * @return bool                    TRUE, pokud jsou zadané přihlašovací údaje platné, jinak FALSE
     */
    public static function tryLogin($username, $password, $methods = AUTHENTICATION_TYPE) {
      $methods = array_map('trim', explode(',', $methods));

      $validCreds = false;

      if (str_contains($username, '@')) {
        $username = get_email_part($username, 'username');
      }

      if (AUTHENTICATION_USERNAME_TO_LOWERCASE) {
        $username = mb_strtolower($username);
      }

      self::$username = $username;
      self::$password = $password;

      foreach ($methods as $method) {
        if ($method == 'ldap') {
          $validCreds = self::tryLdapLogin();
        }
        elseif ($method == 'web') {
          $validCreds = self::tryWebLogin();
        }
        elseif ($method == 'm365') {
          $validCreds = self::tryM365Login();
        }
        elseif ($method == 'kerberos') {
          $validCreds = self::tryKerberosLogin();
        }
        elseif ($method == 'imap') {
          $validCreds = self::tryImapLogin();
        }
        elseif ($method == 'policy') {
          $validCreds = self::tryPolicyCheck();
        }

        if ($validCreds) {
          break;
        }
      }

      return $validCreds;
    }


    /**
     * Otevřením LDAP spojení ověří, zdali byly zadány platné přihlašovací údaje.
     *
     * @return bool                    TRUE pokud byly zadány platné přihlašovací údaje, jinak FALSE
     */
    private static function tryLdapLogin() {
      $ldap = new LdapModel(false);

      $username = self::$username;

      if (!empty(TEST_USERNAME_LDAP_SUFFIX) && $username == TEST_USERNAME) {
        $username .= TEST_USERNAME_LDAP_SUFFIX;
      }
      elseif (!empty(AUTHENTICATION_LDAP_USER_SUFFIX) && !str_contains($username, AUTHENTICATION_LDAP_USER_SUFFIX)) {
        $username .= AUTHENTICATION_LDAP_USER_SUFFIX;
      }

      $username = AUTHENTICATION_LDAP_USER_PREFIX . $username;
      $username = ldap_escape($username, '', LDAP_ESCAPE_FILTER);

      $validCreds = $ldap->connect($username, self::$password, AUTHENTICATION_LDAP_HOST, AUTHENTICATION_LDAP_PORT, false);

      $ldap->close();

      return $validCreds;
    }


    /**
     * Odesláním přihlašovacího formuláře přes metodu POST na zvoleném webu ověří,
     * zdali byly zadány platné přihlašovací údaje.
     *
     * @return bool                    TRUE, pokud byly zadány platné přihlašovací údaje, jinak FALSE
     */
    private static function tryWebLogin() {
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, AUTHENTICATION_WEB_URL);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
          AUTHENTICATION_WEB_INPUT_USERNAME => self::$username,
          AUTHENTICATION_WEB_INPUT_PASSWORD => self::$password
        ])
      );

      $serverOutput = curl_exec($ch);
      $serverResponse = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      curl_close($ch);

      return ($serverResponse == AUTHENTICATION_WEB_RESPONSE_CODE || $serverOutput == AUTHENTICATION_WEB_RESPONSE_OUTPUT);
    }


    /**
     * Odesláním požadavku na M365 endpoint přes metodu POST ověří,
     * zdali byly zadány platné přihlašovací údaje.
     *
     * @return bool                    TRUE, pokud byly zadány platné přihlašovací údaje, jinak FALSE
     */
    private static function tryM365Login() {
      $validCreds = false;

      $username = self::$username;

      if (!empty(AUTHENTICATION_M365_USER_SUFFIX) && !str_contains($username, AUTHENTICATION_M365_USER_SUFFIX)) {
        $username .= AUTHENTICATION_M365_USER_SUFFIX;
      }

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, AUTHENTICATION_M365_URL);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
          'client_id' => AUTHENTICATION_M365_CLIENT_ID,
          'client_secret' => AUTHENTICATION_M365_CLIENT_SECRET,
          'scope' => 'openid profile offline_access',
          'grant_type' => 'password',
          'username' => $username,
          'password' => self::$password
        ])
      );

      $serverOutput = curl_exec($ch);

      curl_close($ch);

      $serverOutputDecoded = json_decode($serverOutput, true);
      $serverOutputError = $serverOutputDecoded['error_description'] ?? '';

      $validOutputErrors = [
        'AADSTS65001', // Consent required
        'AADSTS50076', // MFA required
        'AADSTS50079', // MFA registration required
        'AADSTS53003', // Conditional Access
      ];

      foreach ($validOutputErrors as $errorCode) {
        if (str_contains($serverOutputError, $errorCode)) {
          $validCreds = true;
          break;
        }
      }

      if (isset($serverOutputDecoded['access_token'])) {
        $validCreds = true;
      }

      return $validCreds;
    }


    /**
     * Voláním příkazu kinit ve službě Kerberos ověří, zdali byly zadány platné přihlašovací údaje.
     *
     * @return bool                    TRUE pokud byly zadány platné přihlašovací údaje, jinak FALSE
     */
    private static function tryKerberosLogin() {
      $validCreds = false;

      $returnCode = CommandRunnerModel::run('/usr/bin/kinit', [self::$username], $output, self::$password);

      if ($returnCode === 0) {
        $validCreds = true;
      }

      return $validCreds;
    }


    /**
     * Otevřením IMAP spojení ověří, zdali byly zadány platné přihlašovací údaje.
     *
     * @return bool                    TRUE pokud byly zadány platné přihlašovací údaje, jinak FALSE
     */
    private static function tryImapLogin() {
      $validCreds = false;

      $imap = imap_open(AUTHENTICATION_IMAP_ARGS, self::$username, self::$password,  OP_READONLY, 1);

      if ($imap !== false) {
        $validCreds = true;

        imap_close($imap);
      }

      return $validCreds;
    }


    /**
     * Otestováním bezpečnostní politiky hesel ověří, zdali zadané heslo
     * této politice vyhoví, a mohlo by se tak jednat o skutečné heslo.
     *
     * @return bool                    TRUE pokud heslo vyhovuje heslové politice, jinak FALSE
     */
    private static function tryPolicyCheck() {
      $validCreds = false;

      if (mb_strlen(self::$password) >= AUTHENTICATION_POLICY_MIN_LENGTH) {
        $sumGroupsChars = 0;

        // Malá písmena
        if (preg_match('/[a-z]/', self::$password)) {
          $sumGroupsChars++;
        }

        // Velká písmena
        if (preg_match('/[A-Z]/', self::$password)) {
          $sumGroupsChars++;
        }

        // Čísla
        if (preg_match('/[0-9]/', self::$password)) {
          $sumGroupsChars++;
        }

        // Speciální znaky
        if (preg_match('/[^\da-zA-Z]/', self::$password)) {
          $sumGroupsChars++;
        }

        if ($sumGroupsChars >= AUTHENTICATION_POLICY_MIN_CHARS_GROUPS) {
          $validCreds = true;
        }

        if (!AUTHENTICATION_POLICY_ALLOW_CONTAIN_USERNAME) {
          $username = str_contains(self::$username, '@') ? get_email_part(self::$username, 'username') : self::$username;

          if (str_contains(mb_strtolower(self::$password), mb_strtolower($username))) {
            $validCreds = false;
          }
        }
      }

      return $validCreds;
    }
  }