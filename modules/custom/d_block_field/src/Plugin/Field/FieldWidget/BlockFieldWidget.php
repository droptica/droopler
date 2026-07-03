<?php

declare(strict_types=1);

namespace Drupal\d_block_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\d_block_field\BlockFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'd_block_field' widget.
 *
 * @FieldWidget(
 *   id = "d_block_field_default",
 *   label = @Translation("Block field"),
 *   field_types = {
 *     "d_block_field"
 *   }
 * )
 */
class BlockFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BlockFieldWidget object.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected BlockManagerInterface $blockManager,
    protected BlockFieldManagerInterface $fieldManager,
    protected ContextRepositoryInterface $contextRepository,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.block'),
      $container->get('d_block_field.manager'),
      $container->get('context.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'plugin_id'          => '',
      'settings'           => [],
      'configuration_form' => 'full',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = [];
    $elements['configuration_form'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Configuration form'),
      '#description'   => $this->t('How the block configuration form will be shown.'),
      '#options'       => [
        'full'   => $this->t('Full'),
        'hidden' => $this->t('Hidden'),
      ],
      '#default_value' => $this->getSetting('configuration_form'),
      '#required'      => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      $this->t('Configuration form: @configuration_form', ['@configuration_form' => $this->getSetting('configuration_form')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\d_block_field\BlockFieldItemInterface $item */
    $item =& $items[$delta];

    $field_name = $this->fieldDefinition->getName();
    $settings_id = implode('-', array_merge(
      $element['#field_parents'],
      [$field_name, $delta, 'settings'],
    ));

    $values = $form_state->getValues();
    $item->plugin_id = $values[$field_name][$delta]['plugin_id'] ?? $item->plugin_id;
    $item->settings = !empty($values[$field_name][$delta]['settings'])
      ? $values[$field_name][$delta]['settings']
      : ($item->settings ?: []);

    $categories = array_filter($this->getFieldSetting('plugin_categories') ?? []);
    $categories_exclude = (bool) ($this->getFieldSetting('plugin_categories_exclude') ?? FALSE);

    $options = [];
    $definitions = $this->fieldManager->getBlockDefinitions();
    foreach ($definitions as $id => $definition) {
      $category = (string) $definition['category'];
      if (empty($categories) || ($categories_exclude xor in_array($category, $categories, TRUE))) {
        $options[$category][$id] = $definition['admin_label'];
      }
    }

    // If the previously stored plugin id is no longer allowed, clear settings.
    if ($item->plugin_id && !isset($definitions[$item->plugin_id])) {
      $item->plugin_id = '';
      $item->settings = [];
    }

    $element['plugin_id'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Block'),
      '#options'       => $options,
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => $item->plugin_id,
      '#required'      => $element['#required'],
    ];

    if ($this->getSetting('configuration_form') !== 'full') {
      return $element;
    }

    $element['plugin_id']['#ajax'] = [
      'callback' => [$this, 'configurationForm'],
      'wrapper'  => $settings_id,
    ];

    $element['settings'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => $settings_id],
      '#tree'       => TRUE,
    ];

    $block_instance = $item->getBlock();
    if ($block_instance !== NULL) {
      $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());
      $element['settings'] += $block_instance->buildConfigurationForm([], $form_state);

      if (isset($element['settings']['admin_label'])) {
        $element['settings']['admin_label']['#access'] = FALSE;
      }

      $element['#element_validate'] = [[$this, 'validate']];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formSingleElement(FieldItemListInterface $items, mixed $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formSingleElement($items, $delta, $element, $form, $form_state);
    // For a single element propagate the field's title/description onto the
    // inner `plugin_id` select.
    $element['plugin_id']['#title']         = $element['#title'];
    $element['plugin_id']['#title_display'] = $element['#title_display'];
    $element['plugin_id']['#description']   = $element['#description'];
    return $element;
  }

  /**
   * Ajax callback returning the freshly-built block configuration form.
   */
  public function configurationForm(array $form, FormStateInterface $form_state): array {
    $trigger_element = $form_state->getTriggeringElement();
    $array_parents = $trigger_element['#array_parents'];
    $array_parents[count($array_parents) - 1] = 'settings';
    $settings_element = NestedArray::getValue($form, $array_parents);

    $plugin_id = $trigger_element['#value'];
    $block_instance = $this->blockManager->createInstance($plugin_id);
    if ($block_instance instanceof BlockPluginInterface) {
      $settings_element['label']['#value'] = $block_instance->label();
    }

    return $settings_element;
  }

  /**
   * Form element validation handler.
   */
  public function validate(array $element, FormStateInterface $form_state, array $form): void {
    $values = $form_state->getValues();
    $plugin_id = NestedArray::getValue($values, $element['plugin_id']['#parents']);

    if (empty($plugin_id) || !$this->blockManager->hasDefinition($plugin_id)) {
      NestedArray::setValue($values, $element['settings']['#parents'], []);
      $form_state->setValues($values);
      return;
    }

    $settings = NestedArray::getValue($values, $element['settings']['#parents']);
    // Convert label_display=0 to FALSE so the label can be hidden.
    if (isset($settings['label_display']) && $settings['label_display'] === 0) {
      $settings['label_display'] = FALSE;
    }

    $block_instance = $this->blockManager->createInstance($plugin_id, $settings);
    $sub_form_state = (new FormState())->setValues($settings);
    $block_instance->validateConfigurationForm($element['settings'], $sub_form_state);

    foreach ($sub_form_state->getErrors() as $key => $error) {
      $parents = implode('][', $element['settings']['#parents']);
      // If the block form used setError() the parents are already part of the
      // key (we're passing along the whole form); if it used setErrorByName
      // we need to prefix them.
      if (!str_contains($key, $parents)) {
        $key = sprintf('%s][%s', $parents, $key);
      }
      $form_state->setErrorByName($key, $error);
    }

    NestedArray::setValue($values, $element['settings']['#parents'], $sub_form_state->getValues());
    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $field_name = $this->fieldDefinition->getName();

    // Some blocks clean processed values in form state. Entity forms extract
    // the form values twice during submission — clone the form state so the
    // second extraction still works.
    $form_state = clone $form_state;

    foreach ($values as &$value) {
      if (empty($value['plugin_id']) || empty($value['settings'])) {
        continue;
      }
      $block = $this->blockManager->createInstance($value['plugin_id']);
      if (!$block instanceof BlockPluginInterface) {
        continue;
      }

      $elements = &$form[$field_name]['widget'][$value['_original_delta']]['settings'];
      $subform_state = SubformState::createForSubform($elements, $form_state->getCompleteForm(), $form_state);
      $block->submitConfigurationForm($elements, $subform_state);
      if ($block instanceof ContextAwarePluginInterface && $block->getContextDefinitions() !== []) {
        $block->setContextMapping($subform_state->getValue('context_mapping', []));
      }
      $value['settings'] = $block->getConfiguration();
    }
    return $values;
  }

}
