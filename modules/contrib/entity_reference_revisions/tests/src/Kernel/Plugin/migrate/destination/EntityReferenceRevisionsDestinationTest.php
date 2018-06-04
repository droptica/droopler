<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel\Plugin\migrate\destination;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\node\Entity\NodeType;

/**
 * Tests the migration destination plugin.
 *
 * @coversDefaultClass \Drupal\entity_reference_revisions\Plugin\migrate\destination\EntityReferenceRevisions
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsDestinationTest extends KernelTestBase implements MigrateMessageInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
    'user',
    'system',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test_composite');
    $this->installSchema('system', ['sequences']);
    $this->installConfig($this->modules);

    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
  }

  /**
   * Tests get entity type id.
   *
   * @dataProvider getEntityTypeIdDataProvider
   *
   * @covers ::getEntityTypeId
   */
  public function testGetEntityTypeId(array $definition, $expected) {
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationPluginManager->createStubMigration($definition);
    /** @var \Drupal\entity_reference_revisions\Plugin\migrate\destination\EntityReferenceRevisions $destination */
    $destination = $migration->getDestinationPlugin();

    /** @var \Drupal\Core\Entity\EntityStorageBase $storage */
    $storage = $this->readAttribute($destination, 'storage');
    $actual = $this->readAttribute($storage, 'entityTypeId');

    $this->assertEquals($expected, $actual);
  }

  /**
   * Provides multiple migration definitions for "getEntityTypeId" test.
   */
  public function getEntityTypeIdDataProvider() {
    $data = $this->getEntityDataProvider();

    foreach ($data as &$datum) {
      $datum['expected'] = 'entity_test_composite';
    }

    return $data;
  }

  /**
   * Tests get entity.
   *
   * @dataProvider getEntityDataProvider
   *
   * @covers ::getEntity
   * @covers ::rollback
   * @covers ::rollbackNonTranslation
   */
  public function testGetEntity(array $definition, array $expected) {
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationPluginManager->createStubMigration($definition);
    $migrationExecutable = (new MigrateExecutable($migration, $this));
    /** @var \Drupal\Core\Entity\EntityStorageBase $storage */
    $storage = $this->readAttribute($migration->getDestinationPlugin(), 'storage');
    // Test inserting and updating by looping twice.
    for ($i = 0; $i < 2; $i++) {
      $migrationExecutable->import();
      $migration->getIdMap()->prepareUpdate();
      foreach ($expected as $data) {
        $entity = $storage->loadRevision($data['revision_id']);
        $this->assertEquals($data['label'], $entity->label());
        $this->assertEquals($data['id'], $entity->id());
      }
    }
    $migrationExecutable->rollback();
    foreach ($expected as $data) {
      $entity = $storage->loadRevision($data['id']);
      $this->assertEmpty($entity);
    }
  }

  /**
   * Provides multiple migration definitions for "getEntity" test.
   */
  public function getEntityDataProvider() {
    return [
      'without keys' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              ['id' => 1, 'name' => 'content item 1a'],
              ['id' => 1, 'name' => 'content item 1b'],
              ['id' => 2, 'name' => 'content item 2'],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
              'name' => ['type' => 'text'],
            ],
          ],
          'process' => [
            'name' => 'name',
          ],
          'destination' => [
            'plugin' => 'entity_reference_revisions:entity_test_composite',
          ],
        ],
        'expected' => [
          ['id' => 1, 'revision_id' => 1, 'label' => 'content item 1a'],
          ['id' => 2, 'revision_id' => 2, 'label' => 'content item 1b'],
          ['id' => 3, 'revision_id' => 3, 'label' => 'content item 2'],
        ],
      ],
      'with ids' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              ['id' => 1, 'name' => 'content item 1a'],
              ['id' => 1, 'name' => 'content item 1b'],
              ['id' => 2, 'name' => 'content item 2'],
              ['id' => 3, 'name' => 'content item 3'],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
              'name' => ['type' => 'text'],
            ],
          ],
          'process' => [
            'name' => 'name',
            'id' => 'id',
          ],
          'destination' => [
            'plugin' => 'entity_reference_revisions:entity_test_composite',
          ],
        ],
        'expected' => [
          ['id' => 1, 'revision_id' => 1, 'label' => 'content item 1b'],
          ['id' => 2, 'revision_id' => 2, 'label' => 'content item 2'],
          ['id' => 3, 'revision_id' => 3, 'label' => 'content item 3'],
        ],
      ],
      'with ids and new revisions' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              ['id' => 1, 'name' => 'content item 1a'],
              ['id' => 1, 'name' => 'content item 1b'],
              ['id' => 2, 'name' => 'content item 2'],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
              'name' => ['type' => 'text'],
            ],
          ],
          'process' => [
            'name' => 'name',
            'id' => 'id',
          ],
          'destination' => [
            'plugin' => 'entity_reference_revisions:entity_test_composite',
            'new_revisions' => TRUE,
          ],
        ],
        'expected' => [
          ['id' => 1, 'revision_id' => 1, 'label' => 'content item 1a'],
          ['id' => 1, 'revision_id' => 2, 'label' => 'content item 1b'],
          ['id' => 2, 'revision_id' => 3, 'label' => 'content item 2'],
        ],
      ],
      'with ids and revisions' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              ['id' => 1, 'revision_id' => 1, 'name' => 'content item 1'],
              ['id' => 2, 'revision_id' => 2, 'name' => 'content item 2'],
              ['id' => 3, 'revision_id' => 3, 'name' => 'content item 3'],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
              'name' => ['type' => 'text'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'revision_id' => 'revision_id',
            'name' => 'name',
          ],
          'destination' => [
            'plugin' => 'entity_reference_revisions:entity_test_composite',
          ],
        ],
        'expected' => [
          ['id' => 1, 'revision_id' => 1, 'label' => 'content item 1'],
          ['id' => 2, 'revision_id' => 2, 'label' => 'content item 2'],
          ['id' => 3, 'revision_id' => 3, 'label' => 'content item 3'],
        ],
      ],
    ];
  }

  /**
   * Tests multi-value and single-value destination field linkage.
   *
   * @dataProvider destinationFieldMappingDataProvider
   */
  public function testDestinationFieldMapping(array $data) {
    $this->enableModules(['node', 'field']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);

    // Create new content type.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();

    // Add the field_err_single field to the node type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_err_single',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'entity_test_composite',
      ],
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    // Add the field_err_multiple field to the node type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_err_multiple',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'entity_test_composite',
      ],
      'cardinality' => -1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    $definitions = [];
    $instances = [];
    foreach ($data as $datum) {
      $definitions[$datum['definition']['id']] = $datum['definition'];
      $instances[$datum['definition']['id']] = $this->migrationPluginManager->createStubMigration($datum['definition']);
    }

    // Reflection is easier than mocking. We need to use createInstance for
    // purposes of registering the migration for the migration process plugin.
    $reflector = new \ReflectionObject($this->migrationPluginManager);
    $property = $reflector->getProperty('definitions');
    $property->setAccessible(TRUE);
    $property->setValue($this->migrationPluginManager, $definitions);
    $this->container->set('plugin.manager.migration', $this->migrationPluginManager);

    foreach ($data as $datum) {
      $migration = $this->migrationPluginManager->createInstance($datum['definition']['id']);
      $migrationExecutable = (new MigrateExecutable($migration, $this));
      /** @var \Drupal\Core\Entity\EntityStorageBase $storage */
      $storage = $this->readAttribute($migration->getDestinationPlugin(), 'storage');
      $migrationExecutable->import();
      foreach ($datum['expected'] as $expected) {
        $entity = $storage->loadRevision($expected['id']);
        $properties = array_diff_key($expected, array_flip(['id']));
        foreach ($properties as $property => $value) {
          if (is_array($value)) {
            foreach ($value as $delta => $text) {
              $this->assertNotEmpty($entity->{$property}[$delta]->entity, "Entity property $property with $delta is empty");
              $this->assertEquals($text, $entity->{$property}[$delta]->entity->label());
            }
          }
          else {
            $this->assertNotEmpty($entity, 'Entity with label ' . $expected[$property] . ' is empty');
            $this->assertEquals($expected[$property], $entity->label());
          }
        }
      }
    }
  }

  /**
   * Provides multiple migration definitions for "getEntity" test.
   */
  public function destinationFieldMappingDataProvider() {
    return [
      'scenario 1' => [
        [
          'single err' => [
            'definition' => [
              'id' => 'single_err',
              'class' => Migration::class,
              'source' => [
                'plugin' => 'embedded_data',
                'data_rows' => [
                  [
                    'id' => 1,
                    'photo' => 'Photo1 here',
                  ],
                  [
                    'id' => 2,
                    'photo' => 'Photo2 here',
                  ],
                ],
                'ids' => [
                  'id' => ['type' => 'integer'],
                ],
              ],
              'process' => [
                'name' => 'photo',
              ],
              'destination' => [
                'plugin' => 'entity_reference_revisions:entity_test_composite',
              ],
            ],
            'expected' => [
              ['id' => 1, 'name' => 'Photo1 here'],
              ['id' => 2, 'name' => 'Photo2 here'],
            ],
          ],
          'multiple err author1' => [
            'definition' => [
              'id' => 'multiple_err_author1',
              'class' => Migration::class,
              'source' => [
                'plugin' => 'embedded_data',
                'data_rows' => [
                  [
                    'id' => 1,
                    'author' => 'Author 1',
                  ],
                  [
                    'id' => 2,
                    'author' => 'Author 2',
                  ],
                ],
                'ids' => [
                  'author' => ['type' => 'text'],
                ],
              ],
              'process' => [
                'name' => 'author',
              ],
              'destination' => [
                'plugin' => 'entity_reference_revisions:entity_test_composite',
              ],
            ],
            'expected' => [
              ['id' => 3, 'name' => 'Author 1'],
              ['id' => 4, 'name' => 'Author 2'],
            ],
          ],
          'multiple err author 2' => [
            'definition' => [
              'id' => 'multiple_err_author2',
              'class' => Migration::class,
              'source' => [
                'plugin' => 'embedded_data',
                'data_rows' => [
                  [
                    'id' => 1,
                    'author' => 'Author 3',
                  ],
                  [
                    'id' => 2,
                    'author' => 'Author 4',
                  ],
                ],
                'ids' => [
                  'author' => ['type' => 'text'],
                ],
              ],
              'process' => [
                'name' => 'author',
              ],
              'destination' => [
                'plugin' => 'entity_reference_revisions:entity_test_composite',
              ],
            ],
            'expected' => [
              ['id' => 5, 'name' => 'Author 3'],
              ['id' => 6, 'name' => 'Author 4'],
            ],
          ],
          'destination entity' => [
            'definition' => [
              'id' => 'node_migration',
              'class' => Migration::class,
              'source' => [
                'plugin' => 'embedded_data',
                'data_rows' => [
                  [
                    'id' => 1,
                    'title' => 'Article 1',
                    'photo' => 'Photo1 here',
                    'author' => ['Author 1', 'Author 3'],
                  ],
                  [
                    'id' => 2,
                    'title' => 'Article 2',
                    'photo' => 'Photo2 here',
                    'author' => ['Author 2', 'Author 4'],
                  ],
                ],
                'ids' => [
                  'id' => ['type' => 'integer'],
                ],
              ],
              'process' => [
                'title' => 'title',
                'type' => [
                  'plugin' => 'default_value',
                  'default_value' => 'article',
                ],
                'field_err_single/target_id' => [
                  [
                    'plugin' => 'migration',
                    'migration' => ['single_err'],
                    'no_stub' => TRUE,
                    'source' => 'id',
                  ],
                  [
                    'plugin' => 'extract',
                    'index' => [
                      '0',
                    ],
                  ],
                ],
                'field_err_single/target_revision_id' => [
                  [
                    'plugin' => 'migration',
                    'migration' => ['single_err'],
                    'no_stub' => TRUE,
                    'source' => 'id',
                  ],
                  [
                    'plugin' => 'extract',
                    'index' => [
                      1,
                    ],
                  ],
                ],
                'field_err_multiple' => [
                  [
                    'plugin' => 'migration',
                    'migration' => [
                      'multiple_err_author1',
                      'multiple_err_author2',
                    ],
                    'no_stub' => TRUE,
                    'source' => 'author',
                  ],
                  [
                    'plugin' => 'iterator',
                    'process' => [
                      'target_id' => '0',
                      'target_revision_id' => '1',
                    ],
                  ],
                ],
              ],
              'destination' => [
                'plugin' => 'entity:node',
              ],
            ],
            'expected' => [
              [
                'id' => 1,
                'title' => 'Article 1',
                'field_err_single' => ['Photo1 here'],
                'field_err_multiple' => ['Author 1', 'Author 3'],
              ],
              [
                'id' => 2,
                'title' => 'Article 2',
                'field_err_single' => ['Photo2 here'],
                'field_err_multiple' => ['Author 2', 'Author 4'],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $this->assertTrue($type == 'status', $message);
  }

}
