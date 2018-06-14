<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Tests paragraphs behavior plugins.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalBehaviorsTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['image', 'file', 'views'];

  /**
   * Tests the behavior plugins for paragraphs.
   */
  public function testBehaviorPluginsFields() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Check default configuration.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $this->assertFieldByName('behavior_plugins[test_text_color][settings][default_color]', 'blue');

    $this->assertText('Behavior plugins are only supported by the EXPERIMENTAL paragraphs widget');
    // Enable the test plugins, with an invalid configuration value.
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
      'behavior_plugins[test_text_color][enabled]' => TRUE,
      'behavior_plugins[test_text_color][settings][default_color]' => 'red',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Red can not be used as the default color.');

    // Ensure the form can be saved with an invalid configuration value when
    // the plugin is not selected.
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
      'behavior_plugins[test_text_color][enabled]' => FALSE,
      'behavior_plugins[test_text_color][settings][default_color]' => 'red',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Saved the text_paragraph Paragraphs type.');

    // Ensure it can be saved with a valid value and that the defaults are
    // correct.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $this->assertFieldChecked('edit-behavior-plugins-test-bold-text-enabled');
    $this->assertNoFieldChecked('edit-behavior-plugins-test-text-color-enabled');
    $this->assertFieldByName('behavior_plugins[test_text_color][settings][default_color]', 'blue');

    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
      'behavior_plugins[test_text_color][enabled]' => TRUE,
      'behavior_plugins[test_text_color][settings][default_color]' => 'green',
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));
    $this->assertText('Saved the text_paragraph Paragraphs type.');

    $this->drupalGet('node/add/paragraphed_test');

    // Behavior plugin settings is not available to users without
    // "edit behavior plugin settings" permission.
    $this->assertNoFieldByName('field_paragraphs[0][behavior_plugins][test_text_color][text_color]', 'green');

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Create a node with a Paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->assertFieldByName('field_paragraphs[0][behavior_plugins][test_text_color][text_color]', 'green');
    // Setting a not allowed value in the text color plugin text field.
    $plugin_text = 'green';
    $edit = [
      'title[0][value]' => 'paragraphs_plugins_test',
      'field_paragraphs[0][subform][field_text][0][value]' => 'amazing_plugin_test',
      'field_paragraphs[0][behavior_plugins][test_text_color][text_color]' => $plugin_text,
    ];
    // Assert that the behavior form is after the dropbutton.
    $behavior_xpath = $this->xpath("//div[@id = 'edit-field-paragraphs-0-top']/following-sibling::*[1][@id = 'edit-field-paragraphs-0-behavior-plugins-test-bold-text']");
    $this->assertNotEqual($behavior_xpath, FALSE, 'Behavior form position incorrect');

    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Asserting that the error message is shown.
    $this->assertText('The only allowed values are blue and red.');
    // Updating the text color to an allowed value.
    $plugin_text = 'red';
    $edit = [
      'field_paragraphs[0][behavior_plugins][test_text_color][text_color]' => $plugin_text,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Assert that the class has been added to the element.
    $this->assertRaw('class="red_plugin_text');

    $this->clickLink('Edit');
    // Assert the plugin fields populate the stored values.
    $this->assertFieldByName('field_paragraphs[0][behavior_plugins][test_text_color][text_color]', $plugin_text);

    // Update the value of both plugins.
    $updated_text = 'blue';
    $edit = [
      'field_paragraphs[0][behavior_plugins][test_text_color][text_color]' => $updated_text,
      'field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoRaw('class="red_plugin_text');
    $this->assertRaw('class="bold_plugin_text blue_plugin_text');
    $this->clickLink('Edit');
    // Assert the plugin fields populate the stored values.
    $this->assertFieldByName('field_paragraphs[0][behavior_plugins][test_text_color][text_color]', $updated_text);
    $this->assertFieldByName('field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]', TRUE);

    $this->loginAsAdmin([
      'edit any paragraphed_test content',
    ]);

    $node = $this->getNodeByTitle('paragraphs_plugins_test');
    $this->drupalGet('node/' . $node->id() . '/edit');

    $this->assertNoFieldByName('field_paragraphs[0][behavior_plugins][test_text_color][text_color]', $updated_text);
    $this->assertNoFieldByName('field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]', TRUE);

    $this->drupalPostForm(NULL, [], t('Save'));

    // Make sure that values don't change if a user without the 'edit behavior
    // plugin settings' permission saves a node with paragraphs and enabled
    // behaviors.
    $this->assertRaw('class="bold_plugin_text blue_plugin_text');
    $this->assertNoRaw('class="red_plugin_text');

    // Test plugin applicability. Add a paragraph type.
    $paragraph_type = 'text_paragraph_test';
    $this->addParagraphsType($paragraph_type);
    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text_test', 'Text', 'text_long', [], []);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'image', 'Image', 'image', [], []);
    // Assert if the plugin is listed on the edit form of the paragraphs type.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $this->assertNoFieldByName('behavior_plugins[test_bold_text][enabled]');
    $this->assertFieldByName('behavior_plugins[test_text_color][enabled]');
    $this->assertFieldByName('behavior_plugins[test_field_selection][enabled]');
    $this->assertText('Choose paragraph field to be applied.');
    // Assert that Field Selection Filter plugin properly filters field types.
    $this->assertOptionByText('edit-behavior-plugins-test-field-selection-settings-field-selection-filter', t('Image'));
    // Check that Field Selection Plugin does not filter any field types.
    $this->assertOptionByText('edit-behavior-plugins-test-field-selection-settings-field-selection', t('Image'));
    $this->assertOptionByText('edit-behavior-plugins-test-field-selection-settings-field-selection', t('Text'));

    // Test a plugin without behavior fields.
    $edit = [
      'behavior_plugins[test_dummy_behavior][enabled]' => TRUE,
      'behavior_plugins[test_text_color][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_test_add_more');
    $edit = [
      'title[0][value]' => 'paragraph with no fields',
      'field_paragraphs[0][subform][field_text_test][0][value]' => 'my behavior plugin does not have any field',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('dummy_plugin_text');

    // Tests behavior plugin on paragraph type with no fields.
    $this->addParagraphsType('fieldless');
    $this->drupalPostForm('admin/structure/paragraphs_type/fieldless', ['behavior_plugins[test_dummy_behavior][enabled]' => TRUE], t('Save'));

    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_fieldless_add_more');
    $edit = ['title[0][value]' => t('Fieldless')];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertResponse(200);

    $field_definition = \Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', $paragraph_type)['uid'];
    $field_definition->getConfig($paragraph_type)->save();

    // Enable the test field selection plugin.
    $edit = [
      'behavior_plugins[test_field_selection][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));
    // Assert that the uid field is not shown as an option for the select.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $this->assertNoOption('edit-behavior-plugins-test-field-selection-settings-field-selection', 'uid');
    // Add a paragraphed content.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_test_add_more');
    $edit = [
      'title[0][value]' => 'field_override_test',
      'field_paragraphs[0][subform][field_text_test][0][value]' => 'This is a test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Check that the summary does not have the user displayed.
    $node = $this->getNodeByTitle('field_override_test');
    $this->drupalPostAjaxForm('node/' . $node->id() . '/edit', [], 'field_paragraphs_0_collapse');
    $this->assertRaw('paragraphs-collapsed-description">This is a test');
  }

  /**
   * Tests the behavior plugins summary for paragraphs closed mode.
   */
  public function testCollapsedSummary() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Add a text paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    // Enable plugins for the text paragraph type.
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
      'behavior_plugins[test_text_color][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));

    // Add a nested Paragraph type.
    $paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsField('nested_paragraph', 'paragraphs', 'paragraph');
    // Enable plugins for the nested paragraph type.
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));

    // Add a node and enabled plugins.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_1_subform_paragraphs_text_paragraph_add_more');

    $this->assertField('field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]');
    $this->assertField('field_paragraphs[1][behavior_plugins][test_bold_text][bold_text]');

    $edit = [
      'title[0][value]' => 'collapsed_test',
      'field_paragraphs[0][subform][field_text][0][value]' => 'first_paragraph',
      'field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]' => TRUE,
      'field_paragraphs[1][subform][paragraphs][0][subform][field_text][0][value]' => 'nested_paragraph',
      'field_paragraphs[1][behavior_plugins][test_bold_text][bold_text]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->clickLink('Edit');

    // Assert that info section includes the information from behavior plugins.
    $this->assertFieldByXPath('//*[@id="edit-field-paragraphs-0-top-icons"]/span[@class="paragraphs-icon paragraphs-icon-bold"]');
    $this->assertFieldByXPath('//*[@id="edit-field-paragraphs-1-top-icons"]/span[@class="paragraphs-badge" and @title="1 child"]');
    $this->assertFieldByXPath('//*[@id="edit-field-paragraphs-1-top-icons"]/span[@class="paragraphs-icon paragraphs-icon-bold"]');

    // Assert that the summary includes the text of the behavior plugins.
    $this->assertRaw('class="paragraphs-collapsed-description">first_paragraph, Bold: Yes, Text color: blue');
    $this->assertRaw('class="paragraphs-collapsed-description">nested_paragraph, Bold: No, Text color: blue, Bold: Yes');

    // Add an empty nested paragraph.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_nested_paragraph_add_more');
    $edit = [
      'title[0][value]' => 'collapsed_test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check an empty nested paragraph summary.
    $this->clickLink('Edit');
    $this->assertRaw('class="paragraphs-collapsed-description">');

  }

  /**
   * Tests the behavior plugins subform state submit.
   */
  public function testBehaviorSubform() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Add a text paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    // Enable plugins for the text paragraph type.
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
      'behavior_plugins[test_text_color][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));

    // Add a nested Paragraph type.
    $paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_paragraph', 'nested', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    // Enable plugins for the nested paragraph type.
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));

    // Add a node and enabled plugins.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_subform_field_nested_text_paragraph_add_more');
    $edit = [
      'title[0][value]' => 'collapsed_test',
      'field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]' => 'nested text paragraph',
      'field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]' => TRUE,
      'field_paragraphs[1][subform][field_text][0][value]' => 'first_paragraph',
      'field_paragraphs[1][behavior_plugins][test_bold_text][bold_text]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->clickLink('Edit');
    $edit = [
      'field_paragraphs[0][_weight]' => 1,
      'field_paragraphs[1][behavior_plugins][test_bold_text][bold_text]' => FALSE,
      'field_paragraphs[1][behavior_plugins][test_text_color][text_color]' => 'red',
      'field_paragraphs[1][_weight]' => 0,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoErrorsLogged();
    $this->clickLink('Edit');
    $this->assertFieldByName('field_paragraphs[0][behavior_plugins][test_text_color][text_color]', 'red');

  }
}
