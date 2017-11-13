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

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Smartdebit_Form_Settings extends CRM_Core_Form {

  function buildQuickForm() {
    parent::buildQuickForm();

    try {
      $sdStatus = CRM_Smartdebit_Api::getSystemStatus(FALSE);
      $sdStatusTest = CRM_Smartdebit_Api::getSystemStatus(TRUE);
      $this->assign('sdStatus', $sdStatus);
      $this->assign('sdStatusTest', $sdStatusTest);
    }
    catch (Exception $e) {
      // Do nothing here. Api will throw exception if API URL is not configured, which it won't be if
      // Smartdebit payment processor has not been setup yet.
      $this->assign('apiStatus', 'No Smartdebit payment processors are configured yet!');
    }

    CRM_Utils_System::setTitle(ts('Smart Debit - Settings'));

    $settings = $this->getFormSettings();

    $this->addSelect('payment_instrument_id',
      array(
        'entity' => 'contribution',
        'label' => ts('Default Payment Method'),
        'placeholder'  => NULL,
      )
    );
    $this->addSelect('financial_type',
      array(
        'entity' => 'contribution',
        'label' => ts('Default Financial Type'),
        'placeholder'  => NULL,
      )
    );

    $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create');
    $this->addElement('select', 'activity_type', ts('Activity Type (Sign Up)'), array('' => ts('- select -')) + $activityTypes);
    $this->addElement('select', 'activity_type_letter', ts('Activity Type (Letter)'), array('' => ts('- select -')) + $activityTypes);

    foreach ($settings as $name => $setting) {
      if (isset($setting['html_type'])) {
        Switch ($setting['html_type']) {
          case 'Text':
            if ($name != 'smartdebit_activity_type_letter') {
              $this->addElement('text', $name, ts($setting['description']), $setting['html_attributes'], array());
            }
            break;
          case 'Checkbox':
            $this->addElement('checkbox', $name, ts($setting['description']), '', '');
            break;
        }
      }
    }
    $this->addButtons(array(
      array (
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
      array (
        'type' => 'cancel',
        'name' => ts('Cancel'),
      )
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

  }

  function postProcess() {
    $changed = $this->_submitValues;
    $settings = $this->getFormSettings(TRUE);
    foreach ($settings as &$setting) {
      if ($setting['html_type'] == 'Checkbox') {
        $setting = false;
      }
      else {
        $setting = NULL;
      }
    }
    // Make sure we have all settings elements set (boolean settings will be unset by default and wouldn't be saved)
    $settingsToSave = array_merge($settings, array_intersect_key($changed, $settings));
    CRM_Smartdebit_Settings::save($settingsToSave);
    parent::postProcess();
    CRM_Core_Session::singleton()->setStatus('Configuration Updated', CRM_Smartdebit_Settings::TITLE, 'success');
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  function getFormSettings($metadata=TRUE) {
    $unprefixedSettings = array();
    $settings = civicrm_api3('setting', 'getfields', array('filters' => CRM_Smartdebit_Settings::getFilter()));
    if (!empty($settings['values'])) {
      foreach ($settings['values'] as $name => $values) {
        if ($metadata) {
          $unprefixedSettings[CRM_Smartdebit_Settings::getName($name, FALSE)] = $values;
        }
        else {
          $unprefixedSettings[CRM_Smartdebit_Settings::getName($name, FALSE)] = NULL;
        }
      }
    }
    return $unprefixedSettings;
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  function setDefaultValues() {
    $settings = $this->getFormSettings(FALSE);
    $defaults = array();

    $existing = CRM_Smartdebit_Settings::get($settings);
    if ($existing) {
      foreach ($existing as $name => $value) {
        $defaults[$name] = $value;
      }
    }
    return $defaults;
  }

}
