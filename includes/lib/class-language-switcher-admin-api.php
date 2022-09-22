<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Language_Switcher_Admin_API {

	/**
	 * Constructor function
	 */
	public function __construct ($parent) {
		
		$this->parent = $parent;
		
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );
	}

	/**
	 * Generate HTML for displaying fields
	 * @param  array   $field Field data
	 * @param  boolean $echo  Whether to echo the field HTML or return it
	 * @return void
	 */
	public function display_field ( $data = array(), $post = false, $echo = true ) {

		// Get field info
		if ( isset( $data['field'] ) ) {
			$field = $data['field'];
		} else {
			$field = $data;
		}

		// Check for prefix on option name
		$option_name = '';
		if ( isset( $data['prefix'] ) ) {
			$option_name = $data['prefix'];
		}

		// Get saved data
		$data = '';
		
		if( isset($field['data']) ) {
		
			if( isset($field['name']) ) {
			
				$option_name .= $field['name'];
			}
			else{
				
				$option_name .= $field['id'];
			}
			
			$data = $field['data'];
		}
		elseif ( $post ) {

			// Get saved field data
			$option_name .= $field['id'];
			$option = get_post_meta( $post->ID, $field['id'], true );

			// Get data to display in field
			if ( isset( $option ) ) {
				
				$data = $option;
			}
		}
		else {

			// Get saved option
			
			if( isset($field['name']) ) {
			
				$option_name .= $field['name'];
			}
			else{
				
				$option_name .= $field['id'];
			}
			
			$option = get_option( $option_name );

			// Get data to display in field
			if ( isset( $option ) ) {
				$data = $option;
			}
		}

		// Show default data if no option saved and default is supplied
		if ( $data === false && isset( $field['default'] ) ) {
			$data = $field['default'];
		} elseif ( $data === false ) {
			$data = '';
		}

		$html = '';

		switch( $field['type'] ) {
			
			case 'none':
			break;
			case 'text':
			case 'url':
			case 'email':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
			break;

			case 'password':
			case 'number':
			case 'hidden':
				$min = '';
				if ( isset( $field['min'] ) ) {
					$min = ' min="' . esc_attr( $field['min'] ) . '"';
				}

				$max = '';
				if ( isset( $field['max'] ) ) {
					$max = ' max="' . esc_attr( $field['max'] ) . '"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '"' . $min . '' . $max . '/>' . "\n";
			break;

			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="" />' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . $data . '</textarea><br/>'. "\n";
			break;

			case 'checkbox':
				$checked = '';
				if ( $data && 'on' == $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . ' />' . "\n";
			break;

			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, (array) $data ) ) {
						$checked = true;
					}
					$html .= '<label style="display:block;" for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
			break;
			
			case 'addon_plugins':
				
				$html .= '<div id="the-list">';
				
					foreach( $this->parent->settings->addons as $addon ){
				
						$html .= '<div class="panel panel-default plugin-card plugin-card-akismet">';
						
							$html .= '<div class="panel-body plugin-card-top">';
								
								$html .= '<div class="name column-name">';
								
									$html .= '<h3>';
									
										$html .= '<a href="'.$addon['addon_link'].'" class="thickbox open-plugin-details-modal" style="text-decoration:none;">';
											
											if( !empty($addon['logo_url']) ){
												
												$html .= '<img class="plugin-icon" src="'.$addon['logo_url'].'" />';
											}
											
											$html .= $addon['title'];	
											
										$html .= '</a>';
										
									$html .= '</h3>';
									
								$html .= '</div>';
								
								$html .= '<div class="desc column-description">';
							
									$html .= '<p>'.$addon['description'].'</p>';
									$html .= '<p class="authors"> <cite>By <a target="_blank" href="'.$addon['author_link'].'">'.$addon['author'].'</a></cite></p>';
								
								$html .= '</div>';
								
							$html .= '</div>';
							
							$html .= '<div class="panel-footer plugin-card-bottom text-right">';
								
								$plugin_file = $addon['addon_name'] . '/' . $addon['addon_name'] . '.php';
								
								if( !file_exists( WP_PLUGIN_DIR . '/' . $addon['addon_name'] . '/' . $addon['addon_name'] . '.php' ) ){
									
									if( !empty($addon['source_url']) ){
									
										$url = $addon['source_url'];
									}
									else{
										
										$url = $addon['addon_link'];
									}
									
									$html .= '<a href="' . esc_url($url) . '" class="button install-now" aria-label="Install">Install Now</a>';
								}
								else{
									
									$html .= '<span>Installed</span>';
								}
							
							$html .= '</div>';
						
						$html .= '</div>';
					}
				
				$html .= '</div>';
			
			break;

			case 'object_checkbox_multi':
			
				$labels = $this->parent->get_labels();
				
				foreach ( $field['options'] as $k => $v ) {
				
					$disabled = true;
					
					if( $this->parent->is_valid_object($field['object'],$v) ){
						
						$disabled = false;
					}
					
					$checked = false;
					
					if ( !$disabled && in_array( $v, (array) $data ) ) {
						
						$checked = true;
					}
					
					$html .= '<label style="display:block;" for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi">';
						
						$html .= '<input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $v ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" ' . disabled( $disabled, true, false ) . ' /> ' . $labels[$field['object']][$v] . ' <i style="font-size:10px;">' . $v . '</i>';
					
					$html .= '</label> ';
				}
				
			break;

			case 'language_checkbox_multi':
				
				$default = $this->parent->get_code_by_locale( get_site_option('WPLANG') );
				
				foreach ( $field['options'] as $k => $v ) {

					$checked = false;

					if ( in_array( $k, (array) $data ) ) {
						
						$checked = true;
					}
					
					$disabled = false;
					
					if ( $k == $default ) {
						
						$disabled = true;
					}
					
					if( is_array($v) ){
						
						$v = $v['full'];
					}

					$html .= '<label style="display:block;padding: 6px 0px;" for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi">';
						
						$html .= '<input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" ' . disabled( $disabled, true, false ) . '/> ' . $v . ( $k == $default ? '<span style="background: #FFC107;font-size: 10px;padding: 1px 5px;color: #fff;margin: 0px 7px;border-radius: 2px;">default</span>' : '' );
					
					$html .= '</label> ';
				}
				
			break;

			case 'default_language_urls':

				if( $options = $this->parent->get_active_languages() ){

					$languages = $this->parent->get_language_labels();
					
					foreach( $languages as $iso => $language ){
						
						if( in_array($iso,$options) ){
							
							$html .= '<div style="margin-bottom:10px;">';
							
								$html .= '<div style="margin-bottom:2px;">' . $language['full'] . '</div>';
							
								$html .= '<input style="width:100%;" id="' . esc_attr( $field['id'] ) . '_' . $iso . '" type="text" name="' . esc_attr( $option_name ) . '['.$iso.']" placeholder="http://" value="' . ( isset($data[$iso]) ? esc_attr( $data[$iso] ) : '' ) . '" />' . "\n";

							$html .= '</div>';
						}
					}
				}
				else{
					
					$html .= 'No language activated';
				}
				
				
			break;
			
			case 'language_switcher_with_url':
				
				if( $active_languages = $this->parent->get_active_languages() ){

					if( !is_array($data) ){
						
						$data = array('main'=>$data);
					}
				
					if( !isset($data['urls']) ){
						
						$data['urls'] = array();
					}

					if( empty($data['main']) ){
						
						$data['main'] = $this->parent->get_code_by_locale( get_site_option('WPLANG') );
					}

					$languages = $this->parent->get_language_labels();
					
					$html .= '<div class="language-switcher">';
						
						foreach( $languages as $iso => $language ){
							
							if( in_array($iso,$active_languages) ){
								
								$value = '';
								
								if( $data['main'] == $iso ){
									
									$value = 'default';
								}
								elseif( isset($data['urls'][$iso]) ){
									
									$value = $data['urls'][$iso];
								}
								
								$html .= '<div style="margin-bottom:10px;">';
									
									$html .= '<input id="' . esc_attr( $field['id'] ) . '_main_' . $iso . '" class="' . esc_attr( $field['id'] ) . '_main" type="radio" name="' . esc_attr( $option_name ) . '[main]" value="' . $iso . '"' . ( $data['main'] == $iso ? ' checked="checked"' : '' ) . ' />' . "\n";										
									
									$html .= '<div style="margin-bottom:2px;width:90%;display: inline-block;">' . $language['full'] . '</div>';

									$html .= '<input style="width:90%;margin-left:25px;" id="' . esc_attr( $field['id'] ) . '_url_' . $iso . '" type="text" name="' . esc_attr( $option_name ) . '[urls]['.$iso.']" placeholder="http://" value="' . $value . '"'.( $data['main'] == $iso ? ' disabled="disabled"' : '' ).'/>' . "\n";

								$html .= '</div>';
							}
						}
						
					$html .= '</div>';
				}
				else{
					
					$html .= 'No language activated';
				}
				
			break;
			
			case 'language_switcher_without_url':
				
				if( $active_languages = $this->parent->get_active_languages() ){

					if( !is_array($data) ){
						
						$data = array('main'=>$data);
					}
				
					if( !isset($data['urls']) ){
						
						$data['urls'] = array();
					}

					if( empty($data['main']) ){
						
						$data['main'] = $this->parent->get_code_by_locale( get_site_option('WPLANG') );
					}
					
					$languages = $this->parent->get_language_labels();
					
					$html .= '<div class="language-switcher">';
						
						foreach( $languages as $iso => $language ){
							
							if( in_array($iso,$active_languages) ){
								
								$value = '';
								
								if( $data['main'] == $iso ){
									
									$value = 'default';
								}
								elseif( isset($data['urls'][$iso]) ){
									
									$value = $data['urls'][$iso];
								}
								
								$html .= '<div style="margin-bottom:10px;">';
									
									$html .= '<input id="' . esc_attr( $field['id'] ) . '_main_' . $iso . '" class="' . esc_attr( $field['id'] ) . '_main" type="radio" name="' . esc_attr( $option_name ) . '[main]" value="' . $iso . '"' . ( $data['main'] == $iso ? ' checked="checked"' : '' ) . ' />' . "\n";										
									
									$html .= '<div style="margin-bottom:2px;width:90%;display: inline-block;">' . $language['full'] . '</div>';
										
									$html .= '<input id="' . esc_attr( $field['id'] ) . '_url_' . $iso . '" type="hidden" name="' . esc_attr( $option_name ) . '[urls]['.$iso.']" ' . $value . '" />' . "\n";
									
								$html .= '</div>';
							}
						}
						
					$html .= '</div>';
				}
				else{
					
					$html .= 'No language activated';
				}
				
			break;
			
			case 'radio':
				
				foreach ( $field['options'] as $k => $v ) {
					
					$checked = false;
					
					if ( $k == $data ) {
						
						$checked = true;
					}
					
					$html .= '<label style="display:block;margin-bottom:5px;" for="' . esc_attr( $field['id'] . '_' . $k ) . '">';
						
						$html .= '<input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ';
							
						$html .= $v;
							
					$html .= '</label> ';
				}
				
			break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( $k == $data ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
			break;

			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( in_array( $k, (array) $data ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
			break;
			
			case 'code':
				$html .= '<code id="' . esc_attr( $field['id'] ) . '" style="height:100px;overflow-x:hidden;overflow-y:scroll;display:block;background:#fff;border:1px solid #eee;border-radius:4px;padding:7px;">';
					
					$html .= $data;
					
				$html .= '</code>';
			break;

			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}
				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image' , 'language-switcher' ) . '" data-uploader_button_text="' . __( 'Use image' , 'language-switcher' ) . '" class="image_upload_button button" value="'. __( 'Upload new image' , 'language-switcher' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="'. __( 'Remove image' , 'language-switcher' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
			break;

			case 'color':
				?><div class="color-picker" style="position:relative;">
			        <input type="text" name="<?php esc_attr_e( $option_name ); ?>" class="color" value="<?php esc_attr_e( $data ); ?>" />
			        <div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>
			    </div>
			    <?php
			break;

		}

		switch( $field['type'] ) {
			
			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				$html .= '<br/><span class="description">' . $field['description'] . '</span>';
			break;

			default:
				if ( ! $post ) {
					$html .= '<label for="' . esc_attr( $field['id'] ) . '">' . "\n";
				}

				$html .= '<span class="description">' . $field['description'] . '</span>' . "\n";

				if ( ! $post ) {
					
					$html .= '</label>' . "\n";
				}
				
			break;
		}

		if ( !$echo ) {
			
			return wp_kses_normalize_entities($html);
		}

		echo wp_kses_normalize_entities($html);
	}

	/**
	 * Validate form field
	 * @param  string $data Submitted value
	 * @param  string $type Type of field to validate
	 * @return string       Validated value
	 */

	public function validate_output ( $data = '', $type = 'text' ) {

		switch( $type ) {
			
			case 'text'		: $data = esc_attr( $data ); break;
			case 'url'		: $data = esc_url( $data ); break;
			case 'email'	: $data = is_email( $data ); break;
		}

		return $data;
	}
	
	public function  validate_input( $data = '', $type = 'text' ) {

		switch( $type ) {
			
			case 'text'		: $data = sanitize_text_field( $data ); break;
			case 'textarea'	: $data = sanitize_textarea_field( $data ); break;
			case 'url'		: $data = sanitize_url( $data ); break;
			case 'email'	: $data = sanitize_email( $data ); break;
		}

		return $data;
	}

	/**
	 * Add meta box to the dashboard
	 * @param string $id            Unique ID for metabox
	 * @param string $title         Display title of metabox
	 * @param array  $post_types    Post types to which this metabox applies
	 * @param string $context       Context in which to display this metabox ('advanced' or 'side')
	 * @param string $priority      Priority of this metabox ('default', 'low' or 'high')
	 * @param array  $callback_args Any axtra arguments that will be passed to the display function for this metabox
	 * @return void
	 */
	public function add_meta_box ( $id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null ) {

		// Get post type(s)
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		// Generate each metabox
		foreach ( $post_types as $post_type ) {
			
			add_meta_box( 
			
				$id, 
				$title, 
				array( $this, 'meta_box_content' ), 
				$post_type, 
				$context, 
				$priority, 
				$callback_args 
			);
		}
	}

	/**
	 * Display metabox content
	 * @param  object $post Post object
	 * @param  array  $args Arguments unique to this metabox
	 * @return void
	 */
	public function meta_box_content ( $post, $args ) {

		$fields = apply_filters( $post->post_type . '_custom_fields', array(), $post->post_type );

		if ( ! is_array( $fields ) || 0 == count( $fields ) ) return;

		echo '<div class="custom-field-panel">' . "\n";

		foreach ( $fields as $field ) {

			if ( ! isset( $field['metabox'] ) ) continue;

			if ( ! is_array( $field['metabox'] ) ) {
				$field['metabox'] = array( $field['metabox'] );
			}

			if ( in_array( $args['id'], $field['metabox'] ) ) {
				$this->display_meta_box_field( $field, $post );
			}

		}

		echo '</div>' . "\n";

	}

	/**
	 * Dispay field in metabox
	 * @param  array  $field Field data
	 * @param  object $post  Post object
	 * @return void
	 */
	public function display_meta_box_field ( $field = array(), $post = null ) {

		if ( ! is_array( $field ) || 0 == count( $field ) ) return;

		$field = '<div class="form-field">' . ( !empty($field['label']) ? '<label for="' . esc_attr($field['id']) . '">' . $field['label'] . '</label>' : '' ) . $this->display_field( $field, $post, false ) . '</div>' . "\n";

		echo wp_kses_normalize_entities($field);
	}

	/**
	 * Save metabox fields
	 * @param  integer $post_id Post ID
	 * @return void
	 */
	public function save_meta_boxes ( $post_id = 0 ) {

		if ( ! $post_id ) return;

		$post_type = get_post_type( $post_id );

		$fields = apply_filters( $post_type . '_custom_fields', array(), $post_type );
		
		if ( ! is_array( $fields ) || 0 == count( $fields ) ) return;
		
		foreach ( $fields as $field ) {
			
			if ( isset( $_REQUEST[ $field['id'] ] ) ) {
				
				update_post_meta( $post_id, $field['id'], $this->validate_input( sanitize_text_field($_REQUEST[$field['id']]), $field['type'] ) );
			} 
			else {
				
				update_post_meta( $post_id, $field['id'], '' );
			}
		}
	}

}
