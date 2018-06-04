<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel\Plugin\Derivative;

use Drupal\entity_reference_revisions\Plugin\migrate\destination\EntityReferenceRevisions;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\Plugin\MigrateDestinationPluginManager;

/**
 * Tests the migration deriver.
 *
 * @coversDefaultClass \Drupal\entity_reference_revisions\Plugin\Derivative\MigrateEntityReferenceRevisions
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsDeriverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate', 'entity_reference_revisions', 'entity_composite_relationship_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig($this->modules);
  }

  /**
   * Tests deriver.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testDestinationDeriver() {
    /** @var MigrateDestinationPluginManager $migrationDestinationManager */
    $migrationDestinationManager = \Drupal::service('plugin.manager.migrate.destination');

    $destination = $migrationDestinationManager->getDefinition('entity_reference_revisions:entity_test_composite');
    $this->assertEquals(EntityReferenceRevisions::class, $destination['class']);
  }



}
