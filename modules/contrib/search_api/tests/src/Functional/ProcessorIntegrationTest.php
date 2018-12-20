<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api_test\PluginTestTrait;

/**
 * Tests the admin UI for processors.
 *
 * @todo Move this whole class into a single IntegrationTest check*() method?
 *
 * @group search_api
 */
class ProcessorIntegrationTest extends SearchApiBrowserTestBase {

  use EntityReferenceTestTrait;
  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'filter',
    'taxonomy',
    'search_api_test_no_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);

    $this->indexId = 'test_index';
    $index = Index::create([
      'name' => 'Test index',
      'id' => $this->indexId,
      'status' => 1,
      'datasource_settings' => [
        'entity:node' => [],
        'entity:user' => [],
      ],
    ]);

    // Add a node and a taxonomy term reference, both of which could be used to
    // create a hierarchy.
    $this->createEntityReferenceField(
      'node',
      'page',
      'term_field',
      NULL,
      'taxonomy_term',
      'default',
      [],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->createEntityReferenceField(
      'node',
      'page',
      'parent_reference',
      NULL,
      'node',
      'default',
      ['target_bundles' => ['page' => 'page']]
    );

    // Index the taxonomy and entity reference fields.
    $term_field = new Field($index, 'term_field');
    $term_field->setType('integer');
    $term_field->setPropertyPath('term_field');
    $term_field->setDatasourceId('entity:node');
    $term_field->setLabel('Terms');
    $index->addField($term_field);

    $parent_reference = new Field($index, 'parent_reference');
    $parent_reference->setType('integer');
    $parent_reference->setPropertyPath('parent_reference');
    $parent_reference->setDatasourceId('entity:node');
    $parent_reference->setLabel('Terms');
    $index->addField($parent_reference);
    $index->save();
  }

