<?php

declare(strict_types = 1);

namespace Drupal\d_product\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\node\NodeStorageInterface;
use Drupal\node\Plugin\views\argument\Vid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a node revision id.
 *
 * @ViewsArgument("node_vid")
 */
class NodeVid extends Vid {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a NodeVid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The current database.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NodeStorageInterface $node_storage,
    Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $node_storage);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('database')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    return $this->database->query('SELECT nr.vid, nr.nid, nfr.title
      FROM {node_revision} nr
      INNER JOIN {node_field_revision} nfr ON nr.vid = nfr.vid
      WHERE nr.vid IN ( :vids[] )', [':vids[]' => $this->value])->fetchAllKeyed(1, 2);
  }

}
