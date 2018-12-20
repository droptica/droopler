<?php

namespace Drupal\paragraphs_library\Tests;

use Drupal\Core\Url;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests paragraphs library functionality.
 *
 * @group paragraphs_library
 */
class ParagraphsLibraryItemTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'views',
    'paragraphs_library',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->addParagraphedContentType('paragraphed_test');
  }

  /**
   * Tests the library items workflow for paragraphs.
   */
  public function testLibraryItems() {
    // Set default theme.
    \Drupal::service('theme_handler')->install(['bartik']);
    $this->config('system.theme')->set('default', 'bartik')->save();
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);

    // Add a Paragraph type with a text field.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a new library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_text_paragraph_add_more');
    $edit = [
      'label[0][value]' => 're usable paragraph label',
      'paragraphs[0][subform][field_text][0][value]' => 're_usable_text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->clickLink('re usable paragraph label');
    $this->assertRaw('bartik/css/base/elements.css', t("The default frontend theme's CSS appears on the page for viewing a library item."));
    $this->clickLink('Edit');
    $this->assertNoRaw('class="messages messages--warning"', 'No warning message is displayed.');
    $items = \Drupal::entityQuery('paragraphs_library_item')->sort('id', 'DESC')->range(0, 1)->execute();
    $library_item_id = reset($items);

    // Assert local tasks and URLs.
    $this->assertLink('Edit');
    $this->assertText('Delete');
    $this->clickLink('View');
    $this->assertUrl(Url::fromRoute('entity.paragraphs_library_item.canonical', ['paragraphs_library_item' => $library_item_id]));
    $this->drupalGet('admin/content/paragraphs/' . $library_item_id . '/delete');
    $this->assertUrl(Url::fromRoute('entity.paragraphs_library_item.delete_form', ['paragraphs_library_item' => $library_item_id]));
    $this->clickLink('Edit');
    $this->assertUrl(Url::fromRoute('entity.paragraphs_library_item.edit_form', ['paragraphs_library_item' => $library_item_id]));

    // Check that the data is correctly stored.
    $this->drupalGet('admin/content/paragraphs');
    $this->assertText('Used', 'Usage column is available.');
    $this->assertText('Changed', 'Changed column is available.');
    $result = $this->cssSelect('.views-field-count');
    $this->assertEqual(trim($result[1]->__toString()), '0', 'Usage info is correctly displayed.');
    $this->assertText('Delete');
    // Check the changed field.
    $result = $this->cssSelect('.views-field-changed');
    $this->assertNotNull(trim($result[1]->__toString()));
    $this->clickLink('Edit');
    $this->assertFieldByName('label[0][value]', 're usable paragraph label');
    $this->assertFieldByName('paragraphs[0][subform][field_text][0][value]', 're_usable_text');

    // Create a node with the library paragraph.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $library_items = \Drupal::entityTypeManager()->getStorage('paragraphs_library_item')->loadByProperties(['label' => 're usable paragraph label']);
    $this->drupalGet('admin/content/paragraphs/' . current($library_items)->id() . '/edit');
    $this->assertText('Library item is used 1 time.');
    $this->assertText('Delete');

    $this->drupalGet('admin/content/paragraphs');
    $result = $this->cssSelect('.views-field-count');
    $this->assertEqual(trim($result[1]->__toString()), '1', 'Usage info is correctly displayed.');

    // Assert that the paragraph is shown correctly.
    $node_one = $this->getNodeByTitle('library_test');
    $this->drupalGet('node/' . $node_one->id());
    $this->assertText('re_usable_text');

    // Create a new node with the library paragraph.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test_new',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Assert that the paragraph is shown correctly.
    $node_two = $this->getNodeByTitle('library_test_new');
    $this->assertUrl('node/' . $node_two->id());
    $this->assertText('re_usable_text');
    $this->assertNoText('Reusable paragraph', 'Label from the paragraph that references a library item is not displayed.');
    $this->assertNoText('re usable paragraph label', 'Label from library item is not visible.');
    $this->assertNoText('Paragraphs', 'Label from library item field paragraphs is hidden.');

    $this->drupalGet('node/' . $node_two->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test_new',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)',
      'field_paragraphs[1][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $reusable_paragraphs_text = $this->xpath('//div[contains(@class, "field--name-field-paragraphs")]/div[@class="field__items"]/div[1]//div[@class="field__item"]/p');
    $this->assertEqual($reusable_paragraphs_text[0], 're_usable_text');

    $second_reusable_paragraphs_text = $this->xpath('//div[contains(@class, "field--name-field-paragraphs")]/div[@class="field__items"]/div[2]//div[@class="field__item"]/p');
    $this->assertEqual($second_reusable_paragraphs_text[0], 're_usable_text');

    // Edit the paragraph and change the text.
    $this->drupalGet('admin/content/paragraphs');

    $this->assertText('Used', 'Usage column is available.');
    $result = $this->cssSelect('.views-field-count');
    $this->assertEqual(trim($result[1]->__toString()), '4', 'Usage info is correctly displayed.');
    $this->assertNoLink('4', 'Link to usage statistics is not available for user without permission.');

    $this->clickLink('Edit');
    $this->assertText('Library item is used 3 times.');
    $edit = [
      'paragraphs[0][subform][field_text][0][value]' => 're_usable_text_new',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check in both nodes that the text is updated. Test as anonymous user, so
    // that the cache is populated.
    $this->drupalLogout();
    $this->drupalGet('node/' . $node_one->id());
    $this->assertText('re_usable_text_new');
    $this->drupalGet('node/' . $node_two->id());
    $this->assertText('re_usable_text_new');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);

    // Unpublish the library item, make sure it still shows up for the curent
    // user but not for an anonymous user.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Edit');
    $edit = [
      'status[value]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/' . $node_one->id());
    $this->assertText('re_usable_text_new');

    $this->drupalLogout();
    $this->drupalGet('node/' . $node_one->id());
    $this->assertNoText('re_usable_text_new');

    // Log in again, publish again, make sure it shows up again.
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Edit');
    $edit = [
      'status[value]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/' . $node_one->id());
    $this->assertText('re_usable_text_new');

    $this->drupalLogout();
    $this->drupalGet('node/' . $node_one->id());
    $this->assertText('re_usable_text_new');

    $this->loginAsAdmin(['administer paragraphs library', 'access entity usage statistics']);
    $this->drupalGet('admin/content/paragraphs');
    $this->assertLink('4', 0, 'Link to usage statistics is available for user with permission.');

    $element = $this->cssSelect('th.views-field-paragraphs__target-id');
    $this->assertEqual($element[0]->__toString(), 'Paragraphs', 'Paragraphs column is available.');

    $element = $this->cssSelect('.paragraphs-collapsed-description');
    $this->assertEqual(trim($element[0]->__toString()), 're_usable_text_new', 'Paragraphs summary available.');

    // Check that the deletion of library items does not cause errors.
    current($library_items)->delete();
    $this->drupalGet('node/' . $node_one->id());
    $this->assertNoErrorsLogged();

    // Test paragraphs library item field UI.
    $this->loginAsAdmin([
      'administer paragraphs library',
      'administer paragraphs_library_item fields',
      'administer paragraphs_library_item form display',
      'administer paragraphs_library_item display',
      'access administration pages',
    ]);
    $this->drupalGet('admin/config/content/paragraphs_library_item');
    $this->assertLink('Manage fields');
    $this->assertLink('Manage form display');
    $this->assertLink('Manage display');
    // Assert that users can create fields to
    $this->clickLink('Manage fields');
    $this->clickLink(t('Add field'));
    $this->assertResponse(200);
    $this->assertNoErrorsLogged();
    $this->assertNoText('plugin does not exist');
    $this->drupalGet('admin/config/content');
    $this->clickLink('Paragraphs library item settings');
  }

  /**
   * Tests converting Library item into a paragraph.
   */
  public function testConvertLibraryItemIntoParagraph() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
    ]);

    // Add a Paragraph type with a text field.
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a new library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_text_add_more');
    $edit = [
      'label[0][value]' => 'reusable paragraph label',
      'paragraphs[0][subform][field_text][0][value]' => 'reusable_text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Paragraph reusable paragraph label has been created.');

    // Add created library item to a node.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'Node with converted library item',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 'reusable paragraph label',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('paragraphed_test Node with converted library item has been created.');
    $this->assertText('reusable_text');

    // Convert library item to paragraph.
    $this->clickLink('Edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_unlink_from_library');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertNoFieldByName('field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]');
    $this->assertText('reusable_text');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertText('reusable_text');
  }

  /**
   * Tests converting paragraph item into library.
   */
  public function testConvertParagraphIntoLibrary() {
    $user = $this->createUser(array_merge($this->admin_permissions, [
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
      'administer paragraphs types',
    ]));
    $this->drupalLogin($user);

    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    $edit = ['allow_library_conversion' => 1];
    $this->drupalPostForm('admin/structure/paragraphs_type/text', $edit, 'Save');

    // Adding library item is available with the administer library permission.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, 'Add text');
    $this->assertField('field_paragraphs_0_promote_to_library');
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->assertResponse(200);

    // Adding library item is not available without appropriate permissions.
    $user_roles = $user->getRoles(TRUE);
    $user_role = end($user_roles);
    user_role_revoke_permissions($user_role, ['administer paragraphs library']);
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, 'Add text');
    $this->assertNoField('field_paragraphs_0_promote_to_library');
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->assertResponse(403);

    // Enabling a dummy behavior plugin for testing the item label creation.
    $edit = [
      'behavior_plugins[test_text_color][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/text', $edit, 'Save');
    // Assign "create paragraph library item" permission to a user.
    user_role_grant_permissions($user_role, ['create paragraph library item']);
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->assertResponse(200);
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, 'Add text');
    $this->assertField('field_paragraphs_0_promote_to_library');
    $this->assertRaw('Promote to library');
    $edit = [
      'field_paragraphs[0][subform][field_text][0][value]' => 'Random text for testing converting into library.',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_promote_to_library');
    $this->assertText('From library');
    $this->assertText('Reusable paragraph');
    $this->assertFieldByName('field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]', 'text: Random text for testing converting into library. (1)');
    $edit = [
      'title[0][value]' => 'TextParagraphs',
    ];
    $this->assertNoField('field_paragraphs_0_promote_to_library');
    $this->assertField('field_paragraphs_0_unlink_from_library');
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalGet('node/1');
    $this->assertText('Random text for testing converting into library.');

    // Create library item from existing paragraph item.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, 'Add text');
    $edit = [
      'title[0][value]' => 'NodeTitle',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Random text for testing converting into library.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $node = $this->getNodeByTitle('NodeTitle');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_promote_to_library');
    user_role_grant_permissions($user_role, ['administer paragraphs library']);
    $this->drupalGet('/admin/content/paragraphs');
    $this->assertText('Text');
    $this->assertText('Random text for testing converting into library.');

    // Test disallow convesrion.
    $edit = ['allow_library_conversion' => FALSE];
    $this->drupalPostForm('admin/structure/paragraphs_type/text', $edit, 'Save');

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = \Drupal::service('config.factory');
    $third_party = $config_factory->get('paragraphs.paragraphs_type.text')->get('third_party_settings');
    $this->assertFalse(isset($third_party['paragraphs_library']['allow_library_conversion']));

    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, 'Add text');
    $this->assertNoRaw('Promote to library');

    // Test that the checkbox is not visible on from_library.
    $this->drupalGet('admin/structure/paragraphs_type/from_library');
    $this->assertNoField('allow_library_conversion');
  }

  /**
   * Tests the library paragraphs summary preview.
   */
  public function testLibraryItemParagraphsSummary() {
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Create paragraph type Nested test.
    $this->addParagraphsType('nested_test');

    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_test', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    $this->drupalGet('admin/structure/paragraphs_type/nested_test/form-display');
    $edit = [
      'fields[field_paragraphs][type]' => 'paragraphs',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalPostAjaxForm('admin/content/paragraphs/add/default', [], 'paragraphs_nested_test_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_0_subform_field_paragraphs_text_add_more');
    $edit = [
      'label[0][value]' => 'Test nested',
      'paragraphs[0][subform][field_paragraphs][0][subform][field_text][0][value]' => 'test text paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('test text paragraph');
  }

  /**
   * Tests the library item validation.
   */
  public function testLibraryitemValidation() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library'
    ]);

    // Add a Paragraph type with a text field.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a new library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');

    // Check the label validation.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Label field is required.');
    $edit = [
      'label[0][value]' => 're usable paragraph label',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check the paragraph validation.
    $this->assertText('Paragraphs field is required.');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_text_paragraph_add_more');
    $edit['paragraphs[0][subform][field_text][0][value]'] = 're_usable_text';

    // Check that the library item is created.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Paragraph re usable paragraph label has been created.');
    $this->clickLink('Edit');
    $edit = [
      'paragraphs[0][subform][field_text][0][value]' => 'new text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Paragraph re usable paragraph label has been updated.');
  }

  /**
   * Tests the validation of paragraphs referencing library items.
   */
  public function testLibraryReferencingParagraphValidation() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library'
    ]);
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a library item with paragraphs type "Text".
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_text_add_more');
    $edit = [
      'label[0][value]' => 'reusable paragraph label',
      'paragraphs[0][subform][field_text][0][value]' => 'reusable_text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Paragraph reusable paragraph label has been created.');

    // Create a node with a "From library" paragraph referencing the library
    // item.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 'reusable paragraph label',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('paragraphed_test library_test has been created.');

    // Disallow the paragraphs type "Text" for the used content type.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/fields/node.paragraphed_test.field_paragraphs');
    $edit = [
      'settings[handler_settings][negate]' => 0,
      'settings[handler_settings][target_bundles_drag_drop][from_library][enabled]' => 1,
      'settings[handler_settings][target_bundles_drag_drop][text][enabled]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertText('Saved field_paragraphs configuration.');

    // Check that the node now fails validation.
    $node = $this->getNodeByTitle('library_test');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertUrl('node/' . $node->id() . '/edit');
    $this->assertText('The Reusable paragraph field cannot contain a text paragraph, because the parent field_paragraphs field disallows it.');
  }

  /**
   * Test paragraphs library item revisions.
   */
  public function testLibraryItemRevisions() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
    ]);

    $this->addParagraphsType('test_content');
    $this->addParagraphsType('nested_paragraph');

    $this->fieldUIAddNewField('admin/structure/paragraphs_type/test_content', 'paragraphs_text', 'Test content', 'text_long', [], []);

    // Add nested paragraph field.
    $this->fieldUIAddNewField('admin/structure/paragraphs_type/nested_paragraph', 'err_field', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);

    // Add nested paragraph directly in library.
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_0_subform_field_err_field_test_content_add_more');
    $edit = [
      'label[0][value]' => 'Test revisions nested original',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'Example text for revision original.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Paragraph Test revisions nested original has been created.');

    // Check revisions tab.
    $this->clickLink('Test revisions nested original');
    $this->clickLink('Revisions');
    $this->assertTitle('Revisions for Test revisions nested original | Drupal');

    // Edit library item, check that new revision is enabled as default.
    $this->clickLink('Edit');
    $this->assertFieldChecked('edit-revision');
    $edit = [
      'label[0][value]' => 'Test revisions nested first change',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'Example text for revision first change.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check previous revision.
    $storage = \Drupal::entityTypeManager()->getStorage('paragraphs_library_item');
    $date_formatter = \Drupal::service('date.formatter');
    $this->clickLink('Test revisions nested first change');
    $this->clickLink('Revisions');
    $this->assertTitle('Revisions for Test revisions nested first change | Drupal');
    $revision = $storage->loadRevision(1);
    $this->assertText('Test revisions nested original by ' . $this->admin_user->getAccountName());
    $this->assertText($date_formatter->format($revision->getChangedTime(), 'short') . ': ' . $revision->label());
    $this->clickLink($date_formatter->format($revision->getChangedTime(), 'short'), 1);
    $this->assertText('Test revisions nested original');
    $this->assertText('Example text for revision original');
    $this->clickLink('Revisions');

    // Test reverting revision.
    $this->clickLink('Revert');
    $this->assertRaw(t('Are you sure you want to revert revision from %revision-date?', [
      '%revision-date' => $date_formatter->format($revision->getChangedTime())
    ]));
    $this->drupalPostForm(NULL, NULL, 'Revert');
    $this->assertRaw(t('%title has been reverted to the revision from %revision-date.', [
      '%title' => 'Test revisions nested original',
      '%revision-date' => $date_formatter->format($revision->getChangedTime())
    ]));

    // Check current revision.
    $current_revision = $storage->loadRevision(3);
    $this->clickLink($date_formatter->format($current_revision->getChangedTime(), 'short'));
    $this->assertText('Example text for revision original');
    $this->clickLink('Revisions');

    // Test deleting revision.
    $revision_for_deleting = $storage->loadRevision(2);
    $this->clickLink('Delete');
    $this->assertRaw(t('Are you sure you want to delete revision from %revision-date', [
      '%revision-date' => $date_formatter->format($revision_for_deleting->getChangedTime())
    ]));
    $this->drupalPostForm(NULL, NULL, 'Delete');
    $this->assertRaw(t('Revision from %revision-date has been deleted.', [
      '%revision-date' => $date_formatter->format($revision_for_deleting->getChangedTime())
    ]));
  }

}
