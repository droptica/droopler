<?php

declare(strict_types=1);

namespace Drupal\d_p\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;

/**
 * Hook implementations for the d_p module.
 */
class Hooks {

  /**
   * Paragraph bundles that gain a background-image wrapper class.
   *
   * @var string[]
   */
  protected const array BACKGROUND_IMAGE_BUNDLES = [
    'd_p_single_text_block',
  ];

  /**
   * Responsive image style IDs known to the d_p module.
   *
   * Kept as a typed class constant so consumers replacing the legacy
   * `d_p_responsive_style_ids()` helper can rely on a stable list.
   *
   * @var string[]
   */
  public const array RESPONSIVE_STYLE_IDS = [
    'responsive_image_768',
    'responsive_image_992',
    'responsive_image_1200',
    'responsive_image_1600',
    'responsive_image_2000',
  ];

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'field__field_d_main_title' => [
        'base hook' => 'field',
      ],
      'field__field_d_subtitle' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_paragraph().
   */
  #[Hook('preprocess_paragraph')]
  public function preprocessParagraph(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    $bundle = $paragraph->bundle();
    $paragraph_id = 'paragraph-' . $bundle . '-' . $paragraph->id();
    $wrapper_attributes = [
      'id' => $paragraph_id,
      'class' => [
        'wrapper-' . $bundle,
      ],
    ];

    foreach (array_keys($paragraph->getFieldDefinitions()) as $field_name) {
      $this->applyFieldDrivenWrapperAttributes($paragraph, $bundle, (string) $field_name, $wrapper_attributes);
    }

    $setting_field = ParagraphSettingsAccessor::field($paragraph);
    if ($setting_field !== NULL) {
      unset($variables['content'][$setting_field->getName()]);
      // Apply classes even when the field is empty: getClasses() emits the
      // configured per-modifier defaults (padding/margin/theme), which legacy
      // paragraphs with no stored settings still need for correct spacing.
      $wrapper_attributes['class'] = array_merge(
        $wrapper_attributes['class'],
        $setting_field->getClasses(),
      );
    }

    $variables['wrapper_attributes'] = new Attribute($wrapper_attributes);
    $variables['paragraph_attributes'] = new Attribute(['data-id' => $paragraph_id]);
    $variables['#attached']['library'][] = 'd_p/d_p';
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $form['#attached']['library'][] = 'd_p/d_p_ckeditor';

    // Move heading-type selector under the main title, drilling into each
    // paragraph subform when the form has a "field_page_section" widget.
    if (isset($form['field_page_section']['widget']['#max_delta'])) {
      for ($i = 0; $i <= $form['field_page_section']['widget']['#max_delta']; $i++) {
        if (!isset($form['field_page_section']['widget'][$i]['subform'])) {
          continue;
        }
        $this->moveHeadingType($form['field_page_section']['widget'][$i]['subform']);
      }
      return;
    }

    $this->moveHeadingType($form);
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array &$context): void {
    $field_definition = $context['items']->getFieldDefinition();
    if (!$field_definition instanceof FieldConfig) {
      return;
    }

    [, $bundle, $field_name] = explode('.', $field_definition->id());

    $this->centerCkeditorWidgetContent($element, $context, $bundle);

    if ($field_name !== 'field_d_cta_link') {
      return;
    }
    if (!empty($element['url']['#default_value'])
      || !empty($element['options']['attributes']['class']['#default_value'])
      || isset($element['attributes']['#value']['class'])
    ) {
      return;
    }

    $default_value = match ($bundle) {
      'd_p_group_of_counters' => 'btn btn-secondary',
      default => 'btn btn-primary',
    };
    $element['options']['attributes']['class']['#default_value'] = $default_value;
  }

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    if (($variables['element']['#field_name'] ?? '') !== 'field_d_main_title') {
      return;
    }

    $entity = $variables['element']['#object'] ?? NULL;
    if ($entity === NULL || $entity->getEntityTypeId() !== 'paragraph') {
      return;
    }

