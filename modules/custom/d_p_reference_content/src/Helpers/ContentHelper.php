<?php

namespace Drupal\d_p_reference_content\Helpers;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;

class ContentHelper {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * ContentHelper constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(Connection $connection, EntityTypeManager $entityTypeManager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
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
   *
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
   * Replace content.
   *
   * @param $variables
   *   Variables array from preprocess.
   * @param $entity_type
   *   Entity type.
   * @param $view_mode
   *   Entity view mode.
   * @param $field
   *   Field name.
   * @param $new_values
   *   Array with new content values
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function replaceContent(&$variables, $entity_type, $view_mode, $field, $new_values) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['elements']['#paragraph'];
    $paragraph->set($field, $new_values);

    // Prepare view array without content.
    $view = $paragraph->{$field}->view('default');
    foreach ($new_values as $key => $data) {
      unset($view[$key]);
    }
    $variables['content'][$field] = $view;

    $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
    $storage = $this->entityTypeManager->getStorage($entity_type);
    foreach ($new_values as $key => $data) {
      if (!empty($data['target_id'])) {
        if ($node = $storage->load($data['target_id'])) {
          $build = $view_builder->view($node, $view_mode);
          // Append element to paragraph content.
          $variables['content'][$field][$key] = $build;
        }
      }
    }
  }

  /**
   * Exclude values from result.
   *
   * @param $data
   *   Query result.
   * @param $values
   *   Values to exclude.
   *
   * @return array
   *   New data.
   */
  private function excludeFromResults($data, $values) {
    // Remove data if target exist.
    foreach ($values as $target) {
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
   *
   * @return array
   *   Data with target_id.
   */
  private function prepareFieldValues($data) {
    $result = [];
    foreach ($data as $item) {
      $result[] = [
        'target_id' => $item['nid'],
      ];
    }

    return $result;
  }

}
