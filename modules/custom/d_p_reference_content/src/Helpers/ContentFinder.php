<?php

/**
 * @file
 * Contains \Drupal\d_p_reference_content\Helpers\ContentFinder.
 */

namespace Drupal\d_p_reference_content\Helpers;

use Drupal\Core\Database\Connection;

class ContentFinder {

  /**
   * @var Connection $dbConnection
   */
  private $connection;

  /**
   * ContentFinder constructor.
   *
   * @param Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Get nids from selected content type.
   *
   * @param $type
   *   Content type.
   * @param $sortBy
   *   Table name used for sorting.
   * @param $sort
   *   Type of sort - DESC, ASC.
   * @param $values
   *   Array of values to exclude.
   * @return mixed
   *   Return ids.
   */
  public function getSortedContentByType($type, $sortBy, $sort, array $values) {
    $query = $this->connection->select('node_field_data', 'nfd')
      ->fields('nfd', ['nid', 'created'])
      ->orderBy($sortBy, $sort)
      ->condition('nfd.type', $type);

    $result = $query->execute();

    $data = [];
    foreach ($result as $record) {
      $data[$record->nid] = [
        'nid' => $record->nid,
        $sortBy => $record->{$sortBy},
      ];
    }

    $result = $this->excludeFromResults($data, $values);

    return $result;
  }

  /**
   * Exclude values from result.
   *
   * @param $data
   *   Query result.
   * @param $values
   *   Values to exclude.
   * @return array
   *   New data.
   */
  private function excludeFromResults($data, $values) {
    // Remove data if target exist.
    foreach($values as $target) {
      unset($data[$target['target_id']]);
    }

    $result = $this->prepareFieldValues($data);

    return $result;
  }

  /**
   * Prepare data for field values.
   *
   * @param $data
   *   Nids.
   * @return array
   *   Data with target_id.
   */
  private function prepareFieldValues($data) {
    $result = [];
    foreach ($data as $item) {
      $result[] = [
        'target_id' => $item['nid']
      ];
    }

    return $result;
  }

}
