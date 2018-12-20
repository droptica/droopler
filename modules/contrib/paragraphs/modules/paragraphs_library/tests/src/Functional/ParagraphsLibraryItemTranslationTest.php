<?php

namespace Drupal\Tests\paragraphs_library\Functional;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests the multilingual functionality of the Paragraphs Library.
 *
 * @group paragraphs_library
 */
class ParagraphsLibraryItemTranslationTest extends BrowserTestBase {

  use ParagraphsTestBaseTrait;
  use LoginAdminTrait;
  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'views',
    'paragraphs_library',
    'link',
    'block',
    'node',
    'field_ui',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->addParagraphedContentType('paragraphed_test');

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    // Add a second language (German) to the site.
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Test conversion and display of translated library items.
   */
  public function testLibraryItemTranslation() {

    $this->loginAsAdmin([
      'administer site configuration',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library'
    ]);

    // Add a Paragraph type with a text field.
    $this->addParagraphsType('text');
    $paragraph_type = ParagraphsType::load('text');
    $paragraph_type->setThirdPartySetting('paragraphs_library', 'allow_library_conversion', TRUE);
    $paragraph_type->save();
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'text', 'Text', 'text_long', [], []);

    // Make everything that is needed translatable.
    $edit = [
      'entity_types[node]' => TRUE,
      'entity_types[paragraph]' => TRUE,
      'entity_types[paragraphs_library_item]' => TRUE,
      'settings[node][paragraphed_test][translatable]' => TRUE,
      'settings[node][paragraphed_test][fields][field_paragraphs]' => FALSE,
      'settings[paragraph][text][translatable]' => TRUE,
      'settings[paragraph][text][fields][field_text]' => TRUE,
      'settings[paragraphs_library_item][paragraphs_library_item][translatable]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Add a node and translate it.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, 'Add text');

    $assert_session->buttonExists('field_paragraphs_0_promote_to_library');
    $assert_session->buttonExists('Promote to library');
    $edit = [
      'title[0][value]' => 'EN Title',
      'field_paragraphs[0][subform][field_text][0][value]' => 'EN Library text',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $assert_session->pageTextContains('paragraphed_test EN Title has been created.');

    $this->clickLink('Translate');
    $this->clickLink('Add');

    $edit = [
      'title[0][value]' => 'DE Title',
      'field_paragraphs[0][subform][field_text][0][value]' => 'DE Library text',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save (this translation)');
    $assert_session->pageTextContains('paragraphed_test DE Title has been updated.');

    // Convert the text to a library item and make sure it is displayed
    // correctly.
    $node = $this->drupalGetNodeByTitle('EN Title');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->pressButton('Promote to library');
    $assert_session->fieldValueEquals('Reusable paragraph', 'text: EN Library text (1)');
    $this->drupalPostForm(NULL, NULL, 'Save');

    $assert_session->pageTextContains('EN Title');
    $assert_session->pageTextContains('EN Library text');

    $this->drupalGet('de/node/' . $node->id());
    $assert_session->pageTextContains('DE Title');
    $assert_session->pageTextContains('DE Library text');

    // The overview currently only shows the original translation to avoid
    // duplicates.
    $this->drupalGet('admin/content/paragraphs');
    $this->assertEquals(1, substr_count($page->getText(), 'text: EN Library text'));
    $assert_session->pageTextNotContains('DE Library text');

    // Assert that the translations exist and can be accessed.
    $this->clickLink('Edit');
    $assert_session->fieldValueEquals('Label', 'text: EN Library text');
    $assert_session->fieldValueEquals('Text', 'EN Library text');
    $this->clickLink('Translate');
    $this->clickLink('Edit', 1);
    $assert_session->fieldValueEquals('Label', 'text: DE Library text');
    $assert_session->fieldValueEquals('Text', 'DE Library text');
  }

}
