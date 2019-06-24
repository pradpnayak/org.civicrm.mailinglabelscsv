<?php

/**
 * This class helps to export the labels for contacts in CSV.
 */
class CRM_MailingLabelsCSV_Form_Task_LabelCSV extends CRM_Contact_Form_Task_Label {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildLabelForm($this);
    CRM_Utils_System::setTitle(ts('Mailing labels - Export CSV'));
    $this->removeElement('label_name');
    $element = &$this->getElement('location_type_id');
    $element->_attributes['class'] = 'crm-select2';
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Export Mailing Labels'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Done'),
      ],
    ]);
  }

  /**
   * Set default values for the form.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults['do_not_mail'] = 1;

    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $fv = $this->controller->exportValues($this->_name);
    $config = CRM_Core_Config::singleton();
    $locName = NULL;
    //get the address format sequence from the config file
    $mailingFormat = Civi::settings()->get('mailing_format');

    $sequence = CRM_Utils_Address::sequence($mailingFormat);

    foreach ($sequence as $v) {
      $address[$v] = 1;
    }

    if (array_key_exists('postal_code', $address)) {
      $address['postal_code_suffix'] = 1;
    }

    //build the returnproperties
    $returnProperties = ['display_name' => 1, 'contact_type' => 1, 'prefix_id' => 1];
    $mailingFormat = Civi::settings()->get('mailing_format');

    $mailingFormatProperties = [];
    if ($mailingFormat) {
      $mailingFormatProperties = CRM_Utils_Token::getReturnProperties($mailingFormat);
      $returnProperties = array_merge($returnProperties, $mailingFormatProperties);
    }
    //we should not consider addressee for data exists, CRM-6025
    if (array_key_exists('addressee', $mailingFormatProperties)) {
      unset($mailingFormatProperties['addressee']);
    }

    $customFormatProperties = [];
    if (stristr($mailingFormat, 'custom_')) {
      foreach ($mailingFormatProperties as $token => $true) {
        if (substr($token, 0, 7) == 'custom_') {
          if (empty($customFormatProperties[$token])) {
            $customFormatProperties[$token] = $mailingFormatProperties[$token];
          }
        }
      }
    }

    if (!empty($customFormatProperties)) {
      $returnProperties = array_merge($returnProperties, $customFormatProperties);
    }

    if (isset($fv['merge_same_address'])) {
      // we need first name/last name for summarising to avoid spillage
      $returnProperties['first_name'] = 1;
      $returnProperties['last_name'] = 1;
    }

    $individualFormat = FALSE;

    /*
     * CRM-8338: replace ids of household members with the id of their household
     * so we can merge labels by household.
     */
    if (isset($fv['merge_same_household'])) {
      $this->mergeContactIdsByHousehold();
      $individualFormat = TRUE;
    }

    //get the contacts information
    $params = [];
    if (!empty($fv['location_type_id'])) {
      $locType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $locName = $locType[$fv['location_type_id']];
      $location = ['location' => ["{$locName}" => $address]];
      $returnProperties = array_merge($returnProperties, $location);
      $params[] = ['location_type', '=', [1 => $fv['location_type_id']], 0, 0];
    }
    else {
      $returnProperties = array_merge($returnProperties, $address);
    }

    $rows = [];

    foreach ($this->_contactIds as $key => $contactID) {
      $params[] = [
        CRM_Core_Form::CB_PREFIX . $contactID,
        '=',
        1,
        0,
        0,
      ];
    }

    // fix for CRM-2651
    if (!empty($fv['do_not_mail'])) {
      $params[] = ['do_not_mail', '=', 0, 0, 0];
    }
    // fix for CRM-2613
    $params[] = ['is_deceased', '=', 0, 0, 0];

    $custom = [];
    foreach ($returnProperties as $name => $dontCare) {
      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if ($cfID) {
        $custom[] = $cfID;
      }
    }

    //get the total number of contacts to fetch from database.
    $numberofContacts = count($this->_contactIds);
    $query = new CRM_Contact_BAO_Query($params, $returnProperties);
    $details = $query->apiQuery($params, $returnProperties, NULL, NULL, 0, $numberofContacts);

    $messageToken = CRM_Utils_Token::getTokens($mailingFormat);

    // also get all token values
    CRM_Utils_Hook::tokenValues($details[0],
      $this->_contactIds,
      NULL,
      $messageToken,
      'CRM_Contact_Form_Task_Label'
    );

    $tokens = [];
    CRM_Utils_Hook::tokens($tokens);
    $tokenFields = [];
    foreach ($tokens as $category => $catTokens) {
      foreach ($catTokens as $token => $tokenName) {
        $tokenFields[] = $token;
      }
    }

    foreach ($this->_contactIds as $value) {
      foreach ($custom as $cfID) {
        if (isset($details[0][$value]["custom_{$cfID}"])) {
          $details[0][$value]["custom_{$cfID}"] = CRM_Core_BAO_CustomField::displayValue($details[0][$value]["custom_{$cfID}"], $cfID);
        }
      }
      $contact = CRM_Utils_Array::value($value, $details['0']);

      if (is_a($contact, 'CRM_Core_Error')) {
        return NULL;
      }

      // we need to remove all the "_id"
      unset($contact['contact_id']);

      if ($locName && !empty($contact[$locName])) {
        // If location type is not primary, $contact contains
        // one more array as "$contact[$locName] = array( values... )"

        if (!self::tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
          continue;
        }

        $contact = array_merge($contact, $contact[$locName]);
        unset($contact[$locName]);

        if (!empty($contact['county_id'])) {
          unset($contact['county_id']);
        }

        foreach ($contact as $field => $fieldValue) {
          $rows[$value][$field] = $fieldValue;
        }

        $valuesothers = [];
        $paramsothers = ['contact_id' => $value];
        $valuesothers = CRM_Core_BAO_Location::getValues($paramsothers, $valuesothers);
        if (!empty($fv['location_type_id'])) {
          foreach ($valuesothers as $vals) {
            if (CRM_Utils_Array::value('location_type_id', $vals) ==
              CRM_Utils_Array::value('location_type_id', $fv)
            ) {
              foreach ($vals as $k => $v) {
                if (in_array($k, [
                  'email',
                  'phone',
                  'im',
                  'openid',
                ])) {
                  if ($k == 'im') {
                    $rows[$value][$k] = $v['1']['name'];
                  }
                  else {
                    $rows[$value][$k] = $v['1'][$k];
                  }
                  $rows[$value][$k . '_id'] = $v['1']['id'];
                }
              }
            }
          }
        }
      }
      else {
        if (!self::tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
          continue;
        }

        if (!empty($contact['addressee_display'])) {
          $contact['addressee_display'] = trim($contact['addressee_display']);
        }
        if (!empty($contact['addressee'])) {
          $contact['addressee'] = $contact['addressee_display'];
        }

        // now create the rows for generating mailing labels
        foreach ($contact as $field => $fieldValue) {
          $rows[$value][$field] = $fieldValue;
        }
      }
    }

    if (isset($fv['merge_same_address'])) {
      self::mergeSameAddress($rows);
      $individualFormat = TRUE;
    }

    //call function to create labels
    self::exportLabel($rows);
    CRM_Utils_System::civiExit();
  }

  /**
   * Check for presence of tokens to be swapped out.
   *
   * @param array $contact
   * @param array $mailingFormatProperties
   * @param array $tokenFields
   *
   * @return bool
   */
  public static function tokenIsFound($contact, $mailingFormatProperties, $tokenFields) {
    foreach (array_merge($mailingFormatProperties, array_fill_keys($tokenFields, 1)) as $key => $dontCare) {
      //we should not consider addressee for data exists, CRM-6025
      if ($key != 'addressee' && !empty($contact[$key])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Export labels (csv).
   *
   * @param array $contactRows
   *   Associated array of contact data.
   * @param string $fileName
   *   The name of the file to save the label in.
   */
  public function exportLabel(&$contactRows, $fileName = 'MailingLabels_CiviCRM.csv') {
    $headers = [
      'contact_type' => ts('Contact Type'),
      'display_name' => ts('Display Name'),
      'addressee_display' => ts('Addressee Display'),
      'addressee_custom' => ts('Addressee Custom'),
      'location_type' => ts('Location Type'),
      'street_address' => ts('Street Address'),
      'supplemental_address_1' => ts('Supplemental Address 1'),
      'supplemental_address_2' => ts('Supplemental Address 2'),
      'supplemental_address_3' => ts('Supplemental Address 3'),
      'city' => ts('City'),
      'postal_code' => ts('Postal Code'),
      'county' => ts('County'),
      'state_province_name' => ts('State Province Name'),
      'state_province' => ts('State Province'),
      'country' => ts('Country'),
    ];
    $config = CRM_Core_Config::singleton();
    $filePath = $config->uploadDir . $fileName;

    $fp = fopen($filePath, 'w');
    fputcsv($fp, $headers);

    foreach ($contactRows as $cid => $contactDetails) {
      $rows = [];
      foreach ($headers as $key => $ignore) {
        $rows[$key] = CRM_Utils_Array::value($key, $contactDetails);
      }
      fputcsv($fp, $rows);
    }
    fclose($fp);

    CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName($fileName));
    CRM_Utils_System::setHttpHeader('Content-Length', '' . filesize($filePath));
    ob_clean();
    flush();
    readfile($filePath);
    CRM_Utils_System::civiExit();
  }

  /**
   * Merge contacts with the Same address to get one shared label.
   * @param array $rows
   *   Array[contact_id][contactDetails].
   */
  public static function mergeSameAddress(&$rows) {
    $uniqueAddress = [];
    foreach (array_keys($rows) as $rowID) {
      // load complete address as array key
      $address = trim($rows[$rowID]['street_address'])
        . trim($rows[$rowID]['city'])
        . trim($rows[$rowID]['postal_code']);
      $address = strtolower($address);
      if (isset($rows[$rowID]['last_name'])) {
        $name = $rows[$rowID]['last_name'];
      }
      else {
        $name = $rows[$rowID]['display_name'];
      }

      // CRM-15120
      $formatted = [
        'first_name' => $rows[$rowID]['first_name'],
        'individual_prefix' => $rows[$rowID]['individual_prefix'],
      ];
      $format = Civi::settings()->get('display_name_format');
      $firstNameWithPrefix = CRM_Utils_Address::format($formatted, $format, FALSE, FALSE);
      $firstNameWithPrefix = trim($firstNameWithPrefix);

      // fill uniqueAddress array with last/first name tree
      if (isset($uniqueAddress[$address])) {
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'];
        // drop unnecessary rows
        unset($rows[$rowID]);
        // this is the first listing at this address
      }
      else {
        $uniqueAddress[$address]['ID'] = $rowID;
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'];
      }
    }
    foreach ($uniqueAddress as $address => $data) {
      // copy data back to $rows
      $count = 0;
      // one last name list per row
      foreach ($data['names'] as $last_name => $first_names) {
        // too many to list
        if ($count > 2) {
          break;
        }
        if (count($first_names) == 1) {
          $family = $first_names[current(array_keys($first_names))]['addressee_display'];
        }
        else {
          // collapse the tree to summarize
          $family = trim(implode(" & ", array_keys($first_names)) . " " . $last_name);
        }
        if ($count) {
          $processedNames .= "\n" . $family;
        }
        else {
          // build display_name string
          $processedNames = $family;
        }
        $count++;
      }
      $rows[$data['ID']]['addressee'] = $rows[$data['ID']]['addressee_display'] = $rows[$data['ID']]['display_name'] = $processedNames;
    }
  }

}
