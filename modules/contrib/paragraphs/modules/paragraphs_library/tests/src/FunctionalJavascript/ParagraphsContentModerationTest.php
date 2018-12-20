<?php

namespace Drupal\Tests\paragraphs_library\FunctionalJavascript;

use Behat\Mink\Element\Element;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsLastEntityQueryTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests Paragraphs, Paragraphs Library and Content Moderation integration.
 *
 * @group paragraphs_library
 */
class ParagraphsContentModerationTest extends JavascriptTestBase {

  use ParagraphsTestBaseTrait, FieldUiTestTrait, ParagraphsLastEntityQueryTrait;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with permission to see content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $visitorUser;

  /**
   * A user with basic permissions to edit and moderate content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $editorUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'entity_browser',
    'paragraphs_library',
    'block',
    'field_ui',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->addParagraphedContentType('paragraphed_moderated_test', 'field_paragraphs', 'entity_reference_paragraphs');

    $this->addParagraphsType('text');
    $this->addFieldtoParagraphType('text', 'field_text', 'text');

    $this->createEditorialWorkflow('paragraphed_moderated_test');
    $type_plugin = $this->workflow->getTypePlugin();
    $type_plugin->addEntityTypeAndBundle('paragraphs_library_item', 'paragraphs_library_item');
    $this->workflow->save();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'view any unpublished content',
      'view all revisions',
      'revert all revisions',
      'view latest version',
      'view any unpublished content',
      'use ' . $this->workflow->id() . ' transition create_new_draft',
      'use ' . $this->workflow->id() . ' transition publish',
      'use ' . $this->workflow->id() . ' transition archived_published',
      'use ' . $this->workflow->id() . ' transition archived_draft',
      'use ' . $this->workflow->id() . ' transition archive',
      'administer nodes',
      'bypass node access',
      'administer paragraphs library',
      'access paragraphs_library_items entity browser pages',
      'administer workflows'
    ]);

    $this->editorUser = $this->drupalCreateUser([
      'access content',
      'view all revisions',
      'view any unpublished content',
      'view latest version',
      'use ' . $this->workflow->id() . ' transition create_new_draft',
      'use ' . $this->workflow->id() . ' transition publish',
      'use ' . $this->workflow->id() . ' transition archived_published',
      'use ' . $this->workflow->id() . ' transition archived_draft',
      'use ' . $this->workflow->id() . ' transition archive',
      'access paragraphs_library_items entity browser pages',
      'create paragraph library item',
      'create paragraphed_moderated_test content',
      'edit any paragraphed_moderated_test content',
      'access administration pages',
      'administer paragraphs library',
    ]);

    $this->visitorUser = $this->drupalCreateUser([
      'access content',
      'view all revisions',
    ]);

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the moderated paragraphed content.
   */
  public function testModeratedParagraphedContent() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a library item.
    $this->drupalGet('/admin/content/paragraphs/add/default');
    $page->fillField('label[0][value]', 'Library item 1');
    $dropbutton_paragraphs = $assert_session->elementExists('css', '#edit-paragraphs-add-more .dropbutton-arrow');
    $dropbutton_paragraphs->click();
    $add_text_paragraph = $assert_session->elementExists('css', '#paragraphs-text-add-more');
    $add_text_paragraph->press();
    $textfield = $assert_session->waitForElement('css', 'input[name="paragraphs[0][subform][field_text][0][value]"]');
    $this->assertNotNull($textfield);
    $page->fillField('paragraphs[0][subform][field_text][0][value]', 'Library item text 1');
    // Ensure it is saved as Draft by default.
    $assert_session->optionExists('moderation_state[0][state]', 'draft');
    $moderation_select = $assert_session->elementExists('css', 'select[name="moderation_state[0][state]"]');
    $this->assertEquals('draft', $moderation_select->getValue());
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Library item 1 has been created.');

