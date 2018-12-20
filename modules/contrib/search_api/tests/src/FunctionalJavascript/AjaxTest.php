<?php

namespace Drupal\Tests\search_api\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_test\PluginTestTrait;

/**
 * Tests AJAX functionality in the Search API module.
 *
 * @group search_api
 */
class AjaxTest extends JavascriptTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'search_api',
    'search_api_db',
    'search_api_test',
    'field_ui',
    'link',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an article node type, if not already present.
    if (!NodeType::load('article')) {
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }

    // Create a page node type, if not already present.
    if (!NodeType::load('page')) {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Page',
      ]);
    }

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    $permissions = [
      'administer search_api',
      'access administration pages',
      'administer nodes',
      'bypass node access',
      'administer content types',
      'administer node fields',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
  }

  /**
   * Tests AJAX functionality in the Search API module.
   */
  public function testAjax() {
    $this->checkServerBackendAjax();
    $this->checkIndexDatasourceAjax();
  }

  /**
   * Tests AJAX display of backend config forms when a new backend is selected.
   */
  protected function checkServerBackendAjax() {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/config/search/search-api/add-server');
    $assert_session->pageTextContains('Database');
    $assert_session->pageTextContains('Test backend');

    $backend_config = '[data-drupal-selector="edit-backend-config"]';
    $assert_session->elementNotExists('css', "$backend_config input");
    $assert_session->elementNotExists('css', "$backend_config select");
    $assert_session->elementNotExists('css', "$backend_config button");

    $this->click('input.form-radio[name="backend"][value="search_api_db"]');

    $element = $assert_session->waitForElement('css', "$backend_config input");
    $this->assertNotEmpty($element);
    $assert_session->elementExists('css', "$backend_config select[name=\"backend_config[min_chars]\"]");
  }

  /**
   * Tests AJAX display of backend config forms when a new backend is selected.
   */
  protected function checkIndexDatasourceAjax() {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/config/search/search-api/add-index');
    $assert_session->pageTextContains('Datasources');
    $assert_session->pageTextContains('Content');

    $datasource_config = '[data-drupal-selector="edit-datasource-configs"]';
    $assert_session->elementNotExists('css', "$datasource_config input");
    $assert_session->elementNotExists('css', "$datasource_config select");
    $assert_session->elementNotExists('css', "$datasource_config button");

    $this->click('input.form-checkbox[name="datasources[entity:node]"]');

    $element = $assert_session->waitForElement('css', "$datasource_config input");
    $this->assertNotEmpty($element);
    $assert_session->elementExists('css', "$datasource_config [name=\"datasource_configs[entity:node][bundles][default]\"]");

    $field = $assert_session
      ->elementExists('css', 'input[data-drupal-selector="edit-name"]');
    $field->setValue('Test index');
    $element = $assert_session->waitForElementVisible('css', '.field-suffix .machine-name-value');
    $this->assertNotEmpty($element);
    $this->assertEquals('test_index', $element->getText());

    $this->click('[data-drupal-selector="edit-actions-submit"]');
  }

  /**
   * Tests JS-based display of processors when they are added.
   */
  protected function checkIndexProcessorJavascript() {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/config/search/search-api/index/test_index/processors');
    $assert_session->pageTextContains('Highlight');
    $assert_session->checkboxNotChecked('status[highlight]');

    $postprocess = '[data-drupal-selector="edit-weights-postprocess-query"]';
    $postprocess_highlight = $postprocess . ' [data-drupal-selector="edit-weights-postprocess-query-order-highlight"]';
    $element = $assert_session->elementExists('css', $postprocess_highlight);
    $this->assertFalse($element->isVisible());

    $settings = '[data-drupal-selector="edit-processor-settings"]';
    $highlight_settings = $settings . ' a[href="#edit-processors-html-filter-settings"]';
    $element = $assert_session->elementExists('css', $highlight_settings);
    $this->assertFalse($element->isVisible());

    $this->click('input.form-checkbox[name="status[highlight]"]');

    $element = $assert_session->elementExists('css', $postprocess_highlight);
    $this->assertFalse($element->isVisible());

    $element = $assert_session->elementExists('css', $highlight_settings);
    $this->assertFalse($element->isVisible());
  }

}
