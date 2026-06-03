<?php

declare(strict_types=1);

namespace Drupal\d_p_subscribe_file\Drush\Listeners;

use Drupal\Core\Database\Connection;
use Drush\Attributes\Bootstrap;
use Drush\Boot\DrupalBootLevels;
use Drush\Event\SanitizeConfirmsEvent;
use Drush\Sql\SqlBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Integrates d_p_subscribe_file with sql:sanitize via Drush 13+ listeners.
 *
 * @see https://www.drush.org/latest/listeners/
 */
#[Bootstrap(level: DrupalBootLevels::FULL)]
#[AsEventListener(method: 'onSanitizeConfirms')]
#[AsEventListener(method: 'onConsoleTerminate')]
final class SubscribeSqlSanitizeListener {

  /**
   * Constructs a SubscribeSqlSanitizeListener object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The module logger channel.
   */
  public function __construct(
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Factory: creates the listener via the Drupal service container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   The listener instance.
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('logger.factory')->get('d_p_subscribe_file'),
    );
  }

  /**
   * Adds a confirmation-line message before sql:sanitize runs.
   *
   * @param \Drush\Event\SanitizeConfirmsEvent $event
   *   The confirms event object.
   */
  public function onSanitizeConfirms(SanitizeConfirmsEvent $event): void {
    if ($this->applies()) {
      $event->addMessage((string) dt('Sanitize subscriptions data name and email'));
    }
  }

  /**
   * Runs subscription sanitizing after sql:sanitize completes successfully.
   *
   * @param \Symfony\Component\Console\Event\ConsoleTerminateEvent $event
   *   The console terminate event.
   */
  public function onConsoleTerminate(ConsoleTerminateEvent $event): void {
    $command = $event->getCommand();
    if ($command === NULL || $command->getName() !== 'sql:sanitize') {
      return;
    }
    if ($event->getExitCode() !== Command::SUCCESS) {
      return;
    }
    $this->sanitize($event->getInput()->getOptions());
  }

  /**
   * Sanitizes email and name columns in d_p_subscribe_file.
   *
   * @param array $cli_options
   *   Drush / SQL driver options passed from the invoking command input.
   */
  protected function sanitize(array $cli_options): void {
    if (!$this->applies()) {
      return;
    }

    $query = $this->database->update('d_p_subscribe_file');

    $sql = SqlBase::create($cli_options);
    $db_driver = $sql->scheme();
    if ($db_driver == 'pgsql') {
      $email_map = [
        '%uid' => "' || id || '",
        '%mail' => "' || replace(mail, '@', '_') || '",
      ];
      $new_mail = "'" . str_replace(array_keys($email_map), array_values($email_map), 'user+%uid@localhost.localdomain') . "'";
      $new_name = 'LEFT(md5(random()::text), 8)';
    }
    elseif ($db_driver == 'mssql') {
      $email_map = [
        '%uid' => "' + id + '",
        '%mail' => "' + replace(mail, '@', '_') + '",
      ];
      $new_mail = "'" . str_replace(array_keys($email_map), array_values($email_map), 'user+%uid@localhost.localdomain') . "'";
      $new_name = 'LEFT(UUID(), 8)';
    }
    else {
      $email_map = [
        '%uid' => "', id, '",
        '%mail' => "', replace(mail, '@', '_'), '",
      ];
      $new_mail = "concat('" . str_replace(array_keys($email_map), array_values($email_map), 'user+%uid@localhost.localdomain') . "')";
      $new_name = 'LEFT(UUID(), 8)';
    }

    $query->expression('mail', $new_mail);
    $query->expression('name', $new_name);
    $query->execute();
    $this->logger->notice((string) dt('Subscriptions data sanitized.'));
  }

  /**
   * Returns whether the subscriptions table exists in the schema.
   *
   * @return bool
   *   TRUE when the entity table exists and sanitization applies.
   */
  protected function applies(): bool {
    return $this->database->schema()->tableExists('d_p_subscribe_file');
  }

}
