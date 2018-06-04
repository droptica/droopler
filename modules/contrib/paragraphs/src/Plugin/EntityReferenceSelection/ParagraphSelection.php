<?php

namespace Drupal\paragraphs\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "default:paragraph",
 *   label = @Translation("Paragraphs"),
 *   group = "default",
 *   entity_types = {"paragraph"},
 *   weight = 0
 * )
 */
class ParagraphSelection extends DefaultSelection {

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  public $entityTypeBundleInfo;

  /**
   * ParagraphSelection constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->configuration['target_type'];
    $selection_handler_settings = $this->configuration['handler_settings'] ?: array();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    // Merge-in default values.
    $selection_handler_settings += array(
      'target_bundles' => array(),
      'negate' => 0,
      'target_bundles_drag_drop' => array(),
    );

    $bundle_options = array();
    $bundle_options_simple = array();

    // Default weight for new items.
    $weight = count($bundles) + 1;

    foreach ($bundles as $bundle_name => $bundle_info) {
      $bundle_options_simple[$bundle_name] = $bundle_info['label'];
      $bundle_options[$bundle_name] = array(
        'label' => $bundle_info['label'],
        'enabled' => isset($selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['enabled']) ? $selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['enabled'] : FALSE,
        'weight' => isset($selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['weight']) ? $selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['weight'] : $weight,
      );
      $weight++;
    }

    // Do negate the selection.
    $form['negate'] = [
      '#type' => 'radios',
      '#options' => [
        1 => $this->t('Exclude the selected below'),
        0 => $this->t('Include the selected below'),
      ],
      '#title' => $this->t('Which Paragraph types should be allowed?'),
      '#default_value' => isset($selection_handler_settings['negate']) ? $selection_handler_settings['negate'] : 0,
    ];

    // Kept for compatibility with other entity reference widgets.
    $form['target_bundles'] = array(
      '#type' => 'checkboxes',
      '#options' => $bundle_options_simple,
      '#default_value' => isset($selection_handler_settings['target_bundles']) ? $selection_handler_settings['target_bundles'] : array(),
      '#access' => FALSE,
    );

    if ($bundle_options) {
      $form['target_bundles_drag_drop'] = [
        '#element_validate' => [[__CLASS__, 'targetTypeValidate']],
        '#type' => 'table',
        '#header' => [
          $this->t('Type'),
          $this->t('Weight'),
        ],
        '#attributes' => [
          'id' => 'bundles',
        ],
        '#prefix' => '<h5>' . $this->t('Paragraph types') . '</h5>',
        '#suffix' => '<div class="description">' . $this->t('Selection of Paragraph types for this field. Select none to allow all Paragraph types.') . '</div>',
      ];

      $form['target_bundles_drag_drop']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'bundle-weight',
      ];
    }

    uasort($bundle_options, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $weight_delta = $weight;

    // Default weight for new items.
    $weight = count($bundles) + 1;
    foreach ($bundle_options as $bundle_name => $bundle_info) {
      $form['target_bundles_drag_drop'][$bundle_name] = array(
        '#attributes' => array(
          'class' => array('draggable'),
        ),
      );

      $form['target_bundles_drag_drop'][$bundle_name]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $bundle_info['label'],
        '#title_display' => 'after',
        '#default_value' => $bundle_info['enabled'],
      );

      $form['target_bundles_drag_drop'][$bundle_name]['weight'] = array(
        '#type' => 'weight',
        '#default_value' => (int) $bundle_info['weight'],
        '#delta' => $weight_delta,
        '#title' => $this->t('Weight for type @type', array('@type' => $bundle_info['label'])),
        '#title_display' => 'invisible',
        '#attributes' => array(
          'class' => array('bundle-weight', 'bundle-weight-' . $bundle_name),
        ),
      );
      $weight++;
    }

    if (!count($bundle_options)) {
      $form['allowed_bundles_explain'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You did not add any Paragraph types yet, click <a href=":here">here</a> to add one.', [':here' => Url::fromRoute('paragraphs.type_add')->toString()]),
      ];
    }

    return $form;
  }

  /**
   * Validate helper to have support for other entity reference widgets.
   *
   * @param $element
   * @param FormStateInterface $form_state
   * @param $form
   */
  public static function targetTypeValidate($element, FormStateInterface $form_state, $form) {
    $values = &$form_state->getValues();
    $element_values = NestedArray::getValue($values, $element['#parents']);
    $bundle_options = array();

    if ($element_values) {
      $enabled = 0;
      foreach ($element_values as $machine_name => $bundle_info) {
        if (isset($bundle_info['enabled']) && $bundle_info['enabled']) {
          $bundle_options[$machine_name] = $machine_name;
          $enabled++;
        }
      }

      // All disabled = all enabled.
      if ($enabled === 0) {
        $bundle_options = NULL;
      }
    }

    // New value parents.
    $parents = array_merge(array_slice($element['#parents'], 0, -1), array('target_bundles'));
    NestedArray::setValue($values, $parents, $bundle_options);
  }

