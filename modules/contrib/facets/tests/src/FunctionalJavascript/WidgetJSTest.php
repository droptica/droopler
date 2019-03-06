<?php

namespace Drupal\Tests\facets\FunctionalJavascript;

use Drupal\facets\Entity\Facet;

/**
 * Tests for the JS that transforms widgets into form elements.
 *
 * @group facets
 */
class WidgetJSTest extends JsBase {

  /**
   * Tests JS interactions in the admin UI.
   */
  public function testAddFacet() {
    $this->drupalGet('admin/config/search/facets/add-facet');

    $page = $this->getSession()->getPage();

    // Select one of the options from the facet source dropdown and wait for the
    // result to show.
    $page->selectFieldOption('edit-facet-source-id', 'search_api:views_page__search_api_test_view__page_1');
    $this->getSession()->wait(6000, "jQuery('.facet-source-field-wrapper').length > 0");

    $page->selectFieldOption('facet_source_configs[search_api:views_page__search_api_test_view__page_1][field_identifier]', 'type');

    // Check that after choosing the field, the name is already filled in.
    $field_value = $this->getSession()->getPage()->findField('edit-name')->getValue();
    $this->assertEquals('Type', $field_value);
  }

  /**
   * Tests show more / less links.
   */
  public function testLinksShowMoreLess() {
    $facet_storage = \Drupal::entityTypeManager()->getStorage('facets_facet');
    $id = 'owl';

    // Create and save a facet with a checkbox widget on the 'type' field.
    $facet_storage->create([
      'id' => $id,
      'name' => strtoupper($id),
      'url_alias' => $id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'field_identifier' => 'type',
      'empty_behavior' => ['behavior' => 'none'],
      'weight' => 1,
      'widget' => [
        'type' => 'links',
        'config' => [
          'show_numbers' => TRUE,
          'soft_limit' => 1,
          'soft_limit_settings' => [
            'show_less_label' => 'Show less',
            'show_more_label' => 'Show more',
          ],
        ],
      ],
      'processor_configs' => [
        'url_processor_handler' => [
          'processor_id' => 'url_processor_handler',
          'weights' => ['pre_query' => -10, 'build' => -10],
          'settings' => [],
        ],
      ],
    ])->save();
    $this->createBlock($id);

    // Go to the views page.
    $this->drupalGet('search-api-test-fulltext');

    // Make sure the block is shown on the page.
    $page = $this->getSession()->getPage();
    $block = $page->findById('block-owl-block');
    $block->isVisible();

    // Make sure the show more / show less links are shown.
    $this->assertSession()->linkExists('Show more');

    // Change the link label of show more into "Moar Llamas".
    $facet = Facet::load('owl');
    $facet->setWidget('links', [
      'show_numbers' => TRUE,
      'soft_limit' => 1,
      'soft_limit_settings' => [
        'show_less_label' => 'Show less',
        'show_more_label' => 'Moar Llamas',
      ],
    ]);
    $facet->save();

    // Check that the new configuration is used now.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->linkNotExists('Show more');
    $this->assertSession()->linkExists('Moar Llamas');
  }

  /**
   * Tests checkbox widget.
   */
  public function testCheckboxWidget() {
    $facet_storage = \Drupal::entityTypeManager()->getStorage('facets_facet');
    $id = 'llama';

    // Create and save a facet with a checkbox widget on the 'type' field.
    $facet_storage->create([
      'id' => $id,
      'name' => strtoupper($id),
      'url_alias' => $id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'field_identifier' => 'type',
      'empty_behavior' => ['behavior' => 'none'],
      'widget' => [
        'type' => 'checkbox',
        'config' => [
          'show_numbers' => TRUE,
        ],
      ],
      'processor_configs' => [
        'url_processor_handler' => [
          'processor_id' => 'url_processor_handler',
          'weights' => ['pre_query' => -10, 'build' => -10],
          'settings' => [],
        ],
      ],
    ])->save();
    $this->createBlock($id);

    // Go to the views page.
    $this->drupalGet('search-api-test-fulltext');

    // Make sure the block is shown on the page.
    $page = $this->getSession()->getPage();
    $block = $page->findById('block-llama-block');
    $block->isVisible();

    // Narrow the context to the block that shows the facet and get objects that
    // contain the <li>-html elements.
    $list_items = $block->findAll('css', 'li');

    $this->assertCount(2, $list_items);

    /** @var \Behat\Mink\Element\NodeElement $list_item */
    foreach ($list_items as $list_item) {
      $this->assertEquals('li', $list_item->getTagName());
      $list_item->find('css', 'label')->isVisible();
      $list_item->find('css', 'input[type="checkbox"]')->isVisible();
    }

    // Check that clicking one of the checkboxes changes the url.
    $checkbox = $page->findField('item (3)');
    $checkbox->click();
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertTrue(strpos($current_url, 'search-api-test-fulltext?f%5B0%5D=llama%3Aitem') !== FALSE);
  }

  /**
   * Tests dropdown widget.
   */
  public function testDropdownWidget() {
    $facet_storage = \Drupal::entityTypeManager()->getStorage('facets_facet');
    $id = 'llama';

    // Create and save a facet with a checkbox widget on the 'type' field.
    $facet_storage->create([
      'id' => $id,
      'name' => strtoupper($id),
      'url_alias' => $id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'field_identifier' => 'type',
      'empty_behavior' => ['behavior' => 'none'],
      'widget' => [
        'type' => 'dropdown',
        'config' => [
          'show_numbers' => TRUE,
          'default_option_label' => '- All -',
        ],
      ],
      'processor_configs' => [
        'url_processor_handler' => [
          'processor_id' => 'url_processor_handler',
          'weights' => ['pre_query' => -10, 'build' => -10],
          'settings' => [],
        ],
      ],
    ])->save();
    $this->createBlock($id);

    // Go to the views page.
    $this->drupalGet('search-api-test-fulltext');

    // Make sure the block is shown on the page.
    $page = $this->getSession()->getPage();
    $block = $page->findById('block-llama-block');
    $block->isVisible();

    // Narrow the context to the block that shows the facet and get the
    // <select>-html element.
    $dropdown = $block->find('css', 'select');
    $dropdown->isVisible();

    $block->find('css', '.item-list__dropdown');
    $block->isVisible();

    $options = $dropdown->findAll('css', 'option');
    $this->assertCount(3, $options);

    // Check the default option.
    $default = $options[0];
    $default->isSelected();
    $this->assertEquals('- All -', $default->getText());

    // Check that selecting one of the options changes the url.
    $dropdown->selectOption('item (3)');
    $this->getSession()->wait(6000, "window.location.search != ''");
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertTrue(strpos($current_url, 'search-api-test-fulltext?f%5B0%5D=llama%3Aitem') !== FALSE);
  }

}
