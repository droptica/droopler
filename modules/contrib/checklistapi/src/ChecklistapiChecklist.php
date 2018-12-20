<?php

namespace Drupal\checklistapi;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Defines the checklist class.
 */
class ChecklistapiChecklist {

  /**
   * The configuration key for saved progress.
   */
  const PROGRESS_CONFIG_KEY = 'progress';

  /**
   * The checklist ID.
   *
   * @var string
   */
  public $id;

  /**
   * The checklist title.
   *
   * @var string
   */
  public $title;

  /**
   * The menu item description.
   *
   * @var string
   */
  public $description;

  /**
   * The checklist path.
   *
   * @var string
   */
  public $path;

  /**
   * The checklist help.
   *
   * @var string
   */
  public $help;

  /**
   * The name of the menu to put the menu item in.
   *
   * @var string
   */
  public $menuName;

  /**
   * The checklist weight.
   *
   * @var float
   */
  public $weight;

  /**
   * The number of list items in the checklist.
   *
   * @var int
   */
  public $numberOfItems = 0;

  /**
   * The checklist groups and items.
   *
   * @var array
   */
  public $items = [];

  /**
   * The saved progress data.
   *
   * @var array
   */
  public $savedProgress;

  /**
   * The configuration object for saving progress.
   *
   * @var \Drupal\Core\Config\Config
   */
  public $config;

  /**
   * Constructs a ChecklistapiChecklist object.
   *
   * @param array $definition
   *   A checklist definition, as returned by checklistapi_get_checklist_info().
   */
  public function __construct(array $definition) {
    foreach (Element::children($definition) as $group_key) {
      $this->numberOfItems += count(Element::children($definition[$group_key]));
      $this->items[$group_key] = $definition[$group_key];
      unset($definition[$group_key]);
    }
    foreach ($definition as $property_key => $value) {
      $property_name = checklistapi_strtolowercamel(Unicode::substr($property_key, 1));
      $this->$property_name = $value;
    }

    $this->config = \Drupal::configFactory()->getEditable("checklistapi.progress.{$this->id}");
    $this->savedProgress = $this->config->get($this::PROGRESS_CONFIG_KEY);
  }

  /**
   * Clears the saved progress for the checklist.
   *
   * Deletes the Drupal configuration object containing the checklist's saved
   * progress.
   */
  public function clearSavedProgress() {
    $this->config->delete();

    drupal_set_message(t('%title saved progress has been cleared.', [
      '%title' => $this->title,
    ]));
  }

  /**
   * Gets the total number of completed items.
   *
   * @return int
   *   The number of completed items.
   */
  public function getNumberCompleted() {
    return (!empty($this->savedProgress['#completed_items'])) ? $this->savedProgress['#completed_items'] : 0;
  }

  /**
   * Gets the total number of items.
   *
   * @return int
   *   The number of items.
   */
  public function getNumberOfItems() {
    return $this->numberOfItems;
  }

  /**
   * Gets the name of the last user to update the checklist.
   *
   * @return string
   *   The themed name of the last user to update the checklist, or 'n/a' if
   *   there is no record of such a user.
   */
  public function getLastUpdatedUser() {
    if (isset($this->savedProgress['#changed_by'])) {
      return User::load($this->savedProgress['#changed_by'])
        ->getUsername();
    }
    else {
      return t('n/a');
    }
  }

  /**
   * Gets the last updated date.
   *
   * @return string
   *   The last updated date formatted with format_date(), or 'n/a' if there is
   *   no saved progress.
   */
  public function getLastUpdatedDate() {
    return (!empty($this->savedProgress['#changed'])) ? format_date($this->savedProgress['#changed']) : t('n/a');
  }

  /**
   * Gets the percentage of items complete.
   *
   * @return float
   *   The percent complete.
   */
  public function getPercentComplete() {
    if ($this->getNumberOfItems() == 0) {
      return 100;
    }
    return ($this->getNumberCompleted() / $this->getNumberOfItems()) * 100;
  }

  /**
   * Gets the route name.
   *
   * @return string
   *   The route name.
   */
  public function getRouteName() {
    return "checklistapi.checklists.{$this->id}";
  }

  /**
   * Gets the checklist form URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to the checklist form.
   */
  public function getUrl() {
    return new Url($this->getRouteName());
  }

  /**
   * Determines whether the checklist has saved progress.
   *
   * @return bool
   *   TRUE if the checklist has saved progress, or FALSE if it doesn't.
   */
  public function hasSavedProgress() {
    return (bool) $this->config->get($this::PROGRESS_CONFIG_KEY);
  }

  /**
   * Saves checklist progress.
   *
   * @param array $values
   *   A multidimensional array of form state checklist values.
   *
   * @see checklistapi_checklist_form_submit()
   */
  public function saveProgress(array $values) {
    $user = \Drupal::currentUser();

    $time = time();
    $num_changed_items = 0;
    $progress = [
      '#changed' => $time,
      '#changed_by' => $user->id(),
      '#completed_items' => 0,
      '#items' => [],
    ];

    // Loop through groups.
    foreach ($values as $group_key => $group) {
      if (!is_array($group)) {
        continue;
      }
      // Loop through items.
      foreach ($group as $item_key => $item) {
        $definition = checklistapi_get_checklist_info($this->id);
        if (!in_array($item_key, array_keys($definition[$group_key]))) {
          // This item wasn't in the checklist definition. Don't include it with
          // saved progress.
          continue;
        }
        $old_item = (!empty($this->savedProgress['#items'][$item_key])) ? $this->savedProgress['#items'][$item_key] : 0;
        if ($item == 1) {
          // Item is checked.
          $progress['#completed_items']++;
          if ($old_item) {
            // Item was previously checked. Use saved value.
            $new_item = $old_item;
          }
          else {
            // Item is newly checked. Set new value.
            $new_item = [
              '#completed' => $time,
              '#uid' => $user->id(),
            ];
            $num_changed_items++;
          }
          $progress['#items'][$item_key] = $new_item;
        }
        else {
          // Item is unchecked.
          if ($old_item) {
            // Item was previously checked.
            $num_changed_items++;
          }
        }
      }
    }

    // Sort array elements alphabetically so changes to the order of items in
    // checklist definitions over time don't affect the order of elements in the
    // saved progress details. This reduces non-substantive changes to
    // configuration files.
    ksort($progress);

    $this->config->set($this::PROGRESS_CONFIG_KEY, $progress)->save();
    drupal_set_message(\Drupal::translation()->formatPlural(
      $num_changed_items,
      '%title progress has been saved. 1 item changed.',
      '%title progress has been saved. @count items changed.',
      ['%title' => $this->title]
    ));
  }

  /**
   * Determines whether the current user has access to the checklist.
   *
   * @param string $operation
   *   (optional) The operation to test access for. Possible values are "view",
   *   "edit", and "any". Defaults to "any".
   *
   * @return bool
   *   Returns TRUE if the user has access, or FALSE if not.
   */
  public function userHasAccess($operation = 'any') {
    return checklistapi_checklist_access($this->id, $operation);
  }

}
