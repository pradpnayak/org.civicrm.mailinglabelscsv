<?php

require_once 'mailinglabelscsv.civix.php';
use CRM_Mailinglabelscsv_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mailinglabelscsv_civicrm_config(&$config) {
  _mailinglabelscsv_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function mailinglabelscsv_civicrm_xmlMenu(&$files) {
  _mailinglabelscsv_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mailinglabelscsv_civicrm_install() {
  _mailinglabelscsv_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function mailinglabelscsv_civicrm_postInstall() {
  _mailinglabelscsv_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function mailinglabelscsv_civicrm_uninstall() {
  _mailinglabelscsv_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mailinglabelscsv_civicrm_enable() {
  _mailinglabelscsv_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function mailinglabelscsv_civicrm_disable() {
  _mailinglabelscsv_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function mailinglabelscsv_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mailinglabelscsv_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function mailinglabelscsv_civicrm_managed(&$entities) {
  _mailinglabelscsv_civix_civicrm_managed($entities);
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
function mailinglabelscsv_civicrm_caseTypes(&$caseTypes) {
  _mailinglabelscsv_civix_civicrm_caseTypes($caseTypes);
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
function mailinglabelscsv_civicrm_angularModules(&$angularModules) {
  _mailinglabelscsv_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function mailinglabelscsv_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _mailinglabelscsv_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function mailinglabelscsv_civicrm_entityTypes(&$entityTypes) {
  _mailinglabelscsv_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_searchTasks().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_searchTasks
 */
function mailinglabelscsv_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'contact') {
    $tasks[] = [
      'title' => ts('Mailing labels - Export CSV'),
      'class' => 'CRM_MailingLabelsCSV_Form_Task_LabelCSV',
      'result' => TRUE,
    ];
  }
}
