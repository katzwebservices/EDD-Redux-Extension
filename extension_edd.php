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
 * @author      Dovy Paukstys (dovy)
 * @version     3.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

// Don't duplicate me!
if( !class_exists( 'ReduxFramework_extension_edd' ) ) {


		/**
		 * Main ReduxFramework customizer extension class
		 *
		 * @since       1.0.0
		 */
		class ReduxFramework_extension_edd extends ReduxFramework {

			// Protected vars
			protected $parent;
			public $extension_url;
			public $extension_dir;
			public static $theInstance;

			/**
			 * Class Constructor. Defines the args for the extions class
			 *
			 * @since       1.0.0
			 * @access      public
			 * @param       array $sections Panel sections.
			 * @param       array $args Class constructor arguments.
			 * @param       array $extra_tabs Extra panel tabs.
			 * @return      void
			 */
			public function __construct( $parent ) {
				$this->parent = $parent;
				if ( empty( $this->extension_dir ) ) {
					$this->extension_dir = trailingslashit( str_replace( '\\', '/', dirname( __FILE__ ) ) );
					$this->extension_url = site_url( str_replace( trailingslashit( str_replace( '\\', '/', ABSPATH ) ), '', $this->extension_dir ) );
				}

				self::$theInstance = $this;

				add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
				add_filter( 'redux/'.$this->parent->args['opt_name'].'/field/class/edd_license', array( &$this, 'overload_edd_license_field_path' ) ); // Adds the local field
				add_action( 'redux/options/'.$this->parent->args['opt_name'].'/field/edd_license/register', array( &$this, 'register' ) );
				add_action( 'redux/options/'.$this->parent->args['opt_name'].'/validate', array( &$this, 'save_options') );
				add_action( 'wp_ajax_redux_edd_'.$parent->args['opt_name'].'_license', array( &$this, 'license_call' ) );

			}

			public function enqueue_scripts() {
				wp_enqueue_script( 'redux-edd_license', plugins_url( '/edd_license/field_edd_license.js', __FILE__ ) );
				wp_enqueue_style( 'redux-edd_license', plugins_url( '/edd_license/field_edd_license.css', __FILE__ ) );
			}

			static public function getInstance() {
				return self::$theInstance;
			}

			function register($field) {

				if ( $field['mode'] == "theme" ) {

					if ( !class_exists( 'EDD_SL_Theme_Updater' ) ) {
						include_once( dirname( __FILE__ ) . '/edd_license/EDD_SL_Theme_Updater.php' );
					}
					if ( !empty( $this->parent->options[ $field['id'] ]['license'] ) && $this->parent->options[ $field['id'] ]['status'] === 'valid' ) {

						$check = array( 'item_name', 'author', 'version' );
						foreach ( $check as $d ) {
							if ( !isset( $field[$d] ) || empty ( $field[$d] ) ) {
								$theme = wp_get_theme();
								$field['item_name'] = $theme->get( 'Name' );
								$field['version'] = $theme->get( 'Version' );
								$field['author'] = $theme->get( 'Author' );
								break;
							}
						}
						$edd_updater = new EDD_SL_Theme_Updater(
							array(
								'remote_api_url'  => $field['remote_api_url'],       // our store URL that is running EDD
								'version'         => $field['version'],  // current version number
								'license'         => $this->parent->options[$field['id']]['license'], // license key
								'item_name'       => $field['item_name'], // name of this theme
								'author'          => strip_tags( $field['author'] )    // author of this theme
							)
						);
					}
				}
				if ( $field['mode'] == "plugin" ) {

					if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
						include_once( dirname( __FILE__ ) . '/edd_license/EDD_SL_Plugin_Updater.php' );
					}

					if ( isset( $field['path'] ) && !empty( $field['path'] ) ) {
						$check = array( 'item_name', 'author', 'version' );
						foreach ( $check as $d ) {
							if ( !isset( $field[$d] ) || empty ( $field[$d] ) ) {
								$plugin = get_plugin_data( $field['path'] );
								$field['item_name'] = $plugin['Name'];
								$field['version'] = $plugin['Version'];
								$field['author'] = strip_tags( $plugin['Author'] );
								break;
							}
						}
					}

					if ( !empty( $this->parent->options[ $field['id'] ]['license'] ) && isset( $this->parent->options[ $field['id'] ]['status'] ) && $this->parent->options[ $field['id'] ]['status'] === 'valid' ) {
						$edd_updater = new EDD_SL_Plugin_Updater( $field['remote_api_url'], $field['path'], array(
								'version' => $field['version'], // current version number
								'license'   =>  $this->parent->options[ $field['id'] ]['license'],    // license key (used get_option above to retrieve from DB)
								'item_name' => $field['item_name'],  // name of this plugin
								'author' => strip_tags( $field['author'] )  // author of this plugin
							)
						);
					}
				}
			}

			/**
			 * When options are saved, delete the transient to force checking license status again
			 *
			 * This removes the need to always activate using AJAX; you can activate by saving the settings.
			 *
			 * @param  array $values Values for the Redux instance settings
			 * @return void
			 */
			function save_options( $values ) {

				if( !empty( $this->parent->fields ) && !empty( $this->parent->fields['edd_license'] ) ) {

					foreach ( (array)$this->parent->fields['edd_license'] as $key => $field ) {

						delete_transient( 'redux_edd_license_'.esc_attr( $key ). '_valid' );

					}

				}

			}

			function strings( $string = '' ) {

				$defaults = array(
					'status' => esc_html__('Status', 'redux-framework'),
					'error' => esc_html__('There was an error processing the request.', 'redux-framework'),
					'failed'  => esc_html__('The request could not be completed.', 'redux-framework'),
					'site_inactive' => esc_html__('Not Activated', 'redux-framework'),
					'no_activations_left' => esc_html__('Invalid; this license has reached its activation limit.', 'redux-framework'),
					'deactivated' => esc_html__('Deactivated', 'redux-framework'),
					'valid' => esc_html__('Valid', 'redux-framework'),
					'invalid' => esc_html__('Not Valid', 'redux-framework'),
					'missing' => esc_html__('Not Valid', 'redux-framework'),
					'revoked' => esc_html__('The license key has been revoked.', 'redux-framework'),
					'expired' => esc_html__('The license key has expired.', 'redux-framework'),

					'verifying_license' => esc_html__('Verifying license&hellip;', 'redux-framework'),
					'activate_license' => esc_html__('Activate License', 'redux-framework'),
					'deactivate_license' => esc_html__('Deactivate License', 'redux-framework'),
					'check_license' => esc_html__('Verify License', 'redux-framework'),
				);

				$updated_strings = apply_filters( 'redux/'.$this->parent->args['opt_name'].'/field/class/edd_license/strings', $defaults );

				$strings = wp_parse_args( $updated_strings, $defaults );

				if( isset( $strings[ $string ] ) ) {
					return $strings[ $string ];
				}

				return $string;

			}

			function license_call($array = array()) {

				global $wp_version;

				if ( !empty( $array ) ) {
					$_POST['data'] = $array;
				}

				if ( empty( $_POST['data']['license'] ) ) {
					die(-1);
				}

				$api_params = array(
					'edd_action'  => esc_attr( $_POST['data']['edd_action'] ),
					'license'     => esc_attr( $_POST['data']['license'] ),
					'item_name'   => urlencode( $_POST['data']['item_name'] ),
					'version'   => urlencode( $_POST['data']['version'] ),
					'author'   => urlencode( $_POST['data']['author'] ),
				);

				if ( !isset( $_POST['data']['remote_api_url'] ) || empty( $_POST['data']['remote_api_url'] ) ) {
					$_POST['data']['remote_api_url'] = 'http://easydigitaldownloads.com';
				}

				$response = wp_remote_get( add_query_arg( $api_params, $_POST['data']['remote_api_url'] ), array( 'timeout' => 15, 'sslverify' => false ) );

				if ( is_wp_error( $response ) ) {
					if ( empty( $array ) ) {
						exit( json_encode( array() ) );
					} else { // Non-ajax call
						return json_encode( array() );
					}
				}

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				// Not JSON
				if( empty( $license_data ) ) {

					$message = $this->strings('error');

					delete_transient( 'redux_edd_license_'.esc_attr( $_POST['data']['field_id']). '_valid' );

					// Change status
					return json_encode(array());
				}

			// Return JSON

				if( !empty( $license_data->error ) ) {
					$license_data->message = $this->strings( $license_data->error );
				} else {
					$license_data->message = $this->strings( $license_data->license );
				}

				$json = json_encode( $license_data );

				// Failed is the response from trying to de-activate a license and it didn't work.
				// This likely happened because people entered in a different key and clicked "Deactivate",
				// meaning to deactivate the original key. We don't want to save this response, since it is
				// most likely a mistake.
				if( $license_data->license !== 'failed' ) {

					set_transient( 'redux_edd_license_'.esc_attr( $_POST['data']['field_id'] ) . '_valid', $license_data, DAY_IN_SECONDS );

					// Update option with passed data license
					$options = $this->parent->options;
					$options[$_POST['data']['field_id']]['license'] = $_POST['data']['license'];
					$options[$_POST['data']['field_id']]['status'] = $license_data->license;
					$options[$_POST['data']['field_id']]['response'] = $json;

					update_option($_POST['data']['opt_name'], $options);
				}

				if ( empty( $array ) ) {
					exit( $json );
				} else { // Non-ajax call
					return $json;
				}
			}

			// Forces the use of the embeded field path vs what the core typically would use
			public function overload_edd_license_field_path($field) {
				return dirname(__FILE__).'/edd_license/field_edd_license.php';
			}


		} // class
} // if
