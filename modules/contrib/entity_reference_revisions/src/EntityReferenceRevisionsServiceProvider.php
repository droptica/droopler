<?php

namespace Drupal\entity_reference_revisions;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Entity Reference Revisions.
 */
class EntityReferenceRevisionsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['hal'])) {
      // Hal module is enabled, add our new normalizer for entity reference
      // revision items.
      $service_definition = new Definition('Drupal\entity_reference_revisions\Normalizer\EntityReferenceRevisionItemNormalizer', array(
        new Reference('hal.link_manager'),
        new Reference('serializer.entity_resolver'),
      ));
      // The priority must be higher than that of
      // serializer.normalizer.entity_reference_item.hal in
      // hal.services.yml.
      $service_definition->addTag('normalizer', array('priority' => 20));
      $container->setDefinition('serializer.normalizer.entity_reference_revision_item', $service_definition);
    }
  }

}
