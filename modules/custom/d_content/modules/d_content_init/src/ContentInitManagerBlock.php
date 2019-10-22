<?php

namespace Drupal\d_content_init;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class ContentInitManagerBlock
 *
 * @package Drupal\d_content_init
 */
class ContentInitManagerBlock extends ContentInitManagerBase {

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * ContentInitManagerBlock constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Entity manager interface.
   * @param \Drupal\Component\Serialization\SerializationInterface $serialization
   *   Serialization interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger channel factory.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_manager,
    SerializationInterface $serialization,
    LoggerChannelFactory $logger_factory,
    BlockManagerInterface $block_manager) {
    parent::__construct($entity_manager, $serialization, $logger_factory);
    $this->blockManager = $block_manager;
  }

  /**
   * Create block content.
   *
   * @param array $block
   *   Array with definition of block to create.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created block entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createBlockContent(array $block) {
    $block_entity = $this->saveEntity('block_content', [
      'type' => $block['info']['bundle'],
      'info' => $block['info']['title'],
    ]);
    $this->processFields($block,$block_entity);
    $this->placeBlockContent($block, $block_entity);
    return $block_entity;
  }

  /**
   * Create block plugin instance for the block content and place it in region.
   *
   * @param array $block
   *   Block content definition.
   * @param \Drupal\Core\Entity\EntityInterface $block_entity
   *   Block content entity.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   Created block entity or FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function placeBlockContent(array $block, EntityInterface $block_entity) {
    if (isset($block['placement'])) {
      $values = [
        'id' => 'block_content_' . $block_entity->uuid(),
        'plugin' => 'block_content:' . $block_entity->uuid(),
      ] + $this->getBaseBlockValues($block['placement']);
      return $this->saveEntity('block', $values);
    }
    return FALSE;
  }

  /**
   * Create block (plugin type).
   *
   * @param array $block
   *   Array with definition of block to create.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface
   *   Created block entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createBlockPlugin(array $block) {
    $values = $this->getBaseBlockValues($block['info']);
    $values['id'] = $values['id'] ?? 'block_plugin_' . time();
    return $this->saveEntity('block', $values);
  }

  /**
   * Get base block values.
   *
   * @param array $block_info
   *   Array with block info.
   *
   * @return array
   *   Array of base values.
   */
  protected function getBaseBlockValues(array $block_info) {
    return array_intersect_key($block_info, array_flip([
      'id',
      'plugin',
      'langcode',
      'status',
      'theme',
      'region',
      'weight',
      'settings',
    ]));
  }

}
