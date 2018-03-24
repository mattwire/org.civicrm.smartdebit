<?php

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
class CRM_Test extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
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
   * Create Payment Processor.
   *
   * @return int
   *   Id Payment Processor
   */
  public function smartdebitPaymentProcessorCreate($params = array()) {
    $paymentProcessorType = $this->callAPISuccess('PaymentProcessorType', 'get', array('name' => "Smart_Debit"));
    $processorParams = array(
      'domain_id' => '1',
      'name' => 'Smartdebit',
      'payment_processor_type_id' => $paymentProcessorType['id'],
      'is_active' => '1',
      'is_test' => '0',
      'user_name' => 'sdapitest',
      'password' => 'password',
      'signature' => 'sdtest',
      'url_site' => 'https://secure.ddprocessing.co.uk/',
      'url_api' => 'https://secure.ddprocessing.co.uk/',
      'url_recur' => 'https://secure.ddprocessing.co.uk/',
      'class_name' => 'Payment_Smartdebit',
      'billing_mode' => '1',
      'is_recur' => '1',
      'payment_type' => '1',
      'payment_instrument_id' => 'Debit Card'
    );
    $processorParams = array_merge($processorParams, $params);
    $processor = $this->callAPISuccess('PaymentProcessor', 'create', $processorParams);
    return $processor['id'];
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testGetMandate() {
    $mandateParams = array(
      'trxn_id' => 'ABC00000128',
      'refresh' => 1,
    );
    $mandateResult = $this->callAPISuccess('Smartdebit', 'getmandates', $mandateParams);
    $this->assertEquals('55.83', $mandateResult['values'][$mandateParams['trxn_id']]['default_amount']);
  }

}
