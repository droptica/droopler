<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\search_api\IndexBatchHelper;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Task\IndexTaskManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for indexing, clearing, etc., an index.
 */
class IndexStatusForm extends FormBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an IndexStatusForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $messenger = $container->get('messenger');

    return new static($messenger);
  }

  /**
   * The index task manager.
   *
   * @var \Drupal\search_api\Task\IndexTaskManagerInterface|null
   */
  protected $indexTaskManager;

  /**
   * Retrieves the index task manager.
   *
   * @return \Drupal\search_api\Task\IndexTaskManagerInterface
   *   The index task manager.
   */
  public function getIndexTaskManager() {
    return $this->indexTaskManager ?: \Drupal::service('search_api.index_task_manager');
  }

  /**
   * Sets the index task manager.
   *
   * @param \Drupal\search_api\Task\IndexTaskManagerInterface $index_task_manager
   *   The new index task manager.
   *
   * @return $this
   */
  public function setIndexTaskManager(IndexTaskManagerInterface $index_task_manager) {
    $this->indexTaskManager = $index_task_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_index_status';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, IndexInterface $index = NULL) {
    if (!isset($index)) {
      return [];
    }

    $form['#index'] = $index;

    if ($index->status() && $index->hasValidTracker()) {
      $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

      if (!$this->getIndexTaskManager()->isTrackingComplete($index)) {
        $form['tracking'] = [
          '#type' => 'details',
          '#title' => $this->t('Track items for index'),
          '#description' => $this->t('Not all items have been tracked for this index. This means the displayed index status is incomplete and not all items will currently be indexed.'),
          '#open' => TRUE,
          '#attributes' => [
            'class' => ['container-inline'],
          ],
        ];
        $form['tracking']['index_now'] = [
          '#type' => 'submit',
          '#value' => $this->t('Track now'),
          '#name' => 'track_now',
        ];
      }

      // Add the "Index now" form.
      $form['index'] = [
        '#type' => 'details',
        '#title' => $this->t('Start indexing now'),
        '#open' => TRUE,
        '#attributes' => [
          'class' => ['container-inline'],
        ],
      ];
      $has_remaining_items = ($index->getTrackerInstance()->getRemainingItemsCount() > 0);
      $all_value = $this->t('all', [], ['context' => 'items to index']);
      $limit = [
        '#type' => 'textfield',
        '#default_value' => $all_value,
        '#size' => 4,
        '#attributes' => [
          'class' => ['search-api-limit'],
        ],
        '#disabled' => !$has_remaining_items,
      ];
      $batch_size = [
        '#type' => 'textfield',
        '#default_value' => $index->getOption('cron_limit', $this->config('search_api.settings')->get('default_cron_limit')),
        '#size' => 4,
        '#attributes' => [
          'class' => ['search-api-batch-size'],
        ],
        '#disabled' => !$has_remaining_items,
      ];
      // Here it gets complicated. We want to build a sentence from the form
      // input elements, but to translate that we have to make the two form
      // elements (for limit and batch size) pseudo-variables in the $this->t()
      // call.
      // Since we can't pass them directly, we split the translated sentence
      // (which still has the two tokens), figure out their order and then put
      // the pieces together again using the form elements' #prefix and #suffix
      // properties.
      $sentence = preg_split('/@(limit|batch_size)/', $this->t('Index @limit items in batches of @batch_size items'), -1, PREG_SPLIT_DELIM_CAPTURE);
      // Check if the sentence contains the expected amount of parts.
      if (count($sentence) === 5) {
        $first = $sentence[1];
        $form['index'][$first] = ${$first};
        $form['index'][$first]['#prefix'] = $sentence[0];
        $form['index'][$first]['#suffix'] = $sentence[2];
        $second = $sentence[3];
        $form['index'][$second] = ${$second};
        $form['index'][$second]['#suffix'] = "{$sentence[4]} ";
      }
      else {
        // Sentence is broken. Use fallback method instead.
        $limit['#title'] = $this->t('Number of items to index');
        $form['index']['limit'] = $limit;
        $batch_size['#title'] = $this->t('Number of items per batch run');
        $form['index']['batch_size'] = $batch_size;
      }
      // Add the value "all" so it can be used by the validation.
      $form['index']['all'] = [
        '#type' => 'value',
        '#value' => $all_value,
      ];
      $form['index']['index_now'] = [
        '#type' => 'submit',
        '#value' => $this->t('Index now'),
        '#disabled' => !$has_remaining_items,
        '#name' => 'index_now',
      ];

      // Add actions for reindexing and for clearing the index.
      $form['actions']['#type'] = 'actions';
      $form['actions']['reindex'] = [
        '#type' => 'submit',
        '#value' => $this->t('Queue all items for reindexing'),
        '#name' => 'reindex',
        '#button_type' => 'danger',
      ];
      $form['actions']['clear'] = [
        '#type' => 'submit',
        '#value' => $this->t('Clear all indexed data'),
        '#name' => 'clear',
        '#button_type' => 'danger',
      ];
      $form['actions']['rebuild_tracker'] = [
        '#type' => 'submit',
        '#value' => $this->t('Rebuild tracking information'),
        '#name' => 'rebuild_tracker',
        '#button_type' => 'danger',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Only the "Index now" action needs any validation.
    if ($form_state->getTriggeringElement()['#name'] === 'index_now') {
      $values = $form_state->getValues();
      // Get the translated "all" value and lowercase it for comparison.
      $all_value = mb_strtolower($values['all']);

      foreach (['limit', 'batch_size'] as $field) {
        // Trim and lowercase the value so we correctly identify "all" values,
        // even if not matching exactly.
        $value = mb_strtolower(trim($values[$field]));

        if ($value === $all_value) {
          $value = -1;
        }
        elseif (!$value || !is_numeric($value) || ((int) $value) != $value) {
          $form_state->setErrorByName($field, $this->t('Enter a non-zero integer. Use "-1" or "@all" for "all items".', ['@all' => $values['all']]));
        }
        else {
          $value = (int) $value;
        }

        $form_state->setValue($field, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $form['#index'];

    switch ($form_state->getTriggeringElement()['#name']) {
      case 'index_now':
        $values = $form_state->getValues();
        try {
          IndexBatchHelper::setStringTranslation($this->getStringTranslation());
          IndexBatchHelper::create($index, $values['batch_size'], $values['limit']);
        }
        catch (SearchApiException $e) {
          $this->messenger->addWarning($this->t('Failed to create a batch, please check the batch size and limit.'));
        }
        break;

      case 'reindex':
        $form_state->setRedirect('entity.search_api_index.reindex', ['search_api_index' => $index->id()]);
        break;

      case 'clear':
        $form_state->setRedirect('entity.search_api_index.clear', ['search_api_index' => $index->id()]);
        break;

      case 'rebuild_tracker':
        $form_state->setRedirect('entity.search_api_index.rebuild_tracker', ['search_api_index' => $index->id()]);
        break;

      case 'track_now':
        $this->getIndexTaskManager()->addItemsBatch($index);
        break;
    }
  }

}
