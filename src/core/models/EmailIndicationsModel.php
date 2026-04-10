<?php
  /**
   * Třída sloužící k získávání informací o indiciích u podvodných e-mailů,
   * k přidávání nových indicií, k úpravě těch existujících a dalším souvisejícím operacím.
   *
   * @author Martin Šebela
   */
  class EmailIndicationsModel extends FormModel {
    /**
     * @var int         ID cvičného podvodného e-mailu, ke kterému indicie patří.
     */
    protected $idEmail;

    /**
     * @var string      Tělo cvičného podvodného e-mailu v plain textu, které slouží pouze ke kontrole,
     *                  jestli obsahuje uživatelem vyplněnou indicii.
     */
    public $email;

    /**
     * @var int         Pořadí indicie při výpisu v seznamu indicií u podvodného e-mailu.
     */
    protected $position;

    /**
     * @var string      Výraz (podezřelý text), který představuje indicii k rozpoznání phishingu.
     */
    protected $expression;

    /**
     * @var string      Název indicie.
     */
    protected $title;

    /**
     * @var string      Popis indicie.
     */
    protected $description;


    /**
     * Načte a zpracuje předaná data.
     *
     * @param array $data              Vstupní data
     * @throws UserError
     */
    public function load($data) {
      parent::load($data);

      $this->idEmail = $_GET['id'];
    }


    /**
     * Připraví novou indicii pro podvodný e-mail v závislosti na vyplněných datech a vrátí ji formou pole.
     *
     * @return array                   Pole obsahující data o indicii
     */
    private function makeEmailIndication() {
      return [
        'id_email' => $this->idEmail,
        'position' => $this->position,
        'expression' => $this->expression,
        'title' => $this->title,
        'description' => $this->description
      ];
    }


    /**
     * Uloží do instance a zároveň vrátí (z databáze) informace o zvolené indicii.
     *
     * @param int $id                  ID indicie
     * @return array                   Pole obsahující informace o indicii
     */
    public function getEmailIndication($id) {
      $this->dbRecordData = Database::querySingle('
                              SELECT `position`, `expression`, `title`, `description`
                              FROM `phg_emails_indications`
                              WHERE `id_indication` = ?
                              AND `visible` = 1
                           ', $id);

      return $this->dbRecordData;
    }


    /**
     * Vrátí seznam všech indicií přidaných ke konkrétnímu cvičnému podvodnému e-mailu.
     *
     * @param int $idEmail             ID podvodného e-mailu
     * @return mixed                   Pole indicií s informacemi o každé z nich
     */
    public static function getEmailIndications($idEmail) {
      return Database::queryMulti('
              SELECT `id_indication`, `position`, `expression`, `title`, `description`
              FROM `phg_emails_indications`
              WHERE `id_email` = ?
              AND `visible` = 1
              ORDER BY `position`, `id_indication`
      ', $idEmail);
    }


    /**
     * Vrátí počet indicií přidaných ke konkrétnímu cvičnému podvodnému e-mailu.
     *
     * @param int $idEmail             ID podvodného e-mailu
     * @return int                     Počet indicií
     */
    public static function getCountEmailIndications($idEmail) {
      return Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_emails_indications`
              WHERE `id_email` = ?
              AND `visible` = 1
      ', $idEmail);
    }


    /**
     * Vrátí název CSS třídy v závislosti na počtu indicií.
     *
     * @param int $sumIndications      Počet indicií u e-mailu
     * @return string|null             Název CSS třídy
     */
    public static function getColorByCountIndications($sumIndications) {
      $color = MSG_CSS_ERROR;

      if (is_numeric($sumIndications)) {
        if ($sumIndications > 2) {
          $color = 'light';
        }
        elseif ($sumIndications > 0) {
          $color = MSG_CSS_WARNING;
        }
      }

      return $color;
    }


    /**
     * Ověří, zdali již v databázi pro daný e-mail existuje konkrétní indicie.
     *
     * @param int $idEmail             ID podvodného e-mailu
     * @param string $expression       Výraz představující indicii
     * @param int $idIndication        ID indicie (nepovinný parametr) pro vyloučení právě upravované indicie.
     * @param bool $findSubstring      TRUE, pokud se má indicie vyhledávat jako podřetězec v ostatních indicích, jinak FALSE (výchozí)
     * @return mixed                   0 pokud indicie v databázi zatím neexistuje, jinak 1.
     */
    public static function existEmailIndication($idEmail, $expression, $idIndication = 0, $findSubstring = false) {
      if ($findSubstring) {
        $expression = '%' . $expression . '%';
      }

      return Database::queryCount('
               SELECT COUNT(*)
               FROM `phg_emails_indications`
               WHERE `id_indication` != ?
               AND `id_email` = ?
               AND `expression` LIKE ?
               AND `visible` = 1
      ', [$idIndication, $idEmail, $expression]);
    }


    /**
     * Vloží do databáze novou indicii.
     *
     * @throws UserError
     */
    public function insertEmailIndication() {
      $indication = $this->makeEmailIndication();

      $indication['id_by_user'] = PermissionsModel::getUserId();
      $indication['date_added'] = date('Y-m-d H:i:s');

      $this->isExpressionUnique();
      $this->isNotExpressionInExpressions();

      Logger::info('New phishing sign added.', $indication);

      Database::insert($this->dbTableName, $indication);
    }


    /**
     * Upraví zvolenou indicii.
     *
     * @param int $id                  ID indicie
     * @throws UserError
     */
    public function updateEmailIndication($id) {
      $indication = $this->makeEmailIndication();

      $this->isExpressionUnique($id);
      $this->isNotExpressionInExpressions($id);

      Logger::info('Phishing sign modified.', $indication);

      Database::update(
        $this->dbTableName,
        $indication,
        'WHERE `id_indication` = ? AND `visible` = 1',
        $id
      );
    }


    /**
     * Odstraní (resp. deaktivuje) indicii z databáze.
     *
     * @param int $id                  ID indicie
     * @throws UserError
     */
    public function deleteEmailIndication($id) {
      $result = Database::update(
        'phg_emails_indications',
        ['visible' => 0],
        'WHERE `id_indication` = ? AND `visible` = 1',
        $id
      );

      if ($result == 0) {
        Logger::warning('Attempt to delete a non-existent phishing sign.', $id);

        throw new UserError('Záznam vybraný ke smazání neexistuje.', MSG_ERROR);
      }

      Logger::info('Phishing sign deleted.', $id);
    }


    /**
     * Duplikuje indicii a přidá ji ke konkrétnímu podvodnému e-mailu.
     *
     * @param int $idIndication        ID duplikované indicie
     * @param int $idEmail             ID podvodného e-mailu, ke kterému se duplikovaná indicie přidá
     */
    public function duplicateEmailIndication($idIndication, $idEmail) {
      $indication = $this->getEmailIndication($idIndication);

      if (!empty($indication) && is_numeric($idEmail)) {
        $duplicatedIndication = [
          'id_by_user' => PermissionsModel::getUserId(),
          'id_email' => $idEmail,
          'position' => $this->dbRecordData['position'],
          'expression' => $this->dbRecordData['expression'],
          'title' => $this->dbRecordData['title'],
          'description' => $this->dbRecordData['description'],
          'date_added' => date('Y-m-d H:i:s')
        ];

        Logger::info('Phishing sign duplicated.', $duplicatedIndication);

        Database::insert('phg_emails_indications', $duplicatedIndication);
      }
    }


    /**
     * Vrátí data o indicii na základě klíče indicie.
     *
     * @param array $indications       Pole obsahující indicie
     * @param string $expression       Klíč hledané indicie
     * @return array|null              Identifikátor indicie nebo NULL
     */
    public static function findIndicationByExpression($indications, $expression) {
      $indicationData = null;

      foreach ($indications as $indication) {
        if (isset($indication['expression']) && $indication['expression'] === $expression) {
          $indicationData = $indication;
          break;
        }
      }

      return $indicationData;
    }


    /**
     * Vrátí identifikátor indicie na základě klíče indicie.
     *
     * @param array $indications       Pole obsahující indicie
     * @param string $expression       Klíč hledané indicie
     * @return int|null                Identifikátor indicie nebo NULL
     */
    public static function findIndicationIdByExpression($indications, $expression) {
      $indication = self::findIndicationByExpression($indications, $expression);

      return $indication['id_indication'] ?? null;
    }


    /**
     * Zkontroluje uživatelský vstup (atributy třídy), který se bude zapisovat do databáze.
     *
     * @throws UserError
     */
    public function validateData() {
      $this->isPositionEmpty();
      $this->isPositionNumeric();
      $this->isPositionInLimit();

      $this->isExpressionEmpty();
      $this->isExpressionTooShort();
      $this->isExpressionTooLong();
      $this->existExpressionInText();
      $this->isExpressionInValidPlacement();
      $this->isExpressionNotPartOfVariable();

      $this->isTitleEmpty();
      $this->isTitleTooLong();

      $this->isDescriptionTooLong();
    }


    /**
     * Ověří, zdali bylo vyplněno pořadí indicie.
     *
     * @throws UserError
     */
    private function isPositionEmpty() {
      if (empty($this->position)) {
        throw new UserError('Pořadí indicie nebylo vyplněno.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je pořadí indicie zadáno číselně.
     *
     * @throws UserError
     */
    private function isPositionNumeric() {
      if (!is_numeric($this->position)) {
        throw new UserError('Pořadí indicie není zadáno číselně.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali je pořadí indicie v číselném intervalu.
     *
     * @throws UserError
     */
    private function isPositionInLimit() {
      if ($this->position < 0 || $this->position > 100) {
        throw new UserError('Pořadí indicie je mimo povolený interval.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vyplněn podezřelý text k rozpoznání phishingu.
     *
     * @throws UserError
     */
    private function isExpressionEmpty() {
      if (empty($this->expression)) {
        throw new UserError('Není vyplněn podezřelý text.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný podezřelý text není příliš krátký.
     *
     * @throws UserError
     */
    private function isExpressionTooShort() {
      if (mb_strlen($this->expression) < 3) {
        throw new UserError('Podezřelý text je příliš krátký.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný podezřelý text není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isExpressionTooLong() {
      if (mb_strlen($this->expression) > $this->inputsMaxLengths['expression']) {
        throw new UserError('Podezřelý text je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali se podezřelý text nachází v těle e-mailu, nebo zda jde
     * o proměnnou, která se může vyskytovat v hlavičce e-mailu.
     *
     * @throws UserError
     */
    private function existExpressionInText() {
      if (!str_contains($this->email, $this->expression)
          && !in_array($this->expression, PhishingEmailModel::getEmailHeaderVariables())) {
        throw new UserError('Podezřelý text nebyl v e-mailu nalezen.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali nebyl jako podezřelý text vybrán HTML tag nebo jeho část.
     *
     * @throws UserError
     */
    private function isExpressionInValidPlacement() {
      // Ověření, zda není cílem zvýraznit HTML tag.
      if (str_contains($this->expression, '<') || str_contains($this->expression, '>')) {
        throw new UserError('Podezřelý text nemůže být HTML tag nebo jeho část.', MSG_ERROR);
      }

      $dom = EmailDomProcessor::loadDom($this->email);
      $xpath = new DOMXPath($dom);

      $foundValidOccurrence = false;

      // Proměnné používané v hlavičce e-mailu.
      if (in_array($this->expression, PhishingEmailModel::getEmailHeaderVariables())) {
        $foundValidOccurrence = true;
      }

      // Procházení všech textových uzlů.
      foreach ($xpath->query('//text()') as $textNode) {
        if (!str_contains($textNode->nodeValue, $this->expression)) {
          continue;
        }

        $parent = $textNode->parentNode;

        // Provedení dalších kontrol, pokud je rodič HTML element.
        if ($parent instanceof DOMElement) {
          $tag = strtolower($parent->tagName);

          // Výskyt indicie uvnitř odkazu.
          if ($tag === 'a') {
            continue;
          }

          // Výskyt indicie u již zpracovabých elementů (např. už označená indicie, proměnná apod.).
          if ($parent->hasAttribute('class')) {
            $class = $parent->getAttribute('class');

            if (str_contains($class, 'email-variable') || str_contains($class, 'indication-link')) {
              continue;
            }
          }
        }

        $foundValidOccurrence = true;
        break;
      }

      if (!$foundValidOccurrence) {
        throw new UserError('Podezřelý text musí být mimo odkazy nebo již označené prvky.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali podezřelý text není podřetězcem některé z proměnných.
     *
     * @throws UserError
     */
    private function isExpressionNotPartOfVariable() {
      $variables = PhishingEmailModel::getEmailBodyVariables();

      foreach ($variables as $variable) {
        // Přeskočit přesnou shodu s názvem proměnné.
        if ($this->expression === $variable) {
          continue;
        }

        // Pokud je expression obsaženo uvnitř proměnné → zakázat
        if (str_contains($variable, $this->expression)) {
          throw new UserError('Podezřelý text je částečně obsažen v názvu některé z proměnných.', MSG_ERROR);
        }
      }
    }


    /**
     * Ověří, zdali podezřelý text není již mezi ostatními indiciemi (tzn. jestli je unikátní).
     *
     * @param int $idIndication        ID indicie (nepovinný parametr), aby se při úpravě vyloučila upravovaná indicie
     * @throws UserError
     */
    private function isExpressionUnique($idIndication = 0) {
      if (self::existEmailIndication($this->idEmail, $this->expression, $idIndication) > 0) {
        throw new UserError('Stejný podezřelý text je již veden mezi ostatními indiciemi.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali podezřelý text není obsažen jako podřetězec v některé z jiných indicií.
     *
     * @param int $idIndication        ID indicie (nepovinný parametr), aby se při úpravě vyloučila upravovaná indicie
     * @throws UserError
     */
    private function isNotExpressionInExpressions($idIndication = 0) {
      if (self::existEmailIndication($this->idEmail, $this->expression, $idIndication, true) > 0) {
        throw new UserError('Podezřelý text je obsažen v některé z jiných indicií.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali byl vyplněn název indicie.
     *
     * @throws UserError
     */
    private function isTitleEmpty() {
      if (empty($this->title)) {
        throw new UserError('Není vyplněn název indicie.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali zadaný název indicie není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isTitleTooLong() {
      if (mb_strlen($this->title) > $this->inputsMaxLengths['title']) {
        throw new UserError('Název indicie je příliš dlouhý.', MSG_ERROR);
      }
    }


    /**
     * Ověří, zdali popis zadaný u indicie není příliš dlouhý.
     *
     * @throws UserError
     */
    private function isDescriptionTooLong() {
      if (mb_strlen($this->description) > $this->inputsMaxLengths['description']) {
        throw new UserError('Popis indicie je příliš dlouhý.', MSG_ERROR);
      }
    }
  }
