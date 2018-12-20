<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\search_api\Plugin\search_api\processor\IgnoreCharacters;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Ignore characters" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\IgnoreCharacter
 */
class IgnoreCharacterTest extends UnitTestCase {

  use ProcessorTestTrait;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();
    $this->processor = new IgnoreCharacters(['ignorable' => ''], 'ignore_character', []);
  }

  /**
   * Tests preprocessing with different ignorable character sets.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param string $expected_value
   *   The expected processed value.
   * @param string[] $character_classes
   *   The "character_sets" setting to set on the processor.
   *
   * @dataProvider ignoreCharacterSetsDataProvider
   */
  public function testIgnoreCharacterSets($passed_value, $expected_value, array $character_classes) {
    $this->processor->setConfiguration(['strip' => ['character_sets' => $character_classes]]);
    $this->invokeMethod('process', [&$passed_value, 'text']);
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider for testValueConfiguration().
   */
  public function ignoreCharacterSetsDataProvider() {
    return [
      ['word_s', 'words', ['Pc' => 'Pc']],
      ['word⁔s', 'words', ['Pc' => 'Pc']],

      ['word〜s', 'words', ['Pd' => 'Pd']],
      ['w–ord⸗s', 'words', ['Pd' => 'Pd']],

      ['word⌉s', 'words', ['Pe' => 'Pe']],
      ['word⦊s〕', 'words', ['Pe' => 'Pe']],

      ['word»s', 'words', ['Pf' => 'Pf']],
      ['word⸍s', 'words', ['Pf' => 'Pf']],

      ['word⸂s', 'words', ['Pi' => 'Pi']],
      ['w«ord⸉s', 'words', ['Pi' => 'Pi']],

      ['words%', 'words', ['Po' => 'Po']],
      ['wo*rd/s', 'words', ['Po' => 'Po']],

      ['word༺s', 'words', ['Ps' => 'Ps']],
      ['w❮ord⌈s', 'words', ['Ps' => 'Ps']],

      ['word៛s', 'words', ['Sc' => 'Sc']],
      ['wo₥rd₦s', 'words', ['Sc' => 'Sc']],

      ['w˓ords', 'words', ['Sk' => 'Sk']],
      ['wo˘rd˳s', 'words', ['Sk' => 'Sk']],

      ['word×s', 'words', ['Sm' => 'Sm']],
      ['wo±rd؈s', 'words', ['Sm' => 'Sm']],

      ['wo᧧rds', 'words', ['So' => 'So']],
      ['w᧶ord᧲s', 'words', ['So' => 'So']],

      ["wor\x0Ads", 'words', ['Cc' => 'Cc']],
      ["wo\x0Crds", 'words', ['Cc' => 'Cc']],

      ['word۝s', 'words', ['Cf' => 'Cf']],
      ['wo᠎rd؁s', 'words', ['Cf' => 'Cf']],

      ['words', 'words', ['Co' => 'Co']],
      ['wo󿿽rds', 'words', ['Co' => 'Co']],

      ['wordॊs', 'words', ['Mc' => 'Mc']],
      ['worौdংs', 'words', ['Mc' => 'Mc']],

      ['wo⃞rds', 'words', ['Me' => 'Me']],
      ['wor⃤⃟ds', 'words', ['Me' => 'Me']],

      ['woྰrds', 'words', ['Mn' => 'Mn']],
      ['worྵdྶs', 'words', ['Mn' => 'Mn']],

      ['woྰrds', 'words', ['Mn' => 'Mn', 'Pd' => 'Pd', 'Pe' => 'Pe']],
      ['worྵdྶs', 'words', ['Mn' => 'Mn', 'Pd' => 'Pd', 'Pe' => 'Pe']],
    ];
  }

  /**
   * Tests preprocessing with the "Ignorable characters" setting.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param string $expected_value
   *   The expected processed value.
   * @param string $ignorable
   *   The "ignorable" setting to set on the processor.
   *
   * @dataProvider ignorableCharactersDataProvider
   */
  public function testIgnorableCharacters($passed_value, $expected_value, $ignorable) {
    $this->processor->setConfiguration(['ignorable' => $ignorable, 'strip' => ['character_sets' => []]]);
    $this->invokeMethod('process', [&$passed_value, 'text']);
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Provides sets of test parameters for testIgnorableCharacters().
   *
   * @return array
   *   Sets of arguments for testIgnorableCharacters().
   */
  public function ignorableCharactersDataProvider() {
    return [
      ['abcde', 'ace', '[bd]'],
      [['abcde', 'abcdef'], ['ace', 'ace'], '[bdf]'],
      ["ab.c'de", "a.'de", '[b-c]'],
      ['foo 13$%& (bar)[93]', 'foo $%& (bar)[]', '\d'],
    ];
  }

}
