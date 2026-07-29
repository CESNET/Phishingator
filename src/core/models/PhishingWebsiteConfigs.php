<?php
  /**
   * Třída sloužící pro vytváření a správu konfiguračních
   * souborů podvodných stránek na webovém serveru.
   *
   * @author Martin Šebela
   */
  class PhishingWebsiteConfigs {
    /**
     * Vrátí cestu včetně názvu souboru, kde bude uložen konfigurační soubor podvodné stránky.
     *
     * @param string $url              URL adresa podvodné stránky
     * @param string $extension        Přípona konfiguračního souboru (nepovinné)
     * @return string                  Cesta včetně názvu souboru
     */
    public static function getConfigPath($url, $extension = null) {
      return PHISHING_WEBSITE_CONF_DIR . get_hostname_from_url($url) . (($extension == null) ? PHISHING_WEBSITE_CONF_EXT : $extension);
    }


    /**
     * Ověří, zdali konfigurační soubor pro podvodné stránky není ve stavu probíhající úpravy,
     * a je tak možné s podvodnou stránkou pracovat.
     *
     * @param string $url              URL adresa podvodné stránky
     * @param bool $withException      TRUE, pokud se má vrátit výjimka při nepřipraveném konfiguračním souboru, jinak FALSE (výchozí)
     * @return bool                    TRUE pokud je konfigurační soubor připraven, jinak FALSE
     * @throws UserError
     */
    public static function isConfigReady($url, $withException = false) {
      $configReady = !file_exists(self::getConfigPath($url, PHISHING_WEBSITE_CONF_EXT_NEW)) &&
        !file_exists(self::getConfigPath($url, PHISHING_WEBSITE_CONF_EXT_DEL));

      if ($withException) {
        if (!$configReady) {
          Logger::warning('Changing the phishing website configuration during a change in progress.', $url);

          throw new UserError('Právě probíhá změna konfigurace podvodné stránky, opakujte, prosím, akci o několik minut později.', MSG_WARNING);
        }
      }
      else {
        $configReady = $configReady && file_exists(self::getConfigPath($url));
      }

      return $configReady;
    }


    /**
     * Vygeneruje nový konfigurační soubor pro podvodné stránky se stejnou (sub)doménou.
     *
     * @param string $url              URL adresa podvodné stránky
     * @param int $idTemplateToAdd     ID šablony podvodné stránky pro její přidání (nepovinné)
     * @param int $idWebsiteToDelete   ID podvodné stránky pro její smazání (nepovinné)
     * @return void
     * @throws UserError
     */
    public static function generateNewConfig($url, $idTemplateToAdd = null, $idWebsiteToDelete = null) {
      self::isConfigReady($url, true);

      $configFilepath = self::getConfigPath($url, PHISHING_WEBSITE_CONF_EXT_NEW);

      $websites = PhishingWebsiteModel::getActivePhishingWebsites();
      $websitesToConfig = [];

      // Případné přidání stránky do seznamu aktivních podvodných stránek (protože stránka zatím není v databázi).
      if ($idTemplateToAdd) {
        $websites[] = ['url' => $url, 'id_template' => $idTemplateToAdd];
      }

      // Případné odstranění stránky ze seznamu aktivních stránek.
      if ($idWebsiteToDelete) {
        foreach ($websites as $i => $website) {
          if ($website['id_website'] == $idWebsiteToDelete) {
            unset($websites[$i]);
            break;
          }
        }
      }

      // Nalezení všech aktivních podvodných stránek se stejnou (sub)doménou.
      foreach ($websites as $website) {
        if (get_hostname_from_url($website['url']) == get_hostname_from_url($url)) {
          $template = PhishingWebsiteModel::getPhishingWebsiteTemplate($website['id_template']);

          if ($template) {
            $websitesToConfig[self::getUrlAlias($website['url'])] = $template['server_dir'];
          }
        }
      }

      if (count($websitesToConfig) > 0) {
        $config = self::getConfig(get_hostname_from_url($url), 80, $websitesToConfig);

        if (!is_writable(PHISHING_WEBSITE_CONF_DIR)) {
          Logger::error('The destination directory for inserting the sample phishing website configuration file is not writable.', PHISHING_WEBSITE_CONF_DIR);

          throw new UserError('Adresář, ve kterém se má vytvořit konfigurace podvodné stránky, není zapisovatelný.', MSG_ERROR);
        }

        if (!file_put_contents($configFilepath, $config)) {
          Logger::error('Failed to create a phishing website configuration file.', $configFilepath);

          throw new UserError('Nepodařilo se vytvořit soubor s konfigurací podvodné stránky.', MSG_ERROR);
        }
      }

      // Pokud už existuje nějaký konfigurační soubor, nastavit starší verzi z odstranění.
      self::removeConfig($url);
    }


    /**
     * Přejmenuje konfigurační soubor dané podvodné stránky tak,
     * aby došlo k její deaktivaci a odstranění.
     *
     * @param string $url              URL podvodné stránky
     * @return void
     */
    private static function removeConfig($url) {
      $websiteConfigPath = PHISHING_WEBSITE_CONF_DIR . get_hostname_from_url($url);

      if (file_exists($websiteConfigPath . PHISHING_WEBSITE_CONF_EXT)) {
        rename($websiteConfigPath . PHISHING_WEBSITE_CONF_EXT, $websiteConfigPath . PHISHING_WEBSITE_CONF_EXT_DEL);
      }
    }


    /**
     * Zpracuje všechny vytvořené konfigurační soubory podvodných stránek
     * a buď je aktivuje, nebo deaktivuje na webovém serveru.
     *
     * @return void
     */
    public static function processAllConfigs() {
      $files = scandir(PHISHING_WEBSITE_CONF_DIR);
      $changes = false;

      foreach ($files as $file) {
        if (str_ends_with($file, PHISHING_WEBSITE_CONF_EXT) || str_ends_with($file, PHISHING_WEBSITE_CONF_EXT_DEL) || str_ends_with($file, PHISHING_WEBSITE_CONF_EXT_NEW)) {
          $filepath = PHISHING_WEBSITE_CONF_DIR . $file;

          $serverName = self::pregMatchInFile($filepath, '/ServerName (.*)/');
          $serverNameWithProtocol = (!str_starts_with($serverName, 'https') ? 'https://' : '') . $serverName;

          if (filter_var($serverNameWithProtocol, FILTER_VALIDATE_URL)) {
            // Aktivace podvodné stránky.
            if (self::isConfigType($file, PHISHING_WEBSITE_CONF_EXT_NEW)) {
              $filepathNewConfig = PHISHING_WEBSITE_APACHE_DIR . $serverName . PHISHING_WEBSITE_CONF_EXT;
              $filepathAppliedConfig = PHISHING_WEBSITE_CONF_DIR . $serverName . PHISHING_WEBSITE_CONF_EXT;

              copy($filepath, $filepathNewConfig);
              CommandRunnerModel::run('/usr/sbin/a2ensite', [$serverName]);
              rename($filepath, $filepathAppliedConfig);

              Logger::info('New phishing website activated in Apache.', [$filepath, $filepathNewConfig, $filepathAppliedConfig, $serverName]);

              $changes = true;
            }

            // Deaktivace podvodné stránky.
            elseif (self::isConfigType($file, PHISHING_WEBSITE_CONF_EXT_DEL)) {
              CommandRunnerModel::run('/usr/sbin/a2dissite', [$serverName]);
              FileManagerModel::deleteFileInDirectory($filepath, PHISHING_WEBSITE_CONF_DIR);

              Logger::info('Phishing website deactivated in Apache.', [$filepath, $serverName]);

              $changes = true;
            }

            // Aktivace již z dřívějška existující podvodné stránky.
            elseif (self::isConfigType($file, PHISHING_WEBSITE_CONF_EXT) && !file_exists(PHISHING_WEBSITE_APACHE_DIR . $file)) {
              $filepathNewConfig = PHISHING_WEBSITE_APACHE_DIR . $serverName . PHISHING_WEBSITE_CONF_EXT;

              copy($filepath, $filepathNewConfig);
              CommandRunnerModel::run('/usr/sbin/a2ensite', [$serverName]);

              Logger::info('Existing phishing website activated in Apache.', [$filepath, $filepathNewConfig, $serverName]);

              $changes = true;
            }
          }
        }
      }

      if ($changes) {
        CommandRunnerModel::run('/usr/sbin/apachectl', ['graceful']);

        Logger::info('Apache configuration reloaded.');
      }
    }


    /**
     * Vrátí hlavičku konfiguračního souboru pro Apache VirtualHost.
     *
     * @param string $domain           Název serveru
     * @param int $port                Port, na kterém bude VirtualHost naslouchat
     * @return string                  Část (hlavička) konfigurace
     */
    private static function getHeaderConfigPart($domain, $port) {
      return '<VirtualHost *:' . $port . '>' . "\n" .
             '    ServerName ' . $domain . "\n" .
             '    DocumentRoot ' . getenv('APACHE_DOCUMENT_ROOT') . "\n" .
             "\n" .
             '    <Directory ' . getenv('APACHE_DOCUMENT_ROOT') . '>' . "\n" .
             '        Require all denied' . "\n" .
             '    </Directory>' . "\n\n";
    }


    /**
     * Vrátí patičku konfiguračního souboru pro Apache VirtualHost.
     *
     * @return string                  Část (patička) konfigurace
     */
    private static function getFooterConfigPart() {
      return '    ErrorLog /proc/self/fd/2' . "\n" .
             '    CustomLog /proc/self/fd/1 combined' . "\n" .
             '</VirtualHost>' . "\n";
    }

    /**
     * Vrátí část konfiguračního souboru s definicí nového aliasu pro Apache VirtualHost.
     *
     * @param string $aliasPath        Alias (doména a path) podvodné stránky
     * @param string $templateDir      Adresář se šablonou podvodné stránky
     * @return string                  Část konfiguračního souboru s definicí aliasu
     */
    private static function getAliasConfigPart($aliasPath, $templateDir) {
      return '    Alias "' . $aliasPath . '" ' . $templateDir . "\n" .
             '    <Directory "' . $templateDir . '">' . "\n" .
             '        Options -Indexes' . "\n" .
             '        AllowOverride None' . "\n" .
             '        Require all granted' . "\n\n" .
             '        php_value auto_prepend_file "' . PHISHING_WEBSITE_PREPENDER . '"' . "\n" .
             '    </Directory>' . "\n\n";
    }


    /**
     * Vrátí kompletní obsah konfiguračního souboru pro Apache VirtualHost
     * pro zadané podvodné stránky se stejnou (sub)doménou.
     *
     * @param string $domain           Název serveru
     * @param int $port                Port, na kterém bude VirtualHost naslouchat
     * @param array $aliases           Pole s aliasy a šablonami
     * @return string                  Kompletní obsah konfiguračního souboru
     */
    private static function getConfig($domain, $port, $aliases) {
      $config = self::getHeaderConfigPart($domain, $port);

      // Seřazení aliasů pro potřeby Apache VirtualHost, aby konkrétnější
      // alias byl v souboru uveden výš než ten obecnější.
      $aliases = self::sortUrlAliases($aliases);

      foreach ($aliases as $url => $template) {
        $config .= self::getAliasConfigPart($url, $template);
      }

      $config .= self::getFooterConfigPart();

      return $config;
    }


    /**
     * Ověří, zdali přípona (typ) konfiguračního souboru odpovídá té předpokládané.
     *
     * @param string $filename         Název souboru
     * @param string $extension        Očekávaná přípona souboru
     * @return bool                    TRUE pokud přípona konfiguračního souboru odpovídá té předané
     */
    private static function isConfigType($filename, $extension) {
      return mb_substr($filename, -mb_strlen($extension)) == $extension;
    }


    /**
     * Vrátí alias (adresářovou cestu) pro konfigurační soubor podvodné stárnky.
     *
     * @param string $url              URL podvodné stránky
     * @return string                  Alias (adresářová cesta) podvodné stránky
     */
    private static function getUrlAlias($url) {
      $url = PhishingWebsiteModel::makeWebsiteUrl($url);
      $urlPath = parse_url($url, PHP_URL_PATH);

      return (!empty($urlPath) && $urlPath != '/') ? $urlPath : '/';
    }


    /**
     * Vrátí seřazené pole s aliasy pro podvodné stránky od nejkonkrétnějšího po nejobecnější.
     *
     * @param array $aliases           Asociativní pole s aliasy (klíče) a adresářemi šablon podvodných stránek (hodnoty)
     * @return array                   Seřazené asociativní pole s aliasy
     */
    private static function sortUrlAliases($aliases) {
      $urls = array_keys($aliases);
      $sortedAliases = [];

      usort($urls, function($a, $b) {
        // Pokud alias B začíná jako alias A, alias B je specifičtější (alias B musí být výš).
        if (str_starts_with($b, $a)) {
          return 1;
        }

        // Pokud alias A začíná jako alias B, alias A je specifičtější (alias A musí být výš).
        if (str_starts_with($a, $b)) {
          return -1;
        }

        // Jinak neřešíme (ponecháme původní pořadí).
        return 0;
      });

      // Doplnění adresářů se šablonami k seřazenému seznamu aliasů.
      foreach ($urls as $alias) {
        $sortedAliases[$alias] = $aliases[$alias];
      }

      return $sortedAliases;
    }


    /**
     * Vyhledá v souboru řetězce, které odpovídají danému regulárnímu výrazu.
     *
     * @param string $file             Cesta k souboru
     * @param string $regex            Regulární výraz
     * @param int $matchToReturn       Který z výsledků má být vrácen na výstupu
     * @return string                  Vrácený výsledek, který odpovídá regulárnímu výrazu
     */
    private static function pregMatchInFile($file, $regex, $matchToReturn = 1) {
      $data = '';

      foreach (file($file) as $line) {
        preg_match($regex, $line, $matches);

        if (isset($matches[$matchToReturn])) {
          $data = $matches[$matchToReturn];
          break;
        }
      }

      return $data;
    }
  }