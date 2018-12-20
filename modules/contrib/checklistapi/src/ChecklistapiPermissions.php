<?php

namespace Drupal\checklistapi;

/**
 * Defines a class containing permission callbacks.
 */
class ChecklistapiPermissions {

  /**
   * Constructs a ChecklistapiPermissions object.
   */
  public function __construct() {
    $this->editPermissionDescription = t('Check and uncheck list items and save changes, or clear saved progress.');
    $this->viewPermissionDescription = t('Read-only access: View list items and saved progress.');
  }

  /**
   * Returns an array of universal permissions.
   *
   * @return array
   *   An array of permission details.
   */
  public function universalPermissions() {
    $perms['view checklistapi checklists report'] = [
      'title' => t('View the Checklists report'),
    ];
    $perms['view any checklistapi checklist'] = [
      'title' => t('View any checklist'),
      'description' => $this->viewPermissionDescription,
    ];
    $perms['edit any checklistapi checklist'] = [
      'title' => t('Edit any checklist'),
      'description' => $this->editPermissionDescription,
    ];
    return $perms;
  }

  /**
   * Returns an array of per checklist permissions.
   *
   * @return array
   *   An array of permission details.
   */
  public function perChecklistPermissions() {
    $perms = [];

    // Per checklist permissions.
    foreach (checklistapi_get_checklist_info() as $id => $definition) {
      $checklist = checklistapi_checklist_load($id);

      if (!$checklist) {
        continue;
      }

      $title = $checklist->title;
      $perms["view {$id} checklistapi checklist"] = [
        'title' => t('View the @name checklist', ['@name' => $title]),
        'description' => $this->viewPermissionDescription,
      ];
      $perms["edit {$id} checklistapi checklist"] = [
        'title' => t('Edit the @name checklist', ['@name' => $title]),
        'description' => $this->editPermissionDescription,
      ];
    }

    return $perms;
  }

}
