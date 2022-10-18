<?php
  // Definice DOCUMENT_ROOT, který v CRONu (v rámci kterého je tento soubor spouštěn) neexistuje.
  $_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

  require $_SERVER['DOCUMENT_ROOT'] . '/base.php';

  Database::connect(DB_PDO_DSN, DB_USERNAME, DB_PASSWORD);

  UsersModel::synchronizeUsers();