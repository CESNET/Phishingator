<?php
  /**
   * Třída zajišťující export statistiky z kampaní.
   *
   * @author Martin Šebela
   */
  class StatsExportModel {
    /**
     * Exportuje reakce uživatelů na phishing pro konkrétní kampaň.
     *
     * @param int $id                  ID kampaně
     * @param string|null $filepath    Cesta na webovém serveru, kde má být exportovaný soubor uložen nebo NULL,
     *                                 pokud se soubor na webový server ukládat nemá.
     * @return string|null             Vrátí cestu k souboru (pokud měl být soubor uložen na webovém serveru),
     *                                 jinak NULL.
     * @throws UserError
     */
    public static function exportUsersResponses($id, $filepath = null) {
      Logger::info('Request to export phishing campaign data (users responses).', $id);

      $csvFilename = PHISHING_CAMPAIGN_EXPORT_FILENAME . '-' . $id . '-users-responses';
      $csvData = CampaignModel::getUsersResponsesInCampaign($id, false);

      // Informace o datech pro CSV export.
      $csvHeader = ['username', 'email', 'group', 'action', 'reported', 'educational_site_visited', 'educational_site_visited_utc'];
      $csvDataIndexes = ['username', 'used_email', 'used_group', 'name', 'reported', 'edusite_visit_datetime', 'edusite_visit_datetime_utc'];

      $data = [
        'csvFilename' => $csvFilename,
        'csvHeader' => $csvHeader,
        'csvData' => $csvData,
        'csvDataIndexes' => $csvDataIndexes
      ];

      return self::exportToCSV($data, $filepath);
    }


    /**
     * Exportuje seznam kompromitovaných účtů pro konkrétní kampaň.
     *
     * @param int $id                  ID kampaně
     * @param string|null $filepath    Cesta na webovém serveru, kde má být exportovaný soubor uložen nebo NULL,
     *                                 pokud se soubor na webový server ukládat nemá.
     * @return string|null             Vrátí cestu k souboru (pokud měl být soubor uložen na webovém serveru),
     *                                 jinak NULL.
     * @throws UserError
     */
    public static function exportUsersCompromised($id, $filepath = null) {
      Logger::info('Request to export phishing campaign data (compromised users).', $id);

      $csvFilename = PHISHING_CAMPAIGN_EXPORT_FILENAME . '-' . $id . '-users-compromised';
      $csvData = CampaignModel::getCompromisedUsersInCampaign($id, false);

      // Doplnění pořadí kompromitace.
      foreach ($csvData as $key => $record) {
        $csvData[$key]['compromise_order'] = $key + 1;
      }

      // Informace o datech pro CSV export.
      $csvHeader = ['compromise_order', 'username', 'email', 'group', 'reported', 'time_since_campaign_start', 'seconds_since_campaign_start', 'compromise_datetime', 'compromise_datetime_utc'];
      $csvDataIndexes = ['compromise_order', 'username', 'used_email', 'used_group', 'reported', 'time_since_campaign_start', 'seconds_since_campaign_start', 'visit_datetime', 'visit_datetime_utc'];

      $data = [
        'csvFilename' => $csvFilename,
        'csvHeader' => $csvHeader,
        'csvData' => $csvData,
        'csvDataIndexes' => $csvDataIndexes
      ];

      return self::exportToCSV($data, $filepath);
    }


    /**
     * Exportuje počet jednotlivých akcí každého uživatele zaznamenaných na podvodné stránce u konkrétní kampaně.
     *
     * @param int $id                  ID kampaně
     * @param string|null $filepath    Cesta na webovém serveru, kde má být exportovaný soubor uložen nebo NULL,
     *                                 pokud se soubor na webový server ukládat nemá.
     * @return string|null             Vrátí cestu k souboru (pokud měl být soubor uložen na webovém serveru),
     *                                 jinak NULL.
     * @throws UserError
     */
    public static function exportUsersResponsesSum($id, $filepath = null) {
      Logger::info('Request to export phishing campaign data (count users actions).', $id);

      $csvFilename = PHISHING_CAMPAIGN_EXPORT_FILENAME . '-' . $id . '-users-responses-sum';
      $csvData = [];

      $statsModel = new StatsModel();
      $actions = $statsModel->getEmptyArraySumActions();

      $result = Database::queryMulti('
              SELECT phg_users.id_user, `name`, `username`, `used_email` AS `email`, `used_group`, phg_captured_data.id_action, `reported`
              FROM `phg_captured_data`
              JOIN `phg_captured_data_actions`
              ON phg_captured_data.id_action = phg_captured_data_actions.id_action
              JOIN `phg_users`
              ON phg_captured_data.id_user = phg_users.id_user
              WHERE `id_campaign` = ?
              ORDER BY `id_captured_data`
      ', $id);

      if (!empty($result) && !empty($actions)) {
        // Příprava dat z databáze do struktury, která se bude zapisovat do CSV.
        foreach ($result as $record) {
          if (!isset($csvData[$record['id_user']])) {
            $csvData[$record['id_user']] = [
              'username' => $record['username'],
              'email' => $record['email'],
              'group' => $record['used_group'],
              'reported' => $record['reported']
            ];

            // Místo multidimenzionálního pole změníme pole akcí na další prvky
            // původního pole (kvůli následným názvům indexů v poli).
            foreach ($actions as $idAction => $action) {
              $csvData[$record['id_user']]['action-' . $idAction] = 0;
            }
          }
          elseif (isset($csvData[$record['id_user']]['reported']) && !empty($record['reported'])) {
            // Pokud došlo k nahlášení phishingu až v některé z pozdějších akcí, tak u uživatele přepsat "nahlášení".
            $csvData[$record['id_user']]['reported'] = $record['reported'];
          }

          $csvData[$record['id_user']]['action-' . $record['id_action']] += 1;
        }
      }

      // Informace o datech pro CSV export.
      $csvHeader = [
        'username', 'email', 'group',
        'sum_visit_fraudulent_page', 'sum_invalid_credentials', 'sum_valid_credentials', 'reported'
      ];
      $csvDataIndexes = ['username', 'email', 'group', 'action-2', 'action-3', 'action-4', 'reported'];

      $data = [
        'csvFilename' => $csvFilename,
        'csvHeader' => $csvHeader,
        'csvData' => $csvData,
        'csvDataIndexes' => $csvDataIndexes
      ];

      return self::exportToCSV($data, $filepath);
    }


    /**
     * Exportuje všechny zaznamenané uživatelské aktivity na podvodné stránce u konkrétní kampaně.
     *
     * @param int $id                  ID kampaně
     * @param string|null $filepath    Cesta na webovém serveru, kde má být exportovaný soubor uložen nebo NULL,
     *                                 pokud se soubor na webový server ukládat nemá.
     * @return string|null             Vrátí cestu k souboru (pokud měl být soubor uložen na webovém serveru),
     *                                 jinak NULL.
     * @throws UserError
     */
    public static function exportAllCapturedData($id, $filepath = null) {
      Logger::info('Request to export all phishing campaign data.', $id);

      $csvFilename = PHISHING_CAMPAIGN_EXPORT_FILENAME . '-' . $id . '-website-actions';
      $csvData = CampaignModel::getCapturedDataInCampaign($id, true, false);

      // Informace o datech pro CSV export.
      $csvHeader = [
        'username', 'email', 'group', 'action', 'action_datetime', 'action_datetime_utc', 'reported', 'ip', 'user_agent', 'filled_username', 'filled_password'
      ];
      $csvDataIndexes = [
        'username', 'used_email', 'used_group', 'name', 'visit_datetime', 'visit_datetime_utc', 'reported', 'ip', 'browser_fingerprint', 'filled_username', 'filled_password'
      ];

      $data = [
        'csvFilename' => $csvFilename,
        'csvHeader' => $csvHeader,
        'csvData' => $csvData,
        'csvDataIndexes' => $csvDataIndexes
      ];

      return self::exportToCSV($data, $filepath);
    }


    /**
     * Exportuje všechna data do ZIP archivu.
     *
     * @param int $idCampaign          ID kampaně
     * @throws UserError
     */
    public static function exportAllToZipArchive($idCampaign) {
      Logger::info('Request to export a ZIP archive with all phishing campaign data.', $idCampaign);

      $zip = new ZipArchive();
      $zipFilepath = CORE_DIR_TEMP . '/' . PHISHING_CAMPAIGN_EXPORT_FILENAME . '-' . $idCampaign . '-' . date('Y-m-d') . '.zip';

      $files = [];

      if ($zip->open($zipFilepath, ZipArchive::CREATE) === true) {
        // Získání jednotlivých souborů do archivu.
        $files[] = self::exportUsersResponses($idCampaign, CORE_DIR_TEMP);
        $files[] = self::exportAllCapturedData($idCampaign, CORE_DIR_TEMP);
        $files[] = self::exportUsersResponsesSum($idCampaign, CORE_DIR_TEMP);
        $files[] = self::exportUsersCompromised($idCampaign, CORE_DIR_TEMP);

        // Vložení všech souborů do archivu.
        foreach ($files as $file) {
          if (!empty($file)) {
            $zip->addFile($file, basename($file));
          }
        }

        $zip->close();

        // Vrácení archivu se soubory ke stažení uživateli.
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipFilepath) . '"');
        header('Content-Length: ' . filesize($zipFilepath));

        readfile($zipFilepath);

        // Smazání souborů z dočasného adresáře.
        $files[] = $zipFilepath;

        foreach ($files as $file) {
          if (file_exists($file)) {
            FileManagerModel::deleteFileInDirectory($file, CORE_DIR_TEMP);
          }
        }

        exit();
      }
    }


    /**
     * Exportuje zvolená data do CSV souboru a ten buď vrátí uživateli ke stažení, nebo uloží
     * do konkrétního adresáře na webovém serveru.
     *
     * @param array $data              Data obsahující název CSV souboru, hlavičku, názvy indexů a samotná data.
     * @param string|null $newFilepath Nepovinný parametr určující cestu, kde má být exportovaný CSV soubor
     *                                 uložen na webovém serveru (jinak se posílá na výstup).
     * @return string|null             Pokud má být exportovaný soubor uložený na webovém serveru (viz předchozí
     *                                 parametr), vrátí název exportovaného souboru.
     * @throws UserError
     */
    private static function exportToCSV($data, $newFilepath = null) {
      if (!empty($data) && count($data) == 4) {
        $csvFilename = $data['csvFilename'] . '-' . date('Y-m-d') . '.csv';

        // Vytvářet soubor na základě výpisu, nebo jako nový soubor v konkrétním adresáři na webovém serveru.
        $filepath = Controller::escapeOutput(($newFilepath == null) ? 'php://output' : $newFilepath . '/' . $csvFilename);

        // Vrácení CSV souboru uživateli ke stažení.
        if ($newFilepath == null) {
          header('Content-Type: text/csv; charset=utf-8');
          header('Content-Disposition: attachment; filename="' . $csvFilename . '"');
        }

        $csvFile = fopen($filepath, 'w');
        fputcsv($csvFile, $data['csvHeader'], PHISHING_CAMPAIGN_EXPORT_DELIMITER);

        // Zápis připravených dat do CSV souboru.
        foreach ($data['csvData'] as $row) {
          $csvLineData = [];

          // Příprava zapisovaných dat na základě názvů předaných indexů
          // (aby byla data ve správném pořadí vůči hlavičce CSV souboru).
          foreach ($data['csvDataIndexes'] as $dataIndex) {
            $csvLineData[] = $row[$dataIndex];
          }

          fputcsv($csvFile, $csvLineData, PHISHING_CAMPAIGN_EXPORT_DELIMITER);
        }

        fclose($csvFile);

        if ($newFilepath == null) {
          exit();
        }
        else {
          return $filepath;
        }
      }
      else {
        throw new UserError('Ke zvolené kampani neexistují žádná data.', MSG_ERROR);
      }
    }
  }
