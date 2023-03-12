<?php
// Get gravity forms field object by label from gf form object
function mvs_gform_get_field_by_label($form, $label, $child = false){
	foreach($form['fields'] as $field ){
		if($field->label == $label){
			if($field->inputs){
				foreach($field->inputs as $input){
					if($input['label'] == $child){
						return $input['id'];
					}
				}
			}else{
				return $field->id;
			}
		}
	}
	return false;
}

// TODO: Get associated form from custom gforms settings
$form_id = 3;

// Get form object
$form = GFAPI::get_form($form_id);

// Define Application Status admin field
$field_args = array(
	'type'   		=> 'select',
	'id'     		=> $new_field_id,
	'formId' 		=> $form['id'],
	'cssClass'		=> 'application-status',
	'required' 		=> true,
	'label'  		=> 'Application Status',
	'choices'  		=> array(
		array(
			'text'	=> 'Approved',
			'value'	=> 'approved'
		),
		array(
			'text'	=> 'Denied',
			'value'	=> 'denied'
		),
		array(
			'text'	=> 'Pending',
			'value'	=> 'pending'
		),
		array(
			'text'	=> 'Requires signed agreement',
			'value'	=> 'no-signature',
			'isSelected' => true
		),
	),
	'defaultValue'	=> 'no-signature',
	'adminOnly' 	=> true
);


// Check if Application Status field exists
if(!mvs_gform_get_field_by_label($form, $field_args['label'])){
	
	// If field dosen't exists add it to top of form
	$field = GF_Fields::create($field_args);
	array_unshift($form['fields'], $field);
	GFAPI::update_form($form);
}

?>
