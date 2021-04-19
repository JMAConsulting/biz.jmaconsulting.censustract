<?php
define("GEOCODING_URL", "https://geocoding.geo.census.gov/geocoder/geographies/address?");

require_once 'censustract.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function censustract_civicrm_config(&$config) {
  _censustract_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function censustract_civicrm_xmlMenu(&$files) {
  _censustract_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function censustract_civicrm_install() {
  $census = civicrm_api3('CustomGroup', 'create', array(
    'title' => ts('US Census Information'),
    'name' => 'us_census_info',
    'extends' => array(
      '0' => 'Address',
    ),
    'is_active' => 1,
  ));
  civicrm_api3('CustomField', 'create', array(
    'label' => ts('Census Tract'),
    'custom_group_id' => 'us_census_info',
    'data_type' => "String",
    'html_type' => "Text",
    'is_active' => 1,
    'is_searchable' => 1,
  ));
  $official = civicrm_api3('CustomGroup', 'create', array(
    'title' => ts('Elected Official'),
    'name' => 'elected_official',
    'extends' => array(
      '0' => 'Contact',
    ),
    'is_active' => 1,
  ));
  civicrm_api3('CustomField', 'create', array(
    'label' => ts('Official for Neighbourhood'),
    'custom_group_id' => 'elected_official',
    'data_type' => "String",
    'html_type' => "Select",
    'is_active' => 1,
    'is_searchable' => 1,
  ));
  _censustract_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function censustract_civicrm_uninstall() {
  _censustract_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function censustract_civicrm_enable() {
  _censustract_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_postInstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function censustract_civicrm_postInstall() {
  _censustract_civix_civicrm_postInstall();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function censustract_civicrm_disable() {
  _censustract_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function censustract_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _censustract_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function censustract_civicrm_managed(&$entities) {
  _censustract_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function censustract_civicrm_caseTypes(&$caseTypes) {
  _censustract_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function censustract_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _censustract_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_fieldOptions
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_fieldOptions
 */
function censustract_civicrm_fieldOptions($entity, $field, &$options, $params) {
  $neighbourhood = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_custom_field WHERE 'name' => 'Official_for_Neighbourhood'");
  if ($entity == "Contact" && $field == "custom_" . $neighbourhood) {
    list($table, $column) = CRM_Censustract_BAO_Censustract::getTractData();
    $dao = CRM_Core_DAO::executeQuery("SELECT {$column} as tract FROM {$table} GROUP BY {$column}");
    while ($dao->fetch()) {
      $options[$dao->tract] = $dao->tract;
    }
  }
}

/**
 * Implementation of hook_civicrm_post
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 */
function censustract_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == "Address" && ($op == 'create' || $op == 'edit')) {
    if (!empty($objectRef->state_province_id) && $objectRef->state_province_id != 1004) {
      return;
    }
    $addressFields = array(
      "street_number" => "street",
      "street_name" => "street",
      "street_address" => "street",
      "city" => "city",
      "state_province_id" => "state",
      "postal_code" => "zip",
    );
    $address = "";
    $add = '';
    foreach ($addressFields as $key => $field) {
      if (empty($objectRef->$key)) {
        continue;
      }
      if ($key == "state_province_id") {
        $addressField = CRM_Core_PseudoConstant::stateProvince($objectRef->$key, FALSE);
      }
      else {
        if (in_array($key, array("street_number", "street_name", "street_address"))) {
          $add .= ' ' . $objectRef->$key;
        }
        else {
          $addressField = $objectRef->$key;
        }
      }
      if ($field != "street") {
        $address .= $field . "=" . CRM_Censustract_BAO_Censustract::parseText($addressField) . "&";
      }
    }
    if (!empty($add)) {
      $address .= "street=" . CRM_Censustract_BAO_Censustract::parseText($add) . "&";
    }
    if (!empty($address)) {
      $address .= "benchmark=Public_AR_Current&vintage=Current_Current&format=json";
      $url = GEOCODING_URL . $address;
    }
    $response = CRM_Censustract_BAO_Censustract::getCensusTract($url);

    if ($response) {
      // Save to custom field for address.
      try {
        $census = civicrm_api3('CustomField', 'getvalue', array(
          'name' => 'Census_Tract',
          'return' => 'id',
        ));
        civicrm_api3('CustomValue', 'create', array(
          'entity_id' => $objectId,
          'custom_' . $census => $response,
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        $errorMessage = $e->getMessage();
        $errorCode = $e->getErrorCode();
        $errorData = $e->getExtraParams();
        $errors[] = array(
          'error_message' => $errorMessage,
          'error_code' => $errorCode,
          'error_data' => $errorData,
        );
        CRM_Core_Error::debug_var("Census not saved", $errors);
        CRM_Core_Session::setStatus(ts("Census data not saved/found."), ts("Warning"), "alert");
      }
    }
  }
}

function censustract_civicrm_alterMailParams(&$params, $context) { 
  if (CRM_Utils_Array::value('groupName', $params) == 'Report Email Sender') {
    $email = CRM_Utils_Request::retrieve('email_to_send', 'String', CRM_Core_DAO::$_nullObject);
    if ($email) {
      if (!empty($params['toEmail'])) {
        $params['toEmail'] .= ',' . $email;
      }
      else {
        $params['toEmail'] = $email;
      }
    }
  }  
}
