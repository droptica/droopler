<?php

namespace Drupal\paragraphs_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the paragraphs_library_item entity type.
 *
 * @see \Drupal\paragraphs_library\Entity\LibraryItem
 */
class LibraryItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $library_item, $operation, AccountInterface $account) {
    if ($operation == 'delete') {
      $usage_data = \Drupal::service('entity_usage.usage')->listSources($library_item);
      if ($usage_data) {
        return AccessResult::forbidden();
      }
    }

    // In case a library item is unpublished, only allow access if a user has
    // administrative permission. Ensure to collect the required cacheability
    // metadata and combine both the published and the referenced access check
    // together, both must allow access if unpublished.
    $access = AccessResult::allowed()->addCacheableDependency($library_item);
    if ($operation === 'view' && !$library_item->isPublished()) {
      $access = $access->andIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()));
    }

    // Allow update access with a specific or admin permission.
    if ($operation === 'update') {
      $access = $access->andIf(AccessResult::allowedIfHasPermissions($account, ['edit paragraph library item', $this->entityType->getAdminPermission()], 'OR'));
    }

    // Only users with admin permission can delete library items.
    if ($operation === 'delete') {
      $access = $access->andIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()));
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    if ($referenced_paragraph = $library_item->paragraphs->entity) {
      // Forward the access check to the referenced paragraph.
      $access = $access->andIf($referenced_paragraph->access($operation, $account, TRUE));
    }
    else {
      $access = $access->andIf(AccessResult::neutral());
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create paragraph library item')->orIf(parent::checkCreateAccess($account, $context, $entity_bundle));
  }

}
