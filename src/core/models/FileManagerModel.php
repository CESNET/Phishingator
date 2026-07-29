<?php
  /**
   * Třída řešící práci se soubory.
   *
   * @author Martin Šebela
   */
  class FileManagerModel {
    /**
     * Smaže soubor z vybraného (povoleného) adresáře.
     *
     * @param string $file             Soubor, který má být smazán.
     * @param string $allowedDirectory Povolený adresář, ve kterém se má soubor smazat
     * @return void
     * @throws UserError
     */
    public static function deleteFileInDirectory($file, $allowedDirectory) {
      $realDirectory = realpath($allowedDirectory);
      $realFile = realpath($file);

      if ($realDirectory !== false && $realFile !== false) {
        $realDirectory = rtrim($realDirectory, DIRECTORY_SEPARATOR);

        if (!is_file($realFile) || is_link($file) || dirname($realFile) !== $realDirectory) {
          throw new UserError('Vybraný soubor nelze smazat!');
        }

        if (!unlink($realFile)) {
          throw new UserError(sprintf('Soubor "%s" se nepodařilo odstranit!', basename($realFile)));
        }
      }
    }
  }