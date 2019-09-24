<?php
/**
 * https://civicrm.org/licensing
 */

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
