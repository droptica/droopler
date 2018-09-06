<?php

namespace Drupal\paragraphs_library\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\paragraphs_library\Entity\LibraryItem;
use Drupal\paragraphs_library\LibraryItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LibraryItemController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LibraryItemController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager) {
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')
      );
  }

  /**
   * Generates an overview table of older revisions of a library item.
   *
   * @param \Drupal\paragraphs_library\Entity\LibraryItem $paragraphs_library_item
   *   A library item object.
   *
   * @return array
   *   An array as expected by drupal_render()
   */
  public function revisionOverview(LibraryItem $paragraphs_library_item) {
    $label = $paragraphs_library_item->get('label')->value;
    $build['#title'] = $this->t('Revisions for %label', ['%label' => $label]);

    $header = [$this->t('Revision'), $this->t('Operations')];

    $rows = [];
    $default_revision = $paragraphs_library_item->getRevisionId();
    $storage = $this->entityTypeManager->getStorage('paragraphs_library_item');
    foreach ($this->getRevisionIds($paragraphs_library_item) as $revision_id) {
      $revision = $storage->loadRevision($revision_id);
      $date = $this->dateFormatter->format($revision->get('revision_created')->value, 'short');
      $row = [];
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];
      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }}: {{ label }} by {{ author }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $revision->toLink($date, $revision->isDefaultRevision() ? 'canonical' : 'revision')->toString(),
            'label' => $revision->label(),
            'author' => \Drupal::service('renderer')->renderPlain($username),
            'message' => ['#markup' => $revision->get('revision_log')->value, '#allowed_tags' => Xss::getHtmlTagList()],
          ],
        ],
      ];
      $row[] = $column;
      if ($revision_id == $default_revision) {
        $row[] = [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
        ];

        $rows[] = [
          'data' => $row,
          'class' => ['revision-current'],
        ];
      }
      else {
        $links = [
          'revert' => [
            'title' => $revision_id < $paragraphs_library_item->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
            'url' => $revision->toUrl('revision-revert')
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => $revision->toUrl('revision-delete')
          ],
        ];
        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];
        $rows[] = $row;
      }

    }

    $build['paragraphs_library_item_revisions_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Page title callback for library item revision.
   *
   * @param int $paragraphs_library_item_revision
   *   The library item revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($paragraphs_library_item_revision) {
    $library_item = $this->entityTypeManager->getStorage('paragraphs_library_item')
      ->loadRevision($paragraphs_library_item_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $library_item->label(),
      '%date' => $this->dateFormatter->format($library_item->getChangedTime()),
    ]);
  }

  /**
   *  Display a library item revision.
   *
   * @param int $paragraphs_library_item_revision
   *   The library item revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($paragraphs_library_item_revision) {
    $library_item = $this->entityTypeManager->getStorage('paragraphs_library_item')
      ->loadRevision($paragraphs_library_item_revision);
    $view = $this->entityTypeManager->getViewBuilder('paragraphs_library_item')
      ->view($library_item);
    return $view;
  }

  /**
   * Gets a list of library item revision IDs for a specific library item.
   *
   * @param \Drupal\paragraphs_library\LibraryItemInterface $library_item
   *   Library item entity.
   *
   * @return int[]
   *   Library item revision IDs (in descending order)
   */
  protected function getRevisionIds(LibraryItemInterface $library_item) {
    $result = $this->entityTypeManager->getStorage('paragraphs_library_item')->getQuery()
      ->allRevisions()
      ->condition('id', $library_item->id())
      ->sort($library_item->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }
}
