<?php

namespace Drupal\d_content_init;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\we_megamenu\WeMegaMenuBuilder;

/**
 * Class ContentInitManagerBlock
 *
 * @package Drupal\d_content_init
 */
class ContentInitManagerBlock extends ContentInitManagerBase {

  /**
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ContentInitManagerBlock constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Entity manager interface.
   * @param \Drupal\Component\Serialization\SerializationInterface $serialization
   *   Serialization interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger channel factory.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   UUID Interface.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager interface.
   * @param ModuleHandlerInterface $module_handler
   *   Module handler interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_manager,
    SerializationInterface $serialization,
    LoggerChannelFactory $logger_factory,
    UuidInterface $uuid,
    BlockManagerInterface $block_manager,
    ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_manager, $serialization, $logger_factory);
    $this->uuid = $uuid;
    $this->blockManager = $block_manager;
    $this->moduleHandler = $module_handler;
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
    $values['id'] = $values['id'] ?? 'block_plugin_' . $this->uuid->generate();
    return $this->saveEntity('block', $values);
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
    if ($placed_block = $this->placeBlockContent($block, $block_entity)) {
      $this->placeBlockInWaMegaMenu($block, $placed_block);
    }
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

  /**
   * Place block content in the we mega menu.
   *
   * @param array $block
   *   Block content definition.
   * @param \Drupal\Core\Entity\EntityInterface $block_entity
   *   Block content entity.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  protected function placeBlockInWaMegaMenu(array $block, EntityInterface $block_entity) {
    if (isset($block['we_megamenu']) && $this->moduleHandler->moduleExists('we_megamenu')) {
      $menu_name = $block['we_megamenu']['menu_name'];
      $parent_title = $block['we_megamenu']['parent_title'];
      $parent_uuid = $this->searchForParentUuidInMegaMenuByTitle($menu_name, $parent_title);

      if ($parent_uuid) {
        $theme = $block['we_megamenu']['theme'];
        $col_config = $block['we_megamenu']['col_config'];
        $row = $block['we_megamenu']['row'];
        $col = $block['we_megamenu']['col'];
        $menu_config = WeMegaMenuBuilder::loadConfig($menu_name, $theme);

        if (!isset($menu_config->menu_config->{$parent_uuid})) {
          $submenu_config = $this->getDefaultMegaSubmenuConfig($parent_uuid);
        }
        else {
          $submenu_config = $menu_config->menu_config->{$parent_uuid};
        }

        $col_content = new \stdClass();
        $col_content->block_id = $block_entity->id();
        $col_content->item_config = new \stdClass();

        $col_cfg = new \stdClass();
        $col_cfg->block = $block_entity->id();
        $col_cfg->hidewhencollapse = $col_config['hidewhencollapse'];
        $col_cfg->type = $col_config['type'];
        $col_cfg->width = $col_config['width'];
        $col_cfg->class = $col_config['class'];
        $col_cfg->block_title = $col_config['block_title'];

        $child_item = new \stdClass();
        $child_item->col_content = $col_content;
        $child_item->col_config = $col_cfg;

        $submenu_config->rows_content[$row][$col] = $child_item;
        $menu_config->menu_config->{$parent_uuid} = $submenu_config;
        WeMegaMenuBuilder::saveConfig($menu_name, $theme, json_encode($menu_config));
        we_megamenu_flush_render_cache();
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get default mega submenu configuration object.
   *
   * @param string $parent_uuid
   *   UUID of the parent of this submenu.
   *
   * @return \stdClass
   *   Configuration object.
   */
  protected function getDefaultMegaSubmenuConfig($parent_uuid) {
    $submenu_config = new \stdClass();
    $submenu_config->rows_content = [];
    $submenu_config->submenu_config = new \stdClass();
    $submenu_config->submenu_config->width = '';
    $submenu_config->submenu_config->height = '';
    $submenu_config->submenu_config->type = '';
    $submenu_config->item_config = new \stdClass();
    $submenu_config->item_config->level = 0;
    $submenu_config->item_config->type = 'we-mega-menu-li';
    $submenu_config->item_config->id = $parent_uuid;
    $submenu_config->item_config->submenu = 1;
    $submenu_config->item_config->hide_sub_when_collapse = '';
    $submenu_config->item_config->group = '';
    $submenu_config->item_config->class = '';
    $submenu_config->item_config->{'data-icon'} = '';
    $submenu_config->item_config->{'data-caption'} = '';
    $submenu_config->item_config->{'data-alignsub'} = '';
    $submenu_config->item_config->{'data-target'} = '_self';
    return $submenu_config;
  }

  /**
   * Search for UUID of the parent link item in the specified mega menu.
   *
   * @param string $menu_name
   *   Machine name od the menu to search.
   * @param string $parent_title
   *   Parent title to find.
   *
   * @return bool|string
   *   UUID if parent exists in the menu, FALSE otherwise.
   */
  protected function searchForParentUuidInMegaMenuByTitle($menu_name, $parent_title) {
    foreach (WeMegaMenuBuilder::getMenuTree($menu_name) as $item)  {
      if ($item['title'] == $parent_title) {
        return $item['derivativeId'];
      }
    }
    return FALSE;
  }
}
