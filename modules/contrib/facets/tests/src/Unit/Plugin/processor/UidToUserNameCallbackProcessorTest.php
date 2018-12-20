<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\UidToUserNameCallbackProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class UidToUserNameCallbackProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\processor\SortProcessorInterface
   */
  protected $processor;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->processor = new UidToUserNameCallbackProcessor([], 'uid_to_username_callback', []);
  }

  /**
   * Tests that results were correctly changed.
   */
  public function testResultsChanged() {
    $user_storage = $this->getMock(EntityStorageInterface::class);
    $entity_manager = $this->getMock(EntityManagerInterface::class);
    $entity_repository = $this->getMock(EntityTypeRepositoryInterface::class);
    $entity_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->willReturn('user');
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($user_storage);

    $user1 = $this->getMock(AccountInterface::class);
    $user1->method('getDisplayName')
      ->willReturn('Admin');

    $user_storage->method('load')
      ->willReturn($user1);

    $container = new ContainerBuilder();
    $container->set('entity.manager', $entity_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('entity_type.manager', $entity_manager);
    \Drupal::setContainer($container);

    $facet = new Facet([], 'facets_facet');
    $original_results = [
      new Result($facet, 1, 1, 5),
    ];

    $facet->setResults($original_results);

    $expected_results = [
      ['uid' => 1, 'name' => 'Admin'],
    ];

    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['uid'], $original_results[$key]->getRawValue());
      $this->assertEquals($expected['uid'], $original_results[$key]->getDisplayValue());
    }

    $filtered_results = $this->processor->build($facet, $original_results);

    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['uid'], $filtered_results[$key]->getRawValue());
      $this->assertEquals($expected['name'], $filtered_results[$key]->getDisplayValue());
    }
  }

  /**
   * Tests that deleted entity results were correctly handled.
   */
  public function testDeletedEntityResults() {
    $user_storage = $this->getMock(EntityStorageInterface::class);
    $entity_manager = $this->getMock(EntityManagerInterface::class);
    $entity_repository = $this->getMock(EntityTypeRepositoryInterface::class);
    $entity_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->willReturn('user');
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($user_storage);

    $user_storage->method('load')
      ->willReturn(NULL);

    $container = new ContainerBuilder();
    $container->set('entity.manager', $entity_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('entity_type.manager', $entity_manager);
    \Drupal::setContainer($container);

    $facet = new Facet([], 'facets_facet');
    $original_results = [
      new Result($facet, 1, 1, 5),
    ];

    $facet->setResults($original_results);

    $filtered_results = $this->processor->build($facet, $original_results);

    $this->assertEmpty($filtered_results);
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals([], $config);
  }

}
