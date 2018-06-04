<?php

namespace Drupal\paragraphs\Tests\Classic;

use Drupal\field_ui\Tests\FieldUiTestTrait;

/**
 * Tests paragraphs edit modes.
 *
 * @group paragraphs
 */
class ParagraphsEditModesTest extends ParagraphsTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'image',
    'block_field',
  ];

  /**
   * Tests the collapsed summary of paragraphs.
   */
  public function testCollapsedSummary() {
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs', 'entity_reference_paragraphs');
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);

    // Add a Paragraph type.
    $paragraph_type = 'image_text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $title_paragraphs_type = 'title';
    $this->addParagraphsType($title_paragraphs_type);
    $this->addParagraphsType('text');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'image', 'Image', 'image', [], ['settings[alt_field_required]' => FALSE]);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $title_paragraphs_type, 'title', 'Title', 'string', [], []);

    // Set edit mode to closed.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->drupalPostAjaxForm(NULL, [], "field_paragraphs_settings_edit");
    $edit = ['fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed'];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Add a paragraph.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_image_text_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_title_add_more');

    $files = $this->drupalGetTestFiles('image');
    $file_system = \Drupal::service('file_system');

    // Create a node with an image and text.
    $edit = [
      'title[0][value]' => 'Test article',
      'field_paragraphs[0][subform][field_text][0][value]' => 'text_summary',
      'files[field_paragraphs_0_subform_field_image_0]' => $file_system->realpath($files[0]->uri),
      'field_paragraphs[1][subform][field_title][0][value]' => 'Title example',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert the summary is correctly generated.
    $this->clickLink(t('Edit'));
    $this->assertRaw('<div class="paragraphs-collapsed-description">' . $files[0]->filename . ', text_summary');
    $this->assertRaw('<div class="paragraphs-collapsed-description">Title example');

    // Edit and remove alternative text.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $edit = [
      'field_paragraphs[0][subform][field_image][0][alt]' => 'alternative_text_summary',
      'field_paragraphs[0][subform][field_image][0][width]' => 300,
      'field_paragraphs[0][subform][field_image][0][height]' => 300,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_collapse');
    // Assert the summary is correctly generated.
    $this->assertRaw('<div class="paragraphs-collapsed-description">alternative_text_summary, text_summary');

    // Remove image.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_subform_field_image_0_remove_button');
    $this->drupalPostForm(NULL, [], t('Save'));

    // Assert the summary is correctly generated.
    $this->clickLink(t('Edit'));
    $this->assertRaw('<div class="paragraphs-collapsed-description">text_summary');

    // Add a Block Paragraphs type.
    $this->addParagraphsType('block_paragraph');
    $this->addFieldtoParagraphType('block_paragraph', 'field_block', 'block_field');

    // Test the summary of a Block field.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_block_paragraph_add_more');
    $edit = [
      'title[0][value]' => 'Node with a Block Paragraph',
      'field_paragraphs[0][subform][field_block][0][plugin_id]' => 'system_breadcrumb_block',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->clickLink(t('Edit'));
    $this->assertRaw('<div class="paragraphs-collapsed-description">Breadcrumbs');
  }

}
