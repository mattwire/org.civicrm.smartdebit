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
class CRM_Smartdebit_Form_Settings extends CRM_Core_Form {

  function buildQuickForm() {
    parent::buildQuickForm();

    $className = E::CLASS_PREFIX . '_Settings';
    CRM_Utils_System::setTitle($className::TITLE . ' - ' . E::ts('Settings'));

    $className = E::CLASS_PREFIX . '_Form_SettingsCustom';
    if (method_exists($className, 'buildQuickFormPre')) {
      $className::buildQuickFormPre($this);
    }

    $settings = $this->getFormSettings();

    foreach ($settings as $name => $setting) {
      if (isset($setting['html_type'])) {
        Switch (strtolower($setting['html_type'])) {
          case 'text':
            $this->addElement('text', $name, ts($setting['description']), $setting['html_attributes'], []);
            break;
          case 'checkbox':
            $this->addElement('checkbox', $name, ts($setting['description']), '', '');
            break;
          case 'datepicker':
            foreach ($setting['html_extra'] as $key => $value) {
              if ($key == 'minDate') {
                $minDate = new DateTime('now');
                $minDate->modify($value);
                $setting['html_extra'][$key] = $minDate->format('Y-m-d');
              }
            }
            $this->add('datepicker', $name, ts($setting['description']), $setting['html_attributes'], FALSE, $setting['html_extra']);
            break;
          case 'select2':
            $className = E::CLASS_PREFIX . '_Form_SettingsCustom';
            if (method_exists($className, 'addSelect2Element')) {
              $className::addSelect2Element($this, $name, $setting);
            }
            break;
          case 'select':
            $className = E::CLASS_PREFIX . '_Form_SettingsCustom';
            if (method_exists($className, 'addSelectElement')) {
              $className::addSelectElement($this, $name, $setting);
            }
            break;
          case 'hidden':
            $hidden = TRUE;
        }

        if (isset($hidden)) {
          continue;
        }

        $adminGroup = isset($setting['admin_group']) ? $setting['admin_group'] : 'default';
        $elementGroups[$adminGroup]['elementNames'][] = $name;
        // Title and description may not be defined on all elements (they only need to be on one)
        if (!empty($setting['admin_grouptitle'])) {
          $elementGroups[$setting['admin_group']]['title'] = $setting['admin_grouptitle'];
        }
        if (!empty($setting['admin_groupdescription'])) {
          $elementGroups[$setting['admin_group']]['description'] = $setting['admin_groupdescription'];
        }
      }
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ]
    ]);

    // export form elements
    $this->assign('elementGroups', $elementGroups);

  }

  function postProcess() {
    $className = E::CLASS_PREFIX . '_Settings';
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
    $className::save($settingsToSave);
    parent::postProcess();
    CRM_Core_Session::singleton()->setStatus('Configuration Updated', $className::TITLE, 'success');
  }

  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  function getFormSettings($metadata=TRUE) {
    $className = E::CLASS_PREFIX . '_Settings';
    $unprefixedSettings = [];
    $settings = civicrm_api3('setting', 'getfields', ['filters' => $className::getFilter()]);
    if (!empty($settings['values'])) {
      foreach ($settings['values'] as $name => $values) {
        if ($metadata) {
          $unprefixedSettings[$className::getName($name, FALSE)] = $values;
        }
        else {
          $unprefixedSettings[$className::getName($name, FALSE)] = NULL;
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
    $className = E::CLASS_PREFIX . '_Settings';
    $settings = $this->getFormSettings(FALSE);
    $defaults = [];

    $existing = $className::get(array_keys($settings));
    if ($existing) {
      foreach ($existing as $name => $value) {
        $defaults[$name] = $value;
      }
    }
    return $defaults;
  }

}
