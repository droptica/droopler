<?php

namespace Drupal\Tests\search_api_db_defaults\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\Tests\search_api\Functional\SearchApiBrowserTestBase;

/**
 * Tests the correct installation of the default configs.
 *
 * @group search_api
 */
class IntegrationTest extends SearchApiBrowserTestBase {

  use StringTranslationTrait, CommentTestTrait, EntityReferenceTestTrait;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * A non-admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $authenticatedUser;

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create user with content access permission to see if the view is
    // accessible, and an admin to do the setup.
    $this->authenticatedUser = $this->drupalCreateUser();
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
  }

  /**
   * Tests whether the default search was correctly installed.
   */
  public function testInstallAndDefaultSetupWorking() {
    $this->drupalLogin($this->adminUser);

    // Installation invokes a batch and this breaks it.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    // Install the search_api_db_defaults module.
    $edit_enable = [
      'modules[search_api_db_defaults][enable]' => TRUE,
    ];
    $this->drupalPostForm('admin/modules', $edit_enable, 'Install');

    $this->assertSession()->pageTextContains('Some required modules must be enabled');

    $this->drupalPostForm(NULL, [], 'Continue');

    $this->assertSession()->pageTextContains('2 modules have been enabled: Database Search Defaults, Database Search');

    $this->rebuildContainer();

    $this->drupalPostForm('admin/config/search/search-api/server/default_server/edit', [], 'Save');
    $this->assertSession()->pageTextContains('The server was successfully saved.');

    $server = Server::load('default_server');
    $this->assertTrue($server, 'Server can be loaded');

    $index = Index::load('default_index');
    $this->assertTrue($index, 'Index can be loaded');

    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('search/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogin($this->adminUser);

    $title = 'Test node title';
    $edit = [
      'title[0][value]' => $title,
      'body[0][value]' => 'This is test content for the Search API to index.',
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');

    $this->drupalLogout();
    $this->drupalGet('search/content');
    $this->assertSession()->pageTextContains('Please enter some keywords to search.');
    $this->assertSession()->pageTextNotContains($title);
    $this->assertSession()->responseNotContains('Error message');
    $this->submitForm([], 'Search');
    $this->assertSession()->pageTextNotContains($title);
    $this->assertSession()->responseNotContains('Error message');
    $this->submitForm(['keys' => 'test'], 'Search');
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->responseNotContains('Error message');

    // Uninstall the module.
    $this->drupalLogin($this->adminUser);
    $edit_disable = [
      'uninstall[search_api_db_defaults]' => TRUE,
    ];
    $this->drupalPostForm('admin/modules/uninstall', $edit_disable, 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('search_api_db_defaults'), 'Search API DB Defaults module uninstalled.');

    // Check if the server is found in the Search API admin UI.
    $this->drupalGet('admin/config/search/search-api/server/default_server');
    $this->assertSession()->statusCodeEquals(200);

    // Check if the index is found in the Search API admin UI.
    $this->drupalGet('admin/config/search/search-api/index/default_index');
    $this->assertSession()->statusCodeEquals(200);

    // Check that saving any of the index's config forms works fine.
    foreach (['edit', 'fields', 'processors'] as $tab) {
      $submit = $tab == 'fields' ? 'Save changes' : 'Save';
      $this->drupalGet("admin/config/search/search-api/index/default_index/$tab");
      $this->submitForm([], $submit);
      $this->assertSession()->statusCodeEquals(200);
    }

    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('search/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogin($this->adminUser);

    // Enable the module again. This should fail because the either the index
    // or the server or the view was found.
    $this->drupalPostForm('admin/modules', $edit_enable, 'Install');
    $this->assertSession()->pageTextContains('It looks like the default setup provided by this module already exists on your site. Cannot re-install module.');

    // Delete all the entities that we would fail on if they exist.
    $entities_to_remove = [
      'search_api_index' => 'default_index',
      'search_api_server' => 'default_server',
      'view' => 'search_content',
    ];
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    foreach ($entities_to_remove as $entity_type => $entity_id) {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
      $entity_storage = $entity_type_manager->getStorage($entity_type);
      $entity_storage->resetCache();
      $entities = $entity_storage->loadByProperties(['id' => $entity_id]);

      if (!empty($entities[$entity_id])) {
        $entities[$entity_id]->delete();
      }
    }

    // Delete the article content type.
    $this->drupalGet('node/1/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('admin/structure/types/manage/article');
    $this->clickLink('Delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Delete');

    // Try to install search_api_db_defaults module and test if it failed
    // because there was no content type "article".
    $this->drupalPostForm('admin/modules', $edit_enable, 'Install');
    $success_text = new FormattableMarkup('Content type @content_type not found. Database Search Defaults module could not be installed.', ['@content_type' => 'article']);
    $this->assertSession()->pageTextContains($success_text);
  }

}
