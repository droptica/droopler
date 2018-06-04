<?php

namespace Drupal\paragraphs\Tests\Classic;

/**
 * Tests the field group on node.
 *
 * @group paragraphs
 * @requires module field_group
 */
class ParagraphsFieldGroupTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_group',
  ];

  /**
   * Tests the field group inside paragraph.
   */
  public function testFieldGroup() {
    $this->loginAsAdmin();

    $paragraph_type = 'paragraph_type_test';
    $content_type = 'paragraphed_test';

    // Add a Paragraphed test content type.
    $this->addParagraphedContentType($content_type, 'field_paragraphs', 'entity_reference_paragraphs');
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Create the field group element on paragraph type.
    $edit = [
      'group_formatter' => 'fieldset',
      'label' => 'paragraph_field_group_title',
      'group_name' => 'field'
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type . '/form-display/add-group', $edit, t('Save and continue'));
    $edit = [
      'format_settings[label]' => 'field_group'
    ];
    $this->drupalPostForm(NULL, $edit, t('Create group'));

    // Put the text field into the field group.
    $edit = [
      'fields[field_text][parent]' => 'group_field'
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type . '/form-display', $edit, t('Save'));

    // Create a node with a paragraph.
    $this->drupalGet('node/add/' . $content_type);
    $this->drupalPostAjaxForm('node/add/' . $content_type, [], 'field_paragraphs_paragraph_type_test_add_more');

    // Test if the new field group is displayed.
    $this->assertText('field_group');
    $this->assertFieldByXPath("//fieldset", NULL, t('Fieldset present'));

    // Save the node.
    $edit = [
      'title[0][value]' => 'paragraphed_title',
      'field_paragraphs[0][subform][field_text][0][value]' => 'paragraph_value',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }
}
