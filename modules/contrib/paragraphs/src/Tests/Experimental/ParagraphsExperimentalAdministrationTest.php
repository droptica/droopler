<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests the configuration of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalAdministrationTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'image',
    'file',
    'views'
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create paragraphs content type.
    $this->drupalCreateContentType(array('type' => 'paragraphs', 'name' => 'Paragraphs'));
  }

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsRevisions() {
    $this->addParagraphedContentType('article', 'paragraphs');
    $this->loginAsAdmin([
      'create paragraphs content',
      'administer node display',
      'edit any paragraphs content',
      'administer nodes',
    ]);

    // Create paragraphs type Headline + Block.
    $this->addParagraphsType('text');
    // Create field types for the text.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'text', 'Text', 'text', array(), array());
    $this->assertText('Saved Text configuration.');

    // Create an article with paragraphs field.
    static::fieldUIAddNewField('admin/structure/types/manage/paragraphs', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', array(
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ), array(
      'settings[handler_settings][target_bundles_drag_drop][text][enabled]' => TRUE,
    ));
    // Configure article fields.
    $this->drupalGet('admin/structure/types/manage/paragraphs/fields');
    $this->clickLink(t('Manage form display'));
    $this->drupalPostForm(NULL, array('fields[field_paragraphs][type]' => 'paragraphs'), t('Save'));

    // Create node with our paragraphs.
    $this->drupalGet('node/add/paragraphs');
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_text_add_more');
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_text_add_more');
    $edit = [
      'title[0][value]' => 'TEST TITEL',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Test text 1',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Test text 2',
    ];
    $this->drupalPostForm(NULL, $edit + ['status[value]' => TRUE], t('Save'));

    $node = $this->drupalGetNodeByTitle('TEST TITEL');
    $paragraph1 = $node->field_paragraphs[0]->target_id;
    $paragraph2 = $node->field_paragraphs[1]->target_id;

    $this->countRevisions($node, $paragraph1, $paragraph2, 1);

    // Edit the node without creating a revision. There should still be only 1
    // revision for nodes and paragraphs.
    $edit = [
      'field_paragraphs[0][subform][field_text][0][value]' => 'Foo Bar 1',
      'revision' => FALSE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    $this->countRevisions($node, $paragraph1, $paragraph2, 1);

    // Edit the just created node. Create new revision. Now we should have 2
    // revisions for nodes and paragraphs.
    $edit = [
      'title[0][value]' => 'TEST TITLE',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Foo Bar 2',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    $this->countRevisions($node, $paragraph1, $paragraph2, 2);

    // Assert the paragraphs have been changed.
    $this->assertNoText('Foo Bar 1');
    $this->assertText('Test text 2');
    $this->assertText('Foo Bar 2');
    $this->assertText('TEST TITLE');

    // Check out the revisions page and assert there are 2 revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $rows = $this->xpath('//tbody/tr');
    // Make sure two revisions available.
    $this->assertEqual(count($rows), 2);
    // Revert to the old version.
    $this->clickLink(t('Revert'));
    $this->drupalPostForm(NULL, [], t('Revert'));
    $this->drupalGet('node/' . $node->id());
    // Assert the node has been reverted.
    $this->assertNoText('Foo Bar 2');
    $this->assertText('Test text 2');
    $this->assertText('Foo Bar 1');
    $this->assertText('TEST TITEL');
  }


  /**
   * Tests the paragraph creation.
   */
  public function testParagraphsCreation() {
    // Create an article with paragraphs field.
    $this->addParagraphedContentType('article');
    $this->loginAsAdmin([
      'administer site configuration',
      'create article content',
      'create paragraphs content',
      'administer node display',
      'administer paragraph display',
      'edit any article content',
      'delete any article content',
      'access files overview',
    ]);

    // Assert suggested 'Add a paragraph type' link when there is no type yet.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertText('There is no Paragraphs type yet.');
    $this->drupalGet('admin/structure/types/manage/paragraphs/fields/add-field');
    $edit = [
      'new_storage_type' => 'field_ui:entity_reference_revisions:paragraph',
      'label' => 'Paragraph',
      'field_name' => 'paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $this->assertLinkByHref('admin/structure/paragraphs_type/add');
    $this->clickLink('here');
    $this->assertUrl('admin/structure/paragraphs_type/add');

    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink(t('Add paragraph type'));
    $this->assertTitle('Add Paragraphs type | Drupal');
    // Create paragraph type text + image.
    $this->addParagraphsType('text_image');
    $this->drupalGet('admin/structure/paragraphs_type/text_image');
    $this->assertTitle('Edit text_image paragraph type | Drupal');
    // Create field types for text and image.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'text', 'Text', 'text_long', array(), array());
    $this->assertText('Saved Text configuration.');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'image', 'Image', 'image', array(), array('settings[alt_field_required]' => FALSE));
    $this->assertText('Saved Image configuration.');

    // Create paragraph type Nested test.
    $this->addParagraphsType('nested_test');

    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_test', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', array(
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ), array());

    // Change the add more button to select mode.
    $this->clickLink(t('Manage form display'));
    $this->drupalPostAjaxForm(NULL, ['fields[field_paragraphs][type]' => 'paragraphs'], 'field_paragraphs_settings_edit');
    $this->drupalPostForm(NULL, ['fields[field_paragraphs][settings_edit_form][settings][add_mode]' => 'select'], t('Update'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Create paragraph type image.
    $this->addParagraphsType('image');
    // Create field types for image.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/image', 'image_only', 'Image only', 'image', array(), array());
    $this->assertText('Saved Image only configuration.');

    $this->drupalGet('admin/structure/paragraphs_type');
    $rows = $this->xpath('//tbody/tr');
    // Make sure 2 types are available with their label.
    $this->assertEqual(count($rows), 3);
    $this->assertText('text_image');
    $this->assertText('image');
    // Make sure there is an edit link for each type.
    $this->clickLink(t('Edit'));
    // Make sure the field UI appears.
    $this->assertLink('Manage fields');
    $this->assertLink('Manage form display');
    $this->assertLink('Manage display');
    $this->assertTitle('Edit image paragraph type | Drupal');

    // Test for "Add mode" setting.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $field_name = 'field_paragraphs';

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, ['fields[field_paragraphs][type]' => 'paragraphs'], $field_name . "_settings_edit");

    // Enable setting.
    $edit = array('fields[' . $field_name . '][settings_edit_form][settings][add_mode]' => 'button');
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check if the setting is stored.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->assertText('Add mode: Buttons', 'Checking the settings value.');

    $this->drupalPostAjaxForm(NULL, array(), $field_name . "_settings_edit");
    // Assert the 'Buttons' option is selected.
    $this->assertOptionSelected('edit-fields-field-paragraphs-settings-edit-form-settings-add-mode', 'button', 'Updated value is correct!.');

    // Add two Text + Image paragraphs in article.
    $this->drupalGet('node/add/article');
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_text_image_add_more');
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_text_image_add_more');

    // Upload some images.
    $files = $this->drupalGetTestFiles('image');
    $file_system = \Drupal::service('file_system');

    $edit = array(
      'title[0][value]' => 'Test article',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Test text 1',
      'files[field_paragraphs_0_subform_field_image_0]' => $file_system->realpath($files[0]->uri),
      'field_paragraphs[1][subform][field_text][0][value]' => 'Test text 2',
      'files[field_paragraphs_1_subform_field_image_0]' => $file_system->realpath($files[1]->uri),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('article Test article has been created.');

    $node = $this->drupalGetNodeByTitle('Test article');
    $img1_url = file_create_url(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $files[0]->filename));
    $img2_url = file_create_url(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $files[1]->filename));
    $img1_size = filesize($files[0]->uri);
    $img2_size = filesize($files[1]->uri);
    $img1_mime = \Drupal::service('file.mime_type.guesser')->guess($files[0]->uri);
    $img2_mime = \Drupal::service('file.mime_type.guesser')->guess($files[1]->uri);

    // Check the text and image after publish.
    $this->assertText('Test text 1');
    $this->assertRaw('<img src="' . file_url_transform_relative($img1_url));
    $this->assertText('Test text 2');
    $this->assertRaw('<img src="' . file_url_transform_relative($img2_url));

    // Tests for "Edit mode" settings.
    // Test for closed setting.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, array(), "field_paragraphs_settings_edit");
    // Enable setting.
    $edit = array('fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check if the setting is stored.
    $this->assertText('Edit mode: Closed', 'Checking the settings value.');
    $this->drupalPostAjaxForm(NULL, array(), "field_paragraphs_settings_edit");
    // Assert the 'Closed' option is selected.
    $this->assertOptionSelected('edit-fields-field-paragraphs-settings-edit-form-settings-edit-mode', 'closed', 'Updated value correctly.');
    $this->drupalGet('node/1/edit');
    // The textareas for paragraphs should not be visible.
    $this->assertNoRaw('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertNoRaw('field_paragraphs[1][subform][field_text][0][value]');
    $this->assertRaw('<div class="paragraphs-collapsed-description">Test text 1, ' . $files[0]->filename);
    $this->assertRaw('<div class="paragraphs-collapsed-description">Test text 2, ' . $files[1]->filename);

    // Test for preview option.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->drupalPostAjaxForm(NULL, array(), "field_paragraphs_settings_edit");
    $edit = [
      'fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed',
      'fields[field_paragraphs][settings_edit_form][settings][closed_mode]' => 'preview',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Edit mode: Closed', 'Checking the "Edit mode" setting value.');
    $this->assertText('Closed mode: Preview', 'Checking the "Closed mode" settings value.');
    $this->drupalGet('node/1/edit');
    // The texts in the paragraphs should be visible.
    $this->assertNoRaw('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertNoRaw('field_paragraphs[1][subform][field_text][0][value]');
    $this->assertText('Test text 1');
    $this->assertText('Test text 2');

    // Test for open option.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->drupalPostAjaxForm(NULL, array(), "field_paragraphs_settings_edit");
    // Assert the "Closed" and "Preview" options are selected.
    $this->assertOptionSelected('edit-fields-field-paragraphs-settings-edit-form-settings-edit-mode', 'closed', 'Correctly updated the "Edit mode" value.');
    $this->assertOptionSelected('edit-fields-field-paragraphs-settings-edit-form-settings-closed-mode', 'preview', 'Correctly updated the "Closed mode" value.');
    // Restore the value to Open for next test.
    $edit = array('fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'open');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/1/edit');
    // The textareas for paragraphs should be visible.
    $this->assertRaw('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertRaw('field_paragraphs[1][subform][field_text][0][value]');

    $paragraphs = Paragraph::loadMultiple();
    $this->assertEqual(count($paragraphs), 2, 'Two paragraphs in article');

    // Check article edit page.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check both paragraphs in edit page.
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', 'Test text 1');
    $this->assertRaw('<a href="' . $img1_url . '" type="' . $img1_mime . '; length=' . $img1_size . '">' . $files[0]->filename . '</a>');
    $this->assertFieldByName('field_paragraphs[1][subform][field_text][0][value]', 'Test text 2');
    $this->assertRaw('<a href="' . $img2_url . '" type="' . $img2_mime . '; length=' . $img2_size . '">' . $files[1]->filename . '</a>');
    // Remove 2nd paragraph.
    $this->drupalPostForm(NULL, NULL, t('Remove'));
    $this->assertNoField('field_paragraphs[1][subform][field_text][0][value]');
    $this->assertNoRaw('<a href="' . $img2_url . '" type="' . $img2_mime . '; length=' . $img2_size . '">' . $files[1]->filename . '</a>');
    // Assert the paragraph is not deleted unless the user saves the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertRaw('<a href="' . $img2_url . '" type="' . $img2_mime . '; length=' . $img2_size . '">' . $files[1]->filename . '</a>');
    // Remove the second paragraph.
    $this->drupalPostForm(NULL, [], t('Remove'));
    $this->assertNoRaw('<a href="' . $img2_url . '" type="' . $img2_mime . '; length=' . $img2_size . '">' . $files[1]->filename . '</a>');
    $edit = [
      'field_paragraphs[0][subform][field_image][0][alt]' => 'test_alt',
      'field_paragraphs[0][subform][field_image][0][width]' => 300,
      'field_paragraphs[0][subform][field_image][0][height]' => 300,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Assert the paragraph is deleted after the user saves the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoRaw('<a href="' . $img2_url . '" type="' . $img2_mime . '; length=' . $img2_size . '">' . $files[1]->filename . '</a>');

    // Delete the node.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertText('Test article has been deleted.');

    // Check if the publish/unpublish option works.
    $this->drupalGet('admin/structure/paragraphs_type/text_image/form-display');
    $edit = [
      'fields[status][type]' => 'boolean_checkbox',
      'fields[status][region]' => 'content',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/add/article');
    $this->drupalPostForm(NULL, NULL, t('Add text_image'));
    $this->assertRaw('edit-field-paragraphs-0-subform-status-value');
    $edit = [
      'title[0][value]' => 'Example publish/unpublish',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Example published and unpublished',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Example published and unpublished'));
    $this->clickLink(t('Edit'));
    $edit = [
      'field_paragraphs[0][subform][status][value]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoText(t('Example published and unpublished'));

    // Set the fields as required.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->clickLink('Edit', 1);
    $this->drupalPostForm(NULL, ['preview_mode' => '1'], t('Save content type'));
    $this->drupalGet('admin/structure/paragraphs_type/nested_test/fields');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, ['required' => TRUE], t('Save settings'));

    // Add a new article.
    $this->drupalGet('node/add/article');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_nested_test_add_more');

    // Ensure that nested header actions do not add a visible weight field.
    $this->assertNoFieldByName('field_paragraphs[0][subform][field_paragraphs][header_actions][_weight]');

    $edit = [
      'field_paragraphs[0][subform][field_paragraphs][add_more][add_more_select]' => 'image',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_subform_field_paragraphs_add_more');
    // Test the new field is displayed.
    $this->assertFieldByName('files[field_paragraphs_0_subform_field_paragraphs_0_subform_field_image_only_0]');

    // Add an image to the required field.
    $edit = array(
      'title[0][value]' => 'test required',
      'files[field_paragraphs_0_subform_field_paragraphs_0_subform_field_image_only_0]' => $file_system->realpath($files[2]->uri),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $edit = [
      'field_paragraphs[0][subform][field_paragraphs][0][subform][field_image_only][0][alt]' => 'Alternative_text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('test required has been created.');
    $this->assertNoRaw('This value should not be null.');

    // Test that unsupported widgets are not displayed.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $select = $this->xpath('//*[@id="edit-fields-field-paragraphs-type"]')[0];
    $this->assertEqual(count($select->option), 2);
    $this->assertRaw('value="paragraphs" selected="selected"');

    // Check that Paragraphs is not displayed as an entity_reference field
    // reference option.
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $edit = [
      'new_storage_type' => 'entity_reference',
      'label' => 'unsupported field',
      'field_name' => 'unsupportedfield',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->assertNoOption('edit-settings-target-type', 'paragraph');

    // Test that all Paragraph types can be referenced if none is selected.
    $this->addParagraphsType('nested_double_test');
    static::fieldUIAddExistingField('admin/structure/paragraphs_type/nested_double_test', 'field_paragraphs', 'paragraphs_1');
    $this->clickLink(t('Manage form display'));
    $this->drupalPostForm(NULL, [], 'Save');
    //$this->drupalPostForm(NULL, array('fields[field_paragraphs][type]' => 'entity_reference_revisions_entity_view'), t('Save'));
    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_double_test', 'paragraphs_2', 'paragraphs_2', 'entity_reference_revisions', array(
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ), array());
    $this->clickLink(t('Manage form display'));
    $this->drupalPostForm(NULL, [], 'Save');
    $this->drupalPostAjaxForm('node/add/article', [], 'field_paragraphs_nested_test_add_more');
    $edit = [
      'field_paragraphs[0][subform][field_paragraphs][add_more][add_more_select]' => 'nested_double_test',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_subform_field_paragraphs_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_subform_field_paragraphs_0_subform_field_paragraphs_image_add_more');
    $edit = array(
      'title[0][value]' => 'Nested twins',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Nested twins has been created.');
    $this->assertNoText('This entity (paragraph: ) cannot be referenced.');

    // Set the fields as not required.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->clickLink('Edit', 1);
    $this->drupalPostForm(NULL, ['required' => FALSE], t('Save settings'));

    // Set the Paragraph field edit mode to "Closed" and the closed mode to
    // "Summary".
    $settings = [
      'edit_mode' => 'closed',
      'closed_mode' => 'summary',
    ];
    $this->setParagraphsWidgetSettings('article', 'field_paragraphs', $settings);

    $this->addParagraphsType('node_test');

    // Add a required node reference field.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/node_test', 'entity_reference', 'Entity reference', 'entity_reference', array(
      'settings[target_type]' => 'node',
      'cardinality' => '-1'
    ), [
      'settings[handler_settings][target_bundles][article]' => TRUE,
      'required' => TRUE,
    ]);
    $node = $this->drupalGetNodeByTitle('Nested twins');

    // Create a node with a reference in a Paragraph.
    $this->drupalPostAjaxForm('node/add/article', [], 'field_paragraphs_node_test_add_more');
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $edit = [
      'field_paragraphs[0][subform][field_entity_reference][0][target_id]' => $node->label() . ' (' . $node->id() . ')',
      'title[0][value]' => 'choke test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Delete the referenced node.
    $node->delete();
    // Edit the node with the reference.
    $this->clickLink(t('Edit'));

    // Adding another required paragraph and deleting that again should not
    // validate closed paragraphs but trying to save the node should.
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_node_test_add_more');
    $this->assertNoText('The referenced entity (node: ' . $node->id() . ') does not exist.');
    $this->assertFieldByName('field_paragraphs[1][subform][field_entity_reference][0][target_id]');
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_1_remove');
    $this->assertNoText('The referenced entity (node: ' . $node->id() . ') does not exist.');
    $this->assertNoFieldByName('field_paragraphs[1][subform][field_entity_reference][0][target_id]');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('The referenced entity (node: ' . $node->id() . ') does not exist.');

    // Attempt to edit the Paragraph.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    // Validate that the reference is removed.
    $this->assertNoErrorsLogged();
    // Try to collapse with an invalid reference.
    $this->drupalPostAjaxForm(NULL, ['field_paragraphs[0][subform][field_entity_reference][0][target_id]' => 'foo'], 'field_paragraphs_0_collapse');
    // Paragraph should be still in edit mode.
    $this->assertFieldByName('field_paragraphs[0][subform][field_entity_reference][0][target_id]');
    $this->assertFieldByName('field_paragraphs[0][subform][field_entity_reference][1][target_id]');
    // Assert the validation message.
    $this->assertText('There are no entities matching "foo".');
    // Fix the broken reference.
    $node = $this->drupalGetNodeByTitle('Example publish/unpublish');
    $edit = ['field_paragraphs[0][subform][field_entity_reference][0][target_id]' => $node->label() . ' (' . $node->id() . ')'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('choke test has been updated.');
    $this->assertLink('Example publish/unpublish');
    // Delete the new referenced node.
    $node->delete();

    // Set the Paragraph field closed mode to "Preview".
    $settings = [
      'edit_mode' => 'closed',
      'closed_mode' => 'preview',
    ];
    $this->setParagraphsWidgetSettings('article', 'field_paragraphs', $settings);

    $node = $this->drupalGetNodeByTitle('choke test');
    // Attempt to edit the Paragraph.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Attempt to edit the Paragraph.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    // Try to save with an invalid reference.
    $edit = ['field_paragraphs[0][subform][field_entity_reference][0][target_id]' => 'foo'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('There are no entities matching "foo".');
    // Remove the Paragraph and save the node.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_remove');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('choke test has been updated.');

    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, ['description' => 'This is the description of the field.'], 'Save settings');
    // Verify that the text displayed is correct when no paragraph has been
    // added yet.
    $this->drupalGet('node/add/article');
    $this->assertText('This is the description of the field.');
    $elements = $this->xpath('//table[@id="field-paragraphs-values"]/tbody');
    $header = $this->xpath('//table[@id="field-paragraphs-values"]/thead');
    $this->assertEqual($elements, []);
    $this->assertNotEqual($header, []);

    $this->drupalGet('admin/content/files');
    $this->clickLink('1 place');
    $label = $this->xpath('//tbody/tr/td[1]');
    $this->assertEqual(trim(htmlspecialchars_decode(strip_tags($label[0]->asXML()))), 'test required > field_paragraphs > Paragraphs');
  }

  /**
   * Asserts that a select option in the current page is checked.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   *
   * @todo Remove function once core issue is resolved: https://www.drupal.org/node/2530092
   */
  protected function assertOptionSelected($id, $option, $message = '', $group = 'Browser') {
    $elements = $this->xpath('//select[contains(@id, :id)]//option[@value=:option]', array(':id' => $id, ':option' => $option));
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['selected']), $message ? $message : SafeMarkup::format('Option @option for field @id is selected.', array('@option' => $option, '@id' => $id)), $group);
  }

  /**
   * Helper function for revision counting.
   */
  private function countRevisions($node, $paragraph1, $paragraph2, $revisions_count) {
    $node_revisions_count = \Drupal::entityQuery('node')->condition('nid', $node->id())->allRevisions()->count()->execute();
    $this->assertEqual($node_revisions_count, $revisions_count);
    $this->assertEqual(\Drupal::entityQuery('paragraph')->condition('id', $paragraph1)->allRevisions()->count()->execute(), $revisions_count);
    $this->assertEqual(\Drupal::entityQuery('paragraph')->condition('id', $paragraph2)->allRevisions()->count()->execute(), $revisions_count);
  }

}
