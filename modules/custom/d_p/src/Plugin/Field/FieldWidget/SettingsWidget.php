<?php

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_p\ParagraphSettingPluginManagerInterface;
use Drupal\d_p\ParagraphSettingSelectInterface;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Settings widget' widget.
 *
 * @FieldWidget(
 *   id = "field_d_p_set_settings",
 *   module = "d_p",
 *   label = @Translation("Settings"),
 *   field_types = {
 *     "field_p_configuration_storage"
 *   }
 * )
 */
class SettingsWidget extends WidgetBase {

  /**
   * Custom class setting name.
   *
   * @deprecated in droopler:8.x-2.2 and is removed from droopler:8.x-2.3.
   * Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   *
   * @see https://www.drupal.org/project/droopler/issues/3180465
   */
  const CSS_CLASS_SETTING_NAME = 'custom_class';

  /**
   * Heading setting name.
   *
   * @deprecated in droopler:8.x-2.2 and is removed from droopler:8.x-2.3.
   * Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   *
   * @see https://www.drupal.org/project/droopler/issues/3180465
   */
  const HEADING_TYPE_SETTING_NAME = 'heading_type';

  /**
   * The paragraph setting plugin manager.
   *
   * @var \Drupal\d_p\ParagraphSettingPluginManagerInterface
   */
  protected $paragraphSettingsManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->paragraphSettingsManager = $container->get('d_p.paragraph_settings.plugin.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return ['filter_mode' => 1, 'allowed_settings' => []] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['filter_mode'] = [
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('Exclude selected'),
        1 => $this->t('Include selected'),
      ],
      '#default_value' => $this->getSetting('filter_mode'),
    ];

    $form['allowed_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Allowed settings'),
    ];

    $options = $this->paragraphSettingsManager->getSettingsFormOptions();

    $allowed_settings = $this->getAllowedSettings();

    foreach ($options as $id => $option) {
      $form['allowed_settings'][$id]['status'] = [
        '#title' => $option['label'],
        '#type' => 'checkbox',
        '#default_value' => $allowed_settings[$id]['status'] ?? FALSE,
        '#states' => [
          'checked' => [
            '[data-setting-id="' . $id . '"]' => ['value' => 1],
          ],
        ],
      ];

      $subtype = ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID;
      if (isset($option[$subtype])) {
        foreach ($option[$subtype] as $mid => $modifier) {
          $form['allowed_settings'][$id][$subtype][$mid]['status'] = [
            '#title' => '<em>' . $option['label'] . '</em> » ' . $modifier['label'],
            '#type' => 'checkbox',
            '#default_value' => $allowed_settings[$id][$subtype][$mid]['status'] ?? FALSE,
            '#attributes' => [
              'data-setting-id' => $id,
            ],
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $include_selected = (bool) $this->getSetting('filter_mode');
    $summary[] = $include_selected ? $this->t('Filter mode: Include selected') : $this->t('Filter mode: Exclude selected');

    return $summary;
  }

  /**
   * Get configuration options form for fields in paragraph settings.
   *
   * @return array
   *   The available configuration for this parapraph bundle.
   */
  private function getConfigOptions(): array {
    $form = $this->paragraphSettingsManager->getSettingsForm();
    $this->processSettingAccess($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = $items->getValue();
    // Set up the form element for this widget.
    $element += [
      '#type' => 'fieldset',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    $config_options = $this->getConfigOptions();
    foreach ($config_options as $key => $options) {
      $value = $config->$key ?? '';
      $type = $options['#subtype'] ?? $options['#type'];

      switch ($type) {
        case 'css':
          $classes = $this->getCssClassList($value);
          $subtype = ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID;
          $this->processModifiers($element, $options[$subtype], $classes);

          // Preserve only root keys, without children/modifiers.
          unset($options[$subtype]);

          $element[$key] = [
            '#default_value' => implode(' ', $classes),
          ] + $options;
          break;

        case 'select':
          $element[$key] = [
            '#default_value' => empty($value) ? $options['#default_value'] : $value,
          ] + $options;
          break;

        case 'number':
          $element[$key] = [
            '#default_value' => !empty($value) && $value !== '' ? $value : $options['#default_value'],
            '#min' => $element[$key]['#min'] ?? NULL,
            '#max' => $element[$key]['#max'] ?? NULL,
          ] + $options;
          break;

        case 'checkboxes':
          // Convert stdClass to array after deserialization.
          $value = is_object($value) ? (array) $value : $value;
          $element[$key] = [
            '#default_value' => empty($value) ? $options['#default_value'] : $value,
          ] + $options;
          break;

        default:
          $value = $config->$key ?? $options['#default_value'];

          $element[$key] = [
            '#size' => $options['#size'] ?? 32,
            '#default_value' => $value,
          ] + $options;
      }

      if ($element['#required']) {
        $element[$key]['#required'] = TRUE;
      }
    }
    // Paragraph theme field.
    if (isset($element['paragraph-theme'])) {
      $this->processCustomThemeElements($element, $config);
    }

    return ['value' => $element];
  }

  /**
   * Validate the fields and convert them into a single value as json.
   *
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $values = [];

    $config_options = $this->getConfigOptions();

    foreach ($config_options as $key => $options) {
      $value = $form_state->getValue(array_merge($element['#parents'], [$key]));

      $type = $options['#subtype'] ?? $options['#type'];
      if ($type === 'css') {
        $classes = $this->getCssClassList($value);

        foreach ($options[ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID] as $class => $modifier) {
          $modifier_value = $element[$class]['#value'] ?? NULL;

          if ($modifier_value) {
            switch ($modifier['#type']) {
              case 'select':
                $classes[] = $modifier_value;
                break;

              default:
                $classes[] = $class;
                break;
            }
          }
        }
        $classes = array_unique($classes);
        $values[$key] = implode(' ', $classes);
      }
      else {
        $values[$key] = $value;
      }
    }
    if (($element['paragraph-theme']['#value'] ?? NULL) === 'theme-custom') {
      $values[ParagraphSettingTypesInterface::THEME_COLORS_SETTING_NAME] = [
        'background' => $element['background-theme-custom']['#value'],
        'text' => $element['text-theme-custom']['#value'],
      ];
    }

    $form_state->setValueForElement($element, json_encode($values));
  }

  /**
   * Process render array in order to exclude not allowed settings.
   *
   * @param array $element
   *   Form element.
   * @param string|null $parent_id
   *   Id of a parent.
   */
  protected function processSettingAccess(array &$element, ?string $parent_id = NULL): void {
    $include_selected = (bool) $this->getSetting('filter_mode');

    foreach ($element as $id => &$item) {
      $is_setting_allowed = $this->isSettingAllowed($id, $parent_id);
      $include_allowed = $include_selected && !$is_setting_allowed;
      $exclude_allowed = !$include_selected && $is_setting_allowed;

      if ($include_allowed || $exclude_allowed) {
        unset($element[$id]);
        continue;
      }

      $subtype = ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID;
      if (isset($item[$subtype])) {
        $this->processSettingAccess($item[$subtype], $id);
      }
    }
  }

  /**
   * Process render array in order to populate modifiers elements.
   *
   * @param array $element
   *   Form element.
   * @param array $modifiers
   *   Modifiers list.
   * @param array $classes
   *   List of CSS classes.
   */
  protected function processModifiers(array &$element, array $modifiers, array &$classes): void {
    foreach ($modifiers as $class => $modifier) {
      $class_key = array_search($class, $classes);
      $default_value = (int) ($class_key !== FALSE);

      if ($default_value) {
        unset($classes[$class_key]);
      }

      $element[$class] = ['#default_value' => $default_value] + $modifier;

      $element[$class]['#attributes'] = [
        'data-modifier' => $class,
      ];

      /** @var \Drupal\d_p\ParagraphSettingInterface $setting_plugin */
      $setting_plugin = $modifier['#plugin'];

      if ($setting_plugin instanceof ParagraphSettingSelectInterface) {
        $default_select_value = $setting_plugin->getDefaultValue();

        foreach ($setting_plugin->getOptions() as $theme_class => $data) {
          $theme_class_key = array_search($theme_class, $classes);
          if ($theme_class_key !== FALSE) {
            $default_select_value = $theme_class;
            unset($classes[$theme_class_key]);
          }
        }

        $element[$class]['#default_value'] = $default_select_value;
      }
    }
  }

  /**
   * Process render array in otder to populate custom theme elements.
   *
   * @param array $element
   *   Form element.
   * @param object $config
   *   Existing configuration.
   */
  protected function processCustomThemeElements(array &$element, object $config): void {
    $selector_string = $this->getSelectorStringFromElement($element);
    $config_name = ParagraphSettingTypesInterface::THEME_COLORS_SETTING_NAME;
    $element['background-theme-custom'] = [
      '#type' => 'd_color',
      '#title' => 'Background color',
      '#default_value' => isset($config->$config_name) ? $config->$config_name->background : '#ffffff',
      '#weight' => 101,
      '#states' => [
        'visible' => [
          ':input[name="' . $selector_string . '[0][value][paragraph-theme]"]' => [
            'value' => 'theme-custom',
          ],
        ],
      ],
    ];

    $element['text-theme-custom'] = [
      '#type' => 'd_color',
      '#title' => 'Text color',
      '#default_value' => isset($config->$config_name) ? $config->$config_name->text : '#000000',
      '#weight' => 102,
      '#states' => [
        'visible' => [
          ':input[name="' . $selector_string . '[0][value][paragraph-theme]"]' => [
            'value' => 'theme-custom',
          ],
        ],
      ],
    ];
  }

  /**
   * Getter for target bundle of the entity.
   *
   * @return string|null
   *   Bundle name, or null if it's not bundle specific.
   */
  protected function getTargetBundle(): ?string {
    return $this->fieldDefinition->getTargetBundle();
  }

  /**
   * Get CSS class from a given string.
   *
   * @param null|string $value
   *   String to be parsed.
   *
   * @return array
   *   CSS classes.
   */
  protected function getCssClassListFromString(?string $value): array {
    $classes = preg_split("/[\s,]+/", $value, -1, PREG_SPLIT_NO_EMPTY);

    return $classes ?: [];
  }

  /**
   * Getter for css class list.
   *
   * @param mixed $value
   *   Stored value.
   *
   * @return array
   *   Array of classes.
   */
  protected function getCssClassList($value): array {
    if (is_array($value)) {
      return $value;
    }
    elseif (is_string($value) || is_null($value)) {
      return $this->getCssClassListFromString($value);
    }

    return [];
  }

  /**
   * Get element unique selector.
   *
   * @param array $element
   *   Form element.
   *
   * @return string
   *   Selector string.
   */
  protected function getSelectorStringFromElement(array $element): string {
    $tree = $element['#field_parents'];
    $tree[] = $this->fieldDefinition->getName();

    $selector_string = array_shift($tree);
    if (!empty($tree)) {
      foreach ($tree as $item) {
        $selector_string .= "[$item]";
      }
    }

    return $selector_string;
  }

  /**
   * Getter for allowed settings.
   *
   * @return array
   *   Allowed settings.
   */
  protected function getAllowedSettings(): array {
    return $this->getSetting('allowed_settings');
  }

  /**
   * Check whether given setting was allowed in the setting configuration.
   *
   * @param string $id
   *   The setting id.
   * @param string|null $parent_id
   *   The parent setting id, null if there is not parent.
   *
   * @return bool
   *   True if this settings is allowed, false otherwise.
   */
  protected function isSettingAllowed(string $id, ?string $parent_id): bool {
    $settings = $this->getAllowedSettings();

    $is_setting_allowed = is_string($parent_id) ?
      ($settings[$parent_id][ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID][$id]['status'] ?: 0) : ($settings[$id]['status'] ?? 0);

    return (bool) $is_setting_allowed;
  }

  /**
   * Returns default options for select fields.
   *
   * @deprecated in droopler:8.x-2.2 and is removed from droopler:8.x-2.3.
   * As this is working on the particular field instance,
   * we have unified and moved all the methods directly to the field list class:
   * Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface
   *
   * @see https://www.drupal.org/project/droopler/issues/3180465
   */
  public static function getModifierDefaults() {
    /** @var \Drupal\d_p\ParagraphSettingPluginManagerInterface $pluginManager */
    $pluginManager = \Drupal::service('d_p.paragraph_settings.plugin.manager');
    $custom_class_plugin_id = ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME;

    /** @var \Drupal\d_p\ParagraphSettingInterface $custom_class_plugin */
    $custom_class_plugin = $pluginManager->getPluginById($custom_class_plugin_id);
    /** @var \Drupal\d_p\ParagraphSettingInterface[] $plugins */
    $plugins = $custom_class_plugin->getChildrenPlugins();

    $defaults = [];

    foreach ($plugins as $plugin) {
      if ($plugin instanceof ParagraphSettingSelectInterface) {
        $defaults[] = [
          'options' => $plugin->getOptions(),
          'default' => $plugin->getDefaultValue(),
        ];
      }
    }

    return $defaults;
  }

  /**
   * Get the default value for config option.
   *
   * @param string $option_name
   *   Config option name.
   *
   * @return mixed|null
   *   Option default value, null if not found.
   *
   * @deprecated in droopler:8.x-2.2 and is removed from droopler:8.x-2.3.
   * As this is working on the particular field instance,
   * we have unified and moved all the methods directly to the field list class:
   * Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface
   *
   * @see https://www.drupal.org/project/droopler/issues/3180465
   */
  public static function getConfigOptionDefaultValue(string $option_name) {
    try {
      /** @var \Drupal\d_p\ParagraphSettingPluginManagerInterface $pluginManager */
      $pluginManager = \Drupal::service('d_p.paragraph_settings.plugin.manager');
      /** @var \Drupal\d_p\ParagraphSettingInterface $plugin */
      $plugin = $pluginManager->getPluginById($option_name);

      return $plugin->getDefaultValue();
    }
    catch (PluginException $exception) {
      return NULL;
    }
  }

}
