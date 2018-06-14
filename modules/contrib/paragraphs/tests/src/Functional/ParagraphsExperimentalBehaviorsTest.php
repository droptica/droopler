<?php

namespace Drupal\Tests\paragraphs\Functional;

use Drupal\paragraphs\Tests\Classic\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests support for Paragraphs behavior plugins.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalBehaviorsTest extends BrowserTestBase {

  use ParagraphsCoreVersionUiTestTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'paragraphs_test',
  ];

  /**
   * Tests that behavior settings have empty leaves removed before being saved.
   */
  public function testBehaviorPluginsSettingsFiltering() {
    $this->addParagraphedContentType('paragraphed_test');

    $admin = $this->drupalCreateUser([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
      'administer paragraphs types',
    ]);
    $this->drupalLogin($admin);

    // Add a text Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addFieldtoParagraphType($paragraph_type, 'field_text', 'text_long');

    // Enable the "Test bold text plugin" to have a behavior form.
    $this->drupalGet('/admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add a note that uses the behavior plugin give it an empty setting.
    $this->drupalGet('node/add/paragraphed_test');
    $edit = [
      'title[0][value]' => 'Test Node',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Non-bold text',
      'field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $bolded_elements = $this->getSession()->getPage()->findAll('css', '.bold_plugin_text');
    $this->assertFalse(count($bolded_elements), 'Test plugin did not add a CSS class.');

    // Check that empty leaves are not saved in the behavior settings.
    $node = $this->getNodeByTitle('Test Node', TRUE);
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->get('field_paragraphs')->entity;
    $behavior_settings = $paragraph->getBehaviorSetting('test_bold_text', []);
    $expected_settings = [];
    self::assertEquals($expected_settings, $behavior_settings);

    // Save a non-empty setting.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'field_paragraphs[0][subform][field_text][0][value]' => 'Bold text',
      'field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $bolded_elements = $this->getSession()->getPage()->findAll('css', '.bold_plugin_text');
    $this->assertTrue(count($bolded_elements), 'Test plugin added a CSS class.');

    // Check that non-empty leaves are saved in the behavior settings.
    $node = $this->getNodeByTitle('Test Node', TRUE);
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->get('field_paragraphs')->entity;
    $behavior_settings = $paragraph->getBehaviorSetting('test_bold_text', []);
    $expected_settings = [
      'bold_text' => 1,
    ];
    self::assertEquals($expected_settings, $behavior_settings);
  }

}
