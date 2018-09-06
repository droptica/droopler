<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Test trait for Paragraphs JS tests.
 */
trait ParagraphsTestBaseTrait {

  use TestFileCreationTrait;

  /**
   * Adds a content type with a Paragraphs field.
   *
   * @param string $content_type_name
   *   Content type name to be used.
   * @param string $paragraphs_field_name
   *   (optional) Field name to be used. Defaults to 'field_paragraphs'.
   * @param string $widget_type
   *   (optional) Declares if we use experimental or classic widget.
   *   Defaults to 'paragraphs' for experimental widget.
   *   Use 'entity_reference_paragraphs' for classic widget.
   */
  protected function addParagraphedContentType($content_type_name, $paragraphs_field_name = 'field_paragraphs', $widget_type = 'paragraphs') {
    // Create the content type.
    $node_type = NodeType::create([
      'type' => $content_type_name,
      'name' => $content_type_name,
    ]);
    $node_type->save();

    $this->addParagraphsField($content_type_name, $paragraphs_field_name, 'node', $widget_type);
  }

  /**
   * Adds a Paragraphs field to a given entity type.
   *
   * @param string $entity_type_name
   *   Entity type name to be used.
   * @param string $paragraphs_field_name
   *   Paragraphs field name to be used.
   * @param string $entity_type
   *   Entity type where to add the field.
   * @param string $widget_type
   *   (optional) Declares if we use experimental or classic widget.
   *   Defaults to 'paragraphs' for experimental widget.
   *   Use 'entity_reference_paragraphs' for classic widget.
   */
  protected function addParagraphsField($entity_type_name, $paragraphs_field_name, $entity_type, $widget_type = 'paragraphs') {
    $field_storage = FieldStorageConfig::loadByName($entity_type, $paragraphs_field_name);
    if (!$field_storage) {
      // Add a paragraphs field.
      $field_storage = FieldStorageConfig::create([
        'field_name' => $paragraphs_field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference_revisions',
        'cardinality' => '-1',
        'settings' => [
          'target_type' => 'paragraph',
        ],
      ]);
      $field_storage->save();
    }
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $entity_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    $form_display = entity_get_form_display($entity_type, $entity_type_name, 'default')
      ->setComponent($paragraphs_field_name, ['type' => $widget_type]);
    $form_display->save();

    $view_display = entity_get_display($entity_type, $entity_type_name, 'default')
      ->setComponent($paragraphs_field_name, ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();
  }

  /**
   * Adds a Paragraphs type.
   *
   * @param string $paragraphs_type_name
   *   Paragraph type name used to create.
   */
  protected function addParagraphsType($paragraphs_type_name) {
    $paragraphs_type = ParagraphsType::create([
      'id' => $paragraphs_type_name,
      'label' => $paragraphs_type_name,
    ]);
    $paragraphs_type->save();
  }

  /**
   * Adds an icon to a paragraphs type.
   *
   * @param string $paragraphs_type
   *   Machine name of the paragraph type to add the icon to.
   *
   * @return \Drupal\file\Entity\File
   *   The file entity used as the icon.
   */
  protected function addParagraphsTypeIcon($paragraphs_type) {
    // Get an image.
    $image_files = $this->getTestFiles('image');
    $uri = current($image_files)->uri;

    // Create a copy of the image, so that multiple file entities don't
    // reference the same file.
    $copy_uri = file_unmanaged_copy($uri);

    // Create a new file entity.
    $file_entity = File::create([
      'uri' => $copy_uri,
    ]);
    $file_entity->save();

    // Add the file entity to the paragraphs type as its icon.
    $paragraphs_type_entity = ParagraphsType::load($paragraphs_type);
    $paragraphs_type_entity->set('icon_uuid', $file_entity->uuid());
    $paragraphs_type_entity->save();

    return $file_entity;
  }

  /**
   * Adds a field to a given paragraph type.
   *
   * @param string $paragraph_type_id
   *   Paragraph type ID to be used.
   * @param string $field_name
   *   Field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $storage_settings
   *   Settings for the field storage.
   */
  protected function addFieldtoParagraphType($paragraph_type_id, $field_name, $field_type, array $storage_settings = []) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'type' => $field_type,
      'cardinality' => 1,
      'settings' => $storage_settings,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $paragraph_type_id,
      'settings' => [],
    ]);
    $field->save();

    $field_type_definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_type);

    entity_get_form_display('paragraph', $paragraph_type_id, 'default')
      ->setComponent($field_name, ['type' => $field_type_definition['default_widget']])
      ->save();

    entity_get_display('paragraph', $paragraph_type_id, 'default')
      ->setComponent($field_name, ['type' => $field_type_definition['default_formatter']])
      ->save();
  }

  /**
   * Sets some of the settings of a paragraphs field widget.
   *
   * @param string $bundle
   *   Machine name of the bundle (e.g., a content type for nodes, a paragraphs
   *   type for paragraphs, etc.) with a paragraphs field.
   * @param string $paragraphs_field
   *   Machine name of the paragraphs field whose widget (settings) to change.
   * @param array $settings
   *   New setting values keyed by names of settings that are to be set.
   * @param string|null $field_widget
   *   (optional) Machine name of the paragraphs field widget to use. NULL to
   *   keep the current widget.
   * @param string $entity_type
   *   (optional) Machine name of the content entity type that the bundle
   *   belongs to. Defaults to "node".
   */
    protected function setParagraphsWidgetSettings($bundle, $paragraphs_field, array $settings, $field_widget = NULL, $entity_type = 'node') {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $default_form_display */
    $default_form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.default');
    $component = $default_form_display->getComponent($paragraphs_field);

    $updated_component = $component;
    if ($field_widget === NULL || $field_widget === $component['type']) {
      // The widget stays the same.
      $updated_component['settings'] = $settings + $component['settings'];
    }
    else {
      // Change the widget.
      $updated_component['type'] = $field_widget;

      $widget_definition = \Drupal::service('plugin.manager.field.widget')
        ->getDefinition($field_widget);
      /** @var \Drupal\Core\Field\WidgetInterface $class */
      $class = $widget_definition['class'];
      $default_settings = $class::defaultSettings();

      $updated_component['settings'] = $settings + $default_settings;
    }

    $default_form_display->setComponent($paragraphs_field, $updated_component)
      ->save();
  }

}
