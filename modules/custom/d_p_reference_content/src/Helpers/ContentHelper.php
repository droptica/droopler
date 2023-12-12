<?php

declare(strict_types = 1);

namespace Drupal\d_p_reference_content\Helpers;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Content helper.
 */
class ContentHelper {

  /**
   * Content helper constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entityDisplayRepository
   *   The entity display repository.
   */
  public function __construct(
    private readonly Connection $connection,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityDisplayRepository $entityDisplayRepository
  ) {}

  /**
   * Get nids from selected content type.
   *
   * @param array $type
   *   Content type.
   * @param string $sortBy
   *   Table name used for sorting.
   * @param string $sort
   *   Type of sort - DESC, ASC.
   * @param array $values
   *   Array of values to exclude.
   *
   * @return mixed
   *   Return ids.
   */
  public function getSortedContentByType(array $type, string $sortBy, string $sort, array $values) {
    if (empty($type)) {
      return [];
    }

    $query = $this->connection->select('node_field_data', 'nfd')
      ->fields('nfd', ['nid', 'created'])
      ->orderBy($sortBy, $sort)
      ->condition('nfd.type', $type, 'IN');

    $result = $query->execute();

    $data = [];
    foreach ($result as $record) {
      $data[$record->nid] = [
        'nid' => $record->nid,
        $sortBy => $record->{$sortBy},
      ];
    }

    return $this->excludeFromResults($data, $values);
  }

  /**
   * Replace content.
   *
   * @param array $variables
   *   Variables array from preprocess.
   * @param string $entity_type
   *   Entity type.
   * @param string $view_mode
   *   Entity view mode.
   * @param string $field
   *   Field name.
   * @param array $new_values
   *   Array with new content values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function replaceContent(array &$variables, string $entity_type, string $view_mode, string $field, array $new_values) {
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
   * @param array $data
   *   Query result.
   * @param array $values
   *   Values to exclude.
   *
   * @return array
   *   New data.
   */
  private function excludeFromResults(array $data, array $values): array {
    // Remove data if target exist.
    foreach ($values as $target) {
      unset($data[$target['target_id']]);
    }

    return $this->prepareFieldValues($data);
  }

  /**
   * Prepare data for field values.
   *
   * @param array $data
   *   Nids.
   *
   * @return array
   *   Data with target_id.
   */
  private function prepareFieldValues(array $data): array {
    $result = [];
    foreach ($data as $item) {
      $result[] = [
        'target_id' => $item['nid'],
      ];
    }

    return $result;
  }

  /**
   * Delete unpublished content.
   *
   * @param array $values
   *   Array with content values.
   *
   * @return array
   *   Content without unpublished nodes.
   */
  public function getPublishedContent(array $values): array {
    $nids = [];
    foreach ($values as $value) {
      $nids[] = $value['target_id'];
    }

    if (empty($nids)) {
      return [];
    }

    $results = $this->connection->select('node_field_data', 'nfd')
      ->fields('nfd', ['nid'])
      ->condition('nfd.status', 1)
      ->condition('nfd.nid', $nids, 'IN')
      ->execute()->fetchCol();

    $diff = array_values(array_diff($nids, $results));

    // Remove data if target exist.
    foreach ($values as $key => $value) {
      if (in_array($value['target_id'], $diff)) {
        unset($values[$key]);
      }
    }

    return array_values($values);
  }

  /**
   * Get list of content types that have available view mode.
   *
   * @param string $view_mode
   *   String with view mode.
   *
   * @return array
   *   Array of content types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentTypes(string $view_mode = 'reference_content'): array {
    $filtered_content_types = [];

    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $content_type) {
      $view_modes = $this->entityDisplayRepository
        ->getViewModeOptionsByBundle('node', $content_type->id());
      if (array_search($view_mode, array_keys($view_modes))) {
        $filtered_content_types[] = $content_type->id();
      }
    }

    return $filtered_content_types;
  }

}
