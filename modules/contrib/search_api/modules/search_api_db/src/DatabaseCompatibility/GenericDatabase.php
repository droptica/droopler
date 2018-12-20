<?php

namespace Drupal\search_api_db\DatabaseCompatibility;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Database\Connection;

/**
 * Represents any database for which no specifics are known.
 */
class GenericDatabase implements DatabaseCompatibilityHandlerInterface {

  /**
   * The connection to the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The transliteration service to use.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliterator;

  /**
   * Constructs a GenericDatabase object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The connection to the database.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliterator
   *   The transliteration service to use.
   */
  public function __construct(Connection $database, TransliterationInterface $transliterator) {
    $this->database = $database;
    $this->transliterator = $transliterator;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabase() {
    return $this->database;
  }

  /**
   * {@inheritdoc}
   */
  public function getCloneForDatabase(Connection $database) {
    $service = clone $this;
    $service->database = $database;
    return $service;
  }

  /**
   * {@inheritdoc}
   */
  public function alterNewTable($table, $type = 'text') {}

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexValue($value, $type = 'text') {
    if ($type == 'text') {
      return $value;
    }
    return mb_strtolower($this->transliterator->transliterate($value));
  }

}
