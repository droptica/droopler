<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\facets\Entity\Facet;

/**
 * Shared test methods for facet blocks.
 */
trait BlockTestTrait {

  /**
   * The block entities used by this test.
   *
   * @var \Drupal\block\BlockInterface[]
   */
  protected $blocks;

  /**
   * Add a facet trough the UI.
   *
   * @param string $name
   *   The facet name.
   * @param string $id
   *   The facet id.
   * @param string $field
   *   The facet field.
   * @param string $display_id
   *   The display id.
   * @param string $source
   *   Facet source.
   * @param bool $allowBlockCreation
   *   Automatically create a block.
   */
  protected function createFacet($name, $id, $field = 'type', $display_id = 'page_1', $source = 'views_page__search_api_test_view', $allowBlockCreation = TRUE) {
    $facet_source = "search_api:{$source}__{$display_id}";

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = Facet::create([
      'id' => $id,
      'name' => $name,
      'weight' => 0,
    ]);
    $facet->setFacetSourceId($facet_source);
    $facet->setFieldIdentifier($field);
    $facet->setUrlAlias($id);
    $facet->setWidget('links', ['show_numbers' => TRUE]);
    $facet->addProcessor([
      'processor_id' => 'url_processor_handler',
      'weights' => ['pre_query' => -10, 'build' => -10],
      'settings' => [],
    ]);
    $facet->setEmptyBehavior(['behavior' => 'none']);
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(TRUE);
    $facet->save();

    if ($allowBlockCreation) {
      $this->blocks[$id] = $this->createBlock($id);
    }
  }

  /**
   * Creates a facet block by id.
   *
   * @param string $id
   *   The id of the block.
   *
   * @return \Drupal\block\Entity\Block
   *   The block entity.
   */
  protected function createBlock($id) {
    $block = [
      'region' => 'footer',
      'id' => str_replace('_', '-', $id),
    ];
    return $this->drupalPlaceBlock('facet_block:' . $id, $block);
  }

  /**
   * Deletes a facet block by id.
   *
   * @param string $id
   *   The id of the block.
   */
  protected function deleteBlock($id) {
    // Delete a facet block trough the UI, the text for that link has changed
    // in Drupal::VERSION 8.3.
    $delete_link_title = \Drupal::VERSION >= 8.3 ? 'Remove block' : 'Delete';
    $delete_confirm_form_button_title = \Drupal::VERSION >= 8.3 ? 'Remove' : 'Delete';
    $orig_success_message = \Drupal::VERSION >= 8.3 ? 'The block ' . $this->blocks[$id]->label() . ' has been removed.' : 'The block ' . $this->blocks[$id]->label() . ' has been deleted.';

    $this->drupalGet('admin/structure/block/manage/' . $this->blocks[$id]->id(), ['query' => ['destination' => 'admin']]);
    $this->clickLink($delete_link_title);
    $this->drupalPostForm(NULL, [], $delete_confirm_form_button_title);
    $this->assertSession()->pageTextContains($orig_success_message);
  }

}