    // Double-check it was saved as draft.
    $library_item = $this->getLastEntityOfType('paragraphs_library_item', TRUE);
    $this->assertEquals('draft', $library_item->moderation_state->value);
    $library_item_id = $library_item->id();

    // Make sure the content moderation control extra field is rendered in the
    // default view display of this library item.
    $this->drupalGet("/admin/content/paragraphs/{$library_item_id}");
    $assert_session->elementExists('css', '#content-moderation-entity-moderation-form');

    // Create a host node, also as a draft.
    $this->drupalGet('/node/add/paragraphed_moderated_test');
    $page->fillField('title[0][value]', 'Host page 1');
    $add_from_library_button = $assert_session->elementExists('css', 'input[name="field_paragraphs_from_library_add_more"]');
    $add_from_library_button->press();
    $button = $assert_session->waitForButton('Select reusable paragraph');
    $this->assertNotNull($button);
    $button->press();
    $modal = $assert_session->waitForElement('css', '.ui-dialog');
    $this->assertNotNull($modal);
    $session->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Library item 1');
    // Select the first item from the library and accept.
    $first_row_checkbox = $assert_session->elementExists('css', '.view-content tbody tr:nth-child(1) input');
    $first_row_checkbox->click();
    $page->pressButton('Select reusable paragraph');
    $session->wait(1000);
    $session->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    // Make sure the content moderation control extra field is not rendered in
    // the summary viewmode of the library item.
    $assert_session->elementExists('css', '#edit-field-paragraphs-wrapper .rendered-entity');
    $assert_session->elementNotExists('css', '#edit-field-paragraphs-wrapper .rendered-entity #content-moderation-entity-moderation-form');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_moderated_test Host page 1 has been created.');
    $host_node = $this->getLastEntityOfType('node', TRUE);
    $host_node_id = $host_node->id();
    $this->assertFalse($host_node->access('view', $this->visitorUser));

    // Create some new revisions of the host entity.
    $this->drupalGet("/node/{$host_node_id}/edit");
    $page->fillField('title[0][value]', 'Host page 1 (rev 2)');
    $dropbutton_paragraphs = $assert_session->elementExists('css', '#edit-field-paragraphs-wrapper .dropbutton-wrapper .dropbutton-arrow');
    $dropbutton_paragraphs->click();
    $add_text_paragraph = $assert_session->elementExists('css', 'input[name="field_paragraphs_text_add_more"]');
    $add_text_paragraph->press();
    $textfield = $assert_session->waitForElement('css', 'input[name="field_paragraphs[1][subform][field_text][0][value]"]');
    $this->assertNotNull($textfield);
    $page->fillField('field_paragraphs[1][subform][field_text][0][value]', 'Direct paragraph text 2');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Node revision #2 - This is a special version!');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_moderated_test Host page 1 (rev 2) has been updated.');

    // Admin users can see both paragraphs.
    $assert_session->pageTextContains('Direct paragraph text 2');
    $assert_session->pageTextContains('Library item text 1');

    // Normal users should see paragraph 2 (direct) but not 1 (from library).
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Direct paragraph text 2');
    $assert_session->pageTextNotContains('Library item text 1');
    $this->drupalLogin($this->adminUser);

    // Create another revision by changing the direct paragraphs.
    $this->drupalGet("/node/{$host_node_id}/edit");
    $page->fillField('title[0][value]', 'Host page 1 (rev 3)');
    $page->fillField('field_paragraphs[1][subform][field_text][0][value]', 'Direct paragraph text 2 modified');
    $dropbutton_paragraphs = $assert_session->elementExists('css', '#edit-field-paragraphs-wrapper .dropbutton-wrapper .dropbutton-arrow');
    $dropbutton_paragraphs->click();
    $add_text_paragraph = $assert_session->elementExists('css', 'input[name="field_paragraphs_text_add_more"]');
    $add_text_paragraph->press();
    $textfield = $assert_session->waitForElement('css', 'input[name="field_paragraphs[2][subform][field_text][0][value]"]');
    $this->assertNotNull($textfield);
    $page->fillField('field_paragraphs[2][subform][field_text][0][value]', 'Direct paragraph text 3');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Node revision #3');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_moderated_test Host page 1 (rev 3) has been updated.');

