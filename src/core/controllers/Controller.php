<?php
  /**
   * Třída shromažďující obecné promměné a metody nutné ke komunikaci s nižší vrstvou View (pohledem),
   * které každý Controller zdědí.
   *
   * @author Martin Šebela
   */
  abstract class Controller {
    /**
     * @var string      Název hlavičky View (horní části stránky)
     */
    protected $viewHeaderName;

    /**
     * @var string      Název View vkládaného do stránky (pod hlavičku)
     */
    protected $viewName;

    /**
     * @var array       Data dále používaná ve View
     */
    protected $viewData = [];

    /**
     * @var string      Nadpis stránky (HTML tag title)
     */
    protected $htmlTitle;

    /**
     * @var string      Název URL sekce
     */
    protected $urlSection;

    /**
     * @var string      CSRF token
     */
    protected $csrfToken;

    /**
     * @var string      Odkaz na nápovědu
     */
    protected $helpLink;


    /**
     * Zpracuje vstup z URL adresy a na základě toho zavolá odpovídající metodu (nutno implementovat).
     *
     * @param string $arguments        Uživatelský vstup
     */
    public abstract function process($arguments);


    /**
     * Nastaví data určená pro View týkající se formuláře.
     *
     * @param mixed $model             Instance třídy modelu podle formuláře
     * @param string $action           Název akce, ke které bude docházet ve formuláři
     * @param string $formPrefix       Prefix formuláře
     * @return void
     */
    public function initViewData($model, $action, $formPrefix) {
      $this->setViewData('action', $action);
      $this->setViewData('formPrefix', $formPrefix);
      $this->setViewData('inputsValues', $model->getInputsValues());
      $this->setViewData('inputsMaxLengths', $model->getInputsMaxLengths());
    }


    /**
     * Nastaví název View, který se bude vkládat do stránky.
     *
     * @param string $view             Název souboru View (bez přípony)
     * @param bool $headerView         TRUE pokud se jedná o hlavičku pohledu (horní část stránky)
     * @return void
     */
    public function setView($view, $headerView = false) {
      $viewFilepath = CORE_DOCUMENT_ROOT . '/' . CORE_DIR_VIEWS . '/' . $view . CORE_VIEWS_FILE_EXTENSION;

      if (!empty($view) && file_exists($viewFilepath)) {
        if ($headerView === false) {
          $this->viewName = $view;
        }
        else {
          $this->viewHeaderName = $view;
        }
      }
    }


    /**
     * Ověří existenci souboru hlavičky View a v případě jeho nalezení jej vloží do stránky společně s extrahovanými
     * proměnnými používanými ve View.
     *
     * @return void
     */
    public function displayHeader() {
      $viewFilepath = CORE_DOCUMENT_ROOT . '/' . CORE_DIR_VIEWS . '/' . $this->viewHeaderName . CORE_VIEWS_FILE_EXTENSION;

      if (!empty($this->viewHeaderName) && file_exists($viewFilepath)) {
        $this->setViewData('helpLink', $this->helpLink);

        extract($this->viewData);

        require $viewFilepath;
      }
    }


    /**
     * Ověří existenci souboru View a v případě jeho nalezení jej vloží do stránky společně s extrahovanými
     * proměnnými používanými ve View.
     *
     * @return void
     */
    public function displayView() {
      $viewFilepath = CORE_DOCUMENT_ROOT . '/' . CORE_DIR_VIEWS . '/' . $this->viewName . CORE_VIEWS_FILE_EXTENSION;

      if (!empty($this->viewName) && file_exists($viewFilepath)) {
        $this->setViewData('helpLink', $this->helpLink);
        $this->setViewData('csrfToken', PermissionsModel::getCsrfToken());

        extract(self::escapeOutput($this->viewData));
        extract($this->viewData, EXTR_PREFIX_ALL, '');

        require $viewFilepath;
      }
    }


    /**
     * Přidá data, která budou dále využívána pro Model.
     *
     * @param string $indexName        Index (resp. proměnná), přes kterou budou data dostupná
     * @param mixed $data              Data
     * @return void
     */
    public function setViewData($indexName, $data) {
      $this->viewData[$indexName] = $data;
    }


    /**
     * Vrátí konkrétní data, která jsou uložena mezi již přidanými daty.
     *
     * @param string $indexName        Index, pod kterým jsou data uložena
     * @return null|mixed              Požadovaná data nebo NULL, pokud index neexistuje
     */
    public function getData($indexName) {
      $data = null;

      if (array_key_exists($indexName, $this->viewData)) {
        $data = $this->viewData[$indexName];
      }

      return $data;
    }


    /**
     * Ošetří řetězec nebo všechny prvky pole pro výstup do stránky.
     *
     * @param string|array $pattern    Ošetřovaný řetězec/pole
     * @return array|string            Ošetřený řetězec pro výstup do stránky
     */
    public static function escapeOutput($pattern) {
      if (is_array($pattern)) {
        foreach ($pattern as $key => $value) {
          $pattern[$key] = self::escapeOutput($value);
        }

        $escaped = $pattern;
      }
      else {
        $escaped = htmlspecialchars(stripslashes(trim($pattern ?? '')), ENT_QUOTES);
      }

      return $escaped;
    }


    /**
     * Vrátí řetězec s dekódovanými HTML entitami.
     *
     * @param string $string           Řetězec s HTML entitami
     * @return string                  Řetězec s dekódovanými HTML entitami
     */
    public static function decodeHtmlEntities($string) {
      return html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }


    /**
     * Přidá do seznamu nezobrazených systémových zpráv další položku.
     *
     * @param string $type             Typ systémové zprávy
     * @param string $message          Text systémové zprávy
     * @return void
     */
    public function addMessage($type, $message) {
      $message = [
        'type' => $this->getMessageColorByType($type),
        'message' => $message
      ];

      if (isset($_SESSION['messages'])) {
        $_SESSION['messages'][] = $message;
      }
      else {
        $_SESSION['messages'] = [$message];
      }
    }


    /**
     * Vrátí CSS třídu (barvu) systémové hlášky na základě jejího typu.
     *
     * @param string $type             Typ systémové zprávy
     * @return string                  Název CSS třídy
     */
    private function getMessageColorByType($type) {
      switch ($type) {
        case MSG_ERROR:
          return MSG_CSS_ERROR;
        case MSG_WARNING:
          return MSG_CSS_WARNING;
        case MSG_SUCCESS:
          return MSG_CSS_SUCCESS;
        default:
          return MSG_CSS_DEFAULT;
      }
    }


    /**
     * Vrátí seznam systémových zpráv pro uživalele.
     *
     * @return array                   Pole systémových zpráv
     */
    public static function getMessages() {
      $messages = [];

      if (isset($_SESSION['messages'])) {
        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);
      }

      return $messages;
    }


    /**
     * Vrátí možnosti dostupné v menu v závislosti na právě zvolené roli uživatele.
     *
     * @return array                   Menu s položkami
     */
    public function getMenu() {
      $menu = [
        'Úvodní stránka' =>              ['minRole' => PERMISSION_USER, 'url' => '', 'icon' => 'home'],
        'Kampaně' =>                     ['minRole' => PERMISSION_TEST_MANAGER, 'url' => 'campaigns', 'icon' => 'layers'],
        'Podvodné e-maily' =>            ['minRole' => PERMISSION_TEST_MANAGER, 'url' => 'phishing-emails', 'icon' => 'mail'],
        'Podvodné stránky' =>            ['minRole' => PERMISSION_ADMIN, 'url' => 'phishing-websites', 'icon' => 'link'],
        'Uživatelé' =>                   ['minRole' => PERMISSION_ADMIN, 'url' => 'users', 'icon' => 'user'],
        'Skupiny' =>                     ['minRole' => PERMISSION_ADMIN, 'url' => 'user-groups', 'icon' => 'users'],
        'Roční statistiky' =>            ['minRole' => PERMISSION_ADMIN, 'url' => 'stats', 'icon' => 'bar-chart-2'],
        'Moje účast v&nbsp;programu' =>  ['minRole' => PERMISSION_USER, 'maxRole' => PERMISSION_USER, 'url' => 'my-participation', 'icon' => 'activity'],
        'Přijaté phishingové e-maily' => ['minRole' => PERMISSION_USER, 'maxRole' => PERMISSION_USER, 'url' => 'recieved-phishing-emails', 'icon' => 'mail']
      ];

      return $this->removeUnavailableMenuItems($menu, PermissionsModel::getUserRole());
    }


    /**
     * Vrátí možnosti dostupné v menu s rolemi v závislosti na právě zvolené roli uživatele.
     *
     * @return array                   Menu s rolemi
     */
    public function getRolesMenu() {
      $menu = [
        PERMISSION_ADMIN =>        ['minRole' => PERMISSION_ADMIN, 'url' => PERMISSION_ADMIN_URL, 'name' => PERMISSION_ADMIN_TEXT],
        PERMISSION_TEST_MANAGER => ['minRole' => PERMISSION_TEST_MANAGER, 'url' => PERMISSION_TEST_MANAGER_URL, 'name' => PERMISSION_TEST_MANAGER_TEXT],
        PERMISSION_USER =>         ['minRole' => PERMISSION_USER, 'url' => PERMISSION_USER_URL, 'name' => PERMISSION_USER_TEXT]
      ];

      return $this->removeUnavailableMenuItems($menu, PermissionsModel::getUserPermission());
    }


    /**
     * Odstraní z menu položky, ke kterým by uživatel podle své role neměl mít přístup.
     *
     * @param array $menu              Menu se všemi položkami
     * @param int $minRole             Minimální požadovaná role
     * @return array                   Menu bez položek, ke kterým by uživatel neměl mít přístup
     */
    private function removeUnavailableMenuItems($menu, $minRole) {
      foreach ($menu as $key => $item) {
        if ($item['minRole'] < $minRole || (isset($item['maxRole']) && $item['maxRole'] > $minRole)) {
          unset($menu[$key]);
        }
      }

      return $menu;
    }


    /**
     * Přesměruje uživatele na konkrétní adresu v rámci systému.
     *
     * @param null|string $target      Relativní URL v rámci systému (nepovinný parametr). Při nevyplnění dojde
     *                                 k přesměrování na úvodní stránku přihlášeného uživatele.
     * @param null|int $redirectToRoot TRUE pro přesměrování do kořene webu (nepovinný parametr).
     * @return void
     */
    public function redirect($target = null, $redirectToRoot = null) {
      $target = (($redirectToRoot == null) ? 'portal/' : '') . (($target != null) ? $target : '');

      header('Location: ' . WEB_URL . '/' . $target);
      exit();
    }


    /**
     * Nastaví URL název sekce, přes kterou je dostupná.
     *
     * @param string $section          URL sekce
     * @return void
     */
    public function setUrlSection($section) {
      if (!empty($section)) {
        $this->urlSection = $section;
        $this->setViewData('urlSection', $this->urlSection);
      }
    }


    /**
     * Nastaví nadpis stránky (HTML tag title).
     *
     * @param string $htmlTitle        Nadpis stránky
     * @return void
     */
    public function setTitle($htmlTitle) {
      if (!empty($htmlTitle)) {
        $this->htmlTitle = $htmlTitle . ' · Phishingator';
      }
    }


    /**
     * Vrátí nadpis stránky (HTML tag title).
     *
     * @return string                  Nadpis stránky
     */
    public function getTitle() {
      return $this->htmlTitle;
    }


    /**
     * Nastaví odkaz na nápovědu pro danou sekci.
     *
     * @param string $link             Odkaz na nápovědu
     * @return void
     */
    public function setHelpLink($link) {
      if (!empty($link)) {
        $this->helpLink = $link;
      }
    }


    /**
     * Vrátí popisek pro patičku tabulky se seznamem záznamů.
     *
     * @param int $countRecords        Počet záznamů
     * @return string                  Popisek pro patičku tabulky
     */
    public function getTableFooter($countRecords) {
      $text = 'Žádné záznamy';

      if ($countRecords > 0) {
        $text = 'Celkem ' . $countRecords . ' ';

        if ($countRecords == 1) {
          $text .= 'záznam';
        }
        elseif ($countRecords >= 2 && $countRecords <= 4) {
          $text .= 'záznamy';
        }
        else {
          $text .= 'záznamů';
        }
      }

      return $text . '.';
    }


    /**
     * Ověří, zdali má uživatel dostatečné oprávnění ke vstupu do dané sekce.
     *
     * @param int $minPermission       Hodnota uživatelova oprávnění
     * @return void
     */
    public function checkPermission($minPermission) {
      if (PermissionsModel::getUserRole() > $minPermission) {
        $this->addMessage(MSG_ERROR, 'Pro tuto akci nemáte dostatečná oprávnění.');
        $this->redirect();
      }
    }


    /**
     * Ověří, zdali je předaný záznam prázdný a pokud ano, vyhodí výjimku, aby kód dále nepokračoval.
     *
     * @param mixed $record            Kontrolovaný záznam
     * @return void
     */
    public function checkRecordExistence($record) {
      if (empty($record) || empty(array_filter($record))) {
        $this->addMessage(MSG_ERROR, 'Zvolený záznam neexistuje.');
        $this->redirect($this->urlSection);
      }
    }
  }
