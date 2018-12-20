<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\user\Entity\User;

/**
 * Provides a processor that transforms the results to show the user's name.
 *
 * @FacetsProcessor(
 *   id = "uid_to_username_callback",
 *   label = @Translation("Transform UID to user name"),
 *   description = @Translation("Display the user name if the source field is a user ID."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class UidToUserNameCallbackProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $usernames = [];

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $result) {
      /** @var \Drupal\user\Entity\User $user */
      if (($user = User::load($result->getRawValue())) !== NULL) {
        $result->setDisplayValue($user->getDisplayName());
        $usernames[] = $result;
      }
    }

    return $usernames;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    $data_definition = $facet->getDataDefinition();
    if ($data_definition->getDataType() === 'entity_reference' &&
      $data_definition->getTargetDefinition()->getConstraint('EntityType') === "user") {
      return TRUE;
    }

    if (!($data_definition instanceof ComplexDataDefinitionInterface)) {
      return FALSE;
    }

    $property_definitions = $data_definition->getPropertyDefinitions();
    foreach ($property_definitions as $definition) {
      if (
        $definition instanceof DataReferenceDefinitionInterface &&
        $definition->getDataType() === 'entity_reference' &&
        $definition->getTargetDefinition()->getConstraint('EntityType') === "user"
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
