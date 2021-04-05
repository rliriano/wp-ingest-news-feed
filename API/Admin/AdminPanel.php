<?php
namespace API\Admin;

use API\NewsFeed;
use API\Admin\AdminOptions;

class AdminPanel {

	static $dashicons = NF_API_FILE . '/img/nbc-icon.png';
	static $logo = NF_API_FILE . '/img/nbc-logo.svg';
	static $tabs = [ 
		'setup' => 'feed_setup_options',
	];

	function __construct() {
		$this->isPage = strpos( $_SERVER['REQUEST_URI'], NewsFeed::$settingsPage );

		$this->adminOptions = new AdminOptions( $this );
		$this->setupOptions = get_option( self::$tabs['setup'] );

		add_action( 'admin_menu', array( $this, 'nfSettingsMenu' ) );
		add_action( 'admin_init', array( $this, 'nfRegisterSettings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'nfAdminScripts' ) );
	}
	
	/**
     * Option Settings
    */
	protected function nfDisplaySettings( $field, $meta = null, $tab ) {
		if ( ! ( $field || is_array( $field ) ) )
			return;
		
		$value = isset( $field['value'] ) ? $field['value'] : null;
		$title = isset( $field['title'] ) ? $field['title'] : null;
		$type = isset( $field['type'] ) ? $field['type'] : null;
		$groups  = isset( $field['groups'] ) ? $field['groups'] : null;
		$options = isset( $field['options'] ) ? $field['options'] : null;
		$width = isset( $field['width'] ) ? $field['width'] : null;
		$label = isset( $field['label'] ) ? '<label for="'.$field['id'].'">'.$field['label'].'</label>' : null;
		$desc = isset( $field['desc'] ) ? '<span class="description">' . $field['desc'] . '</span>' : null;
		$rows = isset( $field['rows'] ) ? $field['rows'] : '4';
		$cols = isset( $field['cols'] ) ? $field['cols'] : '60';
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : null;
		$default_color = isset( $field['default_color'] ) ? $field['default_color'] : null;
		
		$id = $name = isset( $field['id'] ) ? $tab : null;
		// $id = $name = isset( $field['id'] ) ? $field['id'] : null;
		
		switch( $type ) {
			// text
			case 'text':
				echo '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$meta.'" '.($placeholder ? 'placeholder="'.$placeholder.'"' : '').' class="regular-text" '.($width ? 'style="width:'.$width.';"' : '').' size="30" />';
				!empty( $desc ) ? $desc : '';
				break;
			case 'password':
				echo '<input type="password" name="'.$name.'" id="'.$id.'" value="'.$meta.'" '.($placeholder ? 'placeholder="'.$placeholder.'"' : '').' class="regular-text" '.($width ? 'style="width:'.$width.';"' : '').' size="30" />';
				!empty( $desc ) ? $desc : '';
				break;
			// textarea
			case 'textarea':
				echo '<textarea name="'.$name.'" id="'.$id.'" cols="'.$cols.'" rows="'.$rows.'" placeholder="'.$placeholder.'">'.$meta.'</textarea>
				<br />'.!empty( $desc ) ? $desc : '';
				break;
			// checkbox
			case 'checkbox':
				echo '<input type="checkbox" value="1" name="'.$name.'" id="'.$id.'" '.checked( $meta, true, false ).' />';
				echo !empty( $desc ) ? $desc : '';
				break;
			// checkbox
			case 'radio':
				echo '<input type="radio" value="'.$field['id'].'" name="'.$name.'" id="'.$id.'" '.checked( $meta, $field['id'], false ).' />';
				echo !empty( $desc ) ? $desc : '';
				break;
			// colorpicker
			case 'color':
				echo '<input type="text" name="'.esc_attr( $name ).'" id="colorpicker-'.$field['id'].'" value="'.$meta.'" '.($default_color ? 'data-default-color="'.$default_color.'"' : '').' />
				<br />' . !empty( $desc ) ? $desc : '';
				echo '<script type="text/javascript">
					jQuery(function($){
						var colorOptions = {
						defaultColor: true,
						palettes: false
					};
					jQuery("#colorpicker-'.$field['id'].'").wpColorPicker(colorOptions);
					});
				</script>';
				break;
			// select
			case 'select':
				echo '<select name="'.$name.'" id="'.$id.'" class="'.$field['id'].'" ' , isset( $multiple ) && $multiple == true ? ' multiple="multiple"' : '' , '>
					<option value="">Select One</option>'; // Select One
				foreach ( $options as $option ) {
					echo '<option' . selected( $meta, $option['value'], false ) . ' value="'.$option['value'].'">' . $option['label'] . '</option>';
				}
				echo '</select>';
				echo '<br />' . !empty( $desc ) ? $desc : '';
				break;
			// input_group
			case 'input_group':
				echo '<ul id="'.$id.'-group" class="admin_group">';
					$i = 0;
					if ( $meta == '' || $meta === array() ) {
						$keys = wp_list_pluck( $groups, 'id' );
						$meta = array ( array_fill_keys( $keys, null ) );
					}
					$meta = array_values( $meta );
					foreach( $meta as $value ):
						$p = 0;
						// echo '<pre>'; print_r($meta); echo '</pre>';
						foreach( $groups as $field ):
							echo '<li class="r-row '.$field['id'].'">';
								echo '<label>'.$field['label'].'</label>';
								echo $this->nfDisplaySettings( $field, $meta[$i], $id );
							echo '</li>';
						endforeach;
						$i++;
					endforeach;
				echo '</ul><br/>';
				echo !empty( $desc ) ? $desc : '';
				break;
			case 'plain':
				echo '<ul class="plain-list">';
				foreach( $options as $option ) {
					echo '<li>'.$option['label'].' - '.$option['value'].'</li>';
				}
				echo '</ul>';
				echo !empty( $desc ) ? $desc : '';
			break;
		}//end switch

	}
	
	/**
     * Settings display page
	 * @return - html output
    */
	function nfDisplayPage() {
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : self::$tabs['setup'];
		$setup = 'admin.php?page='.NewsFeed::$settingsPage.'&tab='.self::$tabs['setup'];

		echo '<div class="wrap" id="nbc">';
			echo '<h3><img src="'.self::$logo.'" style="max-width: 300px;" /></h3>';

			if ( ! isset( $_REQUEST['settings-updated'] ) )
				$_REQUEST['settings-updated'] = false;
			

			if ( $_REQUEST['settings-updated'] == true ) {
				echo '<div class="updated fade below-h2"><p><strong>'.__( 'Settings Saved' ).'</strong></p></div>';
			}

			echo '<div class="info-column">';
				echo '<div class="nav-tab-wrapper">';
					echo '<a href="'.$setup.'" class="nav-tab '.($active_tab == self::$tabs['setup'] ? 'nav-tab-active' : '').'">Setup</a>';
				echo '</div>';

				if ( self::$tabs['setup'] == $active_tab ) {
					echo '<form method="POST" action="options.php" class="nbc_options_form">';
						settings_fields( self::$tabs['setup'] );
						do_settings_sections( self::$tabs['setup'] );
						echo '<div id="section_container">';
							echo '<table class="form-table nbc">';
								foreach ( $this->adminOptions->nfOptionFields['setup_options'] as $field ) {
									$meta = get_option( self::$tabs['setup'] );
									$tab = self::$tabs['setup'].'['.$field['id'].']';
									
									echo '<tr class="' . $field['id'] . '">';
									if ( $field['type'] == 'section' ) {
										echo '<td colspan="2"><h2 class="section-title">' . $field['label'] . '</h2></td>';
									} else {
										echo '<th><label for="' . $field['id'] . '">' . $field['label'] . '</label></th>';
										echo '<td>';
											echo $this->nfDisplaySettings( $field, $meta[$field['id']], $tab );
										echo '</td>';
									}
									echo '</tr>';
								}
							echo '</table>'; // end table
						echo '</div>';
						echo '<p class="submit">';
							echo '<input name="Submit" type="submit" class="button-primary" value="'. __( 'Save Changes', 'nbc' ). '" />';
						echo '</p>';
					echo '</form>';
				}

			echo '</div>';
			$this->nfAdminSidebarMenu();
		echo '</div>';
	}
	function nfAdminSidebarMenu() {
		?>
		<div class="side-info-column">
			
			<div class="section credits">
				<h3 class="section-title">About</h3>
				<div class="inside">
					<p><b>News Feed Plugin</b>.</p>
				</div>
			</div>
			<div class="section credits">
				<h3 class="section-title"><span>Credits</span></h3>
				<div class="inside">
					<a href="https://www.nbcnewyork.com/" target="_blank">
						<img src="<?php echo self::$logo; ?>" />
					</a>

					<p>Copyright @<?php echo date('Y'); ?> NBC</p>

					<p>Licensed under the Apache License, Version 2.0 (the "License");
						you may not use this file except in compliance with the License.
						You may obtain a copy of the License at</p>

					<p><a href="http://www.apache.org/licensp/LICENSE-2.0">http://www.apache.org/licensp/LICENSE-2.0</a></p>

					<p>Unless required by applicable law or agreed to in writing, software
						distributed under the License is distributed on an "AS IS" BASIS,
						WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
						See the License for the specific language governing permissions and
						limitations under the License.
					</p>
				</div>
			</div>
		</div>
	 	<?php
	}

	protected function nfValidateThis($input) {
		$valid = array();
		// checks each input that has been added
		foreach($input as $key => $value){
			if(get_option($key === FALSE)){
				add_option($key, $value);
			} else {
				update_option($key, $value);
			}
			$valid[$key] = $value;
		}
		return $valid;
	}

	function nfSettingsMenu() {
		add_menu_page( 'Feed Options', 'Feed Options', 'manage_options', NewsFeed::$settingsPage, array( $this, 'nfSettingsOptions' ), self::$dashicons );
	}
	
	function nfSettingsOptions() {
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		} else {
			$this->nfDisplayPage();
		}
	}
	
	function nfRegisterSettings() {		
		foreach ( self::$tabs as $option ) {
			register_setting( $option, $option, array( $this, 'nfValidateThis' ) );
		}
	}
	
	function nfAdminScripts() {
		if ( is_admin() && $this->isPage !== false ) {
			wp_enqueue_style( NewsFeed::$prefix.'admin-css', NF_API_FILE . '/css/admin-panel.css');
		}
	}

}
