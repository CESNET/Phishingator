<?php
  /**
   * Třída řešící odesílání e-mailů a zjišťování informací o již odeslaných e-mailech.
   *
   * @author Martin Šebela
   */
  class EmailSenderModel extends EmailSender {
    /**
     * Přidá do databáze záznam o tom, zdali byl e-mail (ne)úspěšně odeslán.
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idEmail             ID e-mailu
     * @param int $idUser              ID uživatele
     * @param int $result              1 pokud se e-mail podařilo odeslat, 0 pokud ne
     * @param string $errorMessage     Chybová hláška, pokud se e-mail nepodařilo odeslat (nepovinné)
     */
    private function logSentEmail($idCampaign, $idEmail, $idUser, $result, $errorMessage = false) {
      $record = [
        'id_campaign' => $idCampaign,
        'id_email' => $idEmail,
        'id_user' => $idUser
      ];

      // Pokud se e-mail podařilo úspěšně odeslat, uložit do databáze datum odeslání.
      if ($result == 1) {
        Logger::info('Phishing email sent successfully.', $record);

        $record['date_sent'] = date('Y-m-d H:i:s');

        Database::insert('phg_sent_emails', $record);
      }
      else {
        Logger::error('Failure to send phishing email: ' . Controller::escapeOutput($errorMessage), $record);
      }
    }


    /**
     * Ověří, zdali byl v konkrétní phishingové kampani danému uživateli odeslán podvodný e-mail.
     *
     * @param int $idCampaign          ID kampaně
     * @param int $idUser              ID uživatele
     * @return bool                    TRUE pokud ano, FALSE pokud ne
     */
    private function isEmailSent($idCampaign, $idUser) {
      $result = Database::queryCount('
              SELECT COUNT(*)
              FROM `phg_sent_emails`
              WHERE `id_campaign` = ?
              AND `id_user` = ?
              AND `date_sent` IS NOT NULL
      ', [$idCampaign, $idUser]);

      return $result > 0;
    }


    /**
     * Aktivuje odesílání cvičných podvodných e-mailů z phishingových kampaní.
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function startSendingEmails() {
      $countSentMails = 0;

      // Seznam aktivních kampaní, u kterých je možné zahájit rozesílání e-mailů.
      $campaigns = CampaignModel::getActiveCampaigns();

      if (count($campaigns) > 0) {
        $ldap = new LdapModel();

        foreach ($campaigns as $campaign) {
          $campaign = CampaignModel::getCampaignDetail($campaign['id_campaign'], false);
          $recipients = CampaignModel::getCampaignRecipients($campaign['id_campaign']);

          foreach ($recipients as $recipient) {
            $user = UsersModel::getUserByEmail($recipient);

            // Ověření, zdali se podařilo získat informace o uživateli
            // (např. uživatel mohl být v průběhu aktivní kampaně odstraněn).
            if (empty($user['id_user'])) {
              continue;
            }

            // Ověření, zdali nedošlo v rámci kampaně k odeslání e-mailu stejnému uživateli někdy dříve.
            if ($this->isEmailSent($campaign['id_campaign'], $user['id_user'])) {
              continue;
            }

            // Získání dodatečných informací o uživateli.
            $user = array_merge($user, UsersModel::getUserFullname($user['username'], $ldap));

            // E-mail, který se bude rozesílat.
            $email = [
              'body' => $campaign['body'],
              'url' => $campaign['url_protocol'] . $campaign['url'],
              'id_campaign' => $campaign['id_campaign'],
              'sender_email' => $campaign['sender_email'],
              'sender_name' => $campaign['sender_name'],
              'html' => $campaign['html']
            ];

            // Personalizace e-mailu na základě příjemce.
            $emailPersonalized = PhishingEmailModel::preparePhishingEmail(
              $email, $user, false, false, $campaign['html']
            );

            Logger::info('Phishing e-mail ready to send.', [
                'id_campaign' => $campaign['id_campaign'],
                'id_user' => $user['id_user'],
                'sender' => $emailPersonalized['sender'],
                'recipient' => $recipient,
                'subject' => $campaign['subject'],
                'body' => $emailPersonalized['body'],
                'html' => $campaign['html']
              ]
            );

            // Odeslání e-mailu.
            $mailResult = $this->sendEmail(
              $emailPersonalized['sender_email'], $campaign['sender_name'], $recipient,
              $campaign['subject'], $emailPersonalized['body'], $campaign['html']
            );

            // Uložení záznamu o tom, zda se e-mail podařilo odeslat.
            $this->logSentEmail($campaign['id_campaign'], $campaign['id_email'], $user['id_user'], $mailResult, $this->mailer->ErrorInfo);

            if ($mailResult) {
              ParticipationModel::decrementEmailLimit($user['id_user']);

              // Vložení záznamu do databáze o tom, že uživatel zatím na kampaň nereagoval.
              CampaignModel::insertNoReactionRecord($campaign['id_campaign'], $user['id_user'], $recipient, $user['departments']);
            }

            // Vyčištění pro další iteraci.
            $this->mailer->clearAddresses();

            // Uspání skriptu po odeslání určitého množství e-mailů.
            $countSentMails = $this->sleepSender($countSentMails);
          }
        }

        $ldap->close();
      }
    }
  }
