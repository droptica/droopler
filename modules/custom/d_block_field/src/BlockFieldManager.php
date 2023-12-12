<?php

declare(strict_types = 1);

namespace Drupal\d_block_field;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

/**
 * Defines a service that manages block plugins for the block field.
 */
class BlockFieldManager implements BlockFieldManagerInterface {

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Constructs a new BlockFieldManager.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   */
  public function __construct(BlockManagerInterface $block_manager, ContextRepositoryInterface $context_repository) {
    $this->blockManager = $block_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockDefinitions() {
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    return $this->blockManager->getSortedDefinitions($definitions);
  }

}
