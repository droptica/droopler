<?php

namespace Drupal\paragraphs\Tests\Experimental;

/**
 * Tests paragraphs duplicate feature.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalDuplicateFeatureTest extends ParagraphsExperimentalTestBase {

  public static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
  ];

  /**
   * Tests duplicate paragraph feature.
   */
  public function testDuplicateButton() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $text_01 = 'recognizable_text_01';
    $text_02 = 'recognizable_text_02';
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => 'A',
      'field_paragraphs[1][subform][field_text][0][value]' => 'B',
      'field_paragraphs[2][subform][field_text][0][value]' => 'C',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    $this->drupalGet('node/' . $node->id() . '/edit');

    // Click "Duplicate" button on A and move C to the first position.
    $edit = ['field_paragraphs[2][_weight]' => -1];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_duplicate');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', 'A');
    $this->assertFieldByName('field_paragraphs[0][_weight]', 1);
    $this->assertFieldByName('field_paragraphs[1][subform][field_text][0][value]', 'B');
    $this->assertFieldByName('field_paragraphs[1][_weight]', 3);
    $this->assertFieldByName('field_paragraphs[2][subform][field_text][0][value]', 'C');
    $this->assertFieldByName('field_paragraphs[2][_weight]', 0);
    $this->assertFieldByName('field_paragraphs[3][subform][field_text][0][value]', 'A');
    $this->assertFieldByName('field_paragraphs[3][_weight]', 2);

    // Move C after the A's and save.
    $edit = [
      'field_paragraphs[0][_weight]' => -2,
      'field_paragraphs[1][_weight]' => 2,
      'field_paragraphs[2][_weight]' => 1,
      'field_paragraphs[3][_weight]' => -1,
    ];

    // Save and check if all paragraphs are present in the correct order.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', 'A');
    $this->assertFieldByName('field_paragraphs[1][subform][field_text][0][value]', 'A');
    $this->assertFieldByName('field_paragraphs[2][subform][field_text][0][value]', 'C');
    $this->assertFieldByName('field_paragraphs[3][subform][field_text][0][value]', 'B');

    // Delete the second A, then duplicate C.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_1_remove');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_2_duplicate');
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', 'A');
    $this->assertFieldByName('field_paragraphs[1][subform][field_text][0][value]', 'C');
    $this->assertFieldByName('field_paragraphs[2][subform][field_text][0][value]', 'C');
    $this->assertFieldByName('field_paragraphs[3][subform][field_text][0][value]', 'B');
    // Check that the duplicate action is present.
    $this->assertField('field_paragraphs_0_duplicate');

    // Disable show duplicate action.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->assertText('Features: Duplicate, Collapse / Edit all');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_settings_edit');
    $this->drupalPostForm(NULL, ['fields[field_paragraphs][settings_edit_form][settings][features][duplicate]' => FALSE], t('Update'));
    $this->assertText('Features: Collapse / Edit all');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check that the duplicate action is not present.
    $this->assertNoField('field_paragraphs_0_duplicate');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', 'A');

    // Enable show duplicate action.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->assertText('Features: Collapse / Edit all');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_settings_edit');
    $this->drupalPostForm(NULL, ['fields[field_paragraphs][settings_edit_form][settings][features][duplicate]' => TRUE], t('Update'));
    $this->assertText('Features: Duplicate, Collapse / Edit all');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check that the duplicate action is present.
    $this->assertField('field_paragraphs_0_duplicate');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', 'A');
  }

  /**
   * Tests duplicate paragraph feature with nested paragraphs.
   */
  public function testDuplicateButtonWithNesting() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add nested Paragraph type.
    $nested_paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($nested_paragraph_type);
    // Add text Paragraph type.
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a ERR paragraph field to the nested_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $nested_paragraph_type, 'nested', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_nested_paragraph_add_more');

    // Create a node with a Paragraph.
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Add a text field to nested paragraph.
    $text = 'recognizable_text';
    $this->drupalPostAjaxForm('node/' . $node->id() . '/edit', [], 'field_paragraphs_0_subform_field_nested_text_add_more');
    $edit = [
      'field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]' => $text,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Switch mode to closed.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Click "Duplicate" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_duplicate');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]', $text);
    $this->assertFieldByName('field_paragraphs[1][subform][field_nested][0][subform][field_text][0][value]', $text);

    // Change the text paragraph value of duplicated nested paragraph.
    $second_paragraph_text = 'duplicated_text';
    $edit = [
      'field_paragraphs[1][subform][field_nested][0][subform][field_text][0][value]' => $second_paragraph_text,
    ];

    // Save and check if the changed text paragraph value of the duplicated
    // paragraph is not the same as in the original paragraph.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUniqueText($text);
    $this->assertUniqueText($second_paragraph_text);
  }

}
