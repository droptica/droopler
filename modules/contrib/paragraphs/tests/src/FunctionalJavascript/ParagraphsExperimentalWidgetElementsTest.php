<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Test paragraphs widget elements.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalWidgetElementsTest extends JavascriptTestBase {

  use LoginAdminTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'paragraphs',
    'paragraphs_demo',
    'field',
    'field_ui',
    'block',
    'link',
    'text',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test paragraphs drag handler during translation.
   */
  public function testDragHandler() {
    $this->loginAsAdmin([
      'administer site configuration',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'delete any paragraphed_content_demo content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    ConfigurableLanguage::createFromLangcode('sr')->save();
    $edit = [
      'settings[paragraph][nested_paragraph][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][settings][language][language_alterable]' => TRUE,
      'settings[paragraph][images][fields][field_images_demo]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));
    $settings = [
      'add_mode' => 'modal',
    ];
    $this->setParagraphsWidgetSettings('paragraphed_content_demo', 'field_paragraphs_demo', $settings);

    // Create a node and add a paragraph.
    $page = $this->getSession()->getPage();
    $this->drupalGet('node/add/paragraphed_content_demo');
    $page->pressButton('Add Paragraph');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Text');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Assert the draghandle is visible.
    $style_selector = $page->find('css', '.tabledrag-handle');
    $this->assertTrue($style_selector->isVisible());
    $edit = [
      'title[0][value]' => 'Title',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'First',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Translate the node.
    $node = $this->getNodeByTitle('Title');
    $this->drupalGet('node/' . $node->id() . '/translations/add/en/sr');
    $page = $this->getSession()->getPage();
    // Assert that the draghandle is not displayed.
    $this->assertNull($page->find('css', '.tabledrag-handle'));
  }

}
