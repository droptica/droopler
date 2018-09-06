<?php

namespace Drupal\paragraphs\Tests\Classic;

use Drupal\field_ui\Tests\FieldUiTestTrait;

/**
 * Tests paragraphs widget buttons.
 *
 * @group paragraphs
 */
class ParagraphsWidgetButtonsTest extends ParagraphsTestBase {

  use FieldUiTestTrait;

  /**
   * Tests the widget buttons of paragraphs.
   */
  public function testWidgetButtons() {
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs', 'entity_reference_paragraphs');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $text = 'recognizable_text';
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => $text,
    ];
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Test the 'Open' mode.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $text);
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText($text);

    // Test the 'Closed' mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Edit" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_1_edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $text);
    $closed_mode_text = 'closed_mode_text';
    // Click "Collapse" button on both paragraphs.
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $closed_mode_text];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_collapse');
    $edit = ['field_paragraphs[1][subform][field_text][0][value]' => $closed_mode_text];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_1_collapse');
    // Verify that we have warning message for each paragraph.
    $this->assertNoUniqueText('You have unsaved changes on this Paragraph item.');
    $this->assertRaw('<div class="paragraphs-collapsed-description">' . $closed_mode_text);
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertText($closed_mode_text);

    // Test the 'Preview' mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'preview');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Edit" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $closed_mode_text);
    $preview_mode_text = 'preview_mode_text';
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $preview_mode_text];
    // Click "Collapse" button.
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_collapse');
    $this->assertText('You have unsaved changes on this Paragraph item.');
    $this->assertText($preview_mode_text);
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertText($preview_mode_text);

    // Test the remove/restore function.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertText($preview_mode_text);
    // Click "Remove" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_remove');
    $this->assertText('Deleted Paragraph: text_paragraph');
    // Click "Restore" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_restore');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $preview_mode_text);
    $restore_text = 'restore_text';
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $restore_text];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertText($restore_text);

    // Test the remove/confirm remove function.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertText($restore_text);
    // Click "Remove" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_remove');
    $this->assertText('Deleted Paragraph: text_paragraph');
    // Click "Confirm Removal" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_confirm_remove');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertNoText($restore_text);
  }

}
