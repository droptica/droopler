<?php

namespace Drupal\entity_reference_revisions\Plugin\Derivative;

use Drupal\entity_reference_revisions\Plugin\migrate\destination\EntityReferenceRevisions;
use Drupal\migrate\Plugin\Derivative\MigrateEntityRevision;

/**
 * Class MigrateEntityReferenceRevisions
 */
class MigrateEntityReferenceRevisions extends MigrateEntityRevision  {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    foreach ($this->entityDefinitions as $entityType => $entityInfo) {
      if ($entityInfo->getKey('revision')) {
        $this->derivatives[$entityType] = [
          'id' => "entity_reference_revisions:$entityType",
          'class' => EntityReferenceRevisions::class,
          'requirements_met' => 1,
          'provider' => $entityInfo->getProvider(),
        ];
      }
    }
    return $this->derivatives;
  }

}
