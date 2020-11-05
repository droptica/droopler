<?php

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Drupal\d_p\Service\ParagraphSettingsConfigurationInterface;

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
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const CSS_CLASS_SETTING_NAME = 'custom_class';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const HEADING_TYPE_SETTING_NAME = 'heading_type';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const COLUMN_COUNT_SETTING_NAME = 'column_count';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const COLUMN_COUNT_MOBILE_SETTING_NAME = 'column_count_mobile';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const COLUMN_COUNT_TABLET_SETTING_NAME = 'column_count_tablet';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_FEATURED_IMAGES = 'featured_images';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_FORM_LAYOUT = 'form_layout';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_EMBED_LAYOUT = 'embed_layout';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_SIDE_IMAGE_LAYOUT = 'side_image_layout';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_SIDE_TILES_LAYOUT = 'side_tiles_layout';

  /**
   * Get configuration options for fields in paragraph settings.
   */
  private static function getConfigOptions() {
    /** @var \Drupal\d_p\Service\ParagraphSettingsConfigurationManager $settingsManager */
    $settingsManager = \Drupal::service('d_p.paragraph_settings_configuration.manager');

    return $settingsManager->loadMultiple();
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
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = $items[$delta]->getValue();
    // Set up the form element for this widget.
    $element += [
      '#type' => 'fieldset',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    $config_options = self::getConfigOptions();
    foreach ($config_options as $key => $options) {
      // If the widget is not available in the current bundle, just skip it.
      $d_settings = $options['#d_settings'];
      // Add widgets of different types.
      $value = $config->$key ?? '';
      $type = $d_settings['subtype'] ?? $options['#type'];

      switch ($type) {
        case 'css':
          $classes = $this->getCssClassListFromString($value);

          $this->processModifiers($element, $options['modifiers'], $classes);

          $element[$key] = [
            '#default_value' => implode(' ', $classes),
          ];
          break;

        case 'select':
          $element[$key] = [
            '#default_value' => empty($value) ? ($options['#default_value'] ?? array_keys($options['#options'])[0]) : $value,
          ] + $options;
          break;

        case 'number':
          $element[$key] = [
            '#default_value' => !empty($value) && $value !== '' ? $value : $options['#default_value'],
            '#min' => $element[$key]['#min'] ?? NULL,
            '#max' => $element[$key]['#max'] ?? NULL,
          ] + $options;
          break;

        default:
          $element[$key] = [
            '#size' => $element[$key]['#size'] ?? 32,
            '#default_value' => $value,
          ] + $options;
      }

      if ($element['#required']) {
        $element[$key]['#required'] = TRUE;
      }
    }

    $this->processCustomThemeElements($element, $config);
    $this->processBundleAccess($element);

    return ['value' => $element];
  }

  /**
   * Validate the fields and convert them into a single value as json.
   *
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $values = [];
    $config_options = self::getConfigOptions();
    foreach ($config_options as $key => $options) {
      $value = $form_state->getValue(array_merge($element['#parents'], [$key]));
      $d_settings = $options['#d_settings'];

      $type = $d_settings['subtype'] ?? $options['#type'];
      if ($type === 'css') {
        $classes = $this->getCssClassListFromString($value);

        foreach ($options['modifiers'] as $class => $modifier) {
          if (!$this->isElementAccessAllowed($element[$class])) {
            continue;
          }

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
        if (!$this->isElementAccessAllowed($element)) {
          continue;
        }
        $values[$key] = $value;
      }
    }
    if ($element['paragraph-theme']['#value'] === 'theme-custom') {
      $values[ParagraphSettingTypesInterface::THEME_COLORS_SETTING_NAME] = [
        'background' => $element['background-theme-custom']['#value'],
        'text' => $element['text-theme-custom']['#value'],
      ];
    }

    $form_state->setValueForElement($element, json_encode($values));
  }

  /**
   * Returns default options for select fields.
   */
  public static function getModifierDefaults() {
    $modifiers = self::getConfigOptions()[ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME]['modifiers'];
    $defaults = [];
    foreach ($modifiers as $key => $modifier) {
      if (!empty($modifier['#type']) && $modifier['#type'] === 'select') {
        if (isset($modifier['#default_value']) && $modifier['#default_value']) {
          $default = $modifier['#default_value'];
        }
        else {
          $default = key($modifier['#options']);
        }
        $defaults[] = [
          'options' => array_keys($modifier['#options']),
          'default' => $default,
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
   */
  public static function getConfigOptionDefaultValue(string $option_name) {
    /** @var \Drupal\d_p\Service\ParagraphSettingsConfigurationManager $settingsManager */
    $settingsManager = \Drupal::service('d_p.paragraph_settings_configuration.manager');
    $option = $settingsManager->load($option_name);

    return $option['#default_value'] ?? NULL;
  }

  /**
   * Process render array in order to apply access value.
   *
   * @param array $element
   *   Form element.
   */
  protected function processBundleAccess(array &$element): void {
    foreach ($element as $key => &$item) {
      if (!is_array($item)) {
        continue;
      }

      if (empty($item['#d_settings']['allowed_bundles'])) {
        $this->processBundleAccess($item);
      }
      else {
        $allowed_bundles_list = $item['#d_settings']['allowed_bundles'];
        $allow_all_bundles = in_array(ParagraphSettingsConfigurationInterface::ALL_ALLOWED_BUNDLES, $allowed_bundles_list);
        $item['#access'] = $allow_all_bundles || in_array($this->getTargetBundle(), $allowed_bundles_list);
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

      if ($modifier['#type'] == 'select') {
        $default_select_value = $modifier['#default'] ?? key($modifier['#options']);

        foreach ($modifier['#options'] as $theme_class => $data) {
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
   * Check if access to the element is allowed.
   *
   * @param array $element
   *   Form element.
   *
   * @return bool
   *   True if the element is allowed, false otherwise.
   */
  protected function isElementAccessAllowed(array $element): bool {
    return isset($element['#access']) ? (bool) $element['#access'] : TRUE;
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

}
