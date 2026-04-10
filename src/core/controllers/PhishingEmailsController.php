<?php
  /**
   * Třída zpracovává uživatelský vstup týkající správy podvodných e-mailů (a indicií s nimi spojených),
   * na základě kterého volá odpovídající metody, přičemž svůj výstup předává další vrstvě pro výpis.
   *
   * @author Martin Šebela
   */
  class PhishingEmailsController extends Controller {
    /**
     * @var array       Informace o právě přihlášeném uživateli
     */
    private $user;


    /**
     * Zpracuje vstup z URL adresy a na základě toho zavolá odpovídající metodu.
     *
     * @param array $arguments         Uživatelský vstup
     */
    public function process($arguments) {
      $this->checkPermission(PERMISSION_TEST_MANAGER);

      $this->setView('header-phishing-emails', true);
      $this->setUrlSection('phishing-emails');

      $model = new PhishingEmailModel();
      $formData = [
        'inputsNames' => ['name', 'sender-name', 'sender-email', 'subject', 'body', 'html', 'hidden'],
        'formPrefix' => 'phishing-email-',
        'dbTable' => 'phg_emails'
      ];

      $modelUser = new UsersModel();
      $this->user = $modelUser->getUser(PermissionsModel::getUserId(), true);

      if (isset($_GET['action'])) {
        $id = isset($_GET['id']) ? get_number_from_get_string($_GET['id']) : false;
        $displayEmailPreview = isset($_POST[$formData['formPrefix'] . ACT_PREVIEW]);

        if ($_GET['action'] == ACT_NEW && $displayEmailPreview === false) {
          $this->processNew($model, $formData);
        }
        elseif ($_GET['action'] == ACT_EDIT && $displayEmailPreview === false && $id !== false) {
          $this->processEdit($model, $formData, $id);
        }
        elseif ($_GET['action'] == ACT_DEL && $id !== false) {
          $this->processDelete($model, $id);
        }
        elseif ($_GET['action'] == ACT_DUPLICATE && $id !== false) {
          $this->processDuplicate($model, $id);
        }
        elseif ($_GET['action'] == ACT_INDICATIONS && $id !== false) {
          $this->processSetIndications($model, $id);
        }
        elseif ($_GET['action'] == ACT_PREVIEW && $id !== false) {
          $this->processPreview($model, $id);
        }
        elseif ($displayEmailPreview === true) {
          $this->processPreviewEmail($model, $formData);
        }
        else {
          $this->addMessage(MSG_ERROR, 'Zvolená akce neexistuje.');
          $this->redirect($this->urlSection);
        }
      }
      else {
        $this->processList($model);
      }

      $this->setHelpLink('https://github.com/CESNET/Phishingator/blob/main/MANUAL.md#23-podvodn%C3%A9-e-maily-a-indicie');
    }


    /**
     * Vypíše formulář a obslouží s ním související prvky pro přidání nového podvodného e-mailu.
     *
     * @param PhishingEmailModel $model     Instance třídy
     * @param array $formData               Nastavení a vlastnosti formuláře
     */
    private function processNew($model, $formData) {
      $this->checkPermission(PERMISSION_ADMIN);

      $this->setTitle('Nový podvodný e-mail');
      $this->setView('form-phishing-email');

      $model->initForm($formData['inputsNames'], $formData['formPrefix'], $formData['dbTable']);
      $this->initViewData($model, ACT_NEW, $formData['formPrefix']);

      $this->setViewData('phishingEmailVariables', json_encode($model->getEmailBodyVariables()));

      if (isset($_POST[$model->formPrefix . $this->getData('action')])) {
        try {
          $model->load($_POST);

          $model->validateData();
          $idEmail = $model->insertPhishingEmail();

          $this->addMessage(MSG_SUCCESS, 'Přidání proběhlo úspěšně.');
          $this->redirect($this->urlSection . '/' . ACT_INDICATIONS . '/' . $idEmail);
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }
      }
    }


    /**
     * Vypíše formulář a obslouží s ním související prvky pro úpravu konkrétního podvodného e-mailu.
     *
     * @param PhishingEmailModel $model     Instance třídy
     * @param array $formData               Nastavení a vlastnosti formuláře
     * @param int $idEmail                  ID podvodného e-mailu
     */
    private function processEdit($model, $formData, $idEmail) {
      $this->checkPermission(PERMISSION_ADMIN);

      $this->setTitle('Úprava podvodného e-mailu');
      $this->setView('form-phishing-email');

      $model->initForm($formData['inputsNames'], $formData['formPrefix'], $formData['dbTable']);
      $this->setViewData('phishingEmail', $model->getPhishingEmail($idEmail));
      $this->setViewData('phishingEmailVariables', json_encode($model->getEmailBodyVariables()));

      // Ověření existence záznamu.
      $this->checkRecordExistence($this->getData('phishingEmail'));

      $this->initViewData($model, ACT_EDIT, $formData['formPrefix']);

      if (isset($_POST[$model->formPrefix . $this->getData('action')])) {
        try {
          $model->load($_POST);

          $model->validateData();
          $model->updatePhishingEmail($idEmail);

          $this->addMessage(MSG_SUCCESS, 'Úprava proběhla úspěšně.');
          $this->redirect($this->urlSection);
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }
      }
    }


    /**
     * Zavolá metodu pro odstranění konkrétního podvodného e-mailu.
     *
     * @param PhishingEmailModel $model     Instance třídy
     * @param int $idEmail                  ID podvodného e-mailu
     */
    private function processDelete($model, $idEmail) {
      $this->checkPermission(PERMISSION_ADMIN);

      if (isset($_POST)) {
        try {
          $model->isValidCsrfToken($_POST);
          $model->deletePhishingEmail($idEmail);

          $this->addMessage(MSG_SUCCESS, 'Smazání proběhlo úspěšně.');
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }

        $this->redirect($this->urlSection);
      }
    }


    /**
     * Zavolá metodu pro duplikování konkrétního podvodného
     * e-mailu, a to včetně souvisejících indicií.
     *
     * @param PhishingEmailModel $model     Instance třídy
     * @param int $idEmail                  ID podvodného e-mailu
     */
    private function processDuplicate($model, $idEmail) {
      $this->checkPermission(PERMISSION_ADMIN);

      if (isset($_POST)) {
        try {
          $model->isValidCsrfToken($_POST);
          $model->duplicatePhishingEmail($idEmail);

          $this->addMessage(MSG_SUCCESS, 'Duplikování proběhlo úspěšně.');
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }

        $this->redirect($this->urlSection);
      }
    }


    /**
     * Obslouží uživatelské akce při správě indicie u podvodného e-mailu.
     *
     * @param PhishingEmailModel $model     Instance třídy
     * @param int $idEmail                  ID podvodného e-mailu
     */
    private function processSetIndications($model, $idEmail) {
      $this->checkPermission(PERMISSION_ADMIN);

      $this->setTitle('Nastavení indicií podvodného e-mailu');
      $this->setView('form-phishing-email-indications');

      $modelIndication = new EmailIndicationsModel();

      $formData = [
        'inputsNames' => ['position', 'expression', 'title', 'description'],
        'formPrefix' => 'phishing-indication-',
        'dbTable' => 'phg_emails_indications'
      ];

      $modelIndication->initForm($formData['inputsNames'], $formData['formPrefix'], $formData['dbTable']);
      $this->initViewData($modelIndication, ACT_INDICATIONS, $formData['formPrefix']);

      // Získání e-mailu pro HTML náhled a následné kontroly.
      $phishingEmail = $model->getPhishingEmail($idEmail);

      // Ověření existence záznamu.
      $this->checkRecordExistence($phishingEmail);

      // Uložení čistého těla e-mailu (bez vložených HTML indicií apod.) do modelu pro následné kontroly.
      $modelIndication->email = $phishingEmail['body'];

      // Personalizace e-mailu podle přihlášeného uživatele.
      $phishingEmail = $model->preparePhishingEmail($phishingEmail, null, true, true);

      $this->setViewData('phishingEmail', $phishingEmail);
      $this->setViewData('emailIndications', $phishingEmail['indications']);
      $this->setViewData('existsUrlIndication', EmailIndicationsModel::findIndicationIdByExpression($phishingEmail['indications'], VAR_URL) !== null);

      // Přidání nové indicie.
      if (isset($_POST[$modelIndication->formPrefix . ACT_NEW])) {
        try {
          $modelIndication->load($_POST);

          $modelIndication->validateData();
          $modelIndication->insertEmailIndication();

          $this->addMessage(MSG_SUCCESS, 'Přidání proběhlo úspěšně.');
          $this->redirect($this->urlSection . '/' . $this->getData('action') . '/' . $idEmail);
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }
      }
      // Úprava existující indicie.
      elseif (isset($_POST[$modelIndication->formPrefix . ACT_EDIT])) {
        // Změna prefixu formuláře kvůli více formulářům na jedné stránce.
        $modelIndication->formPrefix = $modelIndication->formPrefix . ACT_EDIT . '-';

        $indication = $modelIndication->getEmailIndication(
          (isset($_POST[$modelIndication->formPrefix . 'id'])) ? $_POST[$modelIndication->formPrefix . 'id'] : 0
        );

        if (!empty($indication)) {
          try {
            $modelIndication->load($_POST);

            $modelIndication->validateData();
            $modelIndication->updateEmailIndication($_POST[$modelIndication->formPrefix . 'id']);

            $this->addMessage(MSG_SUCCESS, 'Úprava proběhla úspěšně.');
            $this->redirect($this->urlSection . '/' . $this->getData('action') . '/' . $idEmail);
          }
          catch (UserError $error) {
            $this->addMessage($error->getCode(), $error->getMessage());
          }
        }
        else {
          $this->addMessage(MSG_ERROR, 'Zvolený záznam neexistuje.');
        }
      }
      // Smazání indicie.
      elseif (isset($_POST[$modelIndication->formPrefix . ACT_DEL])) {
        $this->processDeleteIndication($modelIndication, $idEmail);
      }
    }


    /**
     * Zavolá metodu pro odstranění konkrétní indicie u podvodného e-mailu.
     *
     * @param EmailIndicationsModel $model  Instance třídy
     * @param int $idEmail                  ID podvodného e-mailu
     */
    private function processDeleteIndication($model, $idEmail) {
      $this->checkPermission(PERMISSION_ADMIN);

      if (isset($_POST)) {
        try {
          $model->isValidCsrfToken($_POST);
          $model->deleteEmailIndication($_POST[$model->formPrefix . ACT_EDIT . '-id']);

          $this->addMessage(MSG_SUCCESS, 'Smazání proběhlo úspěšně.');
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }

        // Znovu ošetření parametru ID e-mailu kvůli následnému přesměrování (viz redirect dále).
        if (!isset($idEmail) || !is_numeric($idEmail)) {
          $idEmail = '';
        }

        $this->redirect($this->urlSection . '/' . ACT_INDICATIONS . '/' . $idEmail);
      }
    }


    /**
     * Zobrazí náhled personalizovaného (vůči právě přihlášenému uživateli) podvodného e-mailu uloženého v $_POST.
     *
     * @param PhishingEmailModel $model     Instance třídy
     * @param array $formData               Nastavení a vlastnosti formuláře
     */
    private function processPreviewEmail($model, $formData) {
      $this->checkPermission(PERMISSION_ADMIN);

      $this->setTitle('Náhled podvodného e-mailu');
      $this->setView('preview-phishing-email');

      $model->initForm($formData['inputsNames'], $formData['formPrefix'], $formData['dbTable']);

      try {
        $model->load($_POST);
      }
      catch (UserError $error) {
        $this->addMessage($error->getCode(), $error->getMessage());
      }

      // Získání vyplněných informací o e-mailu z POST.
      $phishingEmail = $model->makePhishingEmail();

      // Ověření existence záznamu.
      $this->checkRecordExistence($phishingEmail);

      // Personalizace e-mailu podle přihlášeného uživatele.
      $phishingEmail = $model->preparePhishingEmail($phishingEmail, $this->user, false, true);

      $this->setViewData('email', $phishingEmail);
    }


    /**
     * Zobrazí náhled personalizovaného (vůči právě přihlášenému uživateli) konkrétního podvodného e-mailu.
     *
     * @param PhishingEmailModel $model     Instance třídy
     * @param int $idEmail                  ID podvodného e-mailu
     */
    private function processPreview($model, $idEmail) {
      $this->setTitle('Náhled podvodného e-mailu');
      $this->setView('preview-phishing-email');

      $phishingEmail = $model->getPhishingEmail($idEmail);

      // Ověření existence záznamu.
      $this->checkRecordExistence($phishingEmail);

      // Personalizace e-mailu podle přihlášeného uživatele.
      $phishingEmail = $model->preparePhishingEmail($phishingEmail, $this->user);

      $this->setViewData('email', $phishingEmail);
    }


    /**
     * Vypíše seznam podvodných e-mailů.
     *
     * @param PhishingEmailModel $model     Instance třídy
     */
    private function processList($model) {
      $this->setTitle('Podvodné e-maily');
      $this->setView('list-phishing-emails');

      $records = $model->getPhishingEmails();

      foreach ($records as &$email) {
        $countIndications = EmailIndicationsModel::getCountEmailIndications($email['id_email']);

        $email['indications_sum'] = $countIndications;
        $email['indications_color'] = EmailIndicationsModel::getColorByCountIndications($countIndications);
      }

      $this->setViewData('phishingEmails', $records);
      $this->setViewData('countRecordsText', self::getTableFooter(count($records)));
    }
  }
