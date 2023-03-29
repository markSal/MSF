<?php
require_once(MSF_MEMBERSHIP_PLUGIN_PATH . 'vendor/autoload.php');
require_once(MSF_MEMBERSHIP_PLUGIN_PATH . 'includes/causeway-api-add-company.php');

use Causeway\Client as Causeway;
use Causeway\Models\Company;
use Causeway\Models\CompanyContact;
use Causeway\Models\CompanyEmailDomain;
use MSFCauswayIntegration\AddCompanyToCauseway;

// Get Causeway API auth
$causeway_api_endpoint = get_field('msf_causeway_api_endpoint', 'option');
$causeway_api_username = get_field('msf_causeway_api_username', 'option');
$causeway_api_password = get_field('msf_causeway_api_password', 'option');

// Connect to Causeway API
Causeway::config([
  'endpoint' => $causeway_api_endpoint,
  'username' => $causeway_api_username,
  'password' => $causeway_api_password
]);

// Get entry and form data from Gravity Forms
$entry_id = '1767'; // ID of test entry
$entry = GFAPI::get_entry( $entry_id );
$form = GFAPI::get_form($entry['form_id']);

// Organization Name
$org_name_id = msf_gform_get_field_by_label($form, 'Organization name');
$org_name = msf_gform_get_entry_values($entry, $org_name_id);

// Organization Type(s)
$org_type_ids = msf_gform_get_field_by_label($form, 'Organization Type');
$org_types = msf_gform_get_entry_values($entry, $org_type_ids);

// Organization Type Other
$org_type_other_id = msf_gform_get_field_by_label($form, 'If other, please enter other organization type(s)');
$org_type_other = msf_gform_get_entry_values($entry, $org_type_other_id);

// Organization Domains of Interest
$org_domains_of_interest_ids = msf_gform_get_field_by_label($form, 'Domains of Interest');
$org_domains_of_interest = msf_gform_get_entry_values($entry, $org_domains_of_interest_ids);

// Organization Size
$org_size_id = msf_gform_get_field_by_label($form, 'Number of Employees');
$org_size = msf_gform_get_entry_values($entry, $org_size_id);

// Organization Street Address
$org_address_id = msf_gform_get_field_by_label($form, 'Organization primary address', 'Street Address');
$org_address = msf_gform_get_entry_values($entry, $org_address_id);

// Organization Address Line 2
$org_address2_id = msf_gform_get_field_by_label($form, 'Organization primary address', 'Address Line 2');
$org_address2 = msf_gform_get_entry_values($entry, $org_address2_id);

// Organization City
$org_city_id = msf_gform_get_field_by_label($form, 'Organization primary address', 'City');
$org_city = msf_gform_get_entry_values($entry, $org_city_id);

// Organization State/Province
$org_state_id = msf_gform_get_field_by_label($form, 'Organization primary address', 'State / Province');
$org_state = msf_gform_get_entry_values($entry, $org_state_id);

// Organization Zip/Postal Code
$org_zip_id = msf_gform_get_field_by_label($form, 'Organization primary address', 'ZIP / Postal Code');
$org_zip = msf_gform_get_entry_values($entry, $org_zip_id);

// Organization Country
$org_country_id = msf_gform_get_field_by_label($form, 'Organization primary address', 'Country');
$org_country = msf_gform_get_entry_values($entry, $org_country_id);

// Organization Website
$org_website_id = msf_gform_get_field_by_label($form, 'Organization website');
$org_website = msf_gform_get_entry_values($entry, $org_website_id);

// Organization Email Domain
$org_email_domain_id = msf_gform_get_field_by_label($form, 'Organization email domain');
$org_email_domain = msf_gform_get_entry_values($entry, $org_email_domain_id);

// Organization Primary Contact First Name
$org_primary_contact_first_name_id = msf_gform_get_field_by_label($form, 'Your name', 'First');
$org_primary_contact_first_name = msf_gform_get_entry_values($entry, $org_primary_contact_first_name_id);

