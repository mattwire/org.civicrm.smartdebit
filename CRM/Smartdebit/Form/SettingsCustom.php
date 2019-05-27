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

  public static function addSelectElement(&$form, $name, $setting) {
    switch ($name) {
      case 'payment_instrument_id':
        $form->addSelect($name,
          [
            'entity' => 'contribution',
            'label' => $setting['description'],
            'placeholder'  => NULL,
          ]
        );
        break;
      case 'financial_type':
        $form->addSelect($name,
          [
            'entity' => 'contribution',
            'label' => $setting['description'],
            'placeholder'  => NULL,
          ]
        );
        break;
      case 'activity_type':
        $form->addSelect($name,
          [
            'entity' => 'activity',
            'label' => $setting['description'],
            'field' => 'activity_type_id',
            'placeholder'  => NULL,
          ]
        );
        break;
    }
  }

}
