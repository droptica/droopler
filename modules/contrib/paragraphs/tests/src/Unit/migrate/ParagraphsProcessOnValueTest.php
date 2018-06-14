<?php

namespace Drupal\Tests\paragraphs\Unit\migrate;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\paragraphs\Plugin\migrate\process\ParagraphsProcessOnValue;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test class for the paragraphs_process_on_value process plugin.
 *
 * @group paragraphs
 * @coversDefaultClass \Drupal\paragraphs\Plugin\migrate\process\ParagraphsProcessOnValue
 */
class ParagraphsProcessOnValueTest extends ProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setup() {
    parent::setup();
    $configuration = [
      'source_value' => 'source',
      'expected_value' => 'expected',
      'process' => [
        'plugin' => 'get',
        'source' => 'theValue',
      ],
    ];
    $this->plugin = new ParagraphsProcessOnValue($configuration, 'paragraphs_process_on_value', [], $this->entityTypeBundleInfo);
    $this->row
      ->expects($this->any())
      ->method('getSource')
      ->willReturn([
        'theValue' => 'Final Value',
        'source' => 'expected',
      ]);

  }

  /**
   * Test processing if conditions are met.
   */
  public function testProcess() {
    $migration = $this->getMigration();
    $get = new Get(['source' => 'theValue'], 'get', []);
    $migration->expects($this->any())
      ->method('getProcessPlugins')
      ->willReturn(['destination' => [$get]]);

    $this->row
      ->expects($this->any())
      ->method('getSourceProperty')
      ->with('source')
      ->willReturn('expected');
    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
    $message = $this->createMock(MigrateMessageInterface::class);
    $migrate_executable = new MigrateExecutable($migration, $message, $event_dispatcher);
    $value = $this->plugin->transform('Initial Value', $migrate_executable, $this->row, 'destination');
    $this->assertEquals('Final Value', $value);

  }

  /**
   * Test not processing if conditions are not met.
   */
  public function testSkip() {
    $migration = $this->getMigration();
    $get = new Get(['source' => 'theValue'], 'get', []);
    $migration->expects($this->any())
      ->method('getProcessPlugins')
      ->willReturn(['destination' => [$get]]);

    $this->row
      ->expects($this->any())
      ->method('getSourceProperty')
      ->with('source')
      ->willReturn('unexpected');
    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
    $message = $this->createMock(MigrateMessageInterface::class);
    $migrate_executable = new MigrateExecutable($migration, $message, $event_dispatcher);
    $value = $this->plugin->transform('Initial Value', $migrate_executable, $this->row, 'destination');
    $this->assertEquals('Initial Value', $value);

  }

}
