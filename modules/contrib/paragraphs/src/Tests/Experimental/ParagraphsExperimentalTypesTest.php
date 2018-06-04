<?php

namespace Drupal\paragraphs\Tests\Experimental;

/**
 * Tests paragraphs types.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalTypesTest extends ParagraphsExperimentalTestBase {

  /**
   * Tests the deletion of Paragraphs types.
   */
  public function testRemoveTypesWithContent() {
    $this->loginAsAdmin();
    // Add a Paragraphed test content.
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');

    $this->addParagraphsType('paragraph_type_test');
    $this->addParagraphsType('text');

    // Attempt to delete the content type not used yet.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink(t('Delete'));
    $this->assertText('This action cannot be undone.');
    $this->clickLink(t('Cancel'));

    // Add a test node with a Paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_paragraph_type_test_add_more');
    $edit = ['title[0][value]' => 'test_node'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('paragraphed_test test_node has been created.');

    // Attempt to delete the paragraph type already used.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink(t('Delete'));
    $this->assertText('paragraph_type_test Paragraphs type is used by 1 piece of content on your site. You can not remove this paragraph_type_test Paragraphs type until you have removed all from the content.');
  }

  /**
   * Tests creating paragraph type.
   */
  public function testCreateParagraphType() {
    $this->loginAsAdmin();

    // Add a paragraph type.
    $this->drupalGet('/admin/structure/paragraphs_type/add');

    // Create a paragraph type with label and id more than 32 characters.
    $edit = [
      'label' => 'Test',
      'id' => 'test_name_with_more_than_32_characters'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and manage fields');
    $this->assertNoErrorsLogged();
    $this->assertText('Machine-readable name cannot be longer than 32 characters but is currently 38 characters long.');
    $edit['id'] = 'new_test_id';
    $this->drupalPostForm(NULL, $edit, 'Save and manage fields');
    $this->assertText('Saved the Test Paragraphs type.');
  }
}
