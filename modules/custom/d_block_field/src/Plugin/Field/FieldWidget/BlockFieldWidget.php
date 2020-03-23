<?php

namespace Drupal\d_block_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
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
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Set the block manager.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function setBlockManager(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings']
    );
    $instance->setBlockManager($container->get('plugin.manager.block'));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'plugin_id' => '',
      'settings' => [],
      'configuration_form' => 'full',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['configuration_form'] = [
      '#type' => 'select',
      '#title' => $this->t('Configuration form'),
      '#description' => $this->t('How the block configuration form will be shown.'),
      '#options' => [
        'full' => $this->t('Full'),
        'hidden' => $this->t('Hidden'),
      ],
      '#default_value' => $this->getSetting('configuration_form'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Configuration form: @configuration_form', ['@configuration_form' => $this->getSetting('configuration_form')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\d_block_field\BlockFieldItemInterface $item */
    $item =& $items[$delta];

    $field_name = $this->fieldDefinition->getName();
    $settings_id = implode('-', array_merge(
      $element['#field_parents'],
      [$field_name, $delta, 'settings']
    ));

   // $plugin_ids = $this->fieldDefinition->getSetting('plugin_ids');

    $values = $form_state->getValues();
    $item->plugin_id = (isset($values[$field_name][$delta]['plugin_id'])) ? $values[$field_name][$delta]['plugin_id'] : $item->plugin_id;
    if (!empty($values[$field_name][$delta]['settings'])) {
      $item->settings = $values[$field_name][$delta]['settings'];
    }
    else {
      $item->settings = $item->settings ?: [];
    }

    $options = [];
    /** @var \Drupal\d_block_field\BlockFieldManagerInterface $d_block_field_manager */
    $d_block_field_manager = \Drupal::service('d_block_field.manager');
    $definitions = $d_block_field_manager->getBlockDefinitions();
    foreach ($definitions as $id => $definition) {
      $category = (string) $definition['category'];
      $options[$category][$id] = $definition['admin_label'];
    }

    // Make sure the plugin id is allowed, if not clear all settings.
    if ($item->plugin_id && !isset($definitions[$item->plugin_id])) {
      $item->plugin_id = '';
      $item->setting = [];
    }

    $element['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Block'),
      '#options' => $options,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $item->plugin_id,
      '#required' => $element['#required'],
    ];

    // Show configuration form if required.
    if ($this->getSetting('configuration_form') === 'full') {
      $element['plugin_id']['#ajax'] = [
        'callback' => [$this, 'configurationForm'],
        'wrapper' => $settings_id,
      ];

      // Build configuration container.
      $element['settings'] = [
        '#type' => 'container',
        '#attributes' => ['id' => $settings_id],
        '#tree' => TRUE,
      ];

      // If block plugin exists get the block's configuration form.
      if ($block_instance = $item->getBlock()) {
        /** @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository */
        $context_repository = \Drupal::service('context.repository');
        $form_state->setTemporaryValue('gathered_contexts', $context_repository->getAvailableContexts());

        $element['settings'] += $block_instance->buildConfigurationForm([], $form_state);

        // Hide admin label (aka description).
        if (isset($element['settings']['admin_label'])) {
          $element['settings']['admin_label']['#access'] = FALSE;
        }

        $element['#element_validate'] = [[$this, 'validate']];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formSingleElement($items, $delta, $element, $form, $form_state);
    // For single element set the plugin id title and description to use the
    // field's title and description.
    $element['plugin_id']['#title'] = $element['#title'];
    $element['plugin_id']['#title_display'] = $element['#title_display'];
    $element['plugin_id']['#description'] = $element['#description'];
    return $element;
  }

  /**
   * Ajax callback that return block configuration setting form.
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $array_parents = $trigger_element['#array_parents'];
    $array_parents[(count($array_parents) - 1)] = 'settings';
    $settings_element = NestedArray::getValue($form, $array_parents);

    // Set the label #value to the default block instance's label.
    $plugin_id = $trigger_element['#value'];
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
    if ($block_instance = $block_manager->createInstance($plugin_id)) {
      $settings_element['label']['#value'] = $block_instance->label();
    }

    return $settings_element;
  }

  /**
   * Form element validation handler.
   */
  public static function validate($element, FormStateInterface $form_state, $form) {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');

    $values = $form_state->getValues();
    $plugin_id = NestedArray::getValue($values, $element['plugin_id']['#parents']);

    if (!empty($plugin_id) && $block_manager->hasDefinition($plugin_id)) {
      // Clean up configuration settings.
      $settings = NestedArray::getValue($values, $element['settings']['#parents']);

      // Convert label display to FALSE instead of 0. This allow the label to be
      // hidden.
      if ($settings['label_display'] === 0) {
        $settings['label_display'] = FALSE;
      }

      // Execute block validate configuration.
      $block_instance = $block_manager->createInstance($plugin_id, $settings);
      $settings = (new FormState())->setValues($settings);
      $block_instance->validateConfigurationForm($element['settings'], $settings);

      // Pass along errors from the block validation.
      foreach ($settings->getErrors() as $key => $error) {
        $parents = implode('][', $element['settings']['#parents']);
        // If the block form used setError() then the parents will already be
        // part of the key since we are passing along the element in the context
        // of the whole form. If the block form used setErrorByName we need to
        // add the parents in.
        if (strpos($key, $parents) === FALSE) {
          $key = sprintf('%s][%s', $parents, $key);
        }
        $form_state->setErrorByName($key, $error);
      }

      NestedArray::setValue($values, $element['settings']['#parents'], $settings->getValues());
      $form_state->setValues($values);
    }
    else {
      // Clear all configuration settings.
      NestedArray::setValue($values, $element['settings']['#parents'], []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Some blocks clean the processed values in form state. However, entity
    // forms extract the form values twice during submission. For the second
    // submission to work as well, we need to prevent the removal of the form
    // values during the first submission.
    $form_state = clone $form_state;

    foreach ($values as $delta => &$value) {
      // Execute block submit configuration in order to transform the form
      // values into block configuration.
      if (!empty($value['plugin_id']) && !empty($value['settings']) && $block = $this->blockManager->createInstance($value['plugin_id'])) {
        $elements = &$form[$field_name]['widget'][$value['_original_delta']]['settings'];
        $subform_state = SubformState::createForSubform($elements, $form_state->getCompleteForm(), $form_state);
        $block->submitConfigurationForm($elements, $subform_state);
        // If this block is context-aware, set the context mapping.
        if ($block instanceof ContextAwarePluginInterface && $block->getContextDefinitions()) {
          $context_mapping = $subform_state->getValue('context_mapping', []);
          $block->setContextMapping($context_mapping);
        }
        $value['settings'] = $block->getConfiguration();
      }
    }
    return $values;
  }

}
