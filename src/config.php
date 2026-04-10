<?php
  /* --- NASTAVENÍ PHP DLE LOKALIZACE --- */
  /** Časová zóna, kde běží instance Phishingatoru. */
  define('PHP_TIME_ZONE', 'Europe/Prague');

  /** Multibyte kódování pro PHP funkce pracující s řetězci. */
  define('PHP_MULTIBYTE_ENCODING', 'utf-8');



  /* --- PŘIPOJENÍ K DATABÁZI --- */
  /** PDO řetězec sloužící pro připojení k databázi (včetně hostitele a názvu databáze). */
  define('DB_PDO_DSN', 'mysql:host=' . ((getenv('DB_HOST')) ? getenv('DB_HOST') : 'database') . ';dbname=' . ((getenv('DB_DATABASE')) ? getenv('DB_DATABASE') : 'phishingator'));

  /** Uživatelské jméno do databáze. */
  define('DB_USERNAME', getenv('DB_USERNAME'));

  /** Heslo do databáze. */
  define('DB_PASSWORD', getenv('DB_PASSWORD'));

  /** Kódování použité při práci s databází. */
  define('DB_ENCODING', 'utf8');



  /* --- PŘIPOJENÍ K LDAP --- */
  /** Hostitel LDAP (včetně uvedení protokolu ldaps:// nebo ldap://). */
  define('LDAP_HOSTNAME', (getenv('LDAP_HOSTNAME')) ? getenv('LDAP_HOSTNAME') : 'ldap://ldap');

  /** Port k připojení k LDAP. */
  define('LDAP_PORT', (getenv('LDAP_PORT')) ? getenv('LDAP_PORT') : 389);

  /** Uživatelské jméno pro přístup do LDAP. */
  define('LDAP_USERNAME', getenv('LDAP_USERNAME'));

  /** Heslo pro přístup do LDAP. */
  define('LDAP_PASSWORD', getenv('LDAP_PASSWORD'));

  /** Základní cesta v LDAP. */
  define('LDAP_BASE_DN', getenv('LDAP_BASE_DN'));

  /** Cesta k seznamu uživatelů v LDAP. */
  define('LDAP_USERS_DN', (getenv('LDAP_USERS_DN')) ? getenv('LDAP_USERS_DN') : 'ou=People');

  /** Název LDAP atributu, ve kterém je uložen identifikátor uživatele (typicky "uid" / "samaccountname"). */
  define('LDAP_USER_ATTR_ID', (getenv('LDAP_USER_ATTR_ID')) ? getenv('LDAP_USER_ATTR_ID') : 'uid');

  /** Název LDAP atributu, ve kterém je uloženo jméno a příjmení uživatele (typicky "displayname" / "cn"). */
  define('LDAP_USER_ATTR_FULLNAME', (getenv('LDAP_USER_ATTR_FULLNAME')) ? getenv('LDAP_USER_ATTR_FULLNAME') : 'displayname');

  /** Název LDAP atributu, ve kterém je uloženo křestní jméno uživatele (typicky "givenName"). */
  define('LDAP_USER_ATTR_FIRSTNAME', (getenv('LDAP_USER_ATTR_FIRSTNAME')) ? getenv('LDAP_USER_ATTR_FIRSTNAME') : 'givenname');

  /** Název LDAP atributu, ve kterém je uloženo příjmení uživatele (typicky "sn"). */
  define('LDAP_USER_ATTR_SURNAME', (getenv('LDAP_USER_ATTR_SURNAME')) ? getenv('LDAP_USER_ATTR_SURNAME') : 'sn');

  /** Název LDAP atributu, ve kterém je uložen e-mail uživatele. */
  define('LDAP_USER_ATTR_EMAIL', (getenv('LDAP_USER_ATTR_EMAIL')) ? getenv('LDAP_USER_ATTR_EMAIL') : 'mail');

  /** Název LDAP atributu, ve kterém jsou uloženy e-mailové aliasy uživatele. */
  define('LDAP_USER_ATTR_EMAIL_ALIASES', (getenv('LDAP_USER_ATTR_EMAIL_ALIASES')) ? getenv('LDAP_USER_ATTR_EMAIL_ALIASES') : 'mailalias');

  /** Název LDAP atributu, ze kterého lze získat názvy skupin (oddělení), do nichž je uživatel zařazen. */
  define('LDAP_USER_ATTR_GROUPS', (getenv('LDAP_USER_ATTR_GROUPS')) ? getenv('LDAP_USER_ATTR_GROUPS') : 'departmentnumber');

  /** Cesta k seznamu uživatelských skupin v LDAP. */
  define('LDAP_GROUPS_DN', (getenv('LDAP_GROUPS_DN')) ? getenv('LDAP_GROUPS_DN') : 'ou=Groups');

  /** Název LDAP atributu, který obsahuje identifikátor uživatele patřícího do dané skupiny. */
  define('LDAP_GROUPS_ATTR_MEMBER', (getenv('LDAP_GROUPS_ATTR_MEMBER')) ? getenv('LDAP_GROUPS_ATTR_MEMBER') : 'member');

  /** Cesta k seznamu pracovišť v LDAP. */
  define('LDAP_DEPARTMENTS_DN', getenv('LDAP_DEPARTMENTS_DN'));

  /** Filtr pro získání nadřazených pracovišť v LDAP (např. fakulty). */
  define('LDAP_ROOT_DEPARTMENTS_FILTER_DN', getenv('LDAP_ROOT_DEPARTMENTS_FILTER_DN'));

  /** Zkratky pracovišť (odděleny čárkou), která sice spadají pod nadřazenější pracoviště, ale v grafech mají
   *  být zobrazeny jako samostatné jednotky (nikoliv jako součást nadřazeného pracoviště). */
  define('INDEPENDENT_DEPARTMENTS', getenv('INDEPENDENT_DEPARTMENTS'));



  /* --- PŘIPOJENÍ K SMTP SERVERU --- */
  /** Hostitel SMTP. */
  define('SMTP_HOST', getenv('SMTP_HOST'));

  /** Port k připojení k SMTP. */
  define('SMTP_PORT', getenv('SMTP_PORT'));

  /** Použít TLS při spojení. */
  define('SMTP_TLS', getenv('SMTP_TLS'));

  /** Uživatelské jméno k SMTP. */
  define('SMTP_USERNAME', getenv('SMTP_USERNAME'));

  /** Heslo k SMTP. */
  define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));



  /* --- OVĚŘENÍ PŘIHLAŠOVACÍCH ÚDAJŮ ZADANÝCH NA PODVODNÝCH STRÁNKÁCH --- */
  /** Typ autentizace, který se bude používat při ověřování přihlašovací údajů:
   *    Možnosti: ldap/web/kerberos/imap/policy
   */
  define('AUTHENTICATION_TYPE', getenv('AUTHENTICATION_TYPE'));

  /** Nastavení, zdali má být uživatelské jméno zadané uživatelem do cvičné podvodné stránky převedeno na malá písmena. */
  define('AUTHENTICATION_USERNAME_TO_LOWERCASE', (getenv('AUTHENTICATION_USERNAME_TO_LOWERCASE') && getenv('AUTHENTICATION_USERNAME_TO_LOWERCASE') == 1));

  /** Pokud se použije LDAP autentizace, je nutné uvést hostitele LDAP, vůči kterému se budou přihlašovací údaje z podvodných stránek ověřovat. */
  define('AUTHENTICATION_LDAP_HOST', (getenv('AUTHENTICATION_LDAP_HOST')) ? getenv('AUTHENTICATION_LDAP_HOST') : getenv('LDAP_HOSTNAME'));

  /** Port k připojení k autentizačnímu LDAP serveru. */
  define('AUTHENTICATION_LDAP_PORT', (getenv('AUTHENTICATION_LDAP_PORT')) ? getenv('AUTHENTICATION_LDAP_PORT') : getenv('LDAP_PORT'));

  /** Prefix automaticky přidávaný k uživatelskému jménu (např. "uid="). */
  define('AUTHENTICATION_LDAP_USER_PREFIX', getenv('AUTHENTICATION_LDAP_USER_PREFIX'));

  /** Sufix automaticky přidávaný k uživatelskému jménu (pokud již v uživatelském jménu není obsažen). */
  define('AUTHENTICATION_LDAP_USER_SUFFIX', getenv('AUTHENTICATION_LDAP_USER_SUFFIX'));

  /** Pokud se použije autentizace přes web, je nutné uvést URL adresu, na které dojde k ověření přihlašovacích údajů. */
  define('AUTHENTICATION_WEB_URL', getenv('AUTHENTICATION_WEB_URL'));

  /** Název vstupního pole na webu pro autentizaci, do kterého uživatel zadává uživatelské jméno. */
  define('AUTHENTICATION_WEB_INPUT_USERNAME', getenv('AUTHENTICATION_WEB_INPUT_USERNAME'));

  /** Název vstupního pole na webu pro autentizaci, do kterého uživatel zadává heslo. */
  define('AUTHENTICATION_WEB_INPUT_PASSWORD', getenv('AUTHENTICATION_WEB_INPUT_PASSWORD'));

  /** HTTP kód, pokud dojde na webu pro autentizaci k úspěšnému přihlášení. */
  define('AUTHENTICATION_WEB_RESPONSE_CODE', getenv('AUTHENTICATION_WEB_RESPONSE_CODE'));

  /** Vrácený výstup, pokud dojde na webu pro autentizaci k úspěšnému přihlášení. */
  define('AUTHENTICATION_WEB_RESPONSE_OUTPUT', getenv('AUTHENTICATION_WEB_RESPONSE_OUTPUT'));

  /** Pokud se použije IMAP autentizace, je nutné uvést IMAP server (a port) a případné flagy (např. "{domain.tld:993/imap/ssl/}"). */
  define('AUTHENTICATION_IMAP_ARGS', getenv('AUTHENTICATION_IMAP_ARGS'));

  /** Minimální délka hesla, kterou stanovuje heslová politika. */
  define('AUTHENTICATION_POLICY_MIN_LENGTH', getenv('AUTHENTICATION_POLICY_MIN_LENGTH'));

  /** Minimální počet sad znaků, které musí heslo podle heslové politiky obsahovat. */
  define('AUTHENTICATION_POLICY_MIN_CHARS_GROUPS', getenv('AUTHENTICATION_POLICY_MIN_CHARS_GROUPS'));

  /** Nastavení, zdali heslo může podle heslové politiky obsahovat uživatelské jméno. */
  define('AUTHENTICATION_POLICY_ALLOW_CONTAIN_USERNAME', (getenv('AUTHENTICATION_POLICY_ALLOW_CONTAIN_USERNAME') && getenv('AUTHENTICATION_POLICY_ALLOW_CONTAIN_USERNAME') == 1));



  /* --- LOGOVÁNÍ --- */
  /** Cesta a název souboru, do kterého se budou zapisovat všechny zaznamenané události. Pokud soubor nebude existovat,
      systém jej vytvoří. Záznamy se přidávají atomicky. */
  define('LOGGER_FILEPATH', '/var/www/phishingator/logs/log.log');

  /** Formát data, které se bude zapisovat u každé zaznamenané události do protokolu. */
  define('LOGGER_DATE_FORMAT', 'Y-m-d H:i:s');

  /** Úroveň události, od které se budou záznamy ukládat do protokolu, v pořadí: DEBUG < INFO < WARNING < ERROR. */
  define('LOGGER_LEVEL', 'DEBUG');



  /* --- NASTAVENÍ WEBU --- */
  /** Verze Phishingatoru. */
  define('WEB_VERSION', '1.6');

  /** URL, na které Phishingator běží (slouží pro přesměrování v rámci systému). */
  define('WEB_URL', getenv('WEB_URL'));

  /** URL, která slouží jako úvodní stránka a rozcestník Phishingatoru. */
  define('WEB_BASE_URL', 'https://phishingator.cesnet.cz');



  /* --- SYSTÉM PRO SPRÁVU POŽADAVKŮ --- */
  /** URL obsahující parametr pro identifikátor do interního ticketovacího systému pro správu požadavků
   *  (např. Request Tracker), ve kterém mohou být evidovány phishingové kampaně.
   */
  define('ITS_URL', getenv('ITS_URL'));



  /* --- NOTIFIKACE --- */
  /** E-mail, který bude uveden jako odesílatel notifikací. */
  define('NOTIFICATION_SENDER', getenv('NOTIFICATION_SENDER'));



  /* --- VÝCHOZÍ HODNOTY UŽIVATELŮ --- */
  /** Určuje, zdali se bude při výpisech v GUI Phishingatoru preferovat uživatelské jméno získané z LDAP (0),
   * nebo uživatelské jméno získané z e-mailu z LDAP (1).
   */
  define('USER_PREFER_EMAIL', (getenv('USER_PREFER_EMAIL') && getenv('USER_PREFER_EMAIL') == 1));

  /** Určuje, zdali je uživatel ve výchozím stavu po registraci přihlášen k odebírání cvičných
   *  phishingových zpráv (1 nebo 0).
   */
  define('NEW_USER_PARTICIPATION', 1);

  /** Určuje, zdali je uživatel ve výchozím stavu po nucené registraci v kampani (tzn. do Phishingatoru se uživatel
   *  nepřihlásil sám, ale byl registrován někým jiným) přihlášen k odebírání cvičných phishingových zpráv (1 nebo 0).
   */
  define('NEW_USER_BY_CAMPAIGN_PARTICIPATION', 0);


  /** Výchozí limit počtu e-mailů, který mají uživatelé po registraci nastaven (jedná se o dobrovolný limit, tzn. pokud
   *  bude uživatel zapojen do kampaně někým, kdo má vyšší oprávnění, tento limit počtu přijatých e-mailů nehraje roli).
   */
  define('NEW_USER_PARTICIPATION_EMAILS_LIMIT', 10);

  /** Výchozí limit počtu e-mailů, který mají uživatelé po nucené registraci (tzn. do Phishingatoru se uživatel
   *  nepřihlásil sám, ale byl registrován někým jiným) nastaven (jedná se o dobrovolný limit, tzn. pokud bude
   *  uživatel zapojen do kampaně někým, kdo má vyšší oprávnění, tento limit počtu přijatých e-mailů nehraje roli).
   */
  define('NEW_USER_BY_CAMPAIGN_PARTICIPATION_EMAILS_LIMIT', NULL);


  /** Výchozí skupina, která je uživatelům při registraci přidělena. */
  define('NEW_USER_DEFAULT_GROUP_ID', 3);

  /** Výchozí skupina, která je uživatelům při nucené registraci přidělena (tzn. do Phishingatoru se uživatel
      nepřihlásil sám, ale byl registrován někým jiným). */
  define('NEW_USER_BY_CAMPAIGN_DEFAULT_GROUP_ID', 3);



  /* --- ADRESÁŘOVÁ STRUKTURA SYSTÉMU --- */
  /** Hlavní adresář, od kterého se budou odvíjet další adresáře. */
  define('CORE_DOCUMENT_ROOT', '/var/www/phishingator');

  /** Adresář obsahující soubory controllers (kontrolerů). */
  define('CORE_DIR_CONTROLLERS', 'core/controllers');

  /** Adresář obsahující soubory models (modelů). */
  define('CORE_DIR_MODELS', 'core/models');

  /** Adresář obsahující soubory views (pohledů). */
  define('CORE_DIR_VIEWS', 'core/views');

  /** Adresář obsahující další dodatečné knihovny. */
  define('CORE_DIR_EXTENSIONS', 'extensions');

  /** Adresář pro umístění dočasných (temp) souborů. */
  define('CORE_DIR_TEMP', '/tmp');

  /** Přípona view souborů. */
  define('CORE_VIEWS_FILE_EXTENSION', '.php');



  /* --- CROSS-SITE REQUEST FORGERY --- */
  /** Klíč přidávaný ke generovanému CSRF tokenu (libovolný, náhodný řetězec). */
  define('CSRF_KEY', (getenv('CSRF_KEY')) ? getenv('CSRF_KEY') : base64_encode(openssl_random_pseudo_bytes(32)));



  /* --- EXTERNÍ PŘÍSTUP --- */
  /** Token nutný pro externí přístup (libovolný, náhodný řetězec). */
  define('PHISHINGATOR_TOKEN', (getenv('PHISHINGATOR_TOKEN')) ? getenv('PHISHINGATOR_TOKEN') : base64_encode(openssl_random_pseudo_bytes(32)));

  /** Lokální IP adresa, které je jako jediné povoleno získat seznam podvodných domén. */
  define('DOMAINER_ALLOWED_IP', getenv('DOMAINER_ALLOWED_IP'));



  /* --- MONITORING --- */
  /** IP adresy (oddělené čárkou), které mají přístup k zobrazení monitoringu. */
  define('MONITORING_ALLOWED_IP', getenv('MONITORING_ALLOWED_IP'));

  /** Určuje, zdali se má nad testovacím uživatelem přeskočit test monitoringu zadání neplatných údajů
   * (např. z důvodu možného zablokování testovacího účtu při neplatných pokusech). */
  define('MONITORING_SKIP_TEST_CREDS_INVALID', (getenv('MONITORING_SKIP_TEST_CREDS_INVALID') && getenv('MONITORING_SKIP_TEST_CREDS_INVALID') == 1));

  /** Uživatelské jméno testovacího uživatele (sondy), který v rámci monitoringu periodicky a automatizovaně
   * testuje funkčnost aplikace (např. funkčnost ověřování přihlašovacích údajů). U testovacího uživatele
   * se neprovádí logování přihlášení. */
  define('TEST_USERNAME', getenv('TEST_USERNAME'));

  /** Sufix automaticky přidávaný k uživatelskému jménu testovacího uživatele pro přístup do LDAP. */
  define('TEST_USERNAME_LDAP_SUFFIX', getenv('TEST_USERNAME_LDAP_SUFFIX'));

  /** Heslo testovacího uživatele. */
  define('TEST_PASSWORD', getenv('TEST_PASSWORD'));



  /* --- CONTENT SECURITY POLICY --- */
  /** Hodnota nonce pro Content Security Policy (CSP). */
  define('HTTP_HEADER_CSP_NONCE', base64_encode(openssl_random_pseudo_bytes(32)));



  /* --- SPECIÁLNÍ E-MAILOVÁ HLAVIČKA --- */
  /** Identifikátor speciální hlavičky, která se vkládá ke každému odeslanému e-mailu. */
  define('PHISHING_EMAIL_HEADER_ID', 'X-CESNET-Phishingator');

  /** Hodnota zobrazená u speciální hlavičky. */
  define('PHISHING_EMAIL_HEADER_VALUE', 'https://phishingator.cesnet.cz/.well-known/security.txt');



  /* --- ANONYMIZACE HESEL Z PODVODNÝCH STRÁNEK --- */
  /** Úroveň anonymizace hesel, které se získávají odesláním přihlašovacího formuláře na podvodných stránkách.
        Možnosti:
          between       - první a poslední znak nebude anonymizován, zbytek hesla anonymizován (počet znaků hesla zůstane zachován)
          between3stars - první a poslední znak nebude anonymizován, zbytek hesla anonymizován a zkrácen na 3 znaky (tj. počet znaků hesla bude vždy 5)
          full          - kompletní anonymizace (počet znaků hesla zůstane zachován)
          none          - žádná anonymizace, heslo bude zachováno v plain textu
  */
  define('PASSWORD_LEVEL_ANONYMIZATION', (getenv('PASSWORD_LEVEL_ANONYMIZATION')) ? getenv('PASSWORD_LEVEL_ANONYMIZATION') : 'between3stars');

  /** Znak, který se použije místo původního znaku hesla. */
  define('PASSWORD_CHAR_ANONYMIZATION', '*');



  /* --- IDENTIFIKÁTOR UŽIVATELŮ NA PODVODNÝCH STRÁNKÁCH --- */
  /** Délka identifikátoru, který se používá ke konkretizaci uživatele na podvodných stránkách.
   *  Pozor, musí se jednat o sudé číslo! */
  define('USER_ID_WEBSITE_LENGTH', 6);

  /** Sufix k identifikátoru uživatele na podvodné stránce. */
  define('USER_ID_WEBSITE_SUFFIX', '1');



  /* --- EXPORTY SOUBORŮ --- */
  /** Prefix souborů exportovaných z Phishingatoru. */
  define('PHISHING_CAMPAIGN_EXPORT_FILENAME', 'phishingator-campaign');

  /** Oddělovač používaný k oddělení hodnot v exportovaných CSV souborech. */
  define('PHISHING_CAMPAIGN_EXPORT_DELIMITER', ',');



  /* --- PODVODNÉ STRÁNKY --- */
  /** Adresář pro ukládání konfiguračních VirtualHost souborů pro Apache pro podvodné stránky. */
  define('PHISHING_WEBSITE_CONF_DIR', '/var/www/phishingator/templates/sites-config/');

  /** Adresář, kde se nacházejí dostupné konfigurační VirtualHost soubory pro Apache. */
  define('PHISHING_WEBSITE_APACHE_DIR', '/etc/apache2/sites-available/');

  /** Přípona konfiguračního souboru podvodné stránky. */
  define('PHISHING_WEBSITE_CONF_EXT', '.conf');

  /** Přípona konfiguračního souboru nově vytvořené podvodné stránky. */
  define('PHISHING_WEBSITE_CONF_EXT_NEW', '.conf.new');

  /** Přípona konfiguračního souboru podvodné stránky určené ke smazání. */
  define('PHISHING_WEBSITE_CONF_EXT_DEL', '.conf.delete');

  /** Cesta k souboru, který obsluhuje požadavky uživatelů na podvodných stránkách. */
  define('PHISHING_WEBSITE_PREPENDER', '/var/www/phishingator/websitePrepender.php');

  /** Název souboru (včetně přípony), v němž je uložen screenshot (ve formátu PNG a o šířce 800 px) podvodné stránky. */
  define('PHISHING_WEBSITE_SCREENSHOT_FILENAME', 'thumbnail.png');

  /** Povolené znaky v názvech subdomén u podvodných stránek v proxy Phishingatoru. */
  define('PHISHING_WEBSITE_SUBDOMAINS_REGEXP', '/[^a-z0-9]/');

  /** Regulární výraz specifikující, čím může začínat a jaké znaky může obsahovat uživatelské jméno zadané na podvodné stránce. */
  define('PHISHING_WEBSITE_USERNAME_REGEXP', '/^[a-zA-Z0-9]+[a-zA-Z0-9._@-]+$/');

  /** Název vstupního pole na podvodné stránce, do kterého uživatel zadává uživatelské jméno. */
  define('PHISHING_WEBSITE_INPUT_FIELD_USERNAME', 'username');

  /** Název vstupního pole na podvodné stránce, do kterého uživatel zadává heslo. */
  define('PHISHING_WEBSITE_INPUT_FIELD_PASSWORD', 'password');

  /** Seznam názvů webových prohlížečů (oddělené čárkou), jejichž akce nemají být ukládány při návštěvě podvodné stránky. */
  define('PHISHING_WEBSITE_IGNORED_USER_AGENTS', (getenv('PHISHING_WEBSITE_IGNORED_USER_AGENTS')) ? getenv('PHISHING_WEBSITE_IGNORED_USER_AGENTS') : 'MicrosoftPreview');

  /** Seznam IP adres, popř. IP rozsahů (oddělené čárkou), jejichž akce nemají být ukládány při návštěvě podvodné stránky. */
  define('PHISHING_WEBSITE_IGNORED_IP', getenv('PHISHING_WEBSITE_IGNORED_IP'));

  /** Identifikátor pro náhled podvodné stránky nahrazující identifikátor phishingové kampaně. */
  define('PHISHING_WEBSITE_PREVIEW_ID', 0);

  /** Délka tokenu (v bajtech) pro náhled podvodné stránky. */
  define('PHISHING_WEBSITE_PREVIEW_TOKEN_LENGTH_B', 32);

  /** Doba platnosti tokenu (v sekundách) pro náhled podvodné stránky. */
  define('PHISHING_WEBSITE_PREVIEW_TOKEN_VALIDITY_S', 60);

  /** Název cookie identifikující uživatele na podvodné stránce. */
  define('PHISHING_WEBSITE_COOKIE', 'phishingator');

  /** Doba platnosti cookie (v sekundách) identifikující uživatele na podvodné stránce. */
  define('PHISHING_WEBSITE_COOKIE_VALIDITY_S', 1800);



  /* --- PHISHINGOVÉ KAMPANĚ --- */
  /** Způsob agregace statistiky v kampaních – na základě:
   *  - 1 = LDAP skupiny uživatele (výchozí)
   *  - 2 = subdomény v e-mailu uživatele
   */
  define('CAMPAIGN_STATS_AGGREGATION', (getenv('CAMPAIGN_STATS_AGGREGATION')) ? getenv('CAMPAIGN_STATS_AGGREGATION') : 1);

  /** Výchozí nastavení rozmazání identit uživatelů ve statistikách kampaně. */
  define('CAMPAIGN_STATS_BLUR_IDENTITIES', (getenv('CAMPAIGN_STATS_BLUR_IDENTITIES') && getenv('CAMPAIGN_STATS_BLUR_IDENTITIES') == 1));

  /** Identifikátor výchozí akce po odeslání formuláře (bude ve formuláři při vytváření kampaně předvybrána). */
  define('CAMPAIGN_DEFAULT_ONSUBMIT_ACTION', (getenv('CAMPAIGN_DEFAULT_ONSUBMIT_ACTION')) ? getenv('CAMPAIGN_DEFAULT_ONSUBMIT_ACTION') : 2);

  /** Znak používaný k oddělování příjemců v kampani. */
  define('CAMPAIGN_EMAILS_DELIMITER', "\n");

  /** Nastavení, zdali má být v uživatelské sekci "Přijaté phishingové e-maily" skryt e-mail, pokud daná kampaň zatím ještě neskončila. */
  define('CAMPAIGN_ACTIVE_HIDE_EMAILS', (getenv('CAMPAIGN_ACTIVE_HIDE_EMAILS') && getenv('CAMPAIGN_ACTIVE_HIDE_EMAILS') == 1));



  /* --- STRÁNKOVÁNÍ --- */
  /** Minimální počet záznamů, které si může uživatel nechat vypsat na stránce. */
  define('PAGING_MIN_RECORDS_ON_PAGE', 5);

  /** Maximální počet záznamů, které si může uživatel nechat vypsat na stránce. */
  define('PAGING_MAX_RECORDS_ON_PAGE', 1000);

  /** Výchozí počet záznamů na jedné stránce. */
  define('PAGING_DEFAULT_COUNT_RECORDS_ON_PAGE', 20);



  /* --- PROMĚNNÉ POUŽÍVANÉ V PODVODNÝCH E-MAILECH, KTERÉ JSOU PŘI ODESLÁNÍ NAHRAZENY SKUTEČNÝM OBSAHEM --- */
  /** Regulární výraz, kterému vyhovují všechny proměnné používané v e-mailu. */
  define('VAR_REGEXP', '%[a-z_]+%');

  /** Uživatelské jméno příjemce e-mailu. */
  define('VAR_RECIPIENT_USERNAME', '%recipient_username%');

  /** E-mail příjemce. */
  define('VAR_RECIPIENT_EMAIL', '%recipient_email%');

  /** Jméno a příjmení příjemce e-mailu. */
  define('VAR_RECIPIENT_FULLNAME', '%recipient_name%');

  /** Křestní jméno příjemce e-mailu. */
  define('VAR_RECIPIENT_FIRSTNAME', '%recipient_firstname%');

  /** Příjmení příjemce e-mailu. */
  define('VAR_RECIPIENT_SURNAME', '%recipient_surname%');

  /** Datum v českém formátu. */
  define('VAR_DATE_CZ', '%date_cz%');

  /** Formát českého data. */
  define('VAR_DATE_CZ_FORMAT', 'j. n. Y');

  /** Datum v jiné formě zápisu. */
  define('VAR_DATE_EN', '%date_en%');

  /** Formát data pro jinou formu zápisu. */
  define('VAR_DATE_EN_FORMAT', 'Y-m-d');

  /** URL podvodné stránky. */
  define('VAR_URL', '%url%');

  /** Text nahrazující URL podvodné stránky v náhledu. */
  define('VAR_URL_PREVIEW', '[URL podvodné stránky]');

  /** Identifikátor příjemce pro URL podvodné stránky. */
  define('VAR_RECIPIENT_URL', '%id%');



  /* --- PROMĚNNÉ POUŽÍVANÉ U INDICIÍ V PODVODNÝCH E-MAILECH --- */
  /** Proměnná odkazující na jméno odesílatele e-mailu. */
  define('VAR_INDICATION_SENDER_NAME', '%sender_name%');

  /** Proměnná odkazující na e-mail odesílatele. */
  define('VAR_INDICATION_SENDER_EMAIL', '%sender_email%');

  /** Proměnná odkazující na předmět podvodného e-mailu. */
  define('VAR_INDICATION_SUBJECT', '%subject%');



  /* --- IDENTIFIKÁTORY AKCÍ V KAMPANI DÁLE VYUŽÍVANÝCH VE ZDROJOVÉM KÓDU --- */
  /** Identifikátor akce "bez reakce". */
  define('CAMPAIGN_NO_REACTION_ID', 1);

  /** Identifikátor akce "návštěva podvodné stránky". */
  define('CAMPAIGN_VISIT_FRAUDULENT_PAGE_ID', 2);

  /** Identifikátor akce "zadání neplatných (přihlašovacích) údajů". */
  define('CAMPAIGN_INVALID_CREDENTIALS_ID', 3);

  /** Identifikátor akce "zadání platných (přihlašovacích) údajů". */
  define('CAMPAIGN_VALID_CREDENTIALS_ID', 4);

  /** Identifikátor akce "přístup na vzdělávací stránku". */
  define('CAMPAIGN_VISIT_EDUCATIONAL_SITE_ID', 5);

  /** Identifikátor akce "nahlášení phishingu". */
  define('CAMPAIGN_REPORT_PHISHING_ID', 6);



  /* --- ODESÍLÁNÍ E-MAILŮ --- */
  /** Maximální počet e-mailů, které se pošlou v jedné iteraci cyklu (poté se skript na určitou dobu pozastaví,
      viz další konstanta). */
  define('EMAIL_SENDER_EMAILS_PER_CYCLE', 5);

  /** Zpoždění mezi tím, než se pošle další počet (sada) e-mailů (viz předchozí konstanta). */
  define('EMAIL_SENDER_DELAY_MS', 2000);

  /** O kolik sekund déle bude moct skript běžet poté, co byl pozastaven (viz předchozí konstanta). */
  define('EMAIL_SENDER_CPU_TIME_S', 10);



  /* --- DOMÉNY, ZE KTERÝCH MOHOU POCHÁZET PŘÍJEMCI --- */
  /** Seznam povolených domén (oddělené čárkou), ze kterých mohou pocházet uživatelé a zároveň příjemci cvičného phishingu. */
  define('EMAILS_ALLOWED_DOMAINS', getenv('EMAILS_ALLOWED_DOMAINS') ? getenv('EMAILS_ALLOWED_DOMAINS') : getenv('ORG_DOMAIN'));



  /* --- ODDĚLOVÁNÍ NÁZVŮ LDAP SKUPIN --- */
  /** Oddělovač LDAP skupin, které jsou zobrazené v seznamu příjemců. */
  define('LDAP_GROUPS_DELIMITER', ';');



  /* --- ODDĚLOVAČ DATA --- */
  /** Oddělovač data pro vstup od uživatele. */
  define('DATE_DELIMETER', '-');



  /* --- OPRÁVNĚNÍ V SYSTÉMU --- */
  /** Nejvyšší administrátorské oprávnění. */
  define('PERMISSION_ADMIN', 0);

  /** Název pro URL pro nejvyšší administrátorské oprávnění. */
  define('PERMISSION_ADMIN_URL', 'administrator');

  /** Název nejvyššího administrátorského oprávnění. */
  define('PERMISSION_ADMIN_TEXT', 'administrátor');

  /** Druhé nejvyšší oprávnění (správce testů). */
  define('PERMISSION_TEST_MANAGER', 1);

  /** Název pro URL pro druhé nejvyšší oprávnění (správce testů). */
  define('PERMISSION_TEST_MANAGER_URL', 'test-manager');

  /** Název druhého nejvyššího oprávnění (správce testů). */
  define('PERMISSION_TEST_MANAGER_TEXT', 'správce testů');

  /** Nejnižší oprávnění. */
  define('PERMISSION_USER', 2);

  /** Název pro URL pro nejnižší oprávnění (uživatel). */
  define('PERMISSION_USER_URL', 'user');

  /** Název nejnižšího oprávnění. */
  define('PERMISSION_USER_TEXT', 'uživatel');



  /* --- TYPY A CSS TŘÍDY SYSTÉMOVÝCH HLÁŠENÍ --- */
  /* Názvy hlášení a jejich přiřazení k Exception->getCode() používaných ve zdrojovém kódu. */

  /** Typ: error */
  define('MSG_ERROR', 1);

  /** CSS třída: error */
  define('MSG_CSS_ERROR', 'danger');

  /** Typ: success */
  define('MSG_SUCCESS', 2);

  /** CSS třída: success */
  define('MSG_CSS_SUCCESS', 'success');

  /** Typ: warning */
  define('MSG_WARNING', 3);

  /** CSS třída: warning */
  define('MSG_CSS_WARNING', 'warning');

  /** CSS třída: default */
  define('MSG_CSS_DEFAULT', 'secondary');



  /* --- URL AKCÍ POUŽÍVANÝCH VE ZDROJOVÉM KÓDU --- */
  /** Přepnutí role uživatele. */
  define('ACT_SWITCH_ROLE', 'switch-role');

  /** Vytvoření nového záznamu. */
  define('ACT_NEW', 'new');

  /** Úprava existujícího záznamu. */
  define('ACT_EDIT', 'edit');

  /** Smazání záznamu. */
  define('ACT_DEL', 'delete');

  /** Zobrazení statistiky. */
  define('ACT_STATS', 'stats');

  /** Zobrazení statistiky – reakce jednotlivých uživatelů. */
  define('ACT_STATS_USERS_RESPONSES', 'users-responses');

  /** Zobrazení statistiky – všechny akce provedené na podvodné stránce. */
  define('ACT_STATS_WEBSITE_ACTIONS', 'website-actions');

  /** Úprava nastavení uživatelského hlášení o phishingu. */
  define('ACT_STATS_REPORT_PHISH', 'report');

  /** Rozmazání identit ve statistikách kampaně. */
  define('ACT_STATS_BLUR_IDENTITIES', 'blur-identities');

  /** Zastavení kampaně. */
  define('ACT_STOP', 'stop');

  /** Export dat. */
  define('ACT_EXPORT', 'export');

  /** Náhled. */
  define('ACT_PREVIEW', 'preview');

  /** Duplikace záznamu. */
  define('ACT_DUPLICATE', 'duplicate');

  /** Indicie. */
  define('ACT_INDICATIONS', 'indications');

  /** Stránka s informací o absolvování cvičného phishingu (na veřejné části webu). */
  define('ACT_PHISHING_TEST', 'phishing');

  /** Zobrazení screenshotu podvodné stránky. */
  define('ACT_PHISHING_IMG', 'image');