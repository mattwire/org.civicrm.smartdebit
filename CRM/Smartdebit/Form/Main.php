<?php

/**
 * This class generates form components for processing Event
 *
 */
class CRM_Smartdebit_Form_Main extends CRM_Core_Form
{
  /**
   * Function to add all the direct debit fields
   * Offline is used for backend functions (eg. New Donation, update Billing Details)
   * @param $form
   * @param bool $useRequired
   * @param bool $offline
   */
  function buildDirectDebitForm(&$form) {
    if ( $form->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_FORM ) {
      if ($form->_name != 'UpdateBilling') {
        self::setDirectDebitFields($form);
      }
    }

    $defaults = array();
    $defaults['ddi_reference'] = CRM_Smartdebit_Base::getDDIReference();

    if ($form->_name == 'UpdateBilling') {
      // Get billing data from Smartdebit
      $subscriptionDetails = $form->getVar('_subscriptionDetails');
      $defaults['ddi_reference'] = $subscriptionDetails->subscription_id;
      if (empty($defaults['ddi_reference'])) {
        CRM_Core_Error::statusBounce('No Reference found for this recurring contribution!');
        return;
      }
    }
    $form->setDefaults($defaults);
  }
}
