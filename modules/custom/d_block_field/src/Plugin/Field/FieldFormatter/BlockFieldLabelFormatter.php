<?php

declare(strict_types=1);

namespace Drupal\d_block_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'd_block_field_label' formatter.
 *
 * @FieldFormatter(
 *   id = "d_block_field_label",
 *   label = @Translation("Block field label"),
 *   field_types = {
 *     "d_block_field"
 *   }
 * )
 */
class BlockFieldLabelFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected readonly RendererInterface $renderer,
    protected readonly AccountInterface $currentUser,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\d_block_field\BlockFieldItemInterface $item */
      $block_instance = $item->getBlock();
      if ($block_instance === NULL || !$block_instance->access($this->currentUser)) {
        continue;
      }

      $elements[$delta] = [
        '#markup' => $block_instance->label(),
      ];

      $this->renderer->addCacheableDependency($elements[$delta], $block_instance);
    }
    return $elements;
  }

}
