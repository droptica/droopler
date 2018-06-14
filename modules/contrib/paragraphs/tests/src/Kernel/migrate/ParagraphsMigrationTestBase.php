<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for the paragraph migrations tests.
 */
abstract class ParagraphsMigrationTestBase extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal7.php');

  }

  /**
   * Check to see if paragraph types were created.
   *
   * @param string $bundle_machine_name
   *   The bundle to test for.
   * @param string $bundle_label
   *   The bundle's label.
   */
  protected function assertParagraphBundleExists($bundle_machine_name, $bundle_label) {
    $bundle = ParagraphsType::load($bundle_machine_name);
    $this->assertInstanceOf(ParagraphsType::class, $bundle);
    $this->assertEquals($bundle_label, $bundle->label());
  }

  /**
   * Check if a field storage config entity was created for the paragraph.
   *
   * @param string $field_name
   *   The field to test for.
   * @param string $field_type
   *   The expected field type.
   */
  protected function assertParagraphEntityFieldExists($field_name, $field_type) {
    $field_storage = FieldStorageConfig::loadByName('paragraph', $field_name);
    $this->assertNotNull($field_storage);
    $this->assertEquals($field_type, $field_storage->getType());
  }

  /**
   * Check if a field storage entity was created for the paragraph fields.
   *
   * @param string $entity_type
   *   The entity type to check on.
   * @param string $field_name
   *   The field name to check for.
   */
  protected function assertParagraphFieldExists($entity_type, $field_name) {
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
    $this->assertNotNull($field_storage);
    $this->assertEquals('entity_reference_revisions', $field_storage->getType());
    $this->assertEquals('paragraph', $field_storage->getSetting('target_type'));
  }

  /**
   * Test if the given field instance was created.
   */
  protected function assertFieldInstanceExists($entity_type, $bundle, $field_name, $field_type = 'entity_reference_revisions') {
    $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    $this->assertNotNull($field);
    $this->assertEquals($field_type, $field->getType());
  }

  /**
   * Execute a migration's dependencies followed by the migration.
   *
   * @param string $plugin_id
   *   The migration id to execute.
   */
  protected function executeMigrationWithDependencies($plugin_id) {
    /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.migration');
    $migrations = $manager->createInstances($plugin_id);
    foreach ($migrations as $migration) {
      $this->executeMigrationDependencies($migration);
      $this->executeMigration($migration);
    }
  }

  /**
   * Find and execute a migration's dependencies.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Migration from which to execute dependencies.
   */
  protected function executeMigrationDependencies(MigrationInterface $migration) {
    $dependencies = $migration->getMigrationDependencies();
    foreach ($dependencies['required'] as $dependency) {
      $plugin = $this->getMigration($dependency);
      if (!$plugin->allRowsProcessed()) {
        $this->executeMigrationDependencies($plugin);
        $this->executeMigration($plugin);
      }
    }
    foreach ($dependencies['optional'] as $dependency) {
      if ($plugin = $this->getMigration($dependency)) {
        if (!$plugin->allRowsProcessed()) {
          $this->executeMigrationDependencies($plugin);
          $this->executeMigration($plugin);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMigration(MigrationInterface $migration) {

    // We want to run the revision migraiton without running all the node
    // migrations.
    if ($migration->id() == 'd7_node_revision:paragraphs_test') {
      $migration->set('migration_dependencies', [
        'required' => ['d7_node:paragraphs_test'],
        'optional' => [],
      ]);
      $migration->set('requirements', ['d7_node:paragraphs_test' => 'd7_node:paragraphs_test']);

    }
  }

}
