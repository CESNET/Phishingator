<?php
  /**
   * Třída řešící odesílání e-mailů s notifikacemi o phishingových kampaní.
   *
   * @author Martin Šebela
   */
  class NotificationsModel extends EmailSender {
    /**
     * Odešle e-mail s notifikací.
     *
     * @param string $recipientEmail   E-mail příjemce
     * @param string $subject          Předmět e-mailu
     * @param string $body             Tělo e-mailu
     * @return bool                    Výsledek odeslání e-mailu
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendNotificationEmail($recipientEmail, $subject, $body) {
      return $this->sendEmail(
        NOTIFICATION_SENDER, 'Phishingator',
        $recipientEmail, 'Phishingator · ' . $subject, $body
      );
    }


    /**
     * Přidá do databáze záznam o tom, zdali byl e-mail s notifikací (ne)úspěšně odeslán.
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idUser              ID uživatele
     * @param int $notificationType    Typ notifikace:
     *                                    1 = notifikace administrátorům o přidání nové phishingové kampaně
     *                                    2 = notifikace pro administrátory a tvůrce kampaně o ukončení kampaně
     *                                    3 = notifikace pro příjemce kampaně
     * @param int $result              1 pokud se e-mail s notifikací podařilo odeslat, 0 pokud ne
     * @param string $errorMessage     Chybová hláška, pokud se e-mail nepodařilo odeslat (nepovinné)
     */
    private function logSentNotificationEmail($idCampaign, $idUser, $notificationType, $result, $errorMessage = false) {
      $record = [
        'id_campaign' => $idCampaign,
        'id_user' => $idUser,
        'id_notification_type' => $notificationType
      ];

      // Pokud se e-mail podařilo úspěšně odeslat, uložit do databáze datum odeslání.
      if ($result == 1) {
        Logger::info('Notification email sent successfully.', $record);

        $record['date_sent'] = date('Y-m-d H:i:s');

        Database::insert('phg_sent_notifications', $record);
      }
      else {
        Logger::error('Failure to send notification email: ' . Controller::escapeOutput($errorMessage), $record);
      }
    }


    /**
     * Ověří, zdali byl uživateli e-mail s notifikací o phishingové kampani někdy dříve odeslán.
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idUser              ID uživatele
     * @param int $notificationType    Typ notifikace
     * @return bool                    TRUE pokud byl e-mail v minulosti odeslán, jinak FALSE
     */
    private function isNotificationEmailSent($idCampaign, $idUser, $notificationType) {
      $result = Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_sent_notifications`
              WHERE `id_campaign` = ?
              AND `id_user` = ?
              AND `id_notification_type` = ?
              AND `date_sent` IS NOT NULL
      ', [$idCampaign, $idUser, $notificationType]);

      return $result > 0;
    }


    /**
     * Rozešle všem administrátorům (a tvůrci kampaně) notifikaci o nově přidaných phishingových kampaních.
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendNewCampaignsNotifications() {
      // Seznam nově přidaných kampaní.
      $campaigns = CampaignModel::getNewAddedCampaigns();

      foreach ($campaigns as $campaign) {
        $campaignDetail = CampaignModel::getCampaignDetail($campaign['id_campaign'], false);
        $recipients = UsersModel::getUsersByPermission(PERMISSION_ADMIN);

        // Pokud je tvůrce kampaně uživatel s rolí správce testů, přidat mezi příjemce i jeho.
        if (UsersModel::getUserRole($campaign['id_by_user']) == PERMISSION_TEST_MANAGER) {
          $recipients[] = [
            'id_user' => $campaign['id_by_user'],
            'email' => $campaign['email']
          ];
        }

        // Organizace, ve které k vytvoření phishingové kampaně došlo.
        $campaignOrg = strtoupper(getenv('ORG')) . ' (' . get_organization_domain() . ')';

        // Předmět a obsah notifikace.
        $notificationSubject = 'Nová phishingová kampaň [' . $campaign['id_campaign'] . ']';

        $notificationBody = 'Automatická notifikace systému Phishingator' . "\n" .
          '-------------------------------------------' . "\n\n" .
          'V systému došlo k vytvoření nové kampaně:' . "\n\n" .
          'Organizace:               ' . $campaignOrg . "\n\n" .
          'Název:                    ' . $campaignDetail['name'] . "\n" .
          'Přidáno:                  ' . $campaign['date_added'] . "\n" .
          'Přidal:                   ' . $campaignDetail['username'] . ' ('  . $campaignDetail['email'] . ')' . "\n\n" .
          'Podvodný e-mail:          ' . $campaignDetail['email_name'] . "\n" .
          'Typ e-mailu:              ' . PhishingEmailModel::getEmailTypeName($campaignDetail['html'], $campaignDetail['quishing']) . "\n\n" .
          'URL podvodné stránky:     ' . $campaignDetail['url_protocol'] . $campaignDetail['url'] . "\n" .
          'Šablona podvodné stránky: ' . $campaignDetail['website_name'] . "\n\n" .
          'Počet příjemců:           ' . $campaignDetail['count_recipients'] . "\n\n" .
          'Aktivní od:               ' . $campaignDetail['date_active_since_formatted'] . ' (' . $campaignDetail['time_active_since'] . ')' . "\n" .
          'Aktivní do:               ' . $campaignDetail['date_active_to_formatted'] . ' (' . $campaignDetail['time_active_to'] . ')' . "\n\n" .
          '-------------------------------------------' . "\n\n" .
          'Detaily vytvořené kampaně jsou k dispozici po přihlášení na URL adrese:' . "\n" .
          WEB_URL . '/portal/campaigns/' . ACT_EDIT . '/' . $campaign['id_campaign'];

        foreach ($recipients as $recipient) {
          // Ověření, zdali nedošlo k odeslání notifikace o vytvoření kampaně stejnému uživateli někdy dříve.
          if ($this->isNotificationEmailSent($campaign['id_campaign'], $recipient['id_user'], 1)) {
            continue;
          }

          // Odeslání e-mailu.
          $mailResult = $this->sendNotificationEmail($recipient['email'], $notificationSubject, $notificationBody);

          // Uložení záznamu o tom, zda se e-mail podařilo odeslat.
          $this->logSentNotificationEmail($campaign['id_campaign'], $recipient['id_user'], 1, $mailResult, $this->mailer->ErrorInfo);
        }
      }
    }


    /**
     * Rozešle všem administrátorům (a tvůrci kampaně) notifikaci o právě ukončených phishingových kampaních.
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendFinishedCampaignsNotifications() {
      $statsModel = new StatsModel();

      $campaigns = CampaignModel::getFinishedCampaigns();

      foreach ($campaigns as $campaign) {
        $campaignDetail = CampaignModel::getCampaignDetail($campaign['id_campaign'], false);
        $recipients = UsersModel::getUsersByPermission(PERMISSION_ADMIN);

        // Pokud je tvůrce kampaně uživatel s rolí správce testů, přidat mezi příjemce i jeho.
        if (UsersModel::getUserRole($campaign['id_by_user']) == PERMISSION_TEST_MANAGER) {
          $recipients[] = [
            'id_user' => $campaign['id_by_user'],
            'email' => $campaign['email']
          ];
        }

        // Získání výsledků kampaně.
        $campaignStats = $statsModel->getUsersResponses($campaign['id_campaign'], null, true);
        $campaignStatsPercentages = StatsModel::calculatePercentages($campaignStats);

        $campaignStatsMaxValueLength = mb_strlen(max(array_values($campaignStats)));

        // Organizace, ve které k vytvoření phishingové kampaně došlo.
        $campaignOrg = strtoupper(getenv('ORG')) . ' (' . get_organization_domain() . ')';

        // Předmět a obsah notifikace.
        $notificationSubject = 'Phishingová kampaň ukončena [' . $campaign['id_campaign'] . ']';

        $notificationBody = 'Automatická notifikace systému Phishingator' . "\n" .
          '-------------------------------------------' . "\n\n" .
          'V systému došlo k ukončení platnosti kampaně:' . "\n\n" .
          'Organizace:               ' . $campaignOrg . "\n\n" .
          'Název:                    ' . $campaignDetail['name'] . "\n" .
          'Přidáno:                  ' . $campaign['date_added'] . "\n" .
          'Přidal:                   ' . $campaignDetail['username'] . ' ('  . $campaignDetail['email'] . ')' . "\n\n" .
          'Podvodný e-mail:          ' . $campaignDetail['email_name'] . "\n" .
          'Typ e-mailu:              ' . PhishingEmailModel::getEmailTypeName($campaignDetail['html'], $campaignDetail['quishing']) . "\n\n" .
          'URL podvodné stránky:     ' . $campaignDetail['url_protocol'] . $campaignDetail['url'] . "\n" .
          'Šablona podvodné stránky: ' . $campaignDetail['website_name'] . "\n\n" .
          'Počet příjemců:           ' . $campaignDetail['count_recipients'] . "\n\n" .
          'Aktivní od:               ' . $campaignDetail['date_active_since_formatted'] . ' (' . $campaignDetail['time_active_since'] . ')' . "\n" .
          'Aktivní do:               ' . $campaignDetail['date_active_to_formatted'] . ' (' . $campaignDetail['time_active_to'] . ')' . "\n\n" .
          '-------------------------------------------' . "\n\n" .
          'Reakce příjemců byly následující:' . "\n\n";

        // Vložení výsledků kampaně do obsahu notifikace.
        foreach ($statsModel->legend as $key => $legend) {
          $legend = mb_str_pad($legend . ': ', 26);
          $value = mb_str_pad($campaignStats[$key], $campaignStatsMaxValueLength);
          $percentages = $campaignStatsPercentages[$key] . ' %';

          $notificationBody .= $legend . $value . ' (' . $percentages . ')' . "\n";
        }

        // Patička notifikace.
        $notificationBody .= "\n" . '-------------------------------------------' . "\n\n" .
          'Detaily ukončené kampaně jsou k dispozici po přihlášení na URL adrese:' . "\n" .
          WEB_URL . '/portal/campaigns/' . ACT_STATS . '/' . $campaign['id_campaign'];

        foreach ($recipients as $recipient) {
          // Ověření, zdali nedošlo k odeslání notifikace o ukončení kampaně někdy dříve.
          if ($this->isNotificationEmailSent($campaign['id_campaign'], $recipient['id_user'], 2)) {
            continue;
          }

          // Odeslání e-mailu.
          $mailResult = $this->sendNotificationEmail($recipient['email'], $notificationSubject, $notificationBody);

          // Uložení záznamu o tom, zda se e-mail podařilo odeslat.
          $this->logSentNotificationEmail($campaign['id_campaign'], $recipient['id_user'], 2, $mailResult, $this->mailer->ErrorInfo);
        }
      }
    }


    /**
     * Rozešle všem příjemcům kampaně notifikaci s informací o tom, že byli součástí
     * phishingové kampaně, a to včetně jejich reakce.
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendFinishedCampaignsNotificationsToUsers() {
      $countSentMails = 0;

      $campaigns = CampaignModel::getFinishedCampaigns();

      foreach ($campaigns as $campaign) {
        // Ověření, zdali se má příjemcům kampaně posílat notifikace o jejím ukončení.
        if ($campaign['send_users_notification'] == 0) {
          continue;
        }

        $campaignDetail = CampaignModel::getCampaignDetail($campaign['id_campaign'], false);
        $recipients = CampaignModel::getCampaignRecipients($campaign['id_campaign']);

        // Pokud byl tvůrce kampaně uživatel s rolí správce testů, dojde ke zjištění jeho
        // jména, příjmení a e-mailu a k doplnění této informace do obsahu notifikace.
        if (UsersModel::getUserRole($campaign['id_by_user']) == PERMISSION_TEST_MANAGER) {
          $ldapModel = new LdapModel();

          $testManager['name'] = $ldapModel->getFullnameByUsername(get_email_part($campaign['email'], 'username'));
          $testManager['email'] = $campaign['email'];

          $ldapModel->close();
        }
        else {
          $testManager = null;
        }

        foreach ($recipients as $recipient) {
          $user = UsersModel::getUserByEmail($recipient);

          // Ověření, zdali se podařilo získat informace o uživateli.
          if (empty($user['id_user'])) {
            continue;
          }

          // Ověření, zdali nedošlo k odeslání notifikace o účasti v kampani někdy dříve.
          if ($this->isNotificationEmailSent($campaign['id_campaign'], $user['id_user'], 3)) {
            continue;
          }

          // Zjištění uživatelské URL adresy pro přístup na vzdělávací stránku.
          $user['code'] = WebsitePrependerModel::makeUserWebsiteId($campaign['id_campaign'], $user['url']);

          // Získání reakce uživatele ve phishingové kampani.
          $user['response'] = CampaignModel::getUserResponse($campaign['id_campaign'], $user['id_user']);

          // Zjištění informací o odeslaném podvodném e-mailu konkrétnímu uživateli.
          $email = RecievedEmailModel::getRecievedPhishingEmail($campaign['id_campaign'], $campaignDetail['id_email'], $user['id_user']);

          // Připravení textu personalizované notifikace.
          $notification = $this->prepareUserNotification($user, $email['subject'], $email['date_sent'], $campaign['users_notification_language'], $testManager);

          Logger::info('Notification ready to send.', [
              'id_campaign' => $campaign['id_campaign'],
              'id_user' => $user['id_user'],
              'recipient' => $recipient,
              'subject' => $notification['subject'],
              'body' => $notification['body']
            ]
          );

          // Odeslání e-mailu.
          $mailResult = $this->sendNotificationEmail($recipient, $notification['subject'], $notification['body']);

          // Uložení záznamu o tom, zda se e-mail podařilo odeslat.
          $this->logSentNotificationEmail($campaign['id_campaign'], $user['id_user'], 3, $mailResult, $this->mailer->ErrorInfo);

          // Uspání skriptu po odeslání určitého množství e-mailů.
          $countSentMails = $this->sleepSender($countSentMails);
        }
      }
    }


    /**
     * Vrátí personalizovanou notifikaci o absolvování cvičného phishingu ve zvoleném jazyce vůči konkrétnímu uživateli.
     *
     * @param array $user              Data o uživateli včetně reakce uživatele v kampani a odkazu na vzdělávací stránku
     * @param string $emailSubject     Předmět odeslaného e-mailu
     * @param string $emailSent        Datum a čas odeslání e-mailu
     * @param string[] $languages      Jazyky, ve kterých má být notifikace odeslána (nepovinné)
     * @param array|null $testManager  Data o správci testů, který phishingovou kampaň vytvořil, jinak NULL (výchozí)
     * @return string[]                Předmět a tělo uživatelské notifikace
     */
    private function prepareUserNotification($user, $emailSubject, $emailSent, $languages = ['cz'], $testManager = null) {
      $isUserSuccess = ($user['response']['id_action'] != CAMPAIGN_VALID_CREDENTIALS_ID);
      $isUserVolunteer = ($user['recieve_email'] == 1);
      $userEducationalSiteUrl = WEB_URL . '/' . ACT_PHISHING_TEST . '/' . $user['code'];

      // Datum a čas odeslání e-mailu v různých formátech.
      $emailDatetime = new DateTime($emailSent);

      $emailDatetimeSentCz = $emailDatetime->format('j. n. Y (G:i)');
      $emailDateSentCz = $emailDatetime->format('j. n. Y');
      $emailDatetimeSentEn = $emailDatetime->format('F j, Y (g:i A)');
      $emailDateSentEn = $emailDatetime->format('j M Y');

      // Úprava notifikace podle reakce příjemce (pokud uživatel do podvodné stránky nezadal platné přihlašovací údaje).
      $userResponseTextCz = $isUserSuccess ? "Gratulujeme, ve cvičení jste obstáli :-)\n\n" : '';
      $userResponseTextEn = $isUserSuccess ? "Congratulations, you passed the exercise :-)\n\n" : '';

      $bodyCz =
        "Automatická notifikace systému Phishingator\n" .
        "-------------------------------------------\n\n" .
        "Dne {$emailDatetimeSentCz} Vám byl odeslán e-mail \"{$emailSubject}\".\n" .
        "Jednalo se o cvičný phishing (podvodný e-mail) s typickými znaky, které útočníci\n" .
        "používají při snaze získat Vaše heslo, osobní údaje nebo číslo platební karty.\n\n" .
        $userResponseTextCz .
        "E-mail včetně indicií, podle kterých bylo možné podvod rozpoznat, si můžete prohlédnout zde:\n" .
        $userEducationalSiteUrl . "\n\n\n";

      $bodyEn =
        "Automatic notification from the Phishingator system\n" .
        "---------------------------------------------------\n\n" .
        "On {$emailDatetimeSentEn}, you received an email titled \"{$emailSubject}\".\n" .
        "This was a simulated phishing email with typical characteristics that attackers use\n" .
        "when attempting to obtain your password, personal information, or credit card number.\n\n" .
        $userResponseTextEn .
        "You can view the email, including clues that would have allowed you to recognize the scam, here:\n" .
        $userEducationalSiteUrl . "\n\n\n";

      // Pokud je uživatel dobrovolník.
      if ($isUserVolunteer) {
        $emailLimitCz = $user['email_limit'] ?? 'nenastaven';
        $emailLimitEn = $user['email_limit'] ?? 'not set';

        $bodyCz .=
          "Děkujeme, že máte zájem vzdělávat se v oblasti phishingu.\n\n" .
          "Váš zbývající počet cvičných phishingových zpráv: " . $emailLimitCz . "\n" .
          "Změnu můžete provést po přihlášení na:\n" .
          WEB_URL;

        $bodyEn .=
          "Thank you for your interest in learning about phishing.\n\n" .
          "Your remaining number of practice phishing messages: " . $emailLimitEn . "\n" .
          "You can make changes after logging in at:\n" .
          WEB_URL;
      }
      else {
        $bodyCz .=
          "Cílem je zvýšit povědomí o phishingu a ukázat Vám, čeho jsou dnes útočníci schopni a hlavně,\n" .
          "podle čeho můžete podvodný e-mail (phishing) rozpoznat. Ne každý podvodný e-mail totiž zachytí\n" .
          "filtry a antiviry a je tak možné, že narazíte i na skutečný phishing.\n" .
          "\n" .
          "Další informace naleznete na stránkách Phishingatoru, kde se můžete přihlásit i k dobrovolnému\n" .
          "odebírání cvičných phishingových zpráv od bezpečnostního oddělení tak, abyste vždy věděli, co je\n" .
          "právě aktuální a na co si dát pozor, více na:\n" .
          WEB_BASE_URL;

        $bodyEn .=
          "The goal is to raise awareness about phishing and show you what attackers are capable of these days and,\n" .
          "most importantly, how you can recognize a fraudulent email (phishing). Not every fraudulent email is\n" .
          "caught by filters and antivirus software, so it is possible that you may encounter a real phishing attempt.\n" .
          "\n" .
          "For more information, visit the Phishingator website, where you can also sign up to voluntarily receive\n" .
          "practice phishing messages from the security department so that you always know what’s currently\n" .
          "happening and what to watch out for. For more details, visit:\n" .
          WEB_BASE_URL;
      }

      // Dodatečná informace, pokud e-mail připravil uživatel s oprávněním správce testů, nikoliv administrátor.
      if ($testManager !== null) {
        $bodyCz .= "\n\n-------------------------------------------\n\n" .
          "Tento cvičný phishing pro Vás připravil {$testManager['name']} ({$testManager['email']}).\n" .
          "Jeho cílem nebylo nachytat Vás, ale zvýšit povědomí o této bezpečnostní hrozbě.";

        $bodyEn .= "\n\n-------------------------------------------\n\n" .
          "This phishing simulation was prepared by {$testManager['name']} ({$testManager['email']}).\n" .
          "The goal was not to trick you, but to raise awareness of this security threat.";
      }

      // Předmět uživatelské notifikace.
      $subjectCz = 'Cvičný phishing z ' . $emailDateSentCz;
      $subjectEn = 'Phishing Simulation on ' . $emailDateSentEn;

      // Sestavení textu notifikace podle vybraných jazyků.
      $bodyParts = [];
      $subjectParts = [];

      if (in_array('cz', $languages)) {
        $bodyParts[] = $bodyCz;
        $subjectParts[] = $subjectCz;
      }

      if (in_array('en', $languages)) {
        $bodyParts[] = $bodyEn;
        $subjectParts[] = $subjectEn;
      }

      $notificationBody = implode("\n\n\n", $bodyParts);
      $notificationSubject = implode(' / ', $subjectParts);

      return [
        'subject' => $notificationSubject,
        'body' => $notificationBody
      ];
    }


    /**
     * Vrátí pole podporovaných jazyků uživatelských notifikací.
     *
     * @return array[]                 Pole podporovaných jazyků
     */
    public static function getUserNotificationLanguages() {
      return [
        'cz' => ['Čeština', '🇨🇿'],
        'en' => ['Angličtina', '🇬🇧']
      ];
    }


    /**
     * Vrátí pole podporovaných jazyků uživatelských notifikací na základě řetězce s podporovanými jazyky.
     *
     * @param string $languages        Řetězec s podporovanými jazyky (oddělenými ",")
     * @return string[]                Pole podporovaných jazyků
     */
    public static function parseNotificationLanguages($languages) {
      return $languages ? explode(',', $languages) : [];
    }


    /**
     * Aktivuje odesílání e-mailů s notifikacemi.
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function startSendingNotifications() {
      // Notifikace pro administrátory a správce testů.
      $this->sendNewCampaignsNotifications();
      $this->sendFinishedCampaignsNotifications();

      // Notifikace pro uživatele.
      $this->sendFinishedCampaignsNotificationsToUsers();
    }
  }
