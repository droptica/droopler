<?php

namespace Drupal\Tests\tvi\Functional;

use Drupal\Tests\tvi\Functional\TaxonomyViewsIntegratorTestBase;

/**
 * Tests TaxonomyViewsIntegrator and various term configurations.
 *
 * @group tvi
 */
class TaxonomyViewsIntegratorTest extends TaxonomyViewsIntegratorTestBase {

  public function setUp() {
    parent::setUp();

    $permissions = [
      'access content',
      'administer site configuration',
      'administer views',
      'administer nodes',
      'administer taxonomy',
      'access administration pages',
      'define view for vocabulary ' . $this->vocabulary1->id(),
      'define view for terms in ' . $this->vocabulary1->id(),
      'define view for vocabulary ' . $this->vocabulary2->id(),
      'define view for terms in ' . $this->vocabulary2->id(),
    ];

    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the user can see the form to set Taxonomy Views Integrator settings on a given term.
   */
  public function testTaxonomyHasTaxonomyViewsIntegratorSettingForm() {
    $this->drupalGet('taxonomy/term/' . $this->term1->id() . '/edit');
    $this->assertSession()->responseContains('Taxonomy Views Integrator Settings');
    $this->assertSession()->responseContains('The default view to use for this term page.');
  }

  /**
   * Test that the right view loads when visiting a term that has configured
   * Taxonomy Views Integrator for that term.
   */
  public function testViewLoadsFromTermSettings() {
    // For term1, we should see the page_1 display of tvi_test_view and not the default taxonomy view.
    $this->drupalGet('taxonomy/term/' . $this->term1->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // For term2, we should see the page_2 display of tvi_test_view and not the default taxonomy view.
    $this->drupalGet('taxonomy/term/' . $this->term2->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // For term3, we should see the page_1 display of tvi_test_view and not the default taxonomy view.
    $this->drupalGet('taxonomy/term/' . $this->term3->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // For term4, we should see the page_1 display of tvi_test_view and not the default taxonomy view.
    $this->drupalGet('taxonomy/term/' . $this->term4->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // For term5, we should see page_1 display, it should override term2 settings.
    $this->drupalGet('taxonomy/term/' . $this->term5->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // For term6, it should inherit term2 settings.
    $this->drupalGet('taxonomy/term/' . $this->term6->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // For term7, it should inherit the vocab settings.
    $this->drupalGet('taxonomy/term/' . $this->term7->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // For term8, we should see the page_1 display of tvi_test_view and not the default taxonomy view.
    $this->drupalGet('taxonomy/term/' . $this->term8->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // For term9, we should only see the default taxonomy view, since it has no associated configuration and the vocab override is disabled.
    $this->drupalGet('taxonomy/term/' . $this->term9->id());
    $this->assertSession()->responseNotContains('TVI Foo View');
    $this->assertSession()->responseContains($this->term9->label());

    // For term10,  we should see the page_2 display of tvi_test_view and not the default taxonomy view.
    $this->drupalGet('taxonomy/term/' . $this->term10->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // For term11, it should inherit term10 settings.
    $this->drupalGet('taxonomy/term/' . $this->term11->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // For term12, we should see the page_1 display of tvi_test_view
    $this->drupalGet('taxonomy/term/' . $this->term12->id());
    $this->assertSession()->responseContains('TVI Foo View');
  }
}