    // Admin users can see all paragraphs.
    $assert_session->pageTextContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2 modified');
    $assert_session->pageTextContains('Library item text 1');

    // Normal users should see only the direct paragraphs.
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2 modified');
    $assert_session->pageTextNotContains('Library item text 1');
    $this->drupalLogin($this->adminUser);

    // If we publish the library item, then it becomes visible immediately.
    $this->drupalGet("/admin/content/paragraphs/{$library_item_id}/edit");
    $page->fillField('label[0][value]', 'Library item 1 (rev 2)');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2 modified');
    $assert_session->pageTextContains('Library item text 1');
    $this->drupalLogin($this->adminUser);

    // Do the same with some forward revisions.
    $this->drupalGet("/node/{$host_node_id}/edit");
    $page->fillField('title[0][value]', 'Host page 1 (rev 4)');
    $page->fillField('field_paragraphs[1][subform][field_text][0][value]', 'Direct paragraph text 2 modified again');
    $paragraph3_remove_button = $assert_session->elementExists('css', 'input[name="field_paragraphs_2_remove"]');
    $paragraph3_remove_button->press();
    $paragraph3_confirm_remove_button = $assert_session->waitForElement('css', 'input[name="field_paragraphs_2_confirm_remove"]');
    $paragraph3_confirm_remove_button->press();
    $assert_session->assertWaitOnAjaxRequest();
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->fillField('revision_log[0][value]', 'Node revision #4');
    $page->pressButton('Save');
    // The admin is currently at /node/*/latest.
    $this->assertTrue(strpos($session->getCurrentUrl(), "/node/{$host_node_id}/latest") !== FALSE);
    $assert_session->pageTextContains('paragraphed_moderated_test Host page 1 (rev 4) has been updated.');
    // The admin user should be seeing the latest, forward-revision.
    $assert_session->pageTextNotContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2 modified again');
    $assert_session->pageTextContains('Library item text 1');
    // If the admin goes to the normal node page, the default revision should be
    // shown.
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2 modified');
    $assert_session->pageTextContains('Library item text 1');
    // Non-admins should also see the default revision.
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2 modified');
    $assert_session->pageTextContains('Library item text 1');
    $this->drupalLogin($this->adminUser);
    // Release the last revision and make sure non-admins see what is expected.
    // Use the content_moderation_control widget to make this transition.
    $this->drupalGet("/node/{$host_node_id}/latest");
    $page->selectFieldOption('new_state', 'published');
    $content_moderation_apply_button = $assert_session->elementExists('css', '#content-moderation-entity-moderation-form input[value="Apply"]');
    $content_moderation_apply_button->press();
    $assert_session->pageTextContains('The moderation state has been updated.');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextNotContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2 modified again');
    $assert_session->pageTextContains('Library item text 1');
    $this->drupalLogin($this->adminUser);

    // Roll-back to a previous revision of the host node.
    $this->drupalGet("/node/{$host_node_id}/revisions");
    $table = $assert_session->elementExists('css', 'table');
    $target_row = $this->getTableRowWithText($table, '- This is a special version!');
    $target_row->clickLink('Revert');
    $assert_session->pageTextContains('Are you sure you want to revert to the revision from');
    $page->pressButton('Revert');
    $assert_session->pageTextContains(' has been reverted to the revision from ');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $title_element = $assert_session->elementExists('css', 'h1.page-title');
    $this->assertEquals('Host page 1 (rev 2)', $title_element->getText());
    $assert_session->pageTextNotContains('Direct paragraph text 3');
    $assert_session->pageTextContains('Direct paragraph text 2');
    $assert_session->pageTextNotContains('Direct paragraph text 2 modified');
    // The library item is now published, so it should show up, despite the fact
    // that when this node revision was created it was not visible.
    $assert_session->pageTextContains('Library item text 1');
    $this->drupalLogin($this->adminUser);

    // Test some forward-revisions of the library item itself.
    $this->drupalGet("/admin/content/paragraphs/{$library_item_id}/edit");
    $page->fillField('label[0][value]', 'Library item 1 (rev 3)');
    // Make some modifications on this item and save it as draft.
    $page->fillField('paragraphs[0][subform][field_text][0][value]', 'Library item text - Unapproved version');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save');
    // Normal users should see the default version (non-forward).
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Library item text 1');
    $assert_session->pageTextNotContains('Library item text - Unapproved version');
    $this->drupalLogin($this->adminUser);
    // Publish the forward-version and the node should reflect that immediately.
    $this->drupalGet("/admin/content/paragraphs/{$library_item_id}/edit");
    $page->fillField('label[0][value]', 'Library item 1 (rev 4)');
    $page->fillField('paragraphs[0][subform][field_text][0][value]', 'Library item text - Approved version');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextNotContains('Library item text 1');
    $assert_session->pageTextContains('Library item text - Approved version');

    // Test some editorial workflow with the editor user as well.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet("/admin/content/paragraphs/{$library_item_id}/edit");
    $page->fillField('label[0][value]', 'Library item 1 (rev 5)');
    $page->fillField('paragraphs[0][subform][field_text][0][value]', 'Library item text - Draft created by editor');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Library item 1 (rev 5) has been updated.');
    // The editor can see the unpublished text rendered in the library.
    $assert_session->pageTextNotContains('Library item text - Approved version');
    $assert_session->pageTextContains('Library item text - Draft created by editor');
    // Visitors however only see the published version.
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Library item text - Approved version');
    $assert_session->pageTextNotContains('Library item text - Draft created by editor');
    $this->drupalLogin($this->editorUser);
    // The editor can edit the host node.
    $this->drupalGet("/node/$host_node_id/edit");
    $page->fillField('title[0][value]', 'Host page 1 (rev 6)');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Node revision #6');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_moderated_test Host page 1 (rev 6) has been updated.');
    // The editor still sees only the published paragraph inside the node.
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Library item text - Approved version');
    $assert_session->pageTextNotContains('Library item text - Draft created by editor');

    // @todo Investigate why this is necessary. If we don't clear caches here,
    // the form will load with the old value and save it again.
    // Remove when https://www.drupal.org/node/2951441 is solved.
    drupal_flush_all_caches();

    // If the editor publishes the paragraph item, the new text shows up.
    $this->drupalGet("/admin/content/paragraphs/{$library_item_id}/edit");
    $assert_session->fieldValueEquals('paragraphs[0][subform][field_text][0][value]', 'Library item text - Draft created by editor');
    $page->fillField('label[0][value]', 'Library item 1 (rev 6)');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Library item 1 (rev 6) has been updated.');
    // We should still see the same texts in the library preview.
    $assert_session->pageTextNotContains('Library item text - Approved version');
    $assert_session->pageTextContains('Library item text - Draft created by editor');
    // But now the node should reflect the changes as well.
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextNotContains('Library item text - Approved version');
    $assert_session->pageTextContains('Library item text - Draft created by editor');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextNotContains('Library item text - Approved version');
    $assert_session->pageTextContains('Library item text - Draft created by editor');

    // By this point in the test we should have created a certain number of
    // node and library item revisions. Make sure the expected counts match.
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->allRevisions()
      ->condition($host_node->getEntityType()->getKey('id'), $host_node->id())
      ->execute();
    $this->assertEquals(7, count($nodes));
    $library_items = \Drupal::entityTypeManager()->getStorage('paragraphs_library_item')->getQuery()
      ->allRevisions()
      ->condition($library_item->getEntityType()->getKey('id'), $library_item->id())
      ->execute();
    $this->assertEquals(6, count($library_items));

