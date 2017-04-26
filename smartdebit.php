<?php

// TODO: Test everything (just removed lot's of settings, changed DB etc, test sync job)
require_once 'smartdebit.civix.php';

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
function smartdebit_civicrm_install()
{
  // Create an Direct Debit Activity Type
  // See if we already have this type
  $ddActivity = civicrm_api3('OptionValue', 'get', array(
    'option_group_id' => "activity_type",
    'name' => "Direct Debit Sign Up",
  ));
  if (empty($ddActivity['count'])) {
    $activityParams = array('version' => '3'
    , 'option_group_id' => "activity_type"
    , 'name' => 'Direct Debit Sign Up'
    , 'description' => 'Direct Debit Sign Up');
    $activityType = civicrm_api('OptionValue', 'Create', $activityParams);
    $activityTypeId = $activityType['values'][$activityType['id']]['value'];
    smartdebit_civicrm_saveSetting('activity_type', $activityTypeId);
  }

  // See if we already have this type
  $ddActivity = civicrm_api3('OptionValue', 'get', array(
    'option_group_id' => "activity_type",
    'name' => "DD Confirmation Letter",
  ));
  if (empty($ddActivity['count'])) {
    // Otherwise create it
    $activityParams = array('version' => '3'
    , 'option_group_id' => "activity_type"
    , 'name' => 'DD Confirmation Letter'
    , 'description' => 'DD Confirmation Letter');
    $activityType = civicrm_api('OptionValue', 'Create', $activityParams);
    $activityTypeId = $activityType['values'][$activityType['id']]['value'];
    smartdebit_civicrm_saveSetting('activity_type_letter', $activityTypeId);
  }

  // Create an Direct Debit Payment Instrument
  // See if we already have this type
  $ddPayment = civicrm_api3('OptionValue', 'get', array(
    'option_group_id' => "payment_instrument",
    'name' => "Direct Debit",
  ));
  if (empty($ddPayment['count'])) {
    // Otherwise create it
    $paymentParams = array('version' => '3'
    , 'option_group_id' => "payment_instrument"
    , 'name' => 'Direct Debit'
    , 'description' => 'Direct Debit');
    $paymentType = civicrm_api('OptionValue', 'Create', $paymentParams);
    $paymentTypeId = $paymentType['values'][$paymentType['id']]['value'];
    smartdebit_civicrm_saveSetting('payment_instrument_id', $paymentTypeId);
  }

  // On install, create a table for keeping track of online direct debits
  CRM_Core_DAO::executeQuery("
         CREATE TABLE IF NOT EXISTS `civicrm_direct_debit` (
        `id`                        int(10) unsigned NOT NULL auto_increment,
        `created`                   datetime NOT NULL,
        `data_type`                 varchar(16) ,
        `entity_type`               varchar(32) ,
        `entity_id`                 int(10) unsigned,
        `bank_name`                 varchar(100) ,
        `branch`                    varchar(100) ,
        `address1`                  varchar(100) ,
        `address2`                  varchar(100) ,
        `address3`                  varchar(100) ,
        `address4`                  varchar(100) ,
        `town`                      varchar(100) ,
        `county`                    varchar(100) ,
        `postcode`                  varchar(20)  ,
        `first_collection_date`     varchar(100),
        `preferred_collection_day`  varchar(100) ,
        `confirmation_method`       varchar(100) ,
        `ddi_reference`             varchar(100) NOT NULL,
        `response_status`           varchar(100) ,
        `response_raw`              longtext     ,
        `request_counter`           int(10) unsigned,
        `complete_flag`             tinyint unsigned,
        `additional_details1`       varchar(100),
        `additional_details2`       varchar(100),
        `additional_details3`       varchar(100),
        `additional_details4`       varchar(100),
        `additional_details5`       varchar(100),
        PRIMARY KEY  (`id`),
        KEY `entity_id` (`entity_id`),
        KEY `data_type` (`data_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    ");

  // Create a table to store imported collection reports (CRM_Smartdebit_Auddis::getSmartDebitCollectionReport())
  if (!CRM_Core_DAO::checkTableExists('veda_smartdebit_import')) {
    $createSql = "CREATE TABLE `veda_smartdebit_import` (
                   `id` int(10) unsigned NOT NULL AUTO_INCREMENT, 
                   `transaction_id` varchar(255) DEFAULT NULL,
                   `contact` varchar(255) DEFAULT NULL,
                   `contact_id` varchar(255) DEFAULT NULL,
                   `amount` decimal(20,2) DEFAULT NULL,
                   `info` int(11) DEFAULT NULL,
                   `receive_date` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`)
         ) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=latin1";
    CRM_Core_DAO::executeQuery($createSql);
  }

  // This table is used to store the last set of successful imports
  if (!CRM_Core_DAO::checkTableExists('veda_smartdebit_import_success_contributions')) {
    $createSql = "CREATE TABLE `veda_smartdebit_import_success_contributions` (
                   `id` int(10) unsigned NOT NULL AUTO_INCREMENT, 
                   `transaction_id` varchar(255) DEFAULT NULL,
                   `contribution_id` int(11) DEFAULT NULL,
                   `contact_id` int(11) DEFAULT NULL,
                   `contact` varchar(255) DEFAULT NULL,
                   `amount` decimal(20,2) DEFAULT NULL,
                   `frequency` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`)
         ) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=latin1";
    CRM_Core_DAO::executeQuery($createSql);
  }

  // If no civicrm_sd, then create that table
  if (!CRM_Core_DAO::checkTableExists('veda_smartdebit_mandates')) {
    $createSql = "CREATE TABLE `veda_smartdebit_mandates` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `first_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `last_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `email_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `address_1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `address_2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `address_3` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `town` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `county` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `postcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `first_amount` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `regular_amount` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `frequency_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `frequency_factor` int(10) unsigned DEFAULT NULL,
            `start_date` datetime NOT NULL,
            `current_state` int(10) unsigned DEFAULT NULL,					
            `reference_number` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `payerReference` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            PRIMARY KEY (`id`)
           ) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=latin1";

    CRM_Core_DAO::executeQuery($createSql);
    $alterQuery = "alter table veda_smartdebit_mandates add index reference_number_idx(reference_number)";
    CRM_Core_DAO::executeQuery($alterQuery);
  }

  _smartdebit_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function smartdebit_civicrm_postInstall() {
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

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function smartdebit_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function smartdebit_civicrm_navigationMenu(&$menu) {
  $item[] =  array (
    'label' => ts('Smart Debit', array('domain' => 'uk.co.vedaconsulting.smartdebit')),
    'name'       => 'Smart Debit',
    'url'        => NULL,
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  );
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer', $item[0]);

  $item[] = array (
    'label' => ts('Smart Debit Settings', array('domain' => 'uk.co.vedaconsulting.smartdebit')),
    'name'       => 'Settings',
    'url'        => 'civicrm/smartdebit/settings?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  );
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/Smart Debit', $item[1]);

  $item[] = array(
    'label' => ts('Import Smart Debit Contributions', array('domain' => 'uk.co.vedaconsulting.smartdebit')),
    'label' => ts('Import Smart Debit Contributions'),
    'name'  => 'Import Smart Debit Contributions',
    'url'   => 'civicrm/smartdebit/syncsd?reset=1',
    'permission' => 'access CiviContribute',
    'operator'   => NULL,
    'separator'  => NULL,
  );
  _smartdebit_civix_insert_navigation_menu($menu, 'Contributions', $item[2]);

  $item[] =  array (
    'label' => ts('Smart Debit Reconciliation', array('domain' => 'uk.co.vedaconsulting.smartdebit')),
    'name' => 'Smart Debit Reconciliation',
    'url' => 'civicrm/smartdebit/reconciliation/list?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  );
  _smartdebit_civix_insert_navigation_menu($menu, 'Administer/Smart Debit', $item[3]);

  _smartdebit_civix_navigationMenu($menu);
}

/**
 * Save setting with prefix in database
 * @param $name
 * @param $value
 */
function smartdebit_civicrm_saveSetting($name, $value) {
  civicrm_api3('setting', 'create', array(CRM_Smartdebit_Form_Settings::getSettingName($name,true) => $value));
}

/**
 * Read setting that has prefix in database and return single value
 * @param $name
 * @return mixed
 */
function smartdebit_civicrm_getSetting($name) {
  $settings = civicrm_api3('setting', 'get', array('return' => CRM_Smartdebit_Form_Settings::getSettingName($name,true)));
  if (isset($settings['values'][1][CRM_Smartdebit_Form_Settings::getSettingName($name,true)])) {
    return $settings['values'][1][CRM_Smartdebit_Form_Settings::getSettingName($name, true)];
  }
  return '';
}

/**
 * Implementation of hook_civicrm_pageRun
 * This adds Smart Debit details to "View Recurring Contribution"
 *
 * @param $page
 *
 */
function smartdebit_civicrm_pageRun(&$page)
{
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Contribute_Page_ContributionRecur') {
    // On the view recurring contribution page we add some info from smart debit if we have it
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    $contactID = $page->getVar('_contactId');
    if (CRM_Contact_BAO_Contact_Permission::allow($userID, CRM_Core_Permission::EDIT)) {
      $recurID = $page->getVar('_id');

      $queryParams = array(
        'sequential' => 1,
        'return' => array("processor_id", "id"),
        'id' => $recurID,
        'contact_id' => $contactID,
      );

      $recurRef = civicrm_api3('ContributionRecur', 'getsingle', $queryParams);

      $contributionRecurDetails = array();
      if (!empty($recurRef['processor_id'])) {
        $smartDebitResponse = CRM_Smartdebit_Sync::getSmartDebitPayerContactDetails($recurRef['processor_id']);
        foreach ($smartDebitResponse[0] as $key => $value) {
          $contributionRecurDetails[$key] = $value;
        }
      }
      // Add Smart Debit details via js
      CRM_Core_Resources::singleton()->addVars('smartdebit', array( 'recurdetails' => $contributionRecurDetails));
      CRM_Core_Resources::singleton()->addScriptFile('uk.co.vedaconsulting.smartdebit', 'js/recurdetails.js');
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
function smartdebit_civicrm_buildForm( $formName, &$form )
{
  if ($form->isSubmitted()) return;

  //Smart Debit
  if (isset($form->_paymentProcessor['payment_processor_type']) && ($form->_paymentProcessor['payment_processor_type'] == 'Smart_Debit')) {
    if ($formName == 'CRM_Contribute_Form_Contribution_Confirm') {
      // Confirm Contribution (check details and confirm)
      // Show the direct debit agreement on the confirm page
      CRM_Core_Region::instance('contribution-confirm-billing-block')->update('default', array(
        'disabled' => TRUE,
      ));
      $form->assign('dd_details', CRM_Smartdebit_Base::getDDFormDetails($form->_params));
      CRM_Core_Region::instance('contribution-confirm-billing-block')->add(array(
        'template' => 'CRM/Contribute/Form/Contribution/DirectDebitAgreement.tpl',
      ));
    }
    elseif ($formName == 'CRM_Contribute_Form_Contribution_ThankYou') {
      // Contribution Thankyou form
      // Show the direct debit mandate on the thankyou page
      $form->assign('dd_details', CRM_Smartdebit_Base::getDDFormDetails($form->_params));
      CRM_Core_Region::instance('contribution-thankyou-billing-block')->update('default', array(
        'disabled' => TRUE,
      ));
      CRM_Core_Region::instance('contribution-thankyou-billing-block')->add(array(
        'template' => 'CRM/Contribute/Form/Contribution/DirectDebitMandate.tpl',
      ));
    } elseif ($formName == 'CRM_Contribute_Form_UpdateSubscription') {
      // Accessed when you click edit on a recurring contribution
      $paymentProcessor = $form->_paymentProcessor;
      if (isset($paymentProcessor['payment_processor_type']) && ($paymentProcessor['payment_processor_type'] == 'Smart_Debit')) {
        $recurID = $form->getVar('contributionRecurID');
        $linkedMembership = FALSE;
        $membershipRecord = civicrm_api3('Membership', 'get', array(
          'sequential' => 1,
          'return' => array("id"),
          'contribution_recur_id' => $recurID,
        ));
        if (isset($membershipRecord['id'])) {
          $linkedMembership = TRUE;
        }

        $form->removeElement('installments');

        $frequencyUnits = array('W' => 'week', 'M' => 'month', 'Q' => 'quarter', 'Y' => 'year');
        $frequencyIntervals = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12);

        $form->addElement('select', 'frequency_unit', ts('Frequency'),
          array('' => ts('- select -')) + $frequencyUnits
        );
        $form->addElement('select', 'frequency_interval', ts('Frequency Interval'),
          array('' => ts('- select -')) + $frequencyIntervals
        );
        $form->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'custom'));
        $form->addDate('end_date', ts('End Date'), FALSE, array('formatType' => 'custom'));
        $form->add('text', 'account_holder', ts('Account Holder'), array('size' => 20, 'maxlength' => 18, 'autocomplete' => 'on'));
        $form->add('text', 'bank_account_number', ts('Bank Account Number'), array('size' => 20, 'maxlength' => 8, 'autocomplete' => 'off'));
        $form->add('text', 'bank_identification_number', ts('Sort Code'), array('size' => 20, 'maxlength' => 6, 'autocomplete' => 'off'));
        $form->add('text', 'bank_name', ts('Bank Name'), array('size' => 20, 'maxlength' => 64, 'autocomplete' => 'off'));
        $form->add('hidden', 'payment_processor_type', 'Smart_Debit');
        $subscriptionDetails = $form->getVar('_subscriptionDetails');
        $reference = $subscriptionDetails->subscription_id;
        $frequencyUnit = $subscriptionDetails->frequency_unit;
        $frequencyInterval = $subscriptionDetails->frequency_interval;
        $recur = new CRM_Contribute_BAO_ContributionRecur();
        $recur->processor_id = $reference;
        $recur->find(TRUE);
        $startDate = $recur->start_date;
        list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults($startDate, NULL);
        $defaults['frequency_unit'] = array_search($frequencyUnit, $frequencyUnits);
        $defaults['frequency_interval'] = array_search($frequencyInterval, $frequencyIntervals);
        $form->setDefaults($defaults);
        if ($linkedMembership) {
          $form->assign('membership', TRUE);
          $e =& $form->getElement('frequency_unit');
          $e->freeze();
          $e =& $form->getElement('frequency_interval');
          $e->freeze();
          $e =& $form->getElement('start_date');
          $e->freeze();
        }
      }
    } elseif ($formName == 'CRM_Contribute_Form_UpdateBilling') {
      // This is triggered by clicking "Change Billing Details" on a recurring contribution.
    }
    if ($formName == 'CRM_Contribute_Form_CancelSubscription') {
      // This is triggered when you cancel a recurring contribution
      $paymentProcessorObj = $form->getVar('_paymentProcessorObj');
      $paymentProcessorName = $paymentProcessorObj->_processorName;
      if ($paymentProcessorName == 'Smart Debit Processor') {
        $form->addRule('send_cancel_request', 'Please select one of these options', 'required');
      }
    }
  }
}

function smartdebit_civicrm_validateForm($name, &$fields, &$files, &$form, &$errors) {
  // Only do recurring edit form
  if ($name == 'CRM_Contribute_Form_UpdateSubscription') {
    // only do if payment process is Smart Debit
    if (isset($fields['payment_processor_type']) && $fields['payment_processor_type'] == 'Smart_Debit') {
      $recurID = $form->getVar('_crid');
      $linkedMembership = FALSE;
      // Check this recurring contribution is linked to membership
      $membershipID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_membership WHERE contribution_recur_id = %1', array(1 => array($recurID, 'Int')));
      if ($membershipID) {
        $linkedMembership = TRUE;
      }
      // If recurring is linked to membership then check amount is higher than membership amount
      if ($linkedMembership) {
        $query = "
	SELECT cc.total_amount
	FROM civicrm_contribution cc 
	INNER JOIN civicrm_membership_payment cmp ON cmp.contribution_id = cc.id
	INNER JOIN civicrm_membership cm ON cm.id = cmp.membership_id
	WHERE cmp.membership_id = %1";
        $membershipAmount = CRM_Core_DAO::singleValueQuery($query, array(1 => array($membershipID, 'Int')));
        if ($fields['amount'] < $membershipAmount) {
          $errors['amount'] = ts('Amount should be higher than corresponding membership amount');
        }
      }
    }
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
      $membership = civicrm_api3('Membership', 'getsingle', array(
        'id' => $id,
      ));
      if (isset($membership['contribution_recur_id'])) {
        $recurId = $membership['contribution_recur_id'];
      };
    }
    catch (Exception $e) {
      // Do nothing, $recurId won't be set
    }
    if(!empty($recurId)) {
      $name   = ts('View Direct Debit');
      $title  = ts('View Direct Debit');
      $url    = 'civicrm/contact/view/contributionrecur';
      $qs   	= "reset=1&id=$recurId&cid=$cid";
    }
    $links[] = array(
      'name' => $name,
      'title' => $title,
      'url' => $url,
      'qs' => $qs
    );
  }
}

