<?php

declare(strict_types=1);

namespace Drupal\d_block_field;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

/**
 * Manages block plugins exposed through the d_block_field field type.
 */
class BlockFieldManager implements BlockFieldManagerInterface {

  public function __construct(
    protected readonly BlockManagerInterface $blockManager,
    protected readonly ContextRepositoryInterface $contextRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getBlockDefinitions(): array {
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    return $this->blockManager->getSortedDefinitions($definitions);
  }

}
