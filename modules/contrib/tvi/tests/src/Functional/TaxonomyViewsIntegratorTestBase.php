<?php

namespace Drupal\Tests\tvi\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;

abstract class TaxonomyViewsIntegratorTestBase extends BrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * The first vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary1;

  /**
   * The second vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary2;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'tvi', 'tvi_test', 'views', 'views_ui', 'taxonomy'];

  /**
   * Stores the first term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * Stores the second term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term2;

  /**
   * Stores the third term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term3;

  /**
   * Stores the fourth term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term4;

  /**
   * Stores the fifth term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term5;

  /**
   * Stores the sixth term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term6;

  /**
   * Stores the seventh term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term7;

  /**
   * Stores the eighth term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term8;

  /**
   * Stores the ninth term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term9;

  /**
   * Stores the tenth term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term10;

  /**
   * Stores the eleventh term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term11;

  /**
   * Stores the twelfth term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term12;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->vocabulary1 = $this->createVocabulary([
      'name' => 'TVI Test Vocab 1',
      'vid' => 'tvi_test_vocab_1',
    ]);

    $this->vocabulary2 = $this->createVocabulary([
      'name' => 'TVI Test Vocab 2',
      'vid' => 'tvi_test_vocab_2',
    ]);

    $this->term1 = $this->createTerm(
      [
        'vid' => $this->vocabulary1->id()
      ]
    );

    $this->term2 = $this->createTerm(
      [
        'vid' => $this->vocabulary1->id()
      ]
    );

    $this->term3 = $this->createTerm(
      [
        'vid' => $this->vocabulary1->id()
      ]
    );

    $this->term4 = $this->createTerm(
      [
        'vid' => $this->vocabulary1->id()
      ]
    );

    $this->term5 = $this->createTerm(
      [
        'vid' => $this->vocabulary1->id(),
        'parent' => $this->term2->id()
      ]
    );

    $this->term6 = $this->createTerm(
      [
        'vid' => $this->vocabulary1->id(),
        'parent' => $this->term2->id()
      ]
    );

    $this->term7 = $this->createTerm(
      [
        'vid' => $this->vocabulary1->id(),
        'parent' => $this->term1->id()
      ]
    );

    $this->term8 = $this->createTerm(
      [
        'vid' => $this->vocabulary2->id()
      ]
    );

    $this->term9 = $this->createTerm(
      [
        'vid' => $this->vocabulary2->id()
      ]
    );

    $this->term10 = $this->createTerm(
      [
        'vid' => $this->vocabulary2->id()
      ]
    );

    $this->term11 = $this->createTerm(
      [
        'vid' => $this->vocabulary2->id(),
        'parent' => $this->term10->id(),
      ]
    );

    $this->term12 = $this->createTerm(
      [
        'vid' => $this->vocabulary2->id(),
        'parent' => $this->term11->id(),
      ]
    );

    $this->createTaxonomyViewsIntegratorConfiguration();
  }

  /**
   * Creates and returns a vocabulary.
   *
   * @param array $settings
   *   (optional) An array of values to override the following default
   *   properties of the term:
   *   - name: A random string.
   *   - vid: Vocabulary ID of self::$vocabulary object.
   *   Defaults to an empty array.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The created taxonomy term.
   */
  protected function createVocabulary(array $settings = []) {
    $settings += [
      'name' => $this->randomMachineName(),
      'vid' => $this->randomMachineName(),
    ];

    $vocabulary = Vocabulary::create($settings);
    $vocabulary->save();

    return $vocabulary;
  }

  /**
   * Creates and returns a taxonomy term.
   *
   * Borrowed from TaxonomyTestBase
   *
   * @param array $settings
   *   (optional) An array of values to override the following default
   *   properties of the term:
   *   - name: A random string.
   *   - description: A random string.
   *   - format: First available text format.
   *   - vid: Vocabulary ID of self::$vocabulary object.
   *   - langcode: LANGCODE_NOT_SPECIFIED.
   *   Defaults to an empty array.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The created taxonomy term.
   */
  protected function createTerm(array $settings = []) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $settings += [
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      // Use the first available text format.
      'format' => $format->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    $term = Term::create($settings);
    $term->save();

    return $term;
  }

  /**
   * Generate TVI configuration for the created vocab and terms.
   */
  protected function createTaxonomyViewsIntegratorConfiguration() {
    // set global default

    // set vocabulary1 config
    $this->config('tvi.taxonomy_vocabulary.' . $this->vocabulary1->id())
      ->set('enable_override', 1)
      ->set('view', 'tvi_page')
      ->set('view_display', 'page_1')
      ->set('inherit_settings', 1)
      ->save();

    // set vocabulary2 config
    $this->config('tvi.taxonomy_vocabulary.' . $this->vocabulary2->id())
      ->set('enable_override', 0)
      ->save();

    // term 1
    $this->config('tvi.taxonomy_term.' . $this->term1->id())
      ->set('enable_override', 1)
      ->set('view', 'tvi_page')
      ->set('view_display', 'page_1')
      ->set('inherit_settings', 0)
      ->save();

    // term 2
    $this->config('tvi.taxonomy_term.' . $this->term2->id())
      ->set('enable_override', 1)
      ->set('view', 'tvi_page')
      ->set('view_display', 'page_2')
      ->set('inherit_settings', 1)
      ->save();

    // term 3
    $this->config('tvi.taxonomy_term.' . $this->term3->id())
      ->set('enable_override', 0)
      ->save();

    // term 4
    $this->config('tvi.taxonomy_term.' . $this->term4->id())
      ->set('enable_override', 0)
      ->save();

    // term 5
    $this->config('tvi.taxonomy_term.' . $this->term5->id())
      ->set('enable_override', 1)
      ->set('view', 'tvi_page')
      ->set('view_display', 'page_1')
      ->set('inherit_settings', 0)
      ->save();

    // term 6
    $this->config('tvi.taxonomy_term.' . $this->term6->id())
      ->set('enable_override', 0)
      ->save();

    // term 7
    $this->config('tvi.taxonomy_term.' . $this->term7->id())
      ->set('enable_override', 0)
      ->save();

    // term 8
    $this->config('tvi.taxonomy_term.' . $this->term8->id())
      ->set('enable_override', 1)
      ->set('view', 'tvi_page')
      ->set('view_display', 'page_1')
      ->set('inherit_settings', 0)
      ->save();

    // term 9
    $this->config('tvi.taxonomy_term.' . $this->term9->id())
      ->set('enable_override', 0)
      ->save();

    // term 10
    $this->config('tvi.taxonomy_term.' . $this->term10->id())
      ->set('enable_override', 1)
      ->set('view', 'tvi_page')
      ->set('view_display', 'page_2')
      ->set('inherit_settings', 1)
      ->save();

    // term 11
    $this->config('tvi.taxonomy_term.' . $this->term11->id())
      ->set('enable_override', 0)
      ->save();

    // term 12
    $this->config('tvi.taxonomy_term.' . $this->term12->id())
      ->set('enable_override', 1)
      ->set('view', 'tvi_page')
      ->set('view_display', 'page_1')
      ->set('inherit_settings', 0)
      ->save();

    $this->refreshVariables();
  }
}