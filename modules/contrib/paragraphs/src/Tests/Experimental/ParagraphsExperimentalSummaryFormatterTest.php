<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Drupal\field_ui\Tests\FieldUiTestTrait;

/**
 * Tests the paragraphs summary formatter.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalSummaryFormatterTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'image',
  ];

  /**
   * Tests the paragraphs summary formatter.
   */
  public function testParagraphsSummaryFormatter() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer node display']);

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $title_paragraphs_type = 'title';
    $this->addParagraphsType($title_paragraphs_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $title_paragraphs_type, 'title', 'Title', 'string', [], []);

    // Add a user Paragraph Type
    $paragraph_type = 'user_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'user', 'User', 'entity_reference', ['settings[target_type]' => 'user'], []);

    // Set display format to paragraphs summary.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/display');
    $edit = ['fields[field_paragraphs][type]' => 'paragraph_summary'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Add a paragraph.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_title_add_more');

    // Create a node with a text.
    $edit = [
      'title[0][value]' => 'Test article',
      'field_paragraphs[0][subform][field_text][0][value]' => 'text_summary',
      'field_paragraphs[1][subform][field_title][0][value]' => 'Title example',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->clickLink(t('Edit'));
    $this->drupalPostForm(NULL, [], t('Add user_paragraph'));
    $edit = [
      'field_paragraphs[2][subform][field_user][0][target_id]' => $this->admin_user->label() . ' (' . $this->admin_user->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert the summary is correctly generated.
    $this->assertText($this->admin_user->label());
    $this->assertText('Title example');
  }

}
