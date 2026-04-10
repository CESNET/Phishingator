<?php
  /**
   * Třída zpracovává uživatelský vstup týkající se obdržených cvičných podvodných e-maiů, na základě
   * kterého volá odpovídající metody, přičemž svůj výstup předává další vrstvě pro výpis.
   *
   * @author Martin Šebela
   */
  class RecievedEmailsController extends Controller {
    /**
     * Zpracuje vstup z URL adresy a na základě toho zavolá odpovídající metodu.
     *
     * @param array $arguments         Uživatelský vstup
     */
    public function process($arguments) {
      $this->checkPermission(PERMISSION_USER);

      $this->setView('header-recieved-emails', true);
      $this->setUrlSection('recieved-phishing-emails');

      $model = new RecievedEmailModel();

      $this->processList($model);

      $this->setHelpLink('https://github.com/CESNET/Phishingator/blob/main/MANUAL.md#13-p%C5%99ijat%C3%A9-phishingov%C3%A9-e-maily');
    }


    /**
     * Vypíše seznam přijatých cvičných phishingových zpráv pro právě přihlášeného uživatele.
     *
     * @param RecievedEmailModel $model Instance třídy
     */
    private function processList($model) {
      $this->setTitle('Přijaté phishingové e-maily');
      $this->setView('list-recieved-emails');

      $modelUser = new UsersModel();
      $user = $modelUser->getUser(PermissionsModel::getUserId());

      $records = $model->getRecievedPhishingEmails($user['id_user'], CAMPAIGN_ACTIVE_HIDE_EMAILS);

      // Personalizace a dodatečné úpravy každého z e-mailů.
      foreach ($records as $key => $email) {
        // Získání uživatelského odkazu na vzdělávací stránku.
        $records[$key]['code'] = WebsitePrependerModel::makeUserWebsiteId($email['id_campaign'], $user['url']);

        // Získání uživatelské reakce na e-mail.
        $records[$key]['user_state'] = CampaignModel::getUserResponse($email['id_campaign'], $user['id_user']);

        $records[$key] = PhishingEmailModel::preparePhishingEmail($records[$key], $user, false);
      }

      $this->setViewData('phishingEmails', $records);
      $this->setViewData('countRecordsText', self::getTableFooter(count($records)));
    }
  }
