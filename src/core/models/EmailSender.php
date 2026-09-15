<?php
  require dirname(__DIR__) . '/../vendor/autoload.php';

  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\SMTP;
  use PHPMailer\PHPMailer\Exception;

  /**
   * Třída sdružující obecné metody týkající se odesílání e-mailů.
   *
   * @author Martin Šebela
   */
  class EmailSender {
    /**
     * @var PHPMailer   Instance třídy PHPMailer
     */
    protected PHPMailer $mailer;


    /**
     * Konstruktor pro inicializaci instance třídy PHPMailer.
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function __construct() {
      $this->mailer = $this->getPHPMailerInstance();
    }


    /**
     * Vytvoří novou instanci třídy PHPMailer společně s nastavením společným pro všechny odesílané e-maily.
     *
     * @return PHPMailer               Instance třídy PHPMailer
     * @throws \PHPMailer\PHPMailer\Exception
     */
    protected function getPHPMailerInstance() {
      $mailer = new PHPMailer;

      if (!empty(SMTP_HOST) && !empty(SMTP_PORT)) {
        $mailer->isSMTP();

        $mailer->Host = SMTP_HOST;
        $mailer->Port = SMTP_PORT;

        if (!empty(SMTP_USERNAME) && !empty(SMTP_PASSWORD)) {
          $mailer->SMTPAuth = true;

          if (SMTP_TLS) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
          }

          // Neuzavírat SMTP spojení po odeslání každého e-mailu.
          $mailer->SMTPKeepAlive = true;

          $mailer->Username = SMTP_USERNAME;
          $mailer->Password = SMTP_PASSWORD;
        }
      }

      $mailer->CharSet = 'UTF-8';
      $mailer->Encoding = 'base64';

      // K odstranění názvu klienta, který e-mail zaslal.
      // SMTP::DEBUG_OFF = off (for production use)
      $mailer->SMTPDebug = SMTP::DEBUG_OFF;
      $mailer->XMailer = ' ';

      // Speciální hlavička určená k identifikaci cvičného phishingu z tohoto systému.
      $mailer->addCustomHeader(PHISHING_EMAIL_HEADER_ID, PHISHING_EMAIL_HEADER_VALUE);

      return $mailer;
    }


    /**
     * Odešle e-mail v rámci instance třídy PHPMailer.
     *
     * @param string $senderEmail      E-mail odesílatele
     * @param string $senderName       Jméno odesílatele
     * @param string $recipientEmail   E-mail příjemce
     * @param string $subject          Předmět e-mailu
     * @param string $body             Tělo e-mailu
     * @param string $html             TRUE, pokud má být e-mail odeslán v HTML, jinak FALSE (výchozí)
     * @param array $attachment        Příloha k e-mailu (nepovinné)
     * @return bool                    Výsledek odeslání e-mailu
     * @throws \PHPMailer\PHPMailer\Exception
     */
    protected function sendEmail($senderEmail, $senderName, $recipientEmail, $subject, $body, $html = false, $attachment = null) {
      try {
        $this->mailer->setFrom($senderEmail, $senderName);
        $this->mailer->addAddress($recipientEmail);

        $this->mailer->Subject = $subject;

        if ($html) {
          $this->mailer->isHTML();
          $this->mailer->AltBody = PhishingEmailModel::convertEmailBodyToPlainText($body);

          $body = $this->getHTMLEmailSkeleton($subject, $body);
        }
        else {
          $this->mailer->isHTML(false);
          $this->mailer->AltBody = '';
        }

        $this->mailer->Body = $body;

        if ($attachment !== null) {
          $this->mailer->addStringAttachment(
            $attachment['content'],
            $attachment['filename'],
              $attachment['encoding'] ?? 'base64',
              $attachment['type'] ?? ''
          );
        }

        return $this->mailer->send();
      }
      finally {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
      }
    }


    /**
     * Vrátí HTML kostru e-mailu včetně doplněného předmětu a obsahu.
     *
     * @param string $subject          Předmět, který má být do kostry doplněn
     * @param string $body             HTML obsah, který má být do kostry doplněn
     * @return string                  HTML kostra e-mailu
     */
    private function getHTMLEmailSkeleton($subject, $body) {
      return '<!DOCTYPE html>
        <html>
        <head>
            <title>' . Controller::escapeOutput($subject) . '</title>
        </head>
        <body>
            ' . $body . '
        </body>
        </html>';
    }


    /**
     * Pokud na poštovní server odejde určitý počet e-mailů, uspí na určitou dobu skript, aby poštovní server
     * e-maily mezitím odbavil.
     *
     * @param int $countSentMails      Počet odeslaných e-mailů v aktuální iteraci
     * @return int                     Nový počet odeslaných e-mailů (buď zvýšený o 1, pokud se ještě nedosáhlo limitu,
     *                                 popř. nastavený na 0)
     */
    protected function sleepSender($countSentMails) {
      $newCountSentEmails = 0;

      // Pokud na poštovní server odešel daný počet e-mailů, uspat na určitou dobu skript,
      // aby poštovní server e-maily mezitím odbavil.
      if ($countSentMails >= EMAIL_SENDER_EMAILS_PER_CYCLE) {
        Logger::info(EMAIL_SENDER_EMAILS_PER_CYCLE . ' emails sent. Script will be suspended for ' . (EMAIL_SENDER_DELAY_MS / 1000) . ' seconds.');

        // Uspání skriptu.
        usleep(EMAIL_SENDER_DELAY_MS);

        // Prodloužení maximální doby běhu skriptu.
        set_time_limit(EMAIL_SENDER_CPU_TIME_S);
      }
      else {
        $newCountSentEmails = $countSentMails + 1;
      }

      return $newCountSentEmails;
    }
  }