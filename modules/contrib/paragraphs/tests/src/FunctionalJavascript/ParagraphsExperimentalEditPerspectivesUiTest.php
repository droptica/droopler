<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Test paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalEditPerspectivesUiTest extends JavascriptTestBase {

  use LoginAdminTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'paragraphs_test',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'link',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test paragraphs user interface.
   */
  public function testEditPerspectives() {

    $this->loginAsAdmin([
      'access content overview',
      'edit behavior plugin settings'
    ]);

    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $edit = [
      'label' => 'TestPlugin',
      'id' => 'testplugin',
      'behavior_plugins[test_text_color][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->drupalGet('admin/structure/types/add');
    $edit = [
      'name' => 'TestContent',
      'type' => 'testcontent',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->drupalGet('admin/structure/types/manage/testcontent/fields/add-field');
    $edit = [
      'new_storage_type' => 'field_ui:entity_reference_revisions:paragraph',
      'label' => 'testparagraphfield',
      'field_name' => 'testparagraphfield',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $edit = [
      'settings[target_type]' => 'paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $edit = [
      'settings[handler_settings][target_bundles_drag_drop][testplugin][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->drupalGet('admin/structure/types/manage/testcontent/form-display');
    $page->selectFieldOption('fields[field_testparagraphfield][type]', 'paragraphs');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet('node/add/testcontent');
    $this->clickLink('Behavior');
    $style_selector = $page->find('css', '.form-item-field-testparagraphfield-0-behavior-plugins-test-text-color-text-color');
    $this->assertTrue($style_selector->isVisible());
    $this->clickLink('Content');
    $this->assertFalse($style_selector->isVisible());
  }

  /**
   * Test if tabs are visible with no behavior elements.
   */
  public function testTabsVisibility() {
    $this->loginAsAdmin([
      'access content overview',
    ]);

    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $edit = [
      'label' => 'TestPlugin',
      'id' => 'testplugin',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->drupalGet('admin/structure/types/add');
    $edit = [
      'name' => 'TestContent',
      'type' => 'testcontent',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->drupalGet('admin/structure/types/manage/testcontent/fields/add-field');
    $edit = [
      'new_storage_type' => 'field_ui:entity_reference_revisions:paragraph',
      'label' => 'testparagraphfield',
      'field_name' => 'testparagraphfield',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $edit = [
      'settings[target_type]' => 'paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalPostForm(NULL, NULL, t('Save settings'));
    $this->drupalGet('admin/structure/types/manage/testcontent/form-display');
    $page->selectFieldOption('fields[field_testparagraphfield][type]', 'paragraphs');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet('node/add/testcontent');
    $style_selector = $page->find('css', '.paragraphs-tabs');
    $this->assertFalse($style_selector->isVisible());
  }

  /**
   * Test edit perspectives works fine with multiple fields.
   */
  public function testPerspectivesWithMultipleFields() {
    $this->loginAsAdmin([
      'edit behavior plugin settings'
    ]);

    // Add a nested Paragraph type.
    $paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsField('nested_paragraph', 'paragraphs', 'paragraph');
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));

    $this->addParagraphedContentType('testcontent');
    $this->addParagraphsField('testcontent', 'field_paragraphs2', 'node');

    // Disable the default paragraph on both the node and the nested paragraph
    // to explicitly test with no paragraph and avoid a loop.
    EntityFormDisplay::load('node.testcontent.default')
      ->setComponent('field_paragraphs', ['type' => 'paragraphs', 'settings' => ['default_paragraph_type' => '_none']])
      ->setComponent('field_paragraphs2', ['type' => 'paragraphs', 'settings' => ['default_paragraph_type' => '_none']])
      ->save();
    EntityFormDisplay::load('paragraph' . '.' . $paragraph_type . '.default')
      ->setComponent('paragraphs', ['type' => 'paragraphs', 'settings' => ['default_paragraph_type' => '_none']])
      ->save();

    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/testcontent');
    $assert_session->elementNotExists('css', '.paragraphs-nested');

    // Add a nested paragraph to the first field.
    $button = $this->getSession()->getPage()->findButton('Add nested_paragraph');
    $button->press();

    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.paragraphs-nested');

    // Add a paragraph to the second field.
    $region_field2 = $this->getSession()->getPage()->find('css', '.field--name-field-paragraphs2');
    $button_field2 = $region_field2->findButton('Add nested_paragraph');
    $button_field2->press();
    $assert_session->assertWaitOnAjaxRequest();

    // Ge the style checkboxes from each field, make sure they are not visible
    // by default.
    $page = $this->getSession()->getPage();
    $style_selector = $page->findField('field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]');
    $this->assertFalse($style_selector->isVisible());
    $style_selector2 = $page->findField('field_paragraphs2[0][behavior_plugins][test_bold_text][bold_text]');
    $this->assertFalse($style_selector2->isVisible());

    // Switch to Behavior on the first field, then the second, make sure
    // the visibility of the checkboxes is correct after each change.
    $this->clickLink('Behavior', 0);
    $this->assertTrue($style_selector->isVisible());
    $this->assertFalse($style_selector2->isVisible());
    $this->clickLink('Behavior', 1);
    $this->assertTrue($style_selector->isVisible());
    $this->assertTrue($style_selector2->isVisible());

    // Switch the second field back to Content, verify visibility again.
    $this->clickLink('Content', 1);
    $this->assertTrue($style_selector->isVisible());
    $this->assertFalse($style_selector2->isVisible());
  }

}
