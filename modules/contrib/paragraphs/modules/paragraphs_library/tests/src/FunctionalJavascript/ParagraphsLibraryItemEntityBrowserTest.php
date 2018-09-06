<?php

namespace Drupal\Tests\paragraphs_library\FunctionalJavascript;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\entity_browser\FunctionalJavascript\EntityBrowserJavascriptTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests entity browser integration with paragraphs.
 *
 * @group paragraphs_library
 */
class ParagraphsLibraryItemEntityBrowserTest extends EntityBrowserJavascriptTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'ctools',
    'views',
    'block',
    'node',
    'file',
    'image',
    'field_ui',
    'views_ui',
    'system',
    'node',
    'paragraphs_library',
    'entity_browser',
    'content_translation'
  ];

  /**
   * Tests a flow of adding/removing references with paragraphs.
   */
  public function testEntityBrowserWidget() {
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs');
    $admin = $this->drupalCreateUser([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
      'administer entity browsers',
      'access paragraphs_library_items entity browser pages',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    $this->drupalLogin($admin);

    // Make everything that is needed translatable.
    $edit = [
      'entity_types[paragraphs_library_item]' => TRUE,
      'settings[paragraphs_library_item][paragraphs_library_item][translatable]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));

    $this->addParagraphsType('text');
    $this->addFieldtoParagraphType('text', 'field_text', 'text');

    // Add a paragraph library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->getSession()->getPage()->clickLink('Add library item');
    $element = $this->getSession()->getPage()->find('xpath', '//*[contains(@class, "dropbutton-toggle")]');
    $element->click();
    $button = $this->getSession()->getPage()->findButton('Add text');
    $button->press();
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('label[0][value]', 'test_library_item');
    $this->getSession()->getPage()->fillField('paragraphs[0][subform][field_text][0][value]', 'reusable_text');
    $this->submitForm([], 'Save');

    // Add a node with a paragraph from library.
    $this->drupalGet('node/add');
    $title = $this->assertSession()->fieldExists('Title');
    $title->setValue('Paragraph test');
    $this->getSession()->getPage()->pressButton('field_paragraphs_from_library_add_more');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select reusable paragraph');
    $this->waitForAjaxToFinish();
    $this->getSession()->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->checkField('edit-entity-browser-select-paragraphs-library-item1');
    $this->getSession()->switchToIFrame();

    $drop = <<<JS
    jQuery('input[type=submit][value="Select reusable paragraph"]', window.frames['entity_browser_iframe_paragraphs_library_items'].document).trigger('click')
JS;
    $this->getSession()->evaluateScript($drop);
    // Now wait until the button and iframe is gone, wait at least one second
    // because the ajax detection does not reliable detect the active ajax
    // processing in the iframe.
    sleep(1);
    $this->waitForAjaxToFinish();
    $this->drupalPostForm(NULL, [], t('Save'));
    // Check that the paragraph was correctly reused.
    $this->assertSession()->pageTextContains('reusable_text');

    // Translate the library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('test_library_item');
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $edit = [
      'label[0][value]' => 'DE Title',
      'paragraphs[0][subform][field_text][0][value]' => 'DE Library text',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->pageTextContains('Paragraph DE Title has been updated.');

    // Add a node with a paragraph from library.
    $this->drupalGet('node/add');
    $title = $this->assertSession()->fieldExists('Title');
    $title->setValue('Paragraph test');
    $this->getSession()->getPage()->pressButton('field_paragraphs_from_library_add_more');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select reusable paragraph');
    $this->waitForAjaxToFinish();
    $this->getSession()->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    $this->waitForAjaxToFinish();
    // Check that there is only one translation of the paragraph listed.
    $rows = $this->xpath('//*[@id="entity-browser-paragraphs-library-items-form"]/div[1]/div[2]/table/tbody/tr');
    $this->assertTrue(count($rows) == 1);
  }

}
