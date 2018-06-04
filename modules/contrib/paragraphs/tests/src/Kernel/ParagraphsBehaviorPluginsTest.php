<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior plugins API.
 *
 * @group paragraphs
 */
class ParagraphsBehaviorPluginsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'paragraphs_test',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests the behavior settings API.
   */
  public function testBehaviorSettings() {
    // Create a paragraph type.
    $paragraph_type = ParagraphsType::create(array(
      'label' => 'test_text',
      'id' => 'test_text',
      'behavior_plugins' => [
        'test_text_color' => [
          'enabled' => TRUE,
        ]
      ],
    ));
    $paragraph_type->save();

    // Create a paragraph and set its feature settings.
    $paragraph = Paragraph::create([
      'type' => 'test_text',
    ]);
    $feature_settings = [
      'test_text_color' => [
        'text_color' => 'red'
      ],
    ];
    $paragraph->setAllBehaviorSettings($feature_settings);
    $paragraph->save();

    // Load the paragraph and assert its stored feature settings.
    $paragraph = Paragraph::load($paragraph->id());
    $this->assertEquals($paragraph->getAllBehaviorSettings(), $feature_settings);

    // Check the text color plugin settings summary.
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugins()->getEnabled();
    $this->assertEquals($plugin['test_text_color']->settingsSummary($paragraph)[0], 'Text color: red');

    // Update the value of an specific plugin.
    $paragraph->setBehaviorSettings('test_text_color', ['text_color' => 'blue']);
    $paragraph->save();

    // Assert the values have been updated.
    $paragraph = Paragraph::load($paragraph->id());
    $this->assertEquals($paragraph->getBehaviorSetting('test_text_color', 'text_color'), 'blue');

    // Check the text color plugin settings summary.
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugins()->getEnabled();
    $this->assertEquals($plugin['test_text_color']->settingsSummary($paragraph)[0], 'Text color: blue');

  }

}
