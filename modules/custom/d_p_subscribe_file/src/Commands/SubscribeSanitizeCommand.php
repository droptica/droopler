<?php

declare(strict_types = 1);

namespace Drupal\d_p_subscribe_file\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Drush\Sql\SqlBase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Drush sql-sanitize plugin for sanitizing some random data.
 */
class SubscribeSanitizeCommand extends DrushCommands implements SanitizePluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Subscribe sanitize command constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   */
  public function __construct(Connection $database) {
    parent::__construct();
    $this->database = $database;
  }

  /**
   * Sanitize subscriptions emails and names.
   *
   * @hook post-command sql-sanitize
   *
   * @inheritdoc
   */
  public function sanitize($result, CommandData $commandData) {
    $query = $this->database->update('d_p_subscribe_file');

    $sql = SqlBase::create($commandData->input()->getOptions());
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
    $this->logger()->success(dt('Subscriptions data sanitized.'));

  }

  /**
   * This method will print out what will be sanitized.
   *
   * @hook on-event sql-sanitize-confirms
   *
   * @inheritdoc
   */
  public function messages(&$messages, InputInterface $input) {
    $messages[] = dt('Sanitize subscriptions data name and email');
    return $messages;
  }

}
