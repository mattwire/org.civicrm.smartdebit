<?php
/**
 * https://civicrm.org/licensing
 */

require_once 'smartdebit.civix.php';
use CRM_Smartdebit_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function smartdebit_civicrm_config(&$config) {
  _smartdebit_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function smartdebit_civicrm_xmlMenu(&$files) {
  _smartdebit_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function smartdebit_civicrm_install() {
  _smartdebit_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function smartdebit_civicrm_postInstall() {
  // Create an Direct Debit Activity Type
  // See if we already have this type
  $ddActivity = civicrm_api3('OptionValue', 'get', [
    'option_group_id' => "activity_type",
    'name' => "Direct Debit Sign Up",
  ]);
  if (empty($ddActivity['count'])) {
    $activityParams = [
      'version' => '3'
    , 'option_group_id' => "activity_type"
    , 'name' => 'Direct Debit Sign Up'
    , 'description' => 'Direct Debit Sign Up'
    ];
    $activityType = civicrm_api('OptionValue', 'Create', $activityParams);
    $activityTypeId = $activityType['values'][$activityType['id']]['value'];
    CRM_Smartdebit_Settings::save(['activity_type' => $activityTypeId]);
  }

  // Create an Direct Debit Payment Instrument
  $paymentInstrumentId = CRM_Core_Payment_Smartdebit::createPaymentInstrument(['name' => 'Direct Debit']);
  CRM_Smartdebit_Settings::save(['payment_instrument_id' => $paymentInstrumentId]);

  _smartdebit_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function smartdebit_civicrm_uninstall() {
  _smartdebit_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function smartdebit_civicrm_enable() {
  _smartdebit_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function smartdebit_civicrm_disable() {
  _smartdebit_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function smartdebit_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _smartdebit_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function smartdebit_civicrm_managed(&$entities) {
  _smartdebit_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function smartdebit_civicrm_caseTypes(&$caseTypes) {
  _smartdebit_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function smartdebit_civicrm_angularModules(&$angularModules) {
  _smartdebit_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function smartdebit_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _smartdebit_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function smartdebit_civicrm_navigationMenu(&$menu) {
  $item[] =  [
    'label' => ts('Smart Debit', ['domain' => 'org.civicrm.smartdebit']),
    'name'       => 'Smart Debit',
    'url'        => NULL,
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/CiviContribute', $item[0]);

  $item[] = [
    'label' => ts('Manual Sync', ['domain' => 'org.civicrm.smartdebit']),
    'name'  => 'Manual Sync',
    'url'   => 'civicrm/smartdebit/syncsd?reset=1',
    'permission' => 'access CiviContribute',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/Smart Debit', $item[1]);

  $item[] = [
    'label' => ts('View Results of last Sync', ['domain' => 'org.civicrm.smartdebit']),
    'name'  => 'View Results of last Sync',
    'url'   => 'civicrm/smartdebit/syncsd/confirm?state=done',
    'permission' => 'access CiviContribute',
    'operator'   => NULL,
    'separator'  => 1,
  ];
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/Smart Debit', $item[2]);

  $item[] =  [
    'label' => ts('Reconcile Transactions', ['domain' => 'org.civicrm.smartdebit']),
    'name' => 'Reconcile Transactions',
    'url' => 'civicrm/smartdebit/reconciliation/list?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => 1,
  ];
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/Smart Debit', $item[3]);

  $item[] = [
    'label' => ts('Diagnostics', ['domain' => 'org.civicrm.smartdebit']),
    'name'       => 'Diagnostics',
    'url'        => 'civicrm/admin/smartdebit/diagnostics?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/Smart Debit', $item[4]);

  $item[] = [
    'label' => ts('General Setup', ['domain' => 'org.civicrm.smartdebit']),
    'name'       => 'General Setup',
    'url'        => 'civicrm/admin/smartdebit/settings?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/Smart Debit', $item[5]);

  _smartdebit_civix_navigationMenu($menu);
}

/**
 * Implementation of hook_civicrm_pageRun
 * This adds Smart Debit details to "View Recurring Contribution"
 *
 * @param $page
 *
 * @throws \CRM_Core_Exception
 * @throws \CiviCRM_API3_Exception
 */
function smartdebit_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Contribute_Page_ContributionRecur') {
    // On the view recurring contribution page we add some info from smart debit if we have it
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    $contactID = $page->getVar('_contactId');
    if (CRM_Contact_BAO_Contact_Permission::allow($userID, CRM_Core_Permission::EDIT)) {
      $recurID = $page->getVar('_id');

      $recurParams = [
        'options' => ['sort' => "id DESC", 'limit' => 1],
        'return' => ['id', 'trxn_id', 'payment_processor_id', 'is_test'],
        'id' => $recurID,
        'contact_id' => $contactID,
      ];

      try {
        // Get the recur details and payment processor details
        $recurDetails = civicrm_api3('ContributionRecur', 'getsingle', $recurParams);
        $paymentProcessorDetails = civicrm_api3('PaymentProcessor', 'getsingle', [
          'return' => ["payment_processor_type_id.name"],
          'id' => $recurDetails['payment_processor_id'],
        ]);
        if ($paymentProcessorDetails['payment_processor_type_id.name'] !== 'Smart_Debit') {
          return;
        }
      }
      catch (Exception $e) {
        return;
      }

      $contributionRecurDetails = [];
      if (!empty($recurDetails['trxn_id'])) {
        $smartDebitMandate = CRM_Smartdebit_Mandates::getbyReference($recurDetails);
        if ($smartDebitMandate) {
          $contributionRecurDetails = CRM_Smartdebit_Form_Payerdetails::formatDetails($smartDebitMandate);
          $refreshed = CRM_Utils_Request::retrieve('refreshed', 'Boolean');
          if (CRM_Smartdebit_Sync::updateRecur($smartDebitMandate) && !$refreshed) {
            // Reload the page so we show correct info
            CRM_Utils_System::redirect(CRM_Utils_Array::value('REQUEST_URI', $_SERVER) . '&refreshed=1');
          }
        }
      }
      // Add Smart Debit details via js
      CRM_Core_Resources::singleton()->addVars('smartdebit', ['recurdetails' => $contributionRecurDetails]);
      CRM_Core_Resources::singleton()->addScriptFile('org.civicrm.smartdebit', 'js/recurdetails.js');
      $contributionRecurDetails = json_encode($contributionRecurDetails);
      $page->assign('contributionRecurDetails', $contributionRecurDetails);
    }
  }
}

/**
 * Intercept form functions
 * @param $formName
 * @param $form
 */
function smartdebit_civicrm_buildForm($formName, &$form) {
  if ($form->isSubmitted()) return;

  //Smart Debit
  if (isset($form->_paymentProcessorObj) && ($form->_paymentProcessorObj instanceof CRM_Core_Payment_Smartdebit)
    || (isset($form->_paymentProcessor['payment_processor_type']) && ($form->_paymentProcessor['payment_processor_type'] == 'Smart_Debit'))) {
    if ($formName == 'CRM_Contribute_Form_Contribution_Confirm') {
      // Confirm Contribution (check details and confirm)
      // Show the direct debit agreement on the confirm page
      CRM_Core_Region::instance('contribution-confirm-billing-block')->update('default', [
        'disabled' => TRUE,
      ]);
      $form->assign('dd_details', CRM_Smartdebit_Base::getDDFormDetails($form->_params));
      CRM_Core_Region::instance('contribution-confirm-billing-block')->add([
        'template' => 'CRM/Contribute/Form/Contribution/DirectDebitAgreement.tpl',
      ]);
    }
    elseif ($formName == 'CRM_Contribute_Form_Contribution_ThankYou') {
      // Contribution Thankyou form
      // Show the direct debit mandate on the thankyou page
      $form->assign('dd_details', CRM_Smartdebit_Base::getDDFormDetails($form->_params));
      CRM_Core_Region::instance('contribution-thankyou-billing-block')->update('default', [
        'disabled' => TRUE,
      ]);
      CRM_Core_Region::instance('contribution-thankyou-billing-block')->add([
        'template' => 'CRM/Contribute/Form/Contribution/DirectDebitMandate.tpl',
      ]);
    } elseif ($formName == 'CRM_Contribute_Form_UpdateSubscription') {
      // Accessed when you click edit on a recurring contribution
      $recurID = $form->getVar('contributionRecurID');
      try {
        $recurRecord = civicrm_api3('ContributionRecur', 'getsingle', [
          'id' => $recurID,
          'options' => ['limit' => 1],
        ]);
      }
      catch (CiviCRM_API3_Exception $e) {
        CRM_Core_Error::statusBounce('No recurring record! ' . $e->getMessage());
      }

      // Modify frequency_unit/frequency_interval to set allowed values for Smartdebit
      $frequencyUnits = ['week' => 'week', 'month' => 'month', 'year' => 'year'];
      $form->removeElement('frequency_unit');
      $form->addElement('select', 'frequency_unit', ts('Frequency Unit'), $frequencyUnits);
      $frequencyIntervals = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12];
      $form->removeElement('frequency_interval');
      $form->addElement('select', 'frequency_interval', ts('Frequency Interval'), $frequencyIntervals);

      $form->add('datepicker', 'start_date', ts('Start Date'), [], FALSE, ['time' => FALSE]);

      $reference = $recurRecord['trxn_id'];
      $recur = new CRM_Contribute_BAO_ContributionRecur();
      $recur->trxn_id = $reference;
      $recur->find(TRUE);
      $startDate = $recur->start_date;
      $defaults['start_date'] = $startDate;
      $defaults['frequency_unit'] = $recurRecord['frequency_unit'];
      $defaults['frequency_interval'] = array_search($recurRecord['frequency_interval'], $frequencyIntervals);
      $form->setDefaults($defaults);
    }
    /**
    elseif ($formName == 'CRM_Contribute_Form_UpdateBilling') {
      // This is triggered by clicking "Change Billing Details" on a recurring contribution.
    }
    elseif ($formName == 'CRM_Contribute_Form_CancelSubscription') {
      // This is triggered when you cancel a recurring contribution
    }
    */
  }
}

/**
 * Implements hook_civicrm_links
 * Add links to membership list on contacts tab to view/setup direct debit
 *
 * @param $op
 * @param $objectName
 * @param $objectId
 * @param $links
 * @param $mask
 * @param $values
 */
function smartdebit_civicrm_links( $op, $objectName, $objectId, &$links, &$mask, &$values ) {
  if ($objectName == 'Membership') {
    $cid = $values['cid'];
    $id = $objectId;
    $recurId = NULL;
    try {
      // Get recurring contribution Id for membership
      $membership = civicrm_api3('Membership', 'getsingle', [
        'id' => $id,
      ]);
      if (isset($membership['contribution_recur_id'])) {
        $recurId = $membership['contribution_recur_id'];
      };
    }
    catch (Exception $e) {
      // Do nothing, $recurId won't be set
    }
    if(!empty($recurId)) {
      $name = ts('View Direct Debit');
      $title = ts('View Direct Debit');
      $url = 'civicrm/contact/view/contributionrecur';
      $qs = "reset=1&id=$recurId&cid=$cid";
      $links[] = [
        'name' => $name,
        'title' => $title,
        'url' => $url,
        'qs' => $qs
      ];
    }
  }
}

/**
 * Implements hook_civicrm_pre
 *
 * @param $op
 * @param $objectName
 * @param $id
 * @param $params
 *
 * @throws \CiviCRM_API3_Exception
 */
function smartdebit_civicrm_pre($op, $objectName, $id, &$params) {
  switch ($objectName) {
    case 'Membership':
      if ($op !== 'create') {
        return;
      }
      // If creating a new membership and we have "Mark Initial Payment as Completed" set we need to:
      // 1. Set membership status from Pending->New
      // 2. Set join_date, start_date, end_date as they are not calculated automatically in this case.
      if (empty($params['contribution_recur_id'])
        || !CRM_Smartdebit_Utils::isSmartdebitPaymentProcessor($params['contribution_recur_id'])) {
        return;
      }
      if ((boolean) CRM_Smartdebit_Settings::getValue('initial_completed')) {
        if ($params['status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending')) {
          $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'New');
        }
        $dates = civicrm_api3('MembershipType', 'getdates', ['membershiptype_id' => $params['membership_type_id']]);
        if (empty($params['join_date'])) {
          $params['join_date'] = CRM_Utils_Array::value('join_date', $dates);
        }
        $params['start_date'] = CRM_Utils_Array::value('start_date', $dates);
        $params['end_date'] = CRM_Utils_Array::value('end_date', $dates);
      }
      break;

    case 'ContributionRecur':
      if ($op !== 'edit') {
        return;
      }
      if (!empty($params['start_date'])) {
        // If we are changing the start_date we need to set the next scheduled contribution date to match
        $params['next_sched_contribution_date'] = $params['start_date'];
      }
  }
}
