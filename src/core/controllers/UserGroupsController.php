<?php
  /**
   * Třída zpracovává uživatelský vstup týkající správy uživatelských skupin, na základě kterého volá
   * odpovídající metody, přičemž svůj výstup předává další vrstvě pro výpis.
   *
   * @author Martin Šebela
   */
  class UserGroupsController extends Controller {
    /**
     * @var int         Od jakého počtu znaků se bude název uživatelské skupiny považovat za dlouhý.
     */
    private $longUserGroupMinLength = 15;


    /**
     * Zpracuje vstup z URL adresy a na základě toho zavolá odpovídající metodu.
     *
     * @param array $arguments         Uživatelský vstup
     */
    public function process($arguments) {
      $this->checkPermission(PERMISSION_ADMIN);

      $this->setView('header-user-groups', true);
      $this->setUrlSection('user-groups');

      $model = new UserGroupsModel();
      $formData = [
        'inputsNames' => ['name', 'description', 'role', 'ldap-groups'],
        'formPrefix' => 'user-group-',
        'dbTable' => 'phg_users_groups'
      ];

      if (isset($_GET['action'])) {
        $id = isset($_GET['id']) ? get_number_from_get_string($_GET['id']) : false;

        if ($_GET['action'] == ACT_NEW) {
          $this->processNew($model, $formData);
        }
        elseif ($_GET['action'] == ACT_EDIT && $id !== false) {
          $this->processEdit($model, $formData, $id);
        }
        elseif ($_GET['action'] == ACT_DEL && $id !== false) {
          $this->processDelete($model, $id);
        }
        else {
          $this->addMessage(MSG_ERROR, 'Zvolená akce neexistuje.');
          $this->redirect($this->urlSection);
        }
      }
      else {
        $this->processList($model);
      }

      $this->setHelpLink('https://github.com/CESNET/Phishingator/blob/main/MANUAL.md#26-u%C5%BEivatelsk%C3%A9-skupiny');
    }


    /**
     * Vypíše formulář a obslouží s ním související prvky pro přidání nové uživatelské skupiny.
     *
     * @param UserGroupsModel $model   Instance třídy
     * @param array $formData          Nastavení a vlastnosti formuláře
     */
    private function processNew($model, $formData) {
      $this->setTitle('Nová uživatelská skupina');
      $this->setView('form-user-groups');

      $model->initForm($formData['inputsNames'], $formData['formPrefix'], $formData['dbTable']);
      $this->initViewData($model, ACT_NEW, $formData['formPrefix']);

      $ldap = new LdapModel();
      $groups = $ldap->getGroupNames();
      $ldap->close();

      $this->setViewData('roles', $model->getRoles(true));

      $this->setViewData('groups', $groups);
      $this->setViewData('groupsLongNames', max(array_map('mb_strlen', $groups)) > $this->longUserGroupMinLength);

      if (isset($_POST[$model->formPrefix . $this->getData('action')])) {
        try {
          $model->load($_POST);

          $model->validateData();
          $model->insertUserGroup();

          $this->addMessage(MSG_SUCCESS, 'Přidání proběhlo úspěšně.');
          $this->redirect($this->urlSection);
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }
      }
    }


    /**
     * Vypíše formulář a obslouží s ním související prvky pro úpravu konkrétní uživatelské skupiny.
     *
     * @param UserGroupsModel $model   Instance třídy
     * @param array $formData          Nastavení a vlastnosti formuláře
     * @param int $idGroup             ID uživatelské skupiny
     */
    private function processEdit($model, $formData, $idGroup) {
      $this->setTitle('Úprava uživatelské skupiny');
      $this->setView('form-user-groups');

      $model->initForm($formData['inputsNames'], $formData['formPrefix'], $formData['dbTable']);
      $this->setViewData('group', $model->getUserGroup($idGroup));

      $this->checkRecordExistence($this->getData('group'));

      $this->initViewData($model, ACT_EDIT, $formData['formPrefix']);

      $ldap = new LdapModel();
      $groups = $ldap->getGroupNames();
      $ldap->close();

      $this->setViewData('roles', $model->getRoles(true));

      $this->setViewData('groups', $groups);
      $this->setViewData('groupsLongNames', max(array_map('mb_strlen', $groups)) > $this->longUserGroupMinLength);

      $groupRole = $model->getRole($this->getData('group')['role']);
      $this->setViewData('displayGroups', $groupRole['value'] == PERMISSION_ADMIN || $groupRole['value'] == PERMISSION_TEST_MANAGER);

      if (isset($_POST[$model->formPrefix . $this->getData('action')])) {
        try {
          $model->load($_POST);

          $model->validateData();
          $model->updateUserGroup($idGroup);

          $this->addMessage(MSG_SUCCESS, 'Úprava proběhla úspěšně.');
          $this->redirect($this->urlSection);
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }
      }
    }


    /**
     * Zavolá metodu pro odstranění konkrétní uživatelské skupiny.
     *
     * @param UserGroupsModel $model   Instance třídy
     * @param int $idGroup             ID uživatelské skupiny
     */
    private function processDelete($model, $idGroup) {
      if (isset($_POST)) {
        try {
          $model->isValidCsrfToken($_POST);
          $model->deleteUserGroup($idGroup);

          $this->addMessage(MSG_SUCCESS, 'Smazání proběhlo úspěšně.');
        }
        catch (UserError $error) {
          $this->addMessage($error->getCode(), $error->getMessage());
        }

        $this->redirect($this->urlSection);
      }
    }


    /**
     * Vypíše seznam uživatelských skupin.
     *
     * @param UserGroupsModel $model   Instance třídy
     */
    private function processList($model) {
      $this->setTitle('Uživatelské skupiny');
      $this->setView('list-user-groups');

      $records = $model->getUserGroups();

      $this->setViewData('groups', $records);
      $this->setViewData('countRecordsText', self::getTableFooter(count($records)));
    }
  }
