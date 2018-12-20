<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\SearchApiException;

/**
 * Defines a contextual filter for displaying a "More Like This" list.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_more_like_this")
 */
class SearchApiMoreLikeThis extends SearchApiStandard {

  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    unset($options['break_phrase']);
    unset($options['not']);
    $options['fields'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['break_phrase']);
    unset($form['not']);

    $index = Index::load(substr($this->table, 17));
    $fields = [];
    foreach ($index->getFields() as $key => $field) {
      $fields[$key] = $field->getLabel();
    }

    if ($fields) {
      $form['fields'] = [
        '#type' => 'select',
        '#title' => $this->t('Fields for similarity'),
        '#description' => $this->t('Select the fields that will be used for finding similar content. If no fields are selected, all available fields will be used.'),
        '#options' => $fields,
        '#size' => min(8, count($fields)),
        '#multiple' => TRUE,
        '#default_value' => $this->options['fields'],
      ];
    }
    else {
      $form['fields'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    try {
      $server = $this->query->getIndex()->getServerInstance();
      if (!$server->supportsFeature('search_api_mlt')) {
        $backend_id = $server->getBackendId();
        $this->getLogger()->error('The search backend "@backend_id" does not offer "More like this" functionality.',
          ['@backend_id' => $backend_id]);
        $this->query->abort();
        return;
      }
      $fields = isset($this->options['fields']) ? $this->options['fields'] : [];
      if (!$fields) {
        $fields = array_keys($this->query->getIndex()->getFields());
      }
      $mlt = [
        'id' => $this->argument,
        'fields' => $fields,
      ];
      $this->query->getSearchApiQuery()->setOption('search_api_mlt', $mlt);
    }
    catch (SearchApiException $e) {
      $this->query->abort($e->getMessage());
    }
  }

}
