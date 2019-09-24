<?php
/**
 * https://civicrm.org/licensing
 */

namespace Civi\Test;

trait SmartdebitTestTrait {

  /**
   * Create Payment Processor.
   *
   * @return int
   *   Id Payment Processor
   */
  public function smartdebitPaymentProcessorCreate($params = []) {
    $paymentProcessorType = $this->callAPISuccess('PaymentProcessorType', 'get', [
      'name' => "Smart_Debit",
    ]);
    $processorParams = [
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
    ];
    $processorParams = array_merge($processorParams, $params);
    $processor = $this->callAPISuccess('PaymentProcessor', 'get', $processorParams);
    if (!$processor['id']) {
      $processor = $this->callAPISuccess('PaymentProcessor', 'create', $processorParams);
    }
    return $processor['id'];
  }

}