  /**
   * Returns the sorted allowed types for the field.
   *
   * @return array
   *   A list of arrays keyed by the paragraph type machine name
   *   with the following properties.
   *     - label: The label of the paragraph type.
   *     - weight: The weight of the paragraph type.
   */
  public function getSortedAllowedTypes() {
    $return_bundles = [];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
    if (!empty($this->configuration['handler_settings']['target_bundles'])) {
      if (isset($this->configuration['handler_settings']['negate']) && $this->configuration['handler_settings']['negate'] == '1') {
        $bundles = array_diff_key($bundles, $this->configuration['handler_settings']['target_bundles']);
      }
      else {
        $bundles = array_intersect_key($bundles, $this->configuration['handler_settings']['target_bundles']);
      }
    }

    // Support for the paragraphs reference type.
    if (!empty($this->configuration['handler_settings']['target_bundles_drag_drop'])) {
      $drag_drop_settings = $this->configuration['handler_settings']['target_bundles_drag_drop'];
      $max_weight = count($bundles);

      foreach ($drag_drop_settings as $bundle_info) {
        if (isset($bundle_info['weight']) && $bundle_info['weight'] && $bundle_info['weight'] > $max_weight) {
          $max_weight = $bundle_info['weight'];
        }
      }

      // Default weight for new items.
      $weight = $max_weight + 1;
      foreach ($bundles as $machine_name => $bundle) {
        $return_bundles[$machine_name] = [
          'label' => $bundle['label'],
          'weight' => isset($drag_drop_settings[$machine_name]['weight']) ? $drag_drop_settings[$machine_name]['weight'] : $weight,
        ];
        $weight++;
      }
    }
    else {
      $weight = 0;

      foreach ($bundles as $machine_name => $bundle) {
        $return_bundles[$machine_name] = [
          'label' => $bundle['label'],
          'weight' => $weight,
        ];

        $weight++;
      }
    }
    uasort($return_bundles, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $return_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $bundles = array_keys($this->getSortedAllowedTypes());
    return array_filter($entities, function ($entity) {
      if (isset($bundles)) {
        return in_array($entity->bundle(), $bundles);
      }
      return TRUE;
    });
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->configuration['target_type'];
    $handler_settings = $this->configuration['handler_settings'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();

    // If 'target_bundles' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (isset($handler_settings['target_bundles']) && is_array($handler_settings['target_bundles'])) {
      $target_bundles = array_keys($this->getSortedAllowedTypes());

      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($target_bundles === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), $target_bundles, 'IN');
      }
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if (!empty($handler_settings['sort'])) {
      $sort_settings = $handler_settings['sort'];
      if ($sort_settings['field'] != '_none') {
        $query->sort($sort_settings['field'], $sort_settings['direction']);
      }
    }

    return $query;
  }

}