    $heading_tag = ParagraphSettingsAccessor::value(
      $entity,
      ParagraphSettingTypesInterface::HEADING_TYPE_SETTING_NAME,
    );
    if ($heading_tag !== NULL) {
      $variables['heading_tag'] = $heading_tag;
    }
  }

  /**
   * Implements hook_preprocess_textarea().
   *
   * Adds a class so themed CKEditor textareas get centered styling.
   *
   * @see self::centerCkeditorWidgetContent()
   */
  #[Hook('preprocess_textarea')]
  public function preprocessTextarea(array &$variables): void {
    if ($variables['element']['#d_p_ckeditor_centered'] ?? FALSE) {
      $variables['wrapper_attributes']->addClass('d_p_ckeditor_centered');
    }
  }

  /**
   * Validate callback used by the relocated `heading_type` field.
   *
   * Form API invokes element-validate callbacks without instantiating the
   * service, so this MUST be static. Logic is fully self-contained.
   */
  public static function validateHeadingType(array $element, FormStateInterface $form_state): void {
    $parents = [];
    foreach ($element['#parents'] as $index) {
      if (in_array($index, ['widget', 'heading_type'], TRUE)) {
        continue;
      }
      if (preg_match('/^group_/', (string) $index)) {
        continue;
      }
      $parents[] = $index;
    }
    $parents[] = 'field_d_settings';

    $current_settings = $form_state->getValue($parents);

    if (is_string($current_settings[0]['value'])) {
      $decoded = json_decode($current_settings[0]['value'], TRUE);
      $decoded['heading_type'] = $element['#value'];
      $current_settings[0]['value'] = json_encode($decoded);
    }
    else {
      $current_settings[0]['value']['heading_type'] = $element['#value'];
    }

    $form_state->setValue($parents, $current_settings);
  }

  /**
   * Field-driven wrapper attribute mutations for `preprocess_paragraph`.
   */
  protected function applyFieldDrivenWrapperAttributes(
    object $paragraph,
    string $bundle,
    string $field_name,
    array &$wrapper_attributes,
  ): void {
    if ($field_name === 'field_d_settings') {
      /** @var \Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface $field */
      $field = $paragraph->get($field_name);
      if ($field->isEmpty()) {
        return;
      }
      if (!$field->hasClasses()
        || !$field->hasSettingValue(ParagraphSettingTypesInterface::THEME_COLORS_SETTING_NAME)
        || !$field->hasClass('theme-custom')
      ) {
        return;
      }
      $custom_theme = $field->getSettingValue(ParagraphSettingTypesInterface::THEME_COLORS_SETTING_NAME);
      $wrapper_attributes['style'] = [
        'background-color: ' . $custom_theme->background . ';',
        'color: ' . $custom_theme->text . ';',
      ];
      return;
    }

    if ($field_name === 'field_d_long_text') {
      if (!$paragraph->get($field_name)->isEmpty()) {
        $wrapper_attributes['class'][] = 'with-long-text';
      }
      return;
    }

    if ($field_name === 'field_d_media_background'
      && in_array($bundle, self::BACKGROUND_IMAGE_BUNDLES, TRUE)
      && !$paragraph->get($field_name)->isEmpty()
    ) {
      $wrapper_attributes['class'][] = 'user-image-background';
    }
  }

  /**
   * Move the "heading_type" field from `field_d_settings` under the main title.
   */
  protected function moveHeadingType(array &$form): void {
    if (!isset($form['field_d_settings']['widget'][0]['value']['heading_type'])
      || !isset($form['field_d_main_title'])
    ) {
      return;
    }

    $widget = &$form['field_d_settings']['widget'][0]['value'];
    $widget['heading_type']['#weight'] = $form['field_d_main_title']['#weight'] + 0.5;
    $widget['heading_type']['#element_validate'] = [[self::class, 'validateHeadingType']];

    // If field_d_main_title is in a fieldgroup, place heading_type next to it
    // inside the same group; otherwise hoist it to the top form level.
    if (isset($form['#group_children']['field_d_main_title'])) {
      $group = $form['#group_children']['field_d_main_title'];
      $form['#group_children']['heading_type'] = $group;
      $form[$group]['heading_type'] = $widget['heading_type'];
    }
    else {
      $form['heading_type'] = $widget['heading_type'];
    }

    unset($widget['heading_type']);
  }

  /**
   * Mark CKEditor widgets that should render centered content.
   *
   * @see self::preprocessTextarea()
   *
   * @todo Cross-cutting Sprint 6 task — replace the `invokeAll()` of
   *   `hook_d_p_centered_ckeditor_widget_paragraphs` with an Event Subscriber.
   */
  protected function centerCkeditorWidgetContent(array &$element, array $context, string $paragraph_type): void {
    if (!($context['widget'] instanceof TextareaWidget)) {
      return;
    }

    /** @var string[] $paragraph_types */
    $paragraph_types = [];
    $this->moduleHandler->invokeAll('d_p_centered_ckeditor_widget_paragraphs', [&$paragraph_types]);
    if (in_array($paragraph_type, $paragraph_types, TRUE)) {
      $element['#d_p_ckeditor_centered'] = TRUE;
    }
  }

}
