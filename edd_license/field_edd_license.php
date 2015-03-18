<?php
/**
 * Redux Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Redux Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Redux Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     ReduxFramework
 * @subpackage  Field_Color
 * @author      Daniel J Griffiths (Ghost1227)
 * @author      Dovy Paukstys
 * @version     3.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

// Don't duplicate me!
if( !class_exists( 'ReduxFramework_edd_license' ) ) {

    /**
     * Main ReduxFramework_color class
     *
     * @since       1.0.0
     */
	class ReduxFramework_edd_license extends ReduxFramework {

		/**
		 * Field Constructor.
		 *
		 * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
		 *
	 	 * @since 		1.0.0
	 	 * @access		public
	 	 * @return		void
		 */
		public function __construct( $field = array(), $value ='', $parent ) {

			parent::__construct( $parent->sections, $parent->args );

			$this->field = $field;
			$this->value = $value;

			// Create defaults array
			$defaults = array(
				'mode' => '',
				'path' => '',
				'remote_api_url' => '',
				'version' => '',
				'item_name' => '',
				'author' => '',
			);

			$this->field = wp_parse_args( $this->field, $defaults );

			$defaults = array(
				'license' 	=> '',
				'status' 	=> '',
				'response'	=> '',
			);

			$this->value = wp_parse_args( $this->value, $defaults );

			$this->parent = $parent;

		}

		function get_license_data() {

			$license_data = NULL;

			if ( !empty( $this->value['license'] ) ) {

				$license_data = get_transient('redux_edd_license_'.$this->field['id'] . '_valid');

				if ( empty( $license_data ) || is_wp_error( $license_data ) || !is_object( $license_data ) ) {

					$EDD_Extension = ReduxFramework_extension_edd::$theInstance;

			        $data = array(
			          'edd_action'  => 'check_license',
			          'license'     => $this->value['license'],
			          'field_id'     => $this->field['id'],
			          'opt_name'     => $this->parent->args['opt_name'],
			          'item_name'   => urlencode( $this->field['item_name'] ),
			          'version'   => urlencode( $this->field['version'] ),
			          'author'   => urlencode( $this->field['author'] ),
			          'remote_api_url' => $this->field['remote_api_url']
			        );

			        $license_data = json_decode( $EDD_Extension->license_call( $data ) );

				}

			}

			return $license_data;

		}

		/**
		 * Field Render Function.
		 *
		 * Takes the vars and outputs the HTML for the field in the settings
	 	 *
	 	 * @since 		1.0.0
	 	 * @access		public
	 	 * @return		void
		 */
		public function render() {

			$EDD_Extension = ReduxFramework_extension_edd::$theInstance;

			$license_data = $this->get_license_data();

			if( !empty( $license_data ) ) {

				$this->value['status'] = isset( $license_data->error ) ? $license_data->error : $license_data->license;

			} else {
				$this->value['status'] = 'site_inactive';
			}

			switch( $this->value['status'] ) {
				case 'deactivated':
				case 'site_inactive':
					$noticeClasses = 'redux-warning redux-info-field';
					break;
				case 'valid':
					$noticeClasses = 'redux-success redux-info-field';
					break;
				default:
					if( empty( $license_data->success ) ) {
						$noticeClasses = 'redux-critical redux-info-field';
					} else {
						$noticeClasses = 'redux-success redux-info-field';
					}

					break;
			}

			echo '<div id="' . $this->field['id'] . '-notice" data-verify="'.esc_attr( $EDD_Extension->strings('verifying_license') ).'" class="'.$noticeClasses.'">';
			echo '<strong>' . esc_html( $EDD_Extension->strings('status') ) . ': <span id="' . $this->field['id'] . '-status_notice">'. $EDD_Extension->strings( $this->value['status'] ) .'</span></strong></div>';

			$fields = array(
				'field_id' => 'id',
				'remote_api_url' => 'remote_api_url',
				'version' => 'version',
				'item_name' => 'item_name',
				'author' => 'author',
				'status' => 'status',
			);

			foreach ($fields as $key => $value ) {
				$value = isset( $this->field[ $value ] ) ? $this->field[ $value ] : NULL;
				echo '<input type="hidden" class="redux-edd redux-edd-'.esc_attr( $key ).'" id="' . $this->field['id'] . '-'.esc_attr( $key ).'" value="' . esc_attr( $value ) . '" />';

			}

			echo '<input name="' . $this->args['opt_name'] . '[' . $this->field['id'] . '][license]"  id="' . $this->field['id'] . '-license" class="noUpdate redux-edd-input redux-edd ' . $this->field['class'] . ' regular-text code"  type="text" value="' . $this->value['license'] . '" " />';
			echo '<a href="#" data-id="'.$this->field['id'].'" class="button button-primary redux-EDDAction hide" data-edd_action="check_license">'.esc_attr( $EDD_Extension->strings( 'check_license' ) ).'</a>';
			$hide = "";

			if( $this->value['status'] === 'valid' ) {
				$hide = " hide";
			}
			echo '&nbsp; <a href="#" id="'.$this->field['id'].'-activate" data-id="'.$this->field['id'].'" class="button button-primary redux-EDDAction'.$hide.'" data-edd_action="activate_license">'.esc_attr( $EDD_Extension->strings( 'activate_license' ) ).'</a>';
			$hide = "";
			if ( $this->value['status'] !== 'valid' ) {
				$hide = " hide";
			}
			echo '&nbsp; <a href="#" id="'.$this->field['id'].'-deactivate" data-id="'.$this->field['id'].'" class="button button-secondary redux-EDDAction'.$hide.'" data-edd_action="deactivate_license">'.esc_attr( $EDD_Extension->strings( 'deactivate_license' ) ).'</a>';
			if (isset($this->parent->args['edd'])) {
				foreach( $this->parent->args['edd'] as $k => $v ) {
					echo '<input type="hidden" data-id="'.$this->field['id'].'" id="' . $this->field['id'] . '-'.$k.'" class="redux-edd edd-'.$k.'"  type="text" value="' . $v . '" />';
				}
			}

		}

		/**
		 * Enqueue Function.
		 *
		 * If this field requires any scripts, or css define this function and register/enqueue the scripts/css
		 *
		 * @since		1.0.0
		 * @access		public
		 * @return		void
		 */
		public function enqueue() {

			wp_enqueue_style(
                'redux-field-info-css',
	            ReduxFramework::$_url . 'inc/fields/info/field_info.css',
				ReduxFramework_extension_edd::edd_license_field_version,
                true
            );

			wp_enqueue_script(
				'redux-field-edd_license-js',
				ReduxFramework_extension_edd::getInstance()->extension_url . 'edd_license/field_edd_license.js',
				array( 'jquery', 'redux-js' ),
				ReduxFramework_extension_edd::edd_license_field_version,
				false
			);

			wp_enqueue_style(
				'redux-field-edd_license-css',
				ReduxFramework_extension_edd::getInstance()->extension_url . 'edd_license/field_edd_license.css',
				ReduxFramework_extension_edd::edd_license_field_version,
				true
			);

		}
	}
}
