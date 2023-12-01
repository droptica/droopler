<?php

namespace Drupal\d_block_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'd_block_field' formatter.
 *
 * @FieldFormatter(
 *   id = "d_block_field",
 *   label = @Translation("Block field"),
 *   field_types = {
 *     "d_block_field"
 *   }
 * )
 */
class BlockFieldFormatter extends FormatterBase {

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ContextRepositoryInterface $context_repository,
    ContextHandlerInterface $context_handler,
    RendererInterface $renderer,
    AccountInterface $current_user
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->contextRepository = $context_repository;
    $this->contextHandler = $context_handler;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('context.repository'),
      $container->get('context.handler'),
      $container->get('renderer'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\d_block_field\BlockFieldItemInterface $item */
      $block_instance = $item->getBlock();

      // Inject runtime contexts.
      if ($block_instance instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts($block_instance->getContextMapping());
          $this->contextHandler->applyContextMapping($block_instance, $contexts);
        }
        catch (ContextException $e) {
          continue;
        }
      }

      // Make sure the block exists and is accessible.
      if (!$block_instance || !$block_instance->access($this->currentUser)) {
        continue;
      }

      // See \Drupal\block\BlockViewBuilder::buildPreRenderableBlock
      // See template_preprocess_block()
      $elements[$delta] = [
        '#theme' => 'block',
        '#attributes' => [],
        '#configuration' => $block_instance->getConfiguration(),
        '#plugin_id' => $block_instance->getPluginId(),
        '#base_plugin_id' => $block_instance->getBaseId(),
        '#derivative_plugin_id' => $block_instance->getDerivativeId(),
        'content' => $this->processBlockBuild($block_instance),
      ];

      $this->renderer->addCacheableDependency($elements[$delta], $block_instance);
    }
    return $elements;
  }

  /**
   * Get processed block build.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block_instance
   *   The Block Plugin Interface.
   *
   * @return array
   *   The Block Build.
   */
  private function processBlockBuild(BlockPluginInterface $block_instance): array {
    $block_build = $block_instance->build();

    // Entity view node block without recursive node rendering.
    if ($block_instance->getBaseId() === 'entity_view') {
      $node = $block_build['#node'] ?? NULL;

      if ($node instanceof NodeInterface) {
        $sections = $node->hasField('field_page_section') && !$node->get('field_page_section')->isEmpty()
          ? $node->get('field_page_section')->referencedEntities()
          : NULL;

        if (!$sections) {
          return $block_build;
        }

        $block_build['content']['#node'] = $node->set(
          'field_page_section',
          $this->getAllowedSectionsForEntityViewBlock($sections)
        );
      }
    }

    return $block_build;
  }

  /**
   * Get allowed references for page section field.
   *
   * @param array $sections
   *   All referenced entities from field page section.
   *
   * @return array
   *   Allowed referenced entities for field page section.
   */
  private function getAllowedSectionsForEntityViewBlock(array $sections): array {
    foreach ($sections as $key => $paragraph) {
      if ($paragraph instanceof ParagraphInterface && $paragraph->getType() === 'd_p_block') {
        $field_block_value = $paragraph->hasField('field_block') && !$paragraph->get('field_block')->isEmpty()
          ? $paragraph->get('field_block')->getValue()
          : NULL;
        $field_block_value = reset($field_block_value);

        if (isset($field_block_value['plugin_id']) && $field_block_value['plugin_id'] === 'entity_view:node') {
          unset($sections[$key]);
        }
      }
    }

    return $sections;
  }

}