// Organization Primary Contact Last Name
$org_primary_contact_last_name_id = msf_gform_get_field_by_label($form, 'Your name', 'Last');
$org_primary_contact_last_name = msf_gform_get_entry_values($entry, $org_primary_contact_last_name_id);

// Organization Primary Contact Email
$org_primary_contact_email_id = msf_gform_get_field_by_label($form, 'Your email address');
$org_primary_contact_email = msf_gform_get_entry_values($entry, $org_primary_contact_email_id);

// Organization Membership Type
$org_membership_type_id = msf_gform_get_field_by_label($form, 'Does your company wish to join as');
$org_membership_type = msf_gform_get_entry_values($entry, $org_membership_type_id);

// Organization Join Date
$org_join_date = date('Y-m-d');  // Use today's date for this example

// Get field mappings from Causeway API settings
$causeway_field_mapping = get_field('msf_causeway_api_field_mappings', 'option');

// Prepare mappings for Causeway API request
$org_types_map = msf_get_causeway_api_field_map('orgTypeMapping', $causeway_field_mapping);
$org_domains_map = msf_get_causeway_api_field_map('domainsMapping', $causeway_field_mapping);
$org_size_map = array_flip(msf_get_causeway_api_field_map('orgSize', $causeway_field_mapping));
$org_membership_type_map = array_flip(msf_get_causeway_api_field_map('types', $causeway_field_mapping));

// Setup company to add
$company_config = [
  'name'          => $org_name,
  'address1'      => $org_address,
  'address2'      => $org_address2,
  'city'          => $org_city,
   'country'      => $org_country,
  'postal_code'   => $org_zip,
  'state'         => $org_state,
  'join_date'     => $org_join_date,
  'type_id'       => $org_membership_type_map[$org_membership_type],
  'website'       => $org_website,
  'contacts' => [
    [
      'name'      => $org_primary_contact_first_name . ' ' . $org_primary_contact_last_name, 
      'email'     => $org_primary_contact_email,
      //'type_id' => 1
      /*
      * Note: Contact Types (type_id) for future feature update
      * array(
      *    1   => 'Primary',
      *    2   => 'Alternate',
      *    7   => 'Billing',
      *    6   => 'Finance',
      *    4   => 'IT Support',
      *    10  => 'Legal',
      *    8   => 'Marketing'
      *    11  => 'Public Relations',
      *    9   => 'Technical',
      *    3   => 'Web Developer'
      *  )
      */
    ],
  ],
  'domains' => [
    $org_email_domain
  ],
  'orgTypes'          => $org_types,
  'domainsOfInterest' => $org_domains_of_interest,
  'orgSize'           => $org_size
];

// Add Company to Causeway
$add_company = new AddCompanyToCauseway();
$add_company->setVariable('cwCompanies', $company_config);
$add_company->setVariable('domainsMapping', $org_domains_map);
$add_company->setVariable('orgTypeMapping', $org_types_map);
$add_company->setVariable('types', $org_membership_type_map);
$add_company->setVariable('orgSize', $org_size_map);
$add_company->addCompanyToCauseway();

// Get Causeway ID of Company that was just added
$causeway_id = $add_company->id;

// Prepare field mappings for Company Update
$orgTypeIDs = array_keys(array_intersect($org_types_map, $org_types));
$orgDomainOfInterests = array_keys(array_intersect($org_domains_map, $org_domains_of_interest));
$orgSize = $org_size_map[$org_size];

// Get Company to update using Causeway ID 
$update_company = Company::find($causeway_id);

// Set updated values
$update_company->custom = [
    1 => $orgTypeIDs,           // 1 = Type of Organization
    2 => $orgDomainOfInterests, // 2 = Domains of Interest
    3 => $org_type_other,       // 3 = Type of Organization Other
    4 => '',                    // 4 = Domains of Interest Other
    5 => $orgSize               // 5 = Organization Size - Number of employees/contractors
];

// Update Company
$update_company->save();

?>
