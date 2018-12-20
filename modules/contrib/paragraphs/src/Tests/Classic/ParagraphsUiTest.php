<?php

namespace Drupal\paragraphs\Tests\Classic;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsUiTest extends ParagraphsTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'content_translation',
    'image',
    'field',
    'field_ui',
    'block',
    'language',
    'node'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ConfigurableLanguage::create(['id' => 'de', 'label' => '1German'])->save();
    ConfigurableLanguage::create(['id' => 'fr', 'label' => '2French'])->save();
    $this->addParagraphedContentType('paragraphed_content_demo', 'field_paragraphs_demo', 'entity_reference_paragraphs');
    $this->loginAsAdmin([
      'administer site configuration',
      'administer content translation',
      'administer languages',
    ]);
    $this->addParagraphsType('nested_paragraph');
    $this->addParagraphsField('nested_paragraph', 'field_paragraphs_demo', 'paragraph', 'entity_reference_paragraphs');
    $this->addParagraphsType('images');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/images', 'images_demo', 'Images', 'image', ['cardinality' => -1], ['settings[alt_field]' => FALSE]);
    $this->addParagraphsType('text_image');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'image_demo', 'Images', 'image', ['cardinality' => -1], ['settings[alt_field]' => FALSE]);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'text_demo', 'Text', 'text_long', [], []);
    $this->addParagraphsType('text');
    static::fieldUIAddExistingField('admin/structure/paragraphs_type/text', 'field_text_demo', 'Text', []);
    $edit = [
      'entity_types[node]' => TRUE,
      'entity_types[paragraph]' => TRUE,
      'settings[node][paragraphed_content_demo][translatable]' => TRUE,
      'settings[node][paragraphed_content_demo][fields][field_paragraphs_demo]' => FALSE,
      'settings[paragraph][images][translatable]' => TRUE,
      'settings[paragraph][text_image][translatable]' => TRUE,
      'settings[paragraph][text][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][fields][field_paragraphs_demo]' => FALSE,
      'settings[paragraph][nested_paragraph][settings][language][language_alterable]' => TRUE,
      'settings[paragraph][images][fields][field_images_demo]' => TRUE,
      'settings[paragraph][text_image][fields][field_image_demo]' => TRUE,
      'settings[paragraph][text_image][fields][field_text_demo]' => TRUE,
      'settings[node][paragraphed_content_demo][settings][language][language_alterable]' => TRUE
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));
  }

  /**
   * Tests displaying an error message a required paragraph field that is empty.
   */
  public function testEmptyRequiredField() {
    $admin_user = $this->drupalCreateUser([
      'administer node fields',
      'administer paragraph form display',
      'administer node form display',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
    ]);
    $this->drupalLogin($admin_user);

    // Add required field to paragraphed content type.
    $bundle_path = 'admin/structure/types/manage/paragraphed_content_demo';
    $field_name = 'content';
    $field_title = 'Content Test';
    $field_type = 'field_ui:entity_reference_revisions:paragraph';
    $field_edit = [
      'required' => TRUE,
    ];
    $this->fieldUIAddNewField($bundle_path, $field_name, $field_title, $field_type, [], $field_edit);

    $form_display_edit = [
      'fields[field_content][type]' => 'entity_reference_paragraphs',
    ];
    $this->drupalPostForm($bundle_path . '/form-display', $form_display_edit, t('Save'));

    // Attempt to create a paragraphed node with an empty required field.
    $title = 'Empty';
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->drupalPostForm(NULL, ['title[0][value]' => $title], t('Save'));
    $this->assertText($field_title . ' field is required');

    // Attempt to create a paragraphed node with only a paragraph in the
    // "remove" mode in the required field.
    $title = 'Remove mode';
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->drupalPostAjaxForm(NULL, [], 'field_content_text_image_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_content_0_remove');
    $this->assertNoText($field_title . ' field is required');
    $this->drupalPostForm(NULL, ['title[0][value]' => $title], t('Save'));
    $this->assertText($field_title . ' field is required');
  }

}
