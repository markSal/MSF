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

$entry_id = '1767';
$entry = GFAPI::get_entry($entry_id);
$form = GFAPI::get_form($entry['form_id']);

// Setup Organization Info Output Fields
$output_fields = array(
  // Key name for output            // Gravity Form field label to get value from
  'org_name'                        => 'Organization name',
  'org_address'                     => array('Organization primary address', 'Street Address'),
  'org_address2'                    => array('Organization primary address', 'Address Line 2'),
  'org_city'                        => array('Organization primary address', 'City'),
  'org_state'                       => array('Organization primary address', 'State / Province'),
  'org_zip' 							          => array('Organization primary address', 'ZIP / Postal Code'),
  'org_country' 						        => array('Organization primary address', 'Country'),
  'org_website' 						        => 'Organization website',
  'org_primary_contact_first_name'  => array('Your name', 'First'),
  'org_primary_contact_last_name' 	=> array('Your name', 'Last'),
  'org_primary_contact_email' 		  => 'Your email address',
  'org_email_domain' 					      => 'Organization email domain',
  'org_membership_type' 				    => 'Does your company wish to join as',
  'org_types' 						          => 'Organization Type',
  'org_type_other' 					        => 'If other, please enter other organization type(s)',
  'org_domains_of_interest' 			  => 'Domains of Interest',
  'org_size' 							          => 'Number of Employees'
);

// Get Organization Info from Gravity Form Entry
$org_info = msf_gform_build_entry_array($form, $entry, $output_fields);

// Setup Organization Contacts
$org_contacts = array(
  array(
    'name'	=> $org_info['org_primary_contact_first_name'] . ' ' . $org_info['org_primary_contact_last_name'], 
    'email'	=> $org_info['org_primary_contact_email']
  )
);

// Setup Organization Email Domains
$org_email_domains = array(
  $org_info['org_email_domain']
);

// Get Organization Join Date
$org_join_date = date('Y-m-d');  // Use today's date for this example

// Get field mappings from Causeway API settings
$causeway_field_mapping = get_field('msf_causeway_api_field_mappings', 'option');

// Prepare field mappings for Causeway API request
$org_types_map = msf_get_causeway_api_field_map('orgTypeMapping', $causeway_field_mapping);
$org_domains_map = msf_get_causeway_api_field_map('domainsMapping', $causeway_field_mapping);
$org_size_map = array_flip(msf_get_causeway_api_field_map('orgSize', $causeway_field_mapping));
$org_membership_type_map = array_flip(msf_get_causeway_api_field_map('types', $causeway_field_mapping));

// Setup company to add to Causeway
$company_config = [
  'name'              => $org_info['org_name'],
  'address1'          => $org_info['org_address'],
  'address2'          => $org_info['org_address2'],
  'city'              => $org_info['org_city'],
  'state'             => $org_info['org_state'],
  'postal_code'       => $org_info['org_zip'],
  'country' 			    => $org_info['org_country'],
  'website' 			    => $org_info['org_website'],
  'contacts' 			    => $org_contacts,
  'domains' 			    => $org_email_domains,
  'type_id' 			    => $org_membership_type_map[$org_info['org_membership_type']],
  'orgTypes' 			    => $org_info['org_types'],
  'domainsOfInterest' => $org_info['org_domains_of_interest'],
  'orgSize' 			    => $org_info['org_size'],
  'join_date' 		    => $org_join_date
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
$causeway_id = $causeway_add_company->id;


// Prepare field mappings for Company Update
$orgTypeIDs = array_keys(array_intersect($org_types_map, $org_types));
$orgDomainOfInterests = array_keys(array_intersect($org_domains_map, $org_domains_of_interest));
$orgSize = $org_size_map[$org_size];

// Get Company to update using Causeway ID 
$update_company = Company::find(2491);

// Set updated values
$update_company->custom = [ 
  1 => $orgTypeIDs, 				  // 1 = Type of Organization
  2 => $orgDomainOfInterests, // 2 = Domains of Interest
  3 => '',                    // 3 = Type of Organization Other
  4 => '',                    // 4 = Domains of Interest Other
  5 => $orgSize,              // 5 = Organization Size - Number of employees/contractors
];

// Update Company in Causeway
$update_company->save();

?>
