<?php


				/*
				*
				*	Add logo from GForms Entry to Logo Carousel Pro
				*
				**/
				// Get Wordpress Upload Dir
				$upload_dir = wp_upload_dir();
				
				// Company Name
				$company_name = $entry[11];
				
				// Company URL
				$company_url = $entry[13];
				
				// Logo URL
				$image_url = $entry[21];
				
				// Get Logo pathinfo
				$image = pathinfo($image_url);
				$image_data = file_get_contents($image_url);
				
				
				/*
				*
				*	RESIZE LOGO TO 600x400
				*
				*	AND	
				*
				*	CONVERT SVG TO PNG IF APPLICABLE
				*
				*/
				
				
				// Set logo filename format to company-name-logo.png
				$image_filename = sanitize_title($company_name) . '-logo.' . $image['extension'];
				
				// Make sure logo filename is unique
				$unique_file_name = wp_unique_filename($upload_dir['path'], $image['basename']);
				
				// Store unique logo filename
				$filename = basename($unique_file_name);
				
				// Insert Logo Carousel Post
				$logo_post_id = wp_insert_post(array(
					'post_title'		=> $company_name,
					'post_type'			=> 'sp_logo_carousel',
					'post_status'		=> 'publish',
					'comment_status'	=> 'closed'
				), true);
				
				// Attach Logo image to inserted post
				if(!is_wp_error($insert_id)){
					if($image != ''){
						
						// Check folder permission and define file location
						if(wp_mkdir_p($upload_dir['path'])){
							$file = $upload_dir['path'] . '/' . $filename;
						}else{
							$file = $upload_dir['basedir'] . '/' . $filename;
						}
						
						// Create the image file on the server
						file_put_contents($file, $image_data);
						
						// Check image file type
						$wp_filetype = wp_check_filetype($filename, null);
						
						// Set attachment data
						$attachment = array(
							'post_mime_type' 	=> $wp_filetype['type'],
							'post_title' 		=> sanitize_title($company_name) . '-logo',
							'post_content' 		=> '',
							'post_status' 		=> 'inherit'
						);
						
						// Create the attachment
						$attach_id = wp_insert_attachment($attachment, $file, $logo_post_id);
						
						// Include image.php
						require_once ABSPATH . 'wp-admin/includes/image.php';
						
						// Define attachment metadata
						$attach_data = wp_generate_attachment_metadata($attach_id, $file);
						
						// Assign metadata to attachment
						wp_update_attachment_metadata($attach_id, $attach_data);
						
						// Assign featured image to post
						$thumbnail = set_post_thumbnail($logo_post_id, $attach_id);
					}
				}
				
				// Add Company URL to logo post meta
				update_post_meta(
					// Post ID
					$logo_post_id,
					
					// Meta Key
					'sp_logo_carousel_link_option',
					
					// Meta Value
					array(
						'lcp_logo_link'		=> $company_url,
						'lcp_logo_link_ref'	=> '1'
					)
				);

?>
