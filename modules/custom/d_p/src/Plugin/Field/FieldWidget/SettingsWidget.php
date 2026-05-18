<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
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
   * Default text input size when an option doesn't specify one.
   */
  protected const int DEFAULT_INPUT_SIZE = 32;

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected readonly ParagraphSettingPluginManagerInterface $paragraphSettingsManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    // @phpstan-ignore-next-line Drupal uses late static binding for plugin factory.
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('d_p.paragraph_settings.plugin.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'filter_mode' => 1,
      'allowed_settings' => [],
    ] + parent::defaultSettings();
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
    $subtype = ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID;

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

      if (!isset($option[$subtype])) {
        continue;
      }

      foreach ($option[$subtype] as $modifier_id => $modifier) {
        $form['allowed_settings'][$id][$subtype][$modifier_id]['status'] = [
          '#title' => '<em>' . $option['label'] . '</em> » ' . $modifier['label'],
          '#type' => 'checkbox',
          '#default_value' => $allowed_settings[$id][$subtype][$modifier_id]['status'] ?? FALSE,
          '#attributes' => [
            'data-setting-id' => $id,
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      ((bool) $this->getSetting('filter_mode'))
        ? $this->t('Filter mode: Include selected')
        : $this->t('Filter mode: Exclude selected'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $config = $items->getValue();
    $element += [
      '#type' => 'fieldset',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    foreach ($this->getConfigOptions() as $key => $options) {
      $value = $config->$key ?? '';
      $type = $options['#subtype'] ?? $options['#type'];

      match ($type) {
        'css' => $this->buildCssElement($element, $key, $options, $value),
        'select' => $this->buildSelectElement($element, $key, $options, $value),
        'number' => $this->buildNumberElement($element, $key, $options, $value),
        'checkboxes' => $this->buildCheckboxesElement($element, $key, $options, $value),
        default => $this->buildDefaultElement($element, $key, $options, $config),
      };

      if ($element['#required']) {
        $element[$key]['#required'] = TRUE;
      }
    }

    if (isset($element['paragraph-theme'])) {
      $this->processCustomThemeElements($element, $config);
    }

    return ['value' => $element];
  }

  /**
   * Element-level validator: merges sub-element values into a single JSON blob.
   */
  public function validate(array $element, FormStateInterface $form_state): void {
    $values = [];

    foreach ($this->getConfigOptions() as $key => $options) {
      $value = $form_state->getValue(array_merge($element['#parents'], [$key]));
      $type = $options['#subtype'] ?? $options['#type'];

      if ($type === 'css') {
        $values[$key] = $this->collectCssValue($element, $options, $value);
        continue;
      }
      $values[$key] = $value;
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
   * Build the configuration options form for fields in paragraph settings.
   */
  protected function getConfigOptions(): array {
    $form = $this->paragraphSettingsManager->getSettingsForm();
    $this->processSettingAccess($form);
    return $form;
  }

  /**
   * Render a 'css' element with its modifiers.
   */
  protected function buildCssElement(array &$element, string $key, array $options, mixed $value): void {
    $classes = $this->getCssClassList($value);
    $subtype = ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID;
    $this->processModifiers($element, $options[$subtype] ?? [], $classes);
    unset($options[$subtype]);
    $element[$key] = [
      '#default_value' => implode(' ', $classes),
    ] + $options;
  }

  /**
   * Render a 'select' element.
   */
  protected function buildSelectElement(array &$element, string $key, array $options, mixed $value): void {
    $element[$key] = [
      '#default_value' => empty($value) ? $options['#default_value'] : $value,
    ] + $options;
  }

  /**
   * Render a 'number' element with min/max preserved.
   */
  protected function buildNumberElement(array &$element, string $key, array $options, mixed $value): void {
    $element[$key] = [
      '#default_value' => !empty($value) ? $value : $options['#default_value'],
      '#min' => $element[$key]['#min'] ?? NULL,
      '#max' => $element[$key]['#max'] ?? NULL,
    ] + $options;
  }

  /**
   * Render a 'checkboxes' element, normalising stdClass-deserialised values.
   */
  protected function buildCheckboxesElement(array &$element, string $key, array $options, mixed $value): void {
    if (is_object($value)) {
      $value = (array) $value;
    }
    $element[$key] = [
      '#default_value' => empty($value) ? $options['#default_value'] : $value,
    ] + $options;
  }

  /**
   * Render a fallback text-like element.
   */
  protected function buildDefaultElement(array &$element, string $key, array $options, object $config): void {
    $element[$key] = [
      '#size' => $options['#size'] ?? self::DEFAULT_INPUT_SIZE,
      '#default_value' => $config->$key ?? $options['#default_value'],
    ] + $options;
  }

  /**
   * Collect a CSS element's value during validation.
   *
   * @return string
   *   Space-separated class names.
   */
  protected function collectCssValue(array $element, array $options, mixed $value): string {
    $classes = $this->getCssClassList($value);
    $modifiers = $options[ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID] ?? [];

    foreach ($modifiers as $class => $modifier) {
      $modifier_value = $element[$class]['#value'] ?? NULL;
      if (!$modifier_value) {
        continue;
      }
      $classes[] = match ($modifier['#type']) {
        'select' => $modifier_value,
        default => $class,
      };
    }

    return implode(' ', array_unique($classes));
  }

  /**
   * Trim the render array down to settings that are actually allowed.
   */
  protected function processSettingAccess(array &$element, ?string $parent_id = NULL): void {
    $include_selected = (bool) $this->getSetting('filter_mode');

    foreach ($element as $id => &$item) {
      $is_setting_allowed = $this->isSettingAllowed((string) $id, $parent_id);
      $include_allowed = $include_selected && !$is_setting_allowed;
      $exclude_allowed = !$include_selected && $is_setting_allowed;

      if ($include_allowed || $exclude_allowed) {
        unset($element[$id]);
        continue;
      }

      $subtype = ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID;
      if (isset($item[$subtype])) {
        $this->processSettingAccess($item[$subtype], (string) $id);
      }
    }
  }

  /**
   * Populate modifier elements with defaults from the plugin definition.
   *
   * @param array $element
   *   Form element (mutated in place).
   * @param array $modifiers
   *   Modifiers list.
   * @param string[] $classes
   *   Current list of CSS classes (mutated in place).
   */
  protected function processModifiers(array &$element, array $modifiers, array &$classes): void {
    foreach ($modifiers as $class => $modifier) {
      $class_key = array_search($class, $classes, TRUE);
      $default_value = (int) ($class_key !== FALSE);

      if ($default_value) {
        unset($classes[$class_key]);
      }

      $element[$class] = ['#default_value' => $default_value] + $modifier;
      $element[$class]['#attributes'] = ['data-modifier' => $class];

      /** @var \Drupal\d_p\ParagraphSettingInterface $setting_plugin */
      $setting_plugin = $modifier['#plugin'];

      if (!$setting_plugin instanceof ParagraphSettingSelectInterface) {
        continue;
      }

      $default_select_value = $setting_plugin->getDefaultValue();
      foreach (array_keys($setting_plugin->getOptions()) as $theme_class) {
        $theme_class_key = array_search($theme_class, $classes, TRUE);
        if ($theme_class_key !== FALSE) {
          $default_select_value = $theme_class;
          unset($classes[$theme_class_key]);
        }
      }
      $element[$class]['#default_value'] = $default_select_value;
    }
  }

  /**
   * Populate custom-theme color pickers and their visibility states.
   */
  protected function processCustomThemeElements(array &$element, object $config): void {
    $selector_string = $this->getSelectorStringFromElement($element);
    $config_name = ParagraphSettingTypesInterface::THEME_COLORS_SETTING_NAME;
    $current = $config->$config_name ?? NULL;

    $element['background-theme-custom'] = [
      '#type' => 'd_color',
      '#title' => 'Background color',
      '#default_value' => $current->background ?? '#ffffff',
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
      '#default_value' => $current->text ?? '#000000',
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
   * Target bundle of the field's entity (or NULL if bundle-agnostic).
   */
  protected function getTargetBundle(): ?string {
    return $this->fieldDefinition->getTargetBundle();
  }

  /**
   * Parse a CSS class list out of a free-form string.
   *
   * @return string[]
   *   List of CSS class names.
   */
  protected function getCssClassListFromString(?string $value): array {
    if ($value === NULL) {
      return [];
    }
    $classes = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
    return $classes === FALSE ? [] : $classes;
  }

  /**
   * Normalise a CSS class list from any stored value.
   *
   * @return string[]
   *   List of CSS class names.
   */
  protected function getCssClassList(mixed $value): array {
    if (is_array($value)) {
      return $value;
    }
    if (is_string($value) || $value === NULL) {
      return $this->getCssClassListFromString($value);
    }
    return [];
  }

  /**
   * Build a unique selector string for the field element.
   */
  protected function getSelectorStringFromElement(array $element): string {
    $tree = $element['#field_parents'];
    $tree[] = $this->fieldDefinition->getName();

    $selector_string = array_shift($tree);
    foreach ($tree as $item) {
      $selector_string .= "[$item]";
    }
    return $selector_string;
  }

  /**
   * Allowed settings configured on this widget instance.
   */
  protected function getAllowedSettings(): array {
    return $this->getSetting('allowed_settings');
  }

  /**
   * Check whether a setting (and optionally its parent) is allowed.
   */
  protected function isSettingAllowed(string $id, ?string $parent_id): bool {
    $settings = $this->getAllowedSettings();
    $subtype = ParagraphSettingPluginManagerInterface::SETTINGS_SUBTYPE_ID;

    $value = is_string($parent_id)
      ? ($settings[$parent_id][$subtype][$id]['status'] ?? 0)
      : ($settings[$id]['status'] ?? 0);

    return (bool) $value;
  }

}
