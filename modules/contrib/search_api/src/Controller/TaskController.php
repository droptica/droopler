<?php

namespace Drupal\search_api\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\search_api\Task\TaskManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for task-related routes.
 */
class TaskController extends ControllerBase {

  /**
   * The server task manager.
   *
   * @var \Drupal\search_api\Task\TaskManagerInterface|null
   */
  protected $taskManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $controller */
    $controller = parent::create($container);

    $controller->setTaskManager($container->get('search_api.task_manager'));

    return $controller;
  }

  /**
   * Retrieves the task manager.
   *
   * @return \Drupal\search_api\Task\TaskManagerInterface
   *   The task manager.
   */
  public function getTaskManager() {
    return $this->taskManager ?: \Drupal::service('search_api._task_manager');
  }

  /**
   * Sets the task manager.
   *
   * @param \Drupal\search_api\Task\TaskManagerInterface $task_manager
   *   The new task manager.
   *
   * @return $this
   */
  public function setTaskManager(TaskManagerInterface $task_manager) {
    $this->taskManager = $task_manager;
    return $this;
  }

  /**
   * Executes all pending tasks.
   */
  public function executeTasks() {
    $this->getTaskManager()->setTasksBatch();
    return batch_process(Url::fromRoute('search_api.overview'));
  }

  /**
   * Checks access for executing pending tasks.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function executeTasksAccess(AccountInterface $account) {
    // @todo Once we depend on Drupal 8.7+, check whether this can't just use
    //   the "search_api_task_list" cache tag instead. (See #2722237.)
    if ($this->taskManager->getTasksCount()) {
      return AccessResult::allowedIfHasPermission($account, 'administer search_api')
        ->setCacheMaxAge(0);
    }
    return AccessResult::forbidden()->setCacheMaxAge(0);
  }

}