  /**
   * Tests the admin UI for processors.
   *
   * Calls the other test methods in this class, named check*Integration(), to
   * avoid the overhead of having one test per processor.
   */
  public function testProcessorIntegration() {
    // Some processors are always enabled.
    $enabled = ['add_url', 'aggregated_field', 'rendered_item'];
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    // Ensure the hidden processors aren't displayed in the UI.
    $this->loadProcessorsTab();
    $hidden = $enabled;
    foreach ($hidden as $processor_id) {
      $this->assertSession()->responseNotContains(Html::escape($processor_id));
    }

    $this->checkAggregatedFieldsIntegration();

    $this->checkContentAccessIntegration();
    $enabled[] = 'content_access';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkEntityBundleBoostIntegration();
    $enabled[] = 'type_boost';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkHighlightIntegration();
    $enabled[] = 'highlight';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkHtmlFilterIntegration();
    $enabled[] = 'html_filter';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkIgnoreCaseIntegration();
    $enabled[] = 'ignorecase';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkIgnoreCharactersIntegration();
    $enabled[] = 'ignore_character';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkEntityStatusIntegration();
    $enabled[] = 'entity_status';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkNoUiIntegration();
    $enabled[] = 'search_api_test_no_ui';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkRoleFilterIntegration();
    $enabled[] = 'role_filter';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkRenderedItemIntegration();

    $this->checkStopWordsIntegration();
    $enabled[] = 'stopwords';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkTokenizerIntegration();
    $enabled[] = 'tokenizer';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkTransliterationIntegration();
    $enabled[] = 'transliteration';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkAddHierarchyIntegration();
    $enabled[] = 'hierarchy';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    $this->checkStemmerIntegration();
    $enabled[] = 'stemmer';
    sort($enabled);
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    // The 'add_url' processor is not available to be removed because it's
    // locked.
    $this->checkUrlFieldIntegration();

    // Check the order of the displayed processors.
    $stages = [
      ProcessorInterface::STAGE_PREPROCESS_INDEX,
      ProcessorInterface::STAGE_PREPROCESS_QUERY,
      ProcessorInterface::STAGE_POSTPROCESS_QUERY,
    ];
    /** @var \Drupal\search_api\Processor\ProcessorInterface[] $processors */
    $processors = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createProcessorPlugins($this->loadIndex());
    $page = $this->getSession()->getPage();
    foreach ($stages as $stage) {
      // Since the order of processors with the same weight is undefined, we
      // can't just use $index->getProcessorsByStage() and assertEquals().
      $previous_weight = NULL;
      $class = 'search-api-stage-wrapper-' . Html::cleanCssIdentifier($stage);
      /** @var \Behat\Mink\Element\NodeElement $element */
      foreach ($page->findAll('css', ".$class tr.draggable") as $element) {
        // Since processors are shown right away when checked in the form, they
        // are even included in the form when disabled – just hidden. We can
        // check the "style" attribute to identify them.
        if (preg_match('/\bsearch-api-processor-weight--([-a-z0-9]+)\b/', $element->getAttribute('class'), $m)) {
          $processor_id = str_replace('-', '_', $m[1]);
          $weight = $processors[$processor_id]->getWeight($stage);
          if ($previous_weight !== NULL) {
            $this->assertGreaterThanOrEqual($previous_weight, $weight, "Correct order of processor $processor_id in stage $stage");
          }
          $previous_weight = $weight;
        }
      }
    }

    // Check whether disabling processors also works correctly.
    $this->loadProcessorsTab();
    $edit = [
      'status[stopwords]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $enabled = array_values(array_diff($enabled, ['stopwords']));
    $actual_processors = array_keys($this->loadIndex()->getProcessors());
    sort($actual_processors);
    $this->assertEquals($enabled, $actual_processors);

    // After disabling some datasource, all related processors should be
    // disabled also.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/edit');
    $this->drupalPostForm(NULL, ['datasources[entity:user]' => FALSE], 'Save');
    $processors = $this->loadIndex()->getProcessors();
    $this->assertArrayNotHasKey('role_filter', $processors);
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/processors');
    $this->assertSession()->pageTextNotContains('Role filter');
  }

  /**
   * Tests that processors discouraged by the backend are correctly hidden.
   *
   * @see https://www.drupal.org/node/2228739
   */
  public function testLimitProcessors() {
    $this->loadProcessorsTab();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Highlight');
    $this->assertSession()->pageTextContains('Ignore character');
    $this->assertSession()->pageTextContains('Tokenizer');
    $this->assertSession()->pageTextContains('Stopwords');

    // Create a new server with the "search_api_test" backend.
    $server = Server::create([
      'id' => 'webtest_server',
      'name' => 'WebTest server',
      'description' => 'WebTest server',
      'backend' => 'search_api_test',
      'backend_config' => [],
    ]);
    $server->save();
    $processors = [
      'highlight',
      'ignore_character',
      'tokenizer',
      'stopwords',
    ];
    $this->setReturnValue('backend', 'getDiscouragedProcessors', $processors);

    // Use the newly created server.
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($settings_path);
    $this->submitForm(['server' => 'webtest_server'], 'Save');

    // Load the processors again and check that they are not shown anymore.
    $this->loadProcessorsTab();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Highlight');
    $this->assertSession()->pageTextNotContains('Ignore character');
    $this->assertSession()->pageTextNotContains('Tokenizer');
    $this->assertSession()->pageTextNotContains('Stopwords');
  }

  /**
   * Tests the integration of the "Aggregated fields" processor.
   */
  public function checkAggregatedFieldsIntegration() {
    $index = $this->loadIndex();
    $index->removeProcessor('aggregated_field');
    $index->save();

    $this->assertTrue($this->loadIndex()->isValidProcessor('aggregated_field'), 'The "Aggregated fields" processor cannot be disabled.');

    $options['query']['datasource'] = '';
    $this->drupalGet($this->getIndexPath('fields/add/nojs'), $options);

    // See \Drupal\search_api\Tests\IntegrationTest::addField().
    $this->assertSession()->responseContains('name="aggregated_field"');
    $this->submitForm([], 'aggregated_field');
    $args['%label'] = 'Aggregated field';
    $this->assertSession()->responseContains(new FormattableMarkup('Field %label was added to the index.', $args));
    $this->assertSession()->addressEquals($this->getIndexPath('fields/edit/aggregated_field'));
    $edit = [
      'type' => 'first',
      'fields[entity:node/title]' => 'title',
      'fields[entity:node/type]' => 'type',
      'fields[entity:node/uid]' => 'uid',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->addressEquals($this->getIndexPath('fields'));
    $this->assertSession()->responseContains('The field configuration was successfully saved.');
  }

  /**
   * Tests the UI for the "Content access" processor.
   */
  public function checkContentAccessIntegration() {
    $this->enableProcessor('content_access');

    // Ensure the fields required for the "Content access" processor are now
    // indexed.
    $index = $this->loadIndex();
    $index->save();
    $content_access_fields = [
      'status' => [
        'datasource_id' => 'entity:node',
        'property_path' => 'status',
        'type' => 'boolean',
        'indexed_locked' => TRUE,
        'type_locked' => TRUE,
      ],
      'uid' => [
        'datasource_id' => 'entity:node',
        'property_path' => 'uid',
        'type' => 'integer',
        'indexed_locked' => TRUE,
        'type_locked' => TRUE,
      ],
      'node_grants' => [
        'property_path' => 'search_api_node_grants',
        'type' => 'string',
        'indexed_locked' => TRUE,
        'type_locked' => TRUE,
        'hidden' => TRUE,
      ],
    ];
    $index_fields = $index->getFields();
    foreach ($content_access_fields as $field_id => $settings) {
      $this->assertTrue(!empty($index_fields[$field_id]), "Field $field_id (required by \"Content access\" processor) is present.");
      $field_settings = $index_fields[$field_id]->getSettings();
      unset($field_settings['label'], $field_settings['dependencies']);
      $this->assertEquals($settings, $field_settings, "Field $field_id has the correct settings.");
    }
  }

  /**
   * Tests the UI for the "Type-specific boosting" processor.
   */
  public function checkEntityBundleBoostIntegration() {
    $configuration = [
      'boosts' => [
        'entity:node' => [
          'datasource_boost' => '3.0',
          'bundle_boosts' => [
            'article' => '5.0',
          ],
        ],
        'entity:user' => [
          'datasource_boost' => '1.0',
        ],
      ],
    ];
    $form_values = $configuration;
    $form_values['boosts']['entity:node']['bundle_boosts']['page'] = '';

    $this->editSettingsForm($configuration, 'type_boost', $form_values);
  }

  /**
   * Tests the UI for the "Highlight" processor.
   */
  public function checkHighlightIntegration() {
    $configuration = [
      'highlight' => 'never',
      'excerpt' => FALSE,
      'excerpt_length' => 128,
      'prefix' => '<em>',
      'suffix' => '</em>',
    ];
    $this->editSettingsForm($configuration, 'highlight');
  }

  /**
   * Tests the UI for the "HTML filter" processor.
   */
  public function checkHtmlFilterIntegration() {
    $configuration = [
      'tags' => <<<TAGS
h1: 4
foo bar
TAGS
    ];
    $this->checkValidationError($configuration, 'html_filter', 'Tags is not a valid YAML map.');
    $configuration = [
      'tags' => <<<TAGS
h1:
  - 1
  - 2
h2: foo
h3: -1
TAGS
    ];
    $errors = [
      new FormattableMarkup("Boost value for tag @tag can't be an array.", ['@tag' => '<h1>']),
      new FormattableMarkup('Boost value for tag @tag must be numeric.', ['@tag' => '<h2>']),
      new FormattableMarkup('Boost value for tag @tag must be non-negative.', ['@tag' => '<h3>']),
    ];
    $this->checkValidationError($configuration, 'html_filter', $errors);

    $configuration = $form_values = [
      'title' => FALSE,
      'alt' => FALSE,
      'tags' => [
        'h1' => 10,
      ],
    ];
    $form_values['tags'] = 'h1: 10';
    $this->editSettingsForm($configuration, 'html_filter', $form_values);
  }

  /**
   * Tests the UI for the "Ignore case" processor.
   */
  public function checkIgnoreCaseIntegration() {
    $this->editSettingsForm([], 'ignorecase');
  }

  /**
   * Tests the UI for the "Ignore characters" processor.
   */
  public function checkIgnoreCharactersIntegration() {
    $configuration = [
      'ignorable' => ':)',
    ];
    $this->checkValidationError($configuration, 'ignore_character', 'The entered text is no valid regular expression.');

    $configuration = $form_values = [
      'ignorable' => '[¿¡!?,.]',
      'strip' => [
        'character_sets' => [
          'Pc' => 'Pc',
          'Pd' => 'Pd',
          'Pe' => 'Pe',
          'Pf' => 'Pf',
          'Pi' => 'Pi',
          'Po' => 'Po',
          'Ps' => 'Ps',
          'Cc' => 'Cc',
          'Cf' => FALSE,
          'Co' => FALSE,
          'Mc' => FALSE,
          'Me' => FALSE,
          'Mn' => FALSE,
          'Sc' => FALSE,
          'Sk' => FALSE,
          'Sm' => FALSE,
          'So' => FALSE,
          'Zl' => FALSE,
          'Zp' => FALSE,
          'Zs' => FALSE,
        ],
      ],
    ];
    $this->editSettingsForm($configuration, 'ignore_character', $form_values);
  }

  /**
   * Tests the UI for the "Entity status" processor.
   */
  public function checkEntityStatusIntegration() {
    $this->enableProcessor('entity_status');
  }

  /**
   * Tests the "No UI" test processor.
   */
  public function checkNoUiIntegration() {
    $this->loadProcessorsTab();
    $this->assertSession()->pageTextNotContains('No UI processor');

    // Ensure that the processor can still be enabled programmatically – and
    // stays enabled when submitting the processors form.
    $index = $this->loadIndex();
    $processor = \Drupal::getContainer()->get('search_api.plugin_helper')
      ->createProcessorPlugin($index, 'search_api_test_no_ui');
    $index->addProcessor($processor)->save();
  }

  /**
   * Tests the UI for the "Role filter" processor.
   */
  public function checkRoleFilterIntegration() {
    $this->enableProcessor('role_filter');

    $configuration = [
      'default' => 1,
      'roles' => [
        'anonymous',
      ],
    ];
    $this->editSettingsForm($configuration, 'role_filter');
  }

  /**
   * Tests the integration of the "Rendered item" processor.
   */
  public function checkRenderedItemIntegration() {
    $index = $this->loadIndex();
    $index->removeProcessor('rendered_item');
    $index->save();

    $this->assertTrue($this->loadIndex()->isValidProcessor('rendered_item'), 'The "Rendered item" processor cannot be disabled.');

    $options['query']['datasource'] = '';
    $this->drupalGet($this->getIndexPath('fields/add/nojs'), $options);

    // See \Drupal\search_api\Tests\IntegrationTest::addField().
    $this->assertSession()->responseContains('name="rendered_item"');
    $this->submitForm([], 'rendered_item');
    $args['%label'] = 'Rendered HTML output';
    $this->assertSession()->responseContains(new FormattableMarkup('Field %label was added to the index.', $args));
    $this->assertSession()->addressEquals($this->getIndexPath('fields/edit/rendered_item'));
    $edit = [
      'roles[]' => ['authenticated'],
      'view_mode[entity:node][article]' => 'default',
      'view_mode[entity:node][page]' => 'teaser',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->addressEquals($this->getIndexPath('fields'));
    $this->assertSession()->responseContains('The field configuration was successfully saved.');
  }

  /**
   * Tests the UI for the "Stopwords" processor.
   */
  public function checkStopWordsIntegration() {
    $configuration = [
      'stopwords' => ['the'],
    ];
    $form_values = [
      'stopwords' => 'the',
    ];
    $this->editSettingsForm($configuration, 'stopwords', $form_values);
  }

  /**
   * Tests the UI for the "Tokenizer" processor.
   */
  public function checkTokenizerIntegration() {
    $configuration = [
      'spaces' => '[:foobar:]',
    ];
    $this->checkValidationError($configuration, 'tokenizer', 'The entered text is no valid PCRE character class.');

    $configuration = [
      'spaces' => '',
      'overlap_cjk' => FALSE,
      'minimum_word_size' => 2,
    ];
    $this->editSettingsForm($configuration, 'tokenizer');
  }

  /**
   * Tests the UI for the "Transliteration" processor.
   */
  public function checkTransliterationIntegration() {
    $this->editSettingsForm([], 'transliteration');
  }

  /**
   * Tests the hierarchy processor.
   */
  protected function checkAddHierarchyIntegration() {
    $configuration = [
      'fields' => [
        'term_field' => 'taxonomy_term-parent',
        'parent_reference' => 'node-parent_reference',
      ],
    ];
    $edit = [
      'fields' => [
        'term_field' => ['status' => 1],
        'parent_reference' => ['status' => 1],
      ],
    ];
    $this->editSettingsForm($configuration, 'hierarchy', $edit, TRUE, FALSE);
  }

  /**
   * Tests the UI for the "Stemmer" processor.
   */
  public function checkStemmerIntegration() {
    $this->enableProcessor('stemmer');
    $configuration = [
      'exceptions' => ['indian' => 'india'],
    ];
    $form_values = [
      'exceptions' => 'indian=india',
    ];
    $this->editSettingsForm($configuration, 'stemmer', $form_values);
  }

  /**
   * Tests the integration of the "URL field" processor.
   */
  public function checkUrlFieldIntegration() {
    $index = $this->loadIndex();
    $index->removeProcessor('add_url');
    $index->save();

    $this->assertTrue($this->loadIndex()->isValidProcessor('add_url'), 'The "Add URL" processor cannot be disabled.');
  }

  /**
   * Tests that a processor can be enabled.
   *
   * @param string $processor_id
   *   The ID of the processor to enable.
   */
  protected function enableProcessor($processor_id) {
    $this->loadProcessorsTab();

    $edit = [
      "status[$processor_id]" => 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertTrue($this->loadIndex()->isValidProcessor($processor_id), "Successfully enabled the '$processor_id' processor.'");
  }

  /**
   * Enables a processor with a given configuration.
   *
   * @param array $configuration
   *   The configuration to set for the processor.
   * @param string $processor_id
   *   The ID of the processor to edit.
   * @param array|null $form_values
   *   (optional) The processor configuration to set, as it appears in the form.
   *   Only relevant if the processor does some processing on the form values
   *   before storing them, like parsing YAML or cleaning up checkboxes values.
   *   Defaults to using $configuration as-is.
   * @param bool $enable
   *   (optional) If TRUE, explicitly enable the processor. If FALSE, it should
   *   already be enabled.
   * @param bool $unset_fields
   *   (optional) If TRUE, the "fields" property will be removed from the
   *   actual configuration prior to comparing with the given configuration.
   */
  protected function editSettingsForm(array $configuration, $processor_id, array $form_values = NULL, $enable = TRUE, $unset_fields = TRUE) {
    if (!isset($form_values)) {
      $form_values = $configuration;
    }

    $this->loadProcessorsTab();

    $edit = $this->getFormValues($form_values, "processors[$processor_id][settings]");
    if ($enable) {
      $edit["status[$processor_id]"] = 1;
    }
    $this->submitForm($edit, 'Save');

    $processor = $this->loadIndex()->getProcessor($processor_id);
    $this->assertTrue($processor, "Successfully enabled the '$processor_id' processor.'");
    if ($processor) {
      $actual_configuration = $processor->getConfiguration();
      unset($actual_configuration['weights']);
      if ($unset_fields) {
        unset($actual_configuration['fields']);
      }
      $configuration += $processor->defaultConfiguration();
      $this->assertEquals($configuration, $actual_configuration, "Processor configuration for processor '$processor_id' was set correctly.");
    }
  }

  /**
   * Makes sure that the given form values will fail when submitted.
   *
   * @param array $form_values
   *   The form values, relative to the processor form.
   * @param string $processor_id
   *   The processor's ID.
   * @param string[]|string $messages
   *   Either the expected error message or an array of expected error messages.
   */
  protected function checkValidationError(array $form_values, $processor_id, $messages) {
    $this->loadProcessorsTab();

    $edit = $this->getFormValues($form_values, "processors[$processor_id][settings]");
    $edit["status[$processor_id]"] = 1;
    $this->submitForm($edit, 'Save');

    if (!is_array($messages)) {
      $messages = [$messages];
    }
    foreach ($messages as $message) {
      $this->assertSession()->responseContains($message);
    }
    $this->assertSession()->pageTextNotContains('The indexing workflow was successfully edited.');
    $this->assertSession()->pageTextNotContains('No values were changed.');

    $this->loadProcessorsTab(TRUE);
  }

  /**
   * Converts a configuration array into an array of form values.
   *
   * @param array $configuration
   *   The configuration to convert.
   * @param string $prefix
   *   The common prefix for all form values.
   *
   * @return string[]
   *   An array of form values ready for submission.
   */
  protected function getFormValues(array $configuration, $prefix) {
    $edit = [];

    foreach ($configuration as $key => $value) {
      $key = $prefix . "[$key]";
      if (is_array($value)) {
        // Handling of numerically indexed and associative arrays needs to be
        // different.
        if ($value == array_values($value)) {
          $key .= '[]';
          $edit[$key] = $value;
        }
        else {
          $edit += $this->getFormValues($value, $key);
        }
      }
      else {
        $edit[$key] = $value;
      }
    }

    return $edit;
  }

  /**
   * Loads the test index's "Processors" tab in the test browser, if necessary.
   *
   * @param bool $force
   *   (optional) If TRUE, even load the tab if we are already on it.
   */
  protected function loadProcessorsTab($force = FALSE) {
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/processors';
    if ($force || $this->getAbsoluteUrl($settings_path) != $this->getUrl()) {
      $this->drupalGet($settings_path);
    }
  }

  /**
   * Loads the search index used by this test.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index used by this test.
   */
  protected function loadIndex() {
    $index_storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $index_storage->resetCache([$this->indexId]);

    return $index_storage->load($this->indexId);
  }

}