    // Assert that Paragraph types cannot be selected in the UI.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/workflow/workflows/manage/' . $this->workflow->id());
    $assert_session->pageTextNotContains('Paragraph types');
    $assert_session->pageTextContains('Content types');
    $assert_session->elementNotExists('css', 'a[href$="' . $this->workflow->id() . '/type/paragraph"]');
    $assert_session->elementExists('css', 'a[href$="' . $this->workflow->id() . '/type/node"]');
  }

  /**
   * Tests the moderated content that includes nested paragraphs.
   */
  public function testModeratedContentNestedParagraphs() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->addParagraphsType('rich_paragraph');
    $this->addFieldtoParagraphType('rich_paragraph', 'field_intermediate_text', 'text');
    $this->addFieldtoParagraphType('rich_paragraph', 'field_nested_paragraphs', 'entity_reference', ['target_type' => 'paragraphs_library_item']);
    entity_get_display('paragraph', 'rich_paragraph', 'default')
      ->setComponent('field_nested_paragraphs', [
        'type' => 'entity_reference_entity_view',
      ])->save();

    // Create a child library item.
    $this->drupalGet('/admin/content/paragraphs/add/default');
    $page->fillField('label[0][value]', 'Child library item');
    $dropbutton_paragraphs = $assert_session->elementExists('css', '#edit-paragraphs-add-more .dropbutton-arrow');
    $dropbutton_paragraphs->click();
    $add_text_paragraph = $assert_session->elementExists('css', '#paragraphs-text-add-more');
    $add_text_paragraph->press();
    $textfield = $assert_session->waitForElement('css', 'input[name="paragraphs[0][subform][field_text][0][value]"]');
    $this->assertNotNull($textfield);
    $page->fillField('paragraphs[0][subform][field_text][0][value]', 'This is the low-level text.');
    // This is published initially.
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Child initial revision.');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Child library item has been created.');
    $child_library_item_id = $this->getLastEntityOfType('paragraphs_library_item');

    // Create a rich library item that references the previous one.
    $this->drupalGet('/admin/content/paragraphs/add/default');
    $page->fillField('label[0][value]', 'Rich library item');
    $dropbutton_paragraphs = $assert_session->elementExists('css', '#edit-paragraphs-add-more .dropbutton-arrow');
    $dropbutton_paragraphs->click();
    $add_rich_paragraph = $assert_session->elementExists('css', '#paragraphs-rich-paragraph-add-more');
    $add_rich_paragraph->press();
    $textfield = $assert_session->waitForElement('css', 'input[name="paragraphs[0][subform][field_intermediate_text][0][value]"]');
    $this->assertNotNull($textfield);
    $page->fillField('paragraphs[0][subform][field_intermediate_text][0][value]', 'First level text - draft');
    $paragraphs_field = $assert_session->waitForElement('css', 'input[name="paragraphs[0][subform][field_nested_paragraphs][0][target_id]"]');
    $this->assertNotNull($paragraphs_field);
    $page->fillField('paragraphs[0][subform][field_nested_paragraphs][0][target_id]', "Child library item ($child_library_item_id)");
    // Let's make this initially a draft.
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->fillField('revision_log[0][value]', 'Rich item initial revision.');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Rich library item has been created.');
    $rich_library_item_id = $this->getLastEntityOfType('paragraphs_library_item');

    // Create a host node that uses both a rich item and a child one.
    $this->drupalGet('/node/add/paragraphed_moderated_test');
    $page->fillField('title[0][value]', 'Host page 2');
    $add_from_library_button = $assert_session->elementExists('css', 'input[name="field_paragraphs_from_library_add_more"]');
    $add_from_library_button->press();
    $button = $assert_session->waitForButton('Select reusable paragraph');
    $this->assertNotNull($button);
    $button->press();
    $modal = $assert_session->waitForElement('css', '.ui-dialog');
    $this->assertNotNull($modal);
    $session->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Child library item');
    $assert_session->pageTextContains('Rich library item');
    $table = $assert_session->elementExists('css', 'table.views-table');
    $rich_item_row = $this->getTableRowWithText($table, 'Rich library item');
    $rich_item_checkbox = $assert_session->elementExists('css', 'input[type="checkbox"]', $rich_item_row);
    $rich_item_checkbox->click();
    $page->pressButton('Select reusable paragraph');
    $session->wait(1000);
    $session->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    // Save the node as published.
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Node initial revision');
    $page->pressButton('Save');

    $host_node_id = $this->getLastEntityOfType('node');

    // Visitor users don't see the paragraphs.
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextNotContains('This is the low-level text.');
    $assert_session->pageTextNotContains('First level text - draft');
    $this->drupalLogin($this->adminUser);

    // Make the rich paragraph published.
    $this->drupalGet("/admin/content/paragraphs/{$rich_library_item_id}/edit");
    $page->fillField('paragraphs[0][subform][field_intermediate_text][0][value]', 'First level text - published');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Rich item first published revision.');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Rich library item has been updated.');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('This is the low-level text.');
    $assert_session->pageTextContains('First level text - published');
    $this->drupalLogin($this->adminUser);

    // Make some draft modifications at the child paragraph.
    $this->drupalGet("/admin/content/paragraphs/{$child_library_item_id}/edit");
    $page->fillField('paragraphs[0][subform][field_text][0][value]', 'The low-level text has been modified (pending approval).');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->fillField('revision_log[0][value]', 'Child item unapproved changes.');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Child library item has been updated.');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('This is the low-level text.');
    $assert_session->pageTextContains('First level text - published');
    $assert_session->pageTextNotContains('The low-level text has been modified (pending approval).');
    $this->drupalLogin($this->adminUser);

    // Publish the child paragraph.
    $this->drupalGet("/admin/content/paragraphs/{$child_library_item_id}/edit");
    $page->fillField('paragraphs[0][subform][field_text][0][value]', 'The low-level text has been modified (approved!).');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Child item approved changes.');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Paragraph Child library item has been updated.');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('The low-level text has been modified (approved!).');
    $assert_session->pageTextContains('First level text - published');
    $assert_session->pageTextNotContains('This is the low-level text.');
    $this->drupalLogin($this->adminUser);

    // Revert the child paragraph to its initial version.
    $this->drupalGet("/admin/content/paragraphs/{$child_library_item_id}/revisions");
    $table = $assert_session->elementExists('css', 'table');
    $target_row = $this->getTableRowWithText($table, 'Child initial revision');
    $target_row->clickLink('Revert');
    $assert_session->pageTextContains('Are you sure you want to revert');
    $page->pressButton('Revert');
    $assert_session->pageTextContains(' has been reverted to the revision from ');
    $this->drupalLogin($this->visitorUser);
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('This is the low-level text.');
    $assert_session->pageTextContains('First level text - published');
  }

  /**
   * Retrieve a table row containing specified text from a given element.
   *
   * @param \Behat\Mink\Element\Element $table
   *   The table element.
   * @param string $search
   *   The text to search for in the table row.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The row element.
   *
   * @throws \Exception
   */
  protected function getTableRowWithText(Element $table, $search) {
    $rows = $table->findAll('css', 'tr');
    if (empty($rows)) {
      throw new \Exception(sprintf('No rows found on the received table element.'));
    }
    foreach ($rows as $row) {
      if (strpos($row->getText(), $search) !== FALSE) {
        return $row;
      }
    }
    throw new \Exception(sprintf('Failed to find a row containing "%s" on the received table.', $search));
  }

}
