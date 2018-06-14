<?php

namespace Drupal\Tests\paragraphs\Functional;

use Drupal\paragraphs\Tests\Classic\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests paragraphs experimental widget buttons.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalWidgetButtonsTest extends BrowserTestBase {

  use LoginAdminTrait;
  use ParagraphsCoreVersionUiTestTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'paragraphs_test',
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
  ];

  /**
   * Tests the autocollapse functionality.
   */
  public function testAutocollapse() {
    $this->addParagraphedContentType('paragraphed_test');

    $permissions = [
      'administer content types',
      'administer node fields',
      'administer paragraphs types',
      'administer node form display',
      'administer paragraph fields',
      'administer paragraph form display',
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ];
    $this->loginAsAdmin($permissions, TRUE);

    // Add a text Paragraph type.
    $this->addParagraphsType('text_paragraph');
    $this->addFieldtoParagraphType('text_paragraph', 'field_text', 'text_long');

    // Add another Paragraph type so that there is no default Paragraphs type.
    $this->addParagraphsType('another_paragraph');

    // Check that the paragraphs field uses the experimental widget.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $option = $this->assertSession()->optionExists('fields[field_paragraphs][type]', 'paragraphs');
    $this->assertTrue($option->isSelected());
    // Check that the autocollapse is not displayed if the edit mode is open.
    $this->assertSession()->pageTextNotContains('Autocollapse: None');
    $this->assertSession()->pageTextContains('Edit mode: Open');

    // Create a new node with 2 paragraphs.
    $this->drupalGet('node/add/paragraphed_test');
    $this->getSession()->getPage()->findButton('field_paragraphs_text_paragraph_add_more')->press();
    $this->getSession()->getPage()->findButton('field_paragraphs_text_paragraph_add_more')->press();
    $edit = [
      'title[0][value]' => 'Autocollapse test node',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Fist paragraph',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Second paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle('Autocollapse test node');

    // Set the settings to "Open" edit mode without autocollapse.
    $settings = [
      'edit_mode' => 'open',
      'closed_mode' => 'summary',
      'autocollapse' => 'none',
    ];
    $this->setParagraphsWidgetSettings('paragraphed_test', 'field_paragraphs', $settings);

    // Edit the node. Edit mode is "Open". All paragraphs are in the "Edit"
    // mode.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // Autocollapse is disabled. Closing and opening a paragraphs does not
    // affect the other one.
    $this->getSession()->getPage()->findButton('field_paragraphs_0_collapse')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    $this->getSession()->getPage()->findButton('field_paragraphs_0_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // "Collapse all" affects all paragraphs.
    $this->getSession()->getPage()->findButton('field_paragraphs_collapse_all')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');

    // Open the first paragraph and then the second. Opening the second does not
    // close the first.
    $this->getSession()->getPage()->findButton('field_paragraphs_0_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');

    $this->getSession()->getPage()->findButton('field_paragraphs_1_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // "Edit all" affects all paragraphs.
    $this->getSession()->getPage()->findButton('field_paragraphs_edit_all')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // Closing and opening a paragraphs does not affect the other one.
    $this->getSession()->getPage()->findButton('field_paragraphs_0_collapse')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    $this->getSession()->getPage()->findButton('field_paragraphs_0_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // Enable autocollapse. Set edit mode to "Closed".
    $settings = [
      'edit_mode' => 'closed',
      'closed_mode' => 'summary',
      'autocollapse' => 'all',
    ];
    $this->setParagraphsWidgetSettings('paragraphed_test', 'field_paragraphs', $settings);

    // Edit the node. All paragraphs are closed.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');

    // Open the first paragraph and then the second. Opening the second closes
    // the first.
    $this->getSession()->getPage()->findButton('field_paragraphs_0_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');

    $this->getSession()->getPage()->findButton('field_paragraphs_1_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // "Edit all" disables auto collapse.
    $this->getSession()->getPage()->findButton('field_paragraphs_edit_all')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // Closing and opening a paragraphs does not affect the other one anymore.
    $this->getSession()->getPage()->findButton('field_paragraphs_0_collapse')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    $this->getSession()->getPage()->findButton('field_paragraphs_0_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // "Collapse all" re-enables autocollapse.
    $this->getSession()->getPage()->findButton('field_paragraphs_collapse_all')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');

    // Open the first paragraph and then the second. Opening the second closes
    // the first.
    $this->getSession()->getPage()->findButton('field_paragraphs_0_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'edit');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');

    $this->getSession()->getPage()->findButton('field_paragraphs_1_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'edit');

    // Check that adding a new paragraphs closes the others.
    $this->getSession()->getPage()->findButton('field_paragraphs_text_paragraph_add_more')->press();
    $this->getSession()->getPage()->fillField('field_paragraphs[2][subform][field_text][0][value]', 'Third paragraph');
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');
    $this->checkParagraphInMode('field_paragraphs_2', 'edit');

    // Check that duplicating closes the other paragraphs.
    $this->getSession()->getPage()->findButton('field_paragraphs_2_duplicate')->press();
    $this->getSession()->getPage()->fillField('field_paragraphs[3][subform][field_text][0][value]', 'Fourth paragraph');
    $this->checkParagraphInMode('field_paragraphs_0', 'closed');
    $this->checkParagraphInMode('field_paragraphs_1', 'closed');
    $this->checkParagraphInMode('field_paragraphs_2', 'closed');
    $this->checkParagraphInMode('field_paragraphs_3', 'edit');

    // Check that autocollapse does not restore removed paragraphs.
    $this->getSession()->getPage()->findButton('field_paragraphs_3_remove')->press();
    $this->checkParagraphInMode('field_paragraphs_3', 'removed');
    $this->getSession()->getPage()->findButton('field_paragraphs_2_edit')->press();
    $this->checkParagraphInMode('field_paragraphs_3', 'removed');
  }

  /**
   * Asserts that a paragraph is in a particular mode.
   *
   * It does this indirectly by checking checking what buttons are available.
   *
   * @param string $button_prefix
   *   An initial part of the button name; namely "<paragraphs_field>_<delta>".
   *
   * @param string $mode
   *   Assert that the paragraphs is in this widget item mode. Supported modes
   *   are "edit", "closed" and "removed". A paragraph in the "removed" mode
   *   cannot be distinguished from one that has never been added.
   */
  public function checkParagraphInMode($button_prefix, $mode) {
    switch ($mode) {
      case 'edit':
        $this->assertSession()->buttonNotExists($button_prefix . '_edit');
        $this->assertSession()->buttonExists($button_prefix . '_collapse');
        break;
      case 'closed':
        $this->assertSession()->buttonExists($button_prefix . '_edit');
        $this->assertSession()->buttonNotExists($button_prefix . '_collapse');
        break;
      case 'removed':
        $this->assertSession()->buttonNotExists($button_prefix . '_edit');
        $this->assertSession()->buttonNotExists($button_prefix . '_collapse');
        break;
      default:
        throw new \InvalidArgumentException('This function does not support "' . $mode . '" as an argument for "$mode" parameter');
    }
  }

}
