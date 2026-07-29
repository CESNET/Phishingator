<?php
  /**
   * Třída poskytuje metody pro spouštění povolených příkazů
   * s vybranými argumenty a obsahuje logiku pro validaci
   * příkazů a jejich argumentů.
   *
   * @author Martin Šebela
   */
  class CommandRunnerModel {
    /**
     * @var array       Pole povolených programů a jejich povolených argumentů.
     */
    private const ALLOWED_COMMANDS = [
      '/usr/sbin/a2ensite' => null,
      '/usr/sbin/a2dissite' => null,
      '/usr/sbin/apachectl' => [
        'configtest',
        'graceful',
      ],
      '/usr/bin/kinit' => null
    ];


    /**
     * Spustí program (příkaz) se zadanými argumenty.
     *
     * @param string $program          Název programu/příkazu
     * @param array $arguments         Argumenty předané programu (nepovinné)
     * @param array|null $stdout       Ukazatel na proměnnou, do které se předá výstup programu (nepovinné)
     * @param string|null $stdin       Vstup pro program přes pipe (nepovinné)
     * @return int                     Návratový kód programu
     * @throws UserError
     */
    public static function run($program, $arguments = [], &$stdout = null, $stdin = null) {
      self::assertAllowedProgram($program, $arguments);

      $command = escapeshellarg($program);

      foreach ($arguments as $argument) {
        $command .= ' ' . escapeshellarg($argument);
      }

      if ($stdin !== null) {
        $command = sprintf("printf '%%s\n' %s | %s", escapeshellarg($stdin), $command);
      }

      $stdout = [];

      exec($command, $stdout, $returnCode);

      return $returnCode;
    }


    /**
     * Ověří, zda je zadaný příkaz a jeho argumenty povolené.
     *
     * @param string $program          Název programu/příkazu k ověření
     * @param array $arguments         Argumenty předané programu
     * @return void
     * @throws UserError
     */
    private static function assertAllowedProgram($program, $arguments) {
      if (!array_key_exists($program, self::ALLOWED_COMMANDS)) {
        throw new UserError(
          sprintf('Příkaz "%s" není povolen.', $program),
        );
      }

      $allowedArguments = self::ALLOWED_COMMANDS[$program];

      if ($allowedArguments !== null) {
        foreach ($arguments as $argument) {
          if (!in_array($argument, $allowedArguments, true)) {
            throw new UserError(
              sprintf('Argument "%s" u příkazu "%s" není povolen.', $argument, $program),
            );
          }
        }
      }
    }
  }