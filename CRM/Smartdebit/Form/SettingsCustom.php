<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +-------------------------------------------------------------------*/

use CRM_Smartdebit_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Smartdebit_Form_SettingsCustom extends CRM_Smartdebit_Form_Settings {

  public static function buildQuickFormPre(&$form) {
    try {
      $sdStatus = CRM_Smartdebit_Api::getSystemStatus(FALSE);
      $sdStatusTest = CRM_Smartdebit_Api::getSystemStatus(TRUE);
      $form->assign('sdStatus', $sdStatus);
      $form->assign('sdStatusTest', $sdStatusTest);

      // Get counts
      $sdMandateCount = CRM_Smartdebit_Mandates::count();
      $sdCRCount = CRM_Smartdebit_CollectionReports::count();
      $form->assign('sdMandateCount', $sdMandateCount);
      $form->assign('sdCRCount', $sdCRCount);
    }
    catch (Exception $e) {
      // Do nothing here. Api will throw exception if API URL is not configured, which it won't be if
      // Smartdebit payment processor has not been setup yet.
      $form->assign('apiStatus', 'No Smartdebit payment processors are configured yet!');
    }
  }

  public static function addSelectElement(&$form, $name, $setting) {
    switch ($name) {
      case 'payment_instrument_id':
        $form->addSelect('payment_instrument_id',
          array(
            'entity' => 'contribution',
            'label' => ts('Default Payment Method'),
            'placeholder'  => NULL,
          )
        );
        break;
      case 'financial_type':
        $form->addSelect('financial_type',
          array(
            'entity' => 'contribution',
            'label' => ts('Default Financial Type'),
            'placeholder'  => NULL,
          )
        );
        break;
      case 'activity_type':
        $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create');
        $form->addElement('select', 'activity_type', ts('Activity Type (Sign Up)'), array('' => ts('- select -')) + $activityTypes);
        break;
      case 'activity_type_letter':
        $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create');
        $form->addElement('select', 'activity_type_letter', ts('Activity Type (Letter)'), array('' => ts('- select -')) + $activityTypes);
        break;
    }
  }

}
