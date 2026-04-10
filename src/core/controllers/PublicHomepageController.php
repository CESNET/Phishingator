<?php
  /**
   * Třída zpracovává uživatelský vstup ve veřejné části aplikace, na základě kterého volá
   * odpovídající metody, přičemž svůj výstup předává další vrstvě pro výpis.
   *
   * @author Martin Šebela
   */
  class PublicHomepageController extends Controller {
    /**
     * Zpracuje vstup z URL adresy a na základě toho zavolá odpovídající metodu.
     *
     * @param array $arguments         Uživatelský vstup
     */
    public function process($arguments) {
      if (isset($_GET['section'])) {
        if ($_GET['section'] == ACT_PHISHING_TEST && isset($_GET['id'])) {
          $args = WebsitePrependerModel::parseWebsiteUrl($_GET['id']);

          if ($args == null) {
            Logger::warning('Unauthorized access an educational site (invalid user/campaign argument).', [$args, $_GET]);

            header('Location: ' . WEB_URL);
            exit();
          }

          // Pokud parametry URL adresy odpovídají předpokladu, dojde k připojení k databázi.
          Database::connect();

          $this->processEmailDetail($args['id_campaign'], $args['id_user']);

          $this->setViewData('phishingEmail', true);
          $this->setViewData('user_url', self::escapeOutput($_GET['id']));
        }
        else {
          $this->redirect(null, 1);
        }
      }
      else {
        header('Location: ' . WEB_URL . '/portal');
        exit();
      }
    }


    /**
     * Vypíše informace o absolvování cvičného phishingu včetně personalizovaného e-mailu a seznamu indicií.
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idUser              ID uživatele (příjemce)
     */
    private function processEmailDetail($idCampaign, $idUser) {
      $this->setView('public/phishing-email');

      // Získání informací o kampani.
      $campaignModel = new CampaignModel();
      $campaign = $campaignModel->getCampaign($idCampaign);

      // Získání informací o uživateli (příjemci).
      $user = UsersModel::getUserByURL($idUser);

      // Kontrola existence záznamu.
      if (empty($campaign) || empty($user) || $campaignModel->isUserRecipient($idCampaign, $user['id_user']) != 1) {
        Logger::warning(
          'Unauthorized access an educational site (invalid arguments).',
          [$idCampaign, $idUser, $_GET]
        );

        header('Location: ' . WEB_URL);
        exit();
      }

      // Získání dodatečných informací o uživateli.
      $user = array_merge($user, UsersModel::getUserFullname($user['username']));

      // Vyžádání screenshotu podvodné stránky.
      if (isset($_GET[ACT_PHISHING_IMG])) {
        $websiteModel = new PhishingWebsiteModel();
        $websiteModel->getPhishingWebsiteScreenshot($campaign['id_website']);
      }

      // Zjištění stavu kampaně pro úpravu textů na vzdělávací stránce.
      $this->setViewData('campaign_status', $campaign['status']);

      // Zjištění, zdali je uživatel zaregistrován k dobrovolnému odběru cvičných phishingů (pro úpravu textů na stránce).
      $volunteer = UsersModel::getUserEmailLimit($user['id_user']);
      $this->setViewData('volunteer', $volunteer['recieve_email'] == 1);

      // Získání detailů o e-mailu.
      $phishingEmail = RecievedEmailModel::getRecievedPhishingEmail($idCampaign, $campaign['id_email'], $user['id_user']);

      // Ověření existence záznamu.
      $this->checkRecordExistence($phishingEmail);

      // Personalizace e-mailu podle uživatele.
      $phishingEmail = PhishingEmailModel::preparePhishingEmail($phishingEmail, $user);

      // Získání indicie, která souvisí s podvodnou stránkou.
      $websiteIndication = EmailIndicationsModel::findIndicationByExpression($phishingEmail['indications'], VAR_URL);

      if ($websiteIndication !== null) {
        $website['indication'] = (!empty($websiteIndication['description'])) ? $websiteIndication['description'] : $websiteIndication['title'];
      }

      $phishingEmail['url'] = PhishingWebsiteModel::makeWebsiteUrl($phishingEmail['url'], WebsitePrependerModel::makeUserWebsiteId($idCampaign, $idUser));

      // Získání informací o podvodné stránce a její šabloně.
      $website['http'] = get_protocol_from_url($phishingEmail['url']) == 'http';
      $website['domain'] = get_domain_from_url($phishingEmail['url']);

      $websiteTemplate = PhishingWebsiteModel::getPhishingWebsiteTemplate($phishingEmail['id_template']);
      $website['cloned'] = $websiteTemplate['cloned'];

      $domainPosition = mb_strpos($phishingEmail['url'], $website['domain']);

      $website['url_before_domain'] = mb_substr($phishingEmail['url'], 0, $domainPosition);
      $website['url_after_domain'] = Controller::decodeHtmlEntities(mb_substr($phishingEmail['url'], $domainPosition + mb_strlen($website['domain'])));

      $website['image_src'] = '/' . ACT_PHISHING_TEST . '/' . self::escapeOutput($_GET['id']) . '?' . ACT_PHISHING_IMG;

      $this->setViewData('website', $website);
      $this->setViewData('email', $phishingEmail);

      // Logování přístupu na vzdělávací stránku.
      WebsitePrependerModel::logEducationalSiteAccess($idCampaign, $user['id_user'], $user['email'], $user['departments']);
    }
  }
