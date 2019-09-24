<?php
/**
 * https://civicrm.org/licensing
 */

use CRM_Smartdebit_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
// FIXME: require trait, should not have to hardcode path like this
require_once(__DIR__ . '/../../../../Civi/Test/SmartdebitTestTrait.php');

class CRM_Smartdebit_DateUtilsTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\SmartdebitTestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->install(['org.civicrm.smartdebit'])
      ->apply();
  }

  public function setUp() {
    parent::setUp();
    $this->setUpHeadless();
    $this->smartdebitPaymentProcessorCreate();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * @dataProvider dataProviderGetNextAvailableCollectionDate
   */
  public function testGetNextAvailableCollectionDate($timestamp, $collectionDay, $expectedCollectionDate, $noticePeriod, $collectionDays) {
    Civi::settings()->set('smartdebit_collection_interval', $noticePeriod);
    Civi::settings()->set('smartdebit_notice_period', $noticePeriod);
    Civi::settings()->set('smartdebit_collection_days', $collectionDays);

    timecop_travel(strtotime($timestamp));
    $firstCollectionDate = CRM_Smartdebit_DateUtils::getNextAvailableCollectionDate($collectionDay);
    $this->assertEquals($expectedCollectionDate, $firstCollectionDate->format('Y-m-d'), "timestamp: {$timestamp} collectionDay: {$collectionDay} expectedDate: {$expectedCollectionDate} noticePeriod: {$noticePeriod} collectionDays: {$collectionDays}");
  }

  public function dataProviderGetNextAvailableCollectionDate() {
    // start_timestamp, collection_day, expected_collection_date, notice_period, collectiondays
    $data = [
      ['2018-01-01', 1, '2018-02-01', 10, '1,20'],
      ['2018-01-01', 2, '2018-02-02', 10, '2'],
      ['2018-01-25', 1, '2018-03-01', 10, '1,20'],
      ['2018-01-19', 20, '2018-02-20', 10, '1,20'],
      ['2018-01-27', 7, '2018-02-07', 10, NULL],
      ['2018-01-26', 5, '2018-03-05', 10, '5'],
      ['2018-01-27', 5, '2018-03-05', 10, '5'],
      ['2019-05-21', 1, '2019-06-01', 10, '1,20'],
      ['2019-05-21', 1, '2019-06-01', 10, '1,15'],
      ['2019-05-21', 15, '2019-06-15', 10, '1,15'],
      ['2019-05-21', 1, '2019-07-01', 11, '1,15'],
      ['2019-05-21', 15, '2019-06-15', 11, '1,15'],
    ];
    return $data;
  }

  /**
   * @dataProvider dataProviderFormatPreferredCollectionDays
   *
   */
  public function testFormatPreferredCollectionDay($day, $expectedResult) {
    $result = CRM_Smartdebit_DateUtils::formatPreferredCollectionDay($day);
    $this->assertEquals($expectedResult, $result);
  }

  public function dataProviderFormatPreferredCollectionDays() {
    for ($index = 1; $index<32; $index++) {
      if (in_array($index, [1,21,31])) {
        $end = 'st';
      }
      elseif (in_array($index, [2,22])) {
        $end = 'nd';
      }
      elseif (in_array($index, [3,23])) {
        $end = 'rd';
      }
      else {
        $end = 'th';
      }
      $data[] = [$index, $index . $end];
    }
    return $data;
  }

  /**
   * @dataProvider dataProviderGetCollectionDaysOptions
   */
  public function testGetCollectionDaysOptions($timestamp, $colInterval, $colDays, $formatted, $expected) {
    timecop_travel(strtotime($timestamp));
    $settings = ['collection_interval' => $colInterval, 'collection_days' => $colDays];
    CRM_Smartdebit_Settings::save($settings);
    $result = CRM_Smartdebit_DateUtils::getCollectionDaysOptions($formatted);
    $orderMatches = ($result === $expected);
    $this->assertEquals($expected, $result);
    $this->assertTrue($orderMatches, 'The order of the array does not match');
  }

  public function dataProviderGetCollectionDaysOptions() {
    // start_timestamp, collection_interval, collection_days, expected
    return [
      ['2018-01-01', 10, '1,20', TRUE, [20 => '20th', 1 => '1st']],
      ['2018-01-21', 10, '1,20', TRUE, [1 => '1st', 20 => '20th']],
      ['2018-01-19', 10, '1,20', TRUE, [1 => '1st', 20 => '20th']],
      ['2018-01-01', 10, '1,20', FALSE, [20 => '20', 1 => '1']],
      ['2018-01-21', 10, '1,20', FALSE, [1 => '1', 20 => '20']],
      ['2018-01-19', 10, '1,20', FALSE, [1 => '1', 20 => '20']],
    ];
  }

  /**
   * @dataProvider dataProviderTranslateSmartdebitFrequencytoCiviCRM
   */
  public function testTranslateSmartdebitFrequencytoCiviCRM($sdFactor, $sdUnit, $civiFactor, $civiUnit) {
    list($actualUnit, $actualFactor) = CRM_Smartdebit_DateUtils::translateSmartdebitFrequencytoCiviCRM($sdUnit, $sdFactor);
    $this->assertEquals($civiUnit, $actualUnit);
    $this->assertEquals($civiFactor, $actualFactor);
  }

  public function dataProviderTranslateSmartdebitFrequencytoCiviCRM() {
    // sd_unit, sd_factor, civi_unit, civi_factor
    return [
      [0, 'W', 7, 'day'],
      [1, 'W', 7, 'day'],
      [2, 'W', 14, 'day'],
      [3, 'W', 21, 'day'],
      [4, 'W', 28, 'day'],
      [1, 'M', 1, 'month'],
      [2, 'M', 2, 'month'],
      [1, 'Q', 3, 'month'],
      [4, 'M', 4, 'month'],
      [5, 'M', 5, 'month'],
      [2, 'Q', 6, 'month'],
      [7, 'M', 7, 'month'],
      [8, 'M', 8, 'month'],
      [3, 'Q', 9, 'month'],
      [10, 'M', 10, 'month'],
      [11, 'M', 11, 'month'],
      [1, 'Y', 1, 'year'],
      [2, 'Y', 2, 'year'],
    ];
  }

  /**
   * @dataProvider dataProviderGetNextScheduledDate
   */
  public function testGetNextScheduledDate($paymentDate, $recurParams, $expected) {
    $nextScheduledDate = CRM_Smartdebit_DateUtils::getNextScheduledDate($paymentDate, $recurParams);
    $this->assertEquals($expected, $nextScheduledDate);
  }

  public function dataProviderGetNextScheduledDate() {
    // payment_date, recurparams, expected
    return [
      ['2018-01-01', ['frequency_interval' => 1, 'frequency_unit' => 'month'], '20180201'],
      ['2018-01-01', ['frequency_interval' => 1, 'frequency_unit' => 'year'], '20190101'],
      ['2018-01-01', ['frequency_interval' => 7, 'frequency_unit' => 'day'], '20180108'],
      ['2018-01-31', ['frequency_interval' => 1, 'frequency_unit' => 'month'], '20180228'],
      ['2016-01-31', ['frequency_interval' => 1, 'frequency_unit' => 'month'], '20160229'], // leap year
      ['2018-01-31', ['frequency_interval' => 1, 'frequency_unit' => 'year'], '20190131'],
      ['2018-01-31', ['frequency_interval' => 7, 'frequency_unit' => 'day'], '20180207'],
      ['2018-01-30', ['frequency_interval' => 1, 'frequency_unit' => 'month'], '20180228'],
      ['2016-01-30', ['frequency_interval' => 1, 'frequency_unit' => 'month'], '20160229'], // leap year
      ['2018-01-30', ['frequency_interval' => 1, 'frequency_unit' => 'year'], '20190130'],
      ['2018-01-30', ['frequency_interval' => 7, 'frequency_unit' => 'day'], '20180206'],

    ];
  }

  /**
   * @dataProvider dataProviderTestDateDifference
   */
  public function testDateDifference($date1, $date2, $expectedDays) {
    $days = CRM_Smartdebit_DateUtils::dateDifference($date1, $date2);
    $this->assertEquals($expectedDays, $days);
  }

  public function dataProviderTestDateDifference() {
    // date_1, date_2, expected_days_difference
    return [
      ['2018-01-18 12:01:23', '2018-02-18 12:01:23', 31],
      ['2018-01-18 12:01:23', '2018-02-18 12:01:22', 30],
      ['2018-02-18 12:01:23', '2018-03-18 12:01:23', 28],
      ['2018-02-18 12:01:23', '2018-03-18 12:01:22', 27],
      ['2016-02-18 12:01:23', '2016-03-18 12:01:23', 29], // leap year
    ];
  }

  /**
   * @param $civiFactor
   * @param $civiUnit
   * @param $expectedDays
   * @dataProvider dataProviderDaysDifferenceForFrequency
   */
  public function testDaysDifferenceForFrequency($civiFactor, $civiUnit, $expectedDays) {
    $actualDays = CRM_Smartdebit_DateUtils::daysDifferenceForFrequency($civiUnit, $civiFactor);
    $this->assertEquals($expectedDays, $actualDays);
  }

  public function dataProviderDaysDifferenceForFrequency() {
    // civi_factor, civi_unit, expected_days_difference
    return [
      [7, 'day', 7],
      [14, 'day', 14],
      [21, 'day', 21],
      [28, 'day', 28],
      [1, 'month', 14],
      [2, 'month', 28],
      [3, 'month', 42],
      [4, 'month', 56],
      [5, 'month', 70],
      [6, 'month', 84],
      [7, 'month', 98],
      [8, 'month', 112],
      [9, 'month', 126],
      [10, 'month', 140],
      [11, 'month', 154],
      [1, 'year', 182],
      [2, 'year', 364],
      [1, 'lifetime', 0],
    ];
  }

}
