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

class api_v3_SmartdebitTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\SmartdebitTestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->install(['org.civicrm.paymentlib','org.civicrm.smartdebit'])
      ->apply();
  }

  public function setUp() {
    parent::setUp();
    $this->smartdebitPaymentProcessorCreate();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testGetMandate() {
    $mandateParams = [
      'trxn_id' => 'ABC00000128',
      'refresh' => 1,
      'format' => 'XML',
    ];
    $mandateResult = $this->callAPISuccess('Smartdebit', 'getmandates', $mandateParams);
    $this->assertEquals('55.83', $mandateResult['values'][$mandateParams['trxn_id']]['default_amount']);
  }

}
