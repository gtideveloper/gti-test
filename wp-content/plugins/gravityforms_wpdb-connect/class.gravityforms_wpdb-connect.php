<?php



	/**
 	 Class Name: SP Gravity Forms WPDB Connect
	 Class URI: http://specialpress.de/plugins/spgfwpdb
	 Description: Connect Gravity Forms to the WPDB MySQL Database
	 Version: 3.6.0
	 Date: 2019/03/14
	 Author: Ralf Fuhrmann
	 Author URI: http://naranili.de
	 */



	class SpGfWpdbConnect extends GFFeedAddOn 
	{

	
		private static $_instance = null;


		/**
		 * @var string Version number of the Add-On
		 */
		protected $_version = '3.6.0';

		/**
		 * @var string Gravity Forms minimum version requirement
		 */
		protected $_min_gravityforms_version = '2.0.0';

		/**
		 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
		 */
		protected $_slug = 'wpdb-connect';

		/**
		 * @var string Relative path to the plugin from the plugins folder. Example "gravityforms/gravityforms.php"
		 */
		protected $_path = 'gravityforms_wpdb-connect/gravityforms_wpdb-connect.php';

		/**
		 * @var string Full path the the plugin. Example: __FILE__
		 */
		protected $_full_path = __FILE__;

		/**
		 * @var string URL to the Gravity Forms website. Example: 'http://www.gravityforms.com' OR affiliate link.
		 */
		protected $_url;

		/**
		 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
		 */
		protected $_title = 'Gravity Forms WPDB Connect';

		/**
		 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
		 */
		protected $_short_title = 'GF WPDB Connect';

		/**
		 * @var array Members plugin integration. List of capabilities to add to roles.
		 */
		protected $_capabilities = array();


		// ------------ Permissions -----------

		/**
		 * @var string|array A string or an array of capabilities or roles that have access to the settings page
		 */
		protected $_capabilities_settings_page = array( 'gravityforms_edit_settings' );

		/**
		 * @var string|array A string or an array of capabilities or roles that have access to the form settings
		 */
		protected $_capabilities_form_settings = array( 'gravityforms_edit_forms' );

		/**
		 * @var string|array A string or an array of capabilities or roles that have access to the plugin page
		 */
		protected $_capabilities_plugin_page = array( 'gravityforms_edit_settings' );

		/**
		 * @var string|array A string or an array of capabilities or roles that have access to the app menu
		 */
		protected $_capabilities_app_menu = array( 'gravityforms_edit_settings' );

		/**
		 * @var string|array A string or an array of capabilities or roles that have access to the app settings page
		 */
		protected $_capabilities_app_settings = array( 'gravityforms_edit_settings' );

		/**
		 * @var string|array A string or an array of capabilities or roles that can uninstall the plugin
		 */
		protected $_capabilities_uninstall = array( 'gravityforms_uninstall' );



		/**
		 * get an instance of this class.
		 *
		 * @return GFSimpleAddOn
		 */

		public static function get_instance() 
		{
		
			
			if ( self::$_instance == null ) 
				self::$_instance = new SpGfWpdbConnect();
		

			return( self::$_instance );


		}	



		/**
		 * plugin starting point
		 * handles hooks, loading of language files and PayPal delayed payment support
		 *
		 * @param
		 *
		 * @return
		 */

		public function init() 
		{

		
			parent::init();
			

			/**
			 * load the textdomain
			 */

			if( function_exists('load_plugin_textdomain') )
				load_plugin_textdomain( 'spgfwpdb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');


			add_action( 'wp_enqueue_scripts', array( &$this, 'spgfwpdb_wp_enqueue_scripts' ) );
			

			/**
			 * add GF actions
			 */

			add_action( 'gform_field_advanced_settings', array( &$this, 'spgfwpdb_gform_field_advanced_settings' ), 10, 2 );				
			add_action( 'gform_editor_js', array( &$this, 'spgfwpdb_gform_editor_js' ), 10 );
			add_action( 'gform_post_add_entry', array( &$this, 'spgfwpdb_gform_post_add_entry' ), 10, 2 );
			add_action( 'gform_after_update_entry', array( &$this, 'spgfwpdb_gform_after_update_entry' ), 10, 3 );	
			add_action( 'gform_post_update_entry', array( &$this, 'spgfwpdb_gform_post_update_entry' ), 10, 2 );
			add_action( 'gform_delete_lead', array( &$this, 'spgfwpdb_gform_delete_lead' ), 10, 1 );
			add_action( 'gform_delete_entries', array( &$this, 'spgfwpdb_gform_delete_entries' ), 10, 2 );
			add_action( 'gform_admin_pre_render', array( &$this, 'spgfwpdb_gform_admin_pre_render' ), 10, 1 );
					
		
			/**
			 * add GF filters
			 */

			add_filter( 'gform_save_field_value', array( &$this, 'spgfwpdb_gform_save_field_value' ), 10, 5 );
			add_filter( 'gform_pre_render', array( &$this, 'spgfwpdb_gform_pre_render' ), 99, 3 );		
			add_filter( 'gform_field_validation', array( &$this, 'spgfwpdb_gform_field_validation' ), 10, 4 );
			add_filter( 'gform_entry_post_save', array( &$this, 'spgfwpdb_gform_entry_post_save' ), 10, 2 );
			add_filter( 'gform_form_update_meta', array( &$this, 'spgfwpdb_gform_form_update_meta' ), 10, 3 );
			

		}



		/** 
		 * get the gravity forms shortcode and add some
		 * helpfull JS and CSS
		 */
		function spgfwpdb_wp_enqueue_scripts()
		{

			
			global $post;
			if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'gravityform') ) {

				/**
				 * enqueue the JS and CSS for readonly
				 */
				wp_enqueue_script( 'spgf-readonly', plugins_url( '/js/spgf_readonly.js' , __FILE__ ), array( 'jquery' ) );	
				wp_enqueue_style( 'spgf-readonly', plugins_url( '/css/spgf_readonly.css' , __FILE__ ) );	
				
			}
			

		}




		/**
		 * enable feed duplication.
		 * 
		 * @access public
		 * @param  int|array $feed_id The ID of the feed to be duplicated or the feed object when duplicating a form
		 *
		 * @return bool
		 */
		public function can_duplicate_feed( $feed_id ) 
		{
		
		
			return( true );

		
		}
		
		
  		/**
		 * process the feed 
		 *
		 * @param array $feed   the feed object to be processed
		 * @param array $entry  the entry object currently being processed
		 * @param array $form   the form object currently being processed
		 *
		 * @return bool|void
		 */

		public function process_feed( $feed, $entry, $form ) 
		{
		

			/**
			 * only work at this function if there is a WPDB database table name set
			 */

			if( empty( $feed[ 'meta' ][ 'wpdbTable' ] ) )
				return;


			/**
			 * process the used datafields
			 */

			foreach( $feed[ 'meta' ][ 'wpdbTableFields' ] AS $field ) {


				/**
				 * key = name of the mysql field
				 * value = array-key of the gf entry
				 */

				$saveFieldTypes[ $field[ 'key' ] ] = self::spgfwpdb_get_field_type( $feed[ 'meta' ][ 'wpdbTable' ], $field[ 'key' ] );
				$saveFieldValues[ $field[ 'key' ] ] = $this->get_field_value( $form, $entry, $field[ 'value' ] );
				
				self::log_debug( "Try to use value : " . $saveFieldValues[ $field[ 'key' ] ] . " as " . $saveFieldTypes[ $field[ 'key' ] ] );


			}



			/**
			 * insert the record into the WPDB table
			 */

			global $wpdb;

			$wpdb->insert( $feed[ 'meta' ][ 'wpdbTable' ], $saveFieldValues, $saveFieldTypes );		
			
			self::log_debug( "Inserting Record into " . $feed[ 'meta' ][ 'wpdbTable' ] . " | " . $wpdb->last_error );

			$insertedId = $wpdb->insert_id;
			
			self::log_debug( "InsertedId : " . $insertedId );


			/**
			 * return the insert_id if required
			 */
			
			if( $feed[ 'meta' ][ 'wpdbPrimaryKey' ][0][ 'value' ] ) {


				if( $entry[ $feed[ 'meta' ][ 'wpdbPrimaryKey' ][0][ 'value' ] ] == '{insert:id}' ) {

					
					$entry[ $feed[ 'meta' ][ 'wpdbPrimaryKey' ][0][ 'value' ] ] = $insertedId;

					GFAPI::update_entry( $entry );


				}

			}


		}



		/**
		 * process the feed to update the database
		 *
		 * @param array $feed   the feed object to be processed
		 * @param array $entry  the entry object currently being processed
		 * @param array $form   the form object currently being processed
		 *
		 * @return bool|void
		 */

		public function process_feed_update( $feed, $entry, $form ) 
		{
		

			/**
			 * only work at this function if there is a WPDB database table name set
			 */

			if( empty( $feed[ 'meta' ][ 'wpdbTable' ] ) )
				return;


			/**
			 * process the used primarykeys
			 */

			$is_primaryKey = FALSE;
			foreach( $feed[ 'meta' ][ 'wpdbPrimaryKey' ] AS $field ) {


				/**
				 * key = name of the mysql field
				 * value = array-key of the gf entry
				 */

				$savePrimaryTypes[ $field[ 'key' ] ] = self::spgfwpdb_get_field_type( $feed[ 'meta' ][ 'wpdbTable' ], $field[ 'key' ] );
				$savePrimaryValues[ $field[ 'key' ] ] = $this->get_field_value( $form, $entry, $field[ 'value' ] );
				$is_primaryKey = TRUE;


			}


			/**
			 * without an primary key it's not possible to update the record
			 */

			if( !$is_primaryKey )
				return;



			/**
			 * process the used datafields
			 */

			foreach( $feed[ 'meta' ][ 'wpdbTableFields' ] AS $field ) {


				/**
				 * key = name of the mysql field
				 * value = array-key of the gf entry
				 */

				$saveFieldTypes[ $field[ 'key' ] ] = self::spgfwpdb_get_field_type( $feed[ 'meta' ][ 'wpdbTable' ], $field[ 'key' ] );
				$saveFieldValues[ $field[ 'key' ] ] = $this->get_field_value( $form, $entry, $field[ 'value' ] );
	
				self::log_debug( "Try to update value : " . $saveFieldValues[ $field[ 'key' ] ] . " as " . $saveFieldTypes[ $field[ 'key' ] ] );


			}


			/**
			 * update the record at the WPDB table
			 */

			global $wpdb;

			$wpdb->update( $feed[ 'meta' ][ 'wpdbTable' ], $saveFieldValues, $savePrimaryValues, $saveFieldTypes, $savePrimaryTypes );		

			self::log_debug( "Updating Record from " . $feed[ 'meta' ][ 'wpdbTable' ] . " | " . $wpdb->last_error );


		}



		/**
		 * process the feed to delete the database
		 *
		 * @param array $feed   the feed object to be processed
		 * @param array $entry  the entry object currently being processed
		 * @param array $form   the form object currently being processed
		 *
		 * @return bool|void
		 */

		public function process_feed_delete( $feed, $entry, $form ) 
		{
		

			/**
			 * process the used primarykeys
			 */

			$is_primaryKey = FALSE;
			foreach( $feed[ 'meta' ][ 'wpdbPrimaryKey' ] AS $field ) {


				/**
				 * key = name of the mysql field
				 * value = array-key of the gf entry
				 */

				$savePrimaryTypes[ $field[ 'key' ] ] = self::spgfwpdb_get_field_type( $feed[ 'meta' ][ 'wpdbTable' ], $field[ 'key' ] );
				$savePrimaryValues[ $field[ 'key' ] ] = $this->get_field_value( $form, $entry, $field[ 'value' ] );
				$is_primaryKey = TRUE;


			}


			/**
			 * without an primary key it's not possible to delete the record
			 */

			if( !$is_primaryKey )
				return;



			/**
			 * delete the record from the WPDB table
			 */

			global $wpdb;

			$wpdb->delete( $feed[ 'meta' ][ 'wpdbTable' ], $savePrimaryValues, $savePrimaryTypes );		

			self::log_debug( "Deleting Record from " . $feed[ 'meta' ][ 'wpdbTable' ] . " | " . $wpdb->last_error );


		}




		/**
		 * --------------------------------------------------------------------------------
		 * filters and actions to extend the GF functions
		 * --------------------------------------------------------------------------------
		 */




		/**
		 * return an array of the columns to display
		 *
		 * @return array
		 */

		public function feed_list_columns() 
		{
	
		
			return( array(
				'feedName' => __( 'Name', 'spgfwpdb' ),
				'wpdbTable'   => __( 'WPDB Tablename', 'spgfwpdb' )
			) );


		}



		/**
		 * configures the settings which should be rendered on the feed edit page in the form settings
		 *
		 * @return array
		 */

		public function feed_settings_fields() 
		{
		

			/**
			 * retrieve the current feed meta
			 */

			if( intval( rgget( 'fid' ) ) )
				$feed = $this->get_feed( rgget( 'fid' ) );


			$settingFields = array();


			$settingFields[ 'default' ] = array(
				
				'title'  => esc_html__( 'WPDB Connect Feed Settings', 'spgfwpdb' ),
				'description' => '',
				'fields' => array(

						array(
							'name'		=> 'feedName',
							'type'   	=> 'text',
							'class'		=> 'medium',
							'label'   	=> esc_html__( 'Feed name', 'spgfwpdb' ),
							'tooltip' 	=> esc_html__( 'Enter a name for the feed', 'spgfwpdb' ),
							'required'	=> true,
						),

						array(
							'name'	  => 'wpdbTable',
							'type'	  => 'select',
							'label'	 => esc_html__( 'WPDB Table Name', 'spgfwpdb' ),
							'tooltip'	=> esc_html__( 'Select the WPDB Table name', 'spgfwpdb' ),
							'choices'   => self::spgfwpdb_get_table_names(),
							'required'  => true,
						),

						array(
							'name'		   	=> 'feedCondition',
							'label'		 	=> esc_html__( 'Feed Condition', 'spgfwpdb' ),
							'type'		   	=> 'feed_condition',
							'tooltip'			=> esc_html__( 'Define the conditional logic to process this feed', 'spgfwpdb' ),
							'checkbox_label' 	=> esc_html__( 'Enable Condition', 'spgfwpdb' ),
							'instructions'   	=> esc_html__( 'Process this data if', 'spgfwpdb' ),
						),					
						
					),

				);

		
			/**
			 * display the fields only if we have a table
			 */

			if( !empty( $feed[ 'meta' ][ 'wpdbTable' ] ) ) {

				$settingFields[ 'fieldnames' ] = array(
	
					'title'	   => esc_html__( 'Field Names', 'spgfwpdb' ),
					'description' => esc_html__( 'Please assign your database table fields with your form fields. Do not forget to add your primary key field, if you need to update this from your form data.', 'spgfwpdb' ),
					'dependency'  => array(
						'field'   => 'wpdbTable',
						'values'  => '_notempty_'
					),	
					'fields'	  => array(
						array(	
							'name'	  => 'wpdbTableFields',
							'label'	 => '',
							'type'	  => 'dynamic_field_map',
							'disable_custom' => TRUE,
							'field_map' => self::spgfwpdb_get_field_names( $feed[ 'meta' ][ 'wpdbTable' ] ),
							'class'	 => 'medium'
						),

					),

				);

				$settingFields[ 'primarykey' ] = array(
	
					'title'	   => esc_html__( 'Primary Key', 'spgfwpdb' ),
					'description' => esc_html__( '', 'spgfwpdb' ),
					'dependency'  => array(
						'field'   => 'wpdbTable',
						'values'  => '_notempty_'
					),	
					'fields'	  => array(
						array(	
							'name'	  => 'wpdbPrimaryKey',
							'label'	 => '',
							'type'	  => 'dynamic_field_map',
							'disable_custom' => TRUE,
							'field_map' => self::spgfwpdb_get_primary_keys( $feed[ 'meta' ][ 'wpdbTable' ] ),
							'class'	 => 'medium'
						),

					),

				);

			}

				
			return( array_values( $settingFields ) );


		}



		/**
		 * custom function to create the field-map choices that will exclude
		 * the mutlipleFiles and the list field
		 *
		 * @param int	$form_id			   the id of the current_form
		 * @param string $field_type			type of the field
		 * @param array  $exclude_field_types   field to be excluded
		 *
		 * @return array
		 */

		public static function get_field_map_choices( $form_id, $field_type = null, $exclude_field_types = null ) 
		{


			$choices = parent::get_field_map_choices( $form_id, $field_type, array( 'list', 'multipleFiles' ) );

			return( $choices );
	

		}




		/**
		 * --------------------------------------------------------------------------------
		 * filters and actions to extend the GF functions
		 * --------------------------------------------------------------------------------
		 */


		/**
		 * add some nice and new merge tags to fill with needed data
		 *
		 * @param $form object the form
		 *
		 * @return object
		 */
		 
		function spgfwpdb_gform_admin_pre_render( $form )
		{


			?>
			<script type="text/javascript">
				gform.addFilter('gform_merge_tags', 'add_merge_tags');
				function add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option){
					mergeTags["custom"].tags.push({ tag: '{insert:id}', label: '<?php _e( 'Insert ID', 'spgfwpdb' ); ?>' });
					mergeTags["custom"].tags.push({ tag: '{entry:id}', label: '<?php _e( 'Entry ID', 'spgfwpdb' ); ?>' });
					mergeTags["custom"].tags.push({ tag: '{user:id}', label: '<?php _e( 'User ID', 'spgfwpdb' ); ?>' });
					return mergeTags;
				}
			</script>
			<?php
	
			/* return the form object from the php hook */
			
			return( $form );
			
		
		}



		/**
		 * insert the input settings if a chained select field is triggered by an SELECt
		 *
		 * @param object $form	  the current form
		 *
		 * @return object
		 */

		function spgfwpdb_gform_form_update_meta( $form_meta, $form_id )
		{
		
		
			global $wpdb;
			
		   
			foreach( $form_meta[ 'fields' ] AS $key => $field ) {
			
				
				if( $field[ 'type' ] == 'chainedselect' ) {
				
				
					if( !empty( $field[ 'spgfwpdb_choices' ] ) ) {
					

						$db_query = $field[ 'spgfwpdb_choices' ];
					
						$db_query = GFCommon::replace_variables( $db_query, $form_meta, "" );
						
						$db_results = $wpdb->get_results( $db_query, ARRAY_A );
						
						if( $db_results ) {
							
							unset( $i );
							$inputs = array();

							foreach( $db_results[0] as $name => $value ) {
						
								$i++;
								$inputs[] = array(
									'id'	=> $field->id . '.' . $i,
									'label' => $name,
									'name'  => strtolower( $name )
								);

							}
							
							$form_meta[ 'fields' ][ $key ][ 'inputs' ] = $inputs;
							
												
						}
			
					}
		
				}
				
			}
			
			
			return( $form_meta );
		 
			
		}



		/**
		 * replace the placeholder at a field with the right value
		 *
		 * @param mixed  $value	 the field value
		 * @param object $lead	  the current entry
		 * @param object $field	 the current field
		 * @param object $form	  the current form
		 * @param string $input_id  the id of the input
		 *
		 * @return array
		 */

		function spgfwpdb_gform_save_field_value( $value, $lead, $field, $form, $input_id )
		{
			
			
			$value = str_replace( '{user:id}', wp_get_current_user()->ID, $value );
			$value = str_replace( '{entry:id}', $lead[ 'id' ], $value );
				
			return( $value );
			
			
		}



		/**
		 * process the feeds if an entry was added with the GFAPI
		 *
		 * @param object $entry the current entry
		 * @param object $form  the current form
		 *
		 * @return
		 */

		public function spgfwpdb_gform_post_add_entry( $entry, $form )
		{


			/**
			 * loop thru and process the feeds
			 */

			$feeds = GFAPI::get_feeds( NULL, $entry[ 'form_id' ], $this->_slug );
			foreach( (array)$feeds AS $feed )
				self::process_feed( $feed, $entry, $form );


		}



		/**
		 * process the feeds after the entry was changed with the GFAPI
		 *
		 * @param object $entry			 the current entry		 
		 * @param object $original_entry	the entry before the changes
		 *
		 * @return
		 */

		function spgfwpdb_gform_post_update_entry( $entry, $original_entry )
		{
			

			/**
			 * loop thru and process the feeds
			 */

			$feeds = GFAPI::get_feeds( NULL, $entry[ 'form_id' ], $this->_slug );
			foreach( (array)$feeds AS $feed )
				self::process_feed_update( $feed, $entry, $form );
			
				
		} 



		/**
		 * process the feeds after the entry was changed from the backend
		 *
		 * @param object $form			  the current form
		 * @param int	$entry_id		  the entry id
		 * @param object $original_entry	the entry before the changes
		 *
		 * @return
		 */

		function spgfwpdb_gform_after_update_entry( $form, $entry_id, $original_entry )
		{
			

			$entry = GFAPI::get_entry( $entry_id );


			/**
			 * loop thru and process the feeds
			 */

			$feeds = GFAPI::get_feeds( NULL, $entry[ 'form_id' ], $this->_slug );
			foreach( (array)$feeds AS $feed )
				self::process_feed_update( $feed, $entry, $form );
			
				
		} 



		/**
		 * delete a record from WPDB after the entry was deleted at the backend or with he GFAPI
		 *
		 * @param int	$entry_id  the entry id
		 * @param object $form	  the current form
		 *
		 * @return
		 */

		function spgfwpdb_gform_delete_lead( $entry_id, $form = '' )
		{

			
			$entry = GFAPI::get_entry( $entry_id );


			/**
			 * loop thru and process the feeds
			 */

			$feeds = GFAPI::get_feeds( NULL, $entry[ 'form_id' ], $this->_slug );
			foreach( (array)$feeds AS $feed )
				self::process_feed_delete( $feed, $entry, $form );
			
				
		}



		/**
		 * empty the trash and delete all trashed entries
		 *
		 * @param int	$form_id   the form id
		 * @param string $status	the delete status
		 *
		 * @return
		 */

		function spgfwpdb_gform_delete_entries( $form_id, $status )
		{

			/**
			 * only if we empty the trash
			 */
			if( $status == 'trash' ) {
						
						
				$feeds = GFAPI::get_feeds( NULL, $form_id, $this->_slug );
						
							
				/**
				 * retrieve all assigned entries from the database
				 */

				global $wpdb;
				$lead_table = RGFormsModel::get_lead_table_name();
				$entries = $wpdb->get_results( "SELECT * FROM {$lead_table} WHERE form_id={$form_id} AND status='{$status}';", ARRAY_A );
				foreach( $entries AS $entry ) {

					foreach( (array)$feeds AS $feed )
						self::process_feed_delete( $feed, $entry, $form );
								
					
				}
						
			}
					
					
		}
				
				
				
		/**
		 * add choices from a MySQL-query
		 *
		 * @param object $form		  the current form
		 * @param bool   $ajax		  if ajax is enabled or not
		 * @param array  $field_values  the current valaues for thsi field
		 *
		 * @return array
		 */

		function spgfwpdb_gform_pre_render( $form, $ajax, $field_values )
		{

		
			/**
			 * loop thru the fields to get the fields
			 * with a choices statement
			 */

			foreach( $form[ 'fields' ] as $key => $field ) {


				if( !empty( $field[ 'spgfwpdb_choices' ] ) ) {



					/**
					 * we have a MySQL statement to build choices
					 */

					global $wpdb;
					$choices = $field[ 'choices' ];

					
					/**
					 * replace merge tags at the query string
					 */
					 
					$db_query = $field[ 'spgfwpdb_choices' ];
					
					$db_query = GFCommon::replace_variables( $db_query, $form, "" );
					
							
					/**
					 * check with type of field we have
					 */

					switch( $field[ 'type' ] ) {
								
								
						/**
						 * chained selects support
						 */		
						
						case 'chainedselect':
						
							$choices = array();
							$db_results = $wpdb->get_results( $db_query, ARRAY_A );
							
							if( $db_results ) {
							
								unset( $i );

								// save the result as choices

								foreach( (array)$db_results as $row ) {

									$parent = null;
									
									foreach( $row as $item ) {

										$item = self::sanitize_choice_value( $item );

										if( $parent === null )
											$parent = &$choices;
					
										if( ! isset( $parent[ $item ] ) ) {
						
											$parent[ $item ] = array(
												'text'	   => $item,
												'value'	  => $item,
												'isSelected' => false,
												'choices'	=> array()
											);
										}

										$parent = &$parent[ $item ]['choices'];

									}

								}
								
								self::array_values_recursive( $choices );		
								
								$form[ 'fields' ][ $key ][ 'choices' ] = $choices;

								
							}

							// we need to save the form data
								
							GFAPI::update_form( $form );
								
							break;


						/**
						 * for pricing fields we need two or three values
						 */

						case 'option':
						case 'product':
						case 'shipping':

							$db_results = $wpdb->get_results( $db_query, ARRAY_N );
							if( $db_results ) {

								foreach( (array)$db_results as $value ) {
		
									if( count( $value ) > 2 )
										$choices[] = array( 'value' => $value[ 0 ], 'text' => $value[ 1 ], 'price' => floatval( $value[ 2 ] ) );	
									else
										$choices[] = array( 'value' => $value[ 0 ], 'text' => $value[ 0 ], 'price' => $value[ 1 ] );	

								}
								
								$form[ 'fields' ][ $key ][ 'choices' ] = $choices;
										
							}
							break;

							
						/**
						 * by default we only deliver one or two values
						 */

						default:

							$db_results = $wpdb->get_results( $db_query, ARRAY_N );
							if( $db_results ) {

								foreach( (array)$db_results as $value ) {
		
									if( count( $value ) > 1 )
										$choices[] = array( 'value' => $value[ 0 ], 'text' => $value[ 1 ] );	
									else
										$choices[] = array( 'value' => $value[ 0 ], 'text' => $value[ 0 ] );	

								}
										
								$form[ 'fields' ][ $key ][ 'choices' ] = $choices;

							}
							break;
									
					}

				}
				
				
			}
			
			return( $form );
			
			
		}


		public static function sanitize_choice_value( $value ) 
		{
		
			$allowed_protocols = wp_allowed_protocols();
			$value = wp_kses_no_null( $value, array( 'slash_zero' => 'keep' ) );
			$value = wp_kses_hook( $value, 'post', $allowed_protocols );
			$value = wp_kses_split( $value, 'post', $allowed_protocols );
			
			return( $value );
	
		}
		
		
		public static function array_values_recursive( &$choices, $prop = 'choices' ) 
		{

			$choices = array_values( $choices );

			for( $i = 0; $i <= count( $choices ); $i++ ) {
			
				if( ! empty( $choices[ $i ][ $prop ] ) ) 
					$choices[ $i ][ $prop ] = self::array_values_recursive( $choices[ $i ][ $prop ], $prop );
			}

			return( $choices );
			
		}
			
		
				
		/**
		 * lookup the field value against a WPDB database table
		 *
		 * @param array  $result	the result array
		 * @param string $value	 the value of the field
		 * @param object $form	  the form object
		 * @param object $field	 the field object
		 *
		 * @return array
		 */

		function spgfwpdb_gform_field_validation( $result, $value, $form, $field )
		{

					
			/**
			 * if there isn't a look-up query, return with the given result
			 */

			if( $field[ 'spgfwpdb_lookup' ] ) {
					
				/**
				 * if the field isn't valid, we doesn't need to perform any additional checks
				 */

				if( $result[ 'is_valid' ] ) {
			
					/**
					 * if the field is empty, we doesn't need to perform a database look-up
					 */

					if( $value ) {
					
						/** 
						 * build the query and lookup the database
						 */

						global $wpdb;
						
						if( $field[ 'type' ] == 'textarea' ) {
									
							/**
							 * do a look-up for every line of the textarea
							 */

							$values = explode( '<br />', nl2br( $value ) );
							foreach( (array)$values AS $value ) {

								$db_query = str_replace( '{field}', '%s', $field[ 'spgfwpdb_lookup' ] );
						
								$db_query = GFCommon::replace_variables( $db_query, $form, "" );

								$db_result = $wpdb->get_row( $wpdb->prepare( $db_query, array( trim( $value ) ) ), ARRAY_A );
								if( !$db_result ) {
						
									/**
									 * value not found at the database
									 * return an error
									 */

									$result[ 'message' ] .= $value . ' : ' . __( "Could not look-up this value at the database", 'spgfwpdb' ) . '<br />';
									$result[ 'is_valid' ] = false;
										 
								}
										
							}
									
						} else 	{
								
							/**
							 * all other fields only have single values
							 */

							$db_query = str_replace( '{field}', '%s', $field[ 'spgfwpdb_lookup' ] );
						
							$db_query = GFCommon::replace_variables( $db_query, $form, "" );
		
							$db_result = $wpdb->get_row( $wpdb->prepare( $db_query, array( trim( $value ) ) ), ARRAY_A );
							if( !$db_result ) {
						
								/**
								 * value not found at the database
								 * return an error
								 */

								$result[ 'message' ] = $value . ' : ' . __( "Could not look-up this value at the database", 'spgfwpdb' ) . '<br />';
								$result[ 'is_valid' ] = false;
										 
							}
					
						}
								
					}
							
				}
						
			}

					
			return( $result );
					
					
		}


		/**
		 * add a selectbox with all fieldnames to the advanced settings
		 *
		 * @param int $position the position of the setting
		 * @param int $form_id  the id of the form
		 *
		 * @return
		 */

		function spgfwpdb_gform_field_advanced_settings( $position, $form_id )
		{
	
	
			/**
			 * display the advanced-field-settings at the end
			 */

			if( $position == -1 ) {


				/**
				 * textarea to setup a MySQL Query to fill the choices
				 */

				?>
				<li class="spgfwpdb_choices_setting field_setting">
					<label for="spgfwpdb_choices">
						<?php _e( "MySQL-Query to fill the choices", 'spgfwpdb' ); ?>
					</label>
					<textarea id="spgfwpdb_choices_value" class="fieldwidth-3 fieldheight-2" onkeyup="SetFieldProperty('spgfwpdb_choices', jQuery(this).val() );"></textarea>
				</li>
				<?php
					

				/**
				 * textarea to setup a MySQL Query to look-up the field against a WPDB-table
				 */

				?>
				<li class="spgfwpdb_lookup_setting field_setting">
					<label for="spgfwpdb_lookup">
						<?php _e( "MySQL-Query to look-up your field", 'spgfwpdb' ); ?>
					</label>
					<textarea id="spgfwpdb_lookup_value" class="fieldwidth-3 fieldheight-2" onkeyup="SetFieldProperty('spgfwpdb_lookup', jQuery(this).val() );"></textarea>
				</li>
				<?php

						
			}


		}

			

		/**
		 * reload the saved entry after saving
		 *
		 * @param object $lead  the current entry
		 * @param object $form  the current form
		 *
		 * @return array
		 */
		 
		function spgfwpdb_gform_entry_post_save( $lead, $form )
		{
		
		
			$lead = GFAPI::get_entry( $lead[ 'id' ] );
			return( $lead );
		
		
		}
			
			
		/**
		 * support the new advanced setting at the JS
		 *
		 * @param
		 *
		 * @return
		 */

		function spgfwpdb_gform_editor_js()
		{
	
	
			?>
			<script type='text/javascript'>
					
				fieldSettings["select"] += ", .spgfwpdb_choices_setting";
				fieldSettings["multiselect"] += ", .spgfwpdb_choices_setting";
				fieldSettings["checkbox"] += ", .spgfwpdb_choices_setting";
				fieldSettings["radio"] += ", .spgfwpdb_choices_setting";
				fieldSettings["list"] += ", .spgfwpdb_choices_setting";
				fieldSettings["product"] += ", .spgfwpdb_choices_setting";
				fieldSettings["option"] += ", .spgfwpdb_choices_setting";
				fieldSettings["shipping"] += ", .spgfwpdb_choices_setting";
				fieldSettings["chainedselect"] += ", .spgfwpdb_choices_setting";
				jQuery(document).bind("gform_load_field_settings", function(event, field, form) {
					jQuery("#spgfwpdb_choices_value").val(field.spgfwpdb_choices);
				});
						
				fieldSettings["text"] += ", .spgfwpdb_lookup_setting";
				fieldSettings["number"] += ", .spgfwpdb_lookup_setting";
				fieldSettings["textarea"] += ", .spgfwpdb_lookup_setting";
				jQuery(document).bind("gform_load_field_settings", function(event, field, form) {
					jQuery("#spgfwpdb_lookup_value").val(field.spgfwpdb_lookup);
				});

			</script>
			<?php
				
				
		}
			
			


		/**
		 * --------------------------------------------------------------------------------
		 * functions to retrieve information from the database
		 * --------------------------------------------------------------------------------
		 */



		/**
		 * get an array of valid MYSQL table names fom the current WPDB database
		 *
		 * @param
		 *
		 * @return array
		 */

		private function spgfwpdb_get_table_names()
		{


			global $wpdb;
			
			$wp_tables_array = array(
					'commentmeta',
					'comments',
					'gf_addon_feed',
					'links',
					'options',
					'postmeta',
					'posts',
					'rg_form',
					'rg_form_meta',
					'rg_form_view',
					'rg_incomplete_submissions',
					'rg_lead',
					'rg_lead_detail',
					'rg_lead_detail_long',
					'rg_lead_meta',
					'rg_lead_notes',
					'termmeta',
					'terms',
					'term_relationships',
					'term_taxonomy',
					'usermeta',
					'users'
					);
					
			$wp_multisite_tables_array = array(
					'blogs',
					'blog_versions',
					'registration_log',
					'signups',
					'site',
					'sitemeta',
					'usermeta',
					'users'					
					);
					
				
				
			/**
			 * build a query to retrieve all table-names of the current database
			 */

			$query = "SHOW TABLES FROM `" . DB_NAME . "`"; 
			$tables = $wpdb->get_results( $query, ARRAY_N );
				
			
			/**
			 * define an array with unneeded Tables (all WP, WP Multisite and GF tables)
			 * comment the next lines if you need to include this tables
			 */

			$noTables = array();

			if( is_multisite() ) {
			
				
				/**
				 * loop thru the sites to hide the WP and GF tables
				 */
					
				$sites = get_sites();
				foreach( $sites AS $site ) {
				

					foreach( $wp_tables_array AS $table_name )
						$noTables[] = $wpdb->base_prefix . $site->blog_id . '_' . $table_name;
					
				}					
			
				foreach( $wp_multisite_tables_array AS $table_name )
					$noTables[] = $wpdb->base_prefix . $table_name;

				foreach( $wp_tables_array AS $table_name )
					$noTables[] = $wpdb->base_prefix . $table_name;


			
			} else 	{
			
				foreach( $wp_tables_array AS $table_name )
					$noTables[] = $wpdb->prefix . $table_name;
			
			
			}


			/**
			 * build the return array
			 */

			foreach( $tables as $table ) {
				
				if( !in_array( $table[0], $noTables ) )
					$choices[] = array( 'label' => $table[0], 'value' => $table[0] );
						
			}


			return( $choices );


		}



		/**
		 * get an array of valid MYSQL fields from the selected table
		 *
		 * @param string $tableName name of the selected table
		 *
		 * @return array
		 */

		private function spgfwpdb_get_field_names( $tableName )
		{


			global $wpdb;


			/**
			 * retrieve the field-names and build the options
			 */

			$db_results = $wpdb->get_results( "SHOW COLUMNS FROM {$tableName};", ARRAY_N );

			$fieldNames[] = array(
				'value'		 => '',
				'label'		 => '',
			);

			foreach( $db_results as $result )
				$fieldNames[] = array(
					'value'		 => $result[0],
					'label'		 => esc_html__( $result[0], 'spgfwpdb' ),
				);


			$fieldNames = array(
				'label'   => esc_html__( 'Table field names', 'spgfwpdb' ),
				'choices' => $fieldNames
			);


			$choices = array();
			$choices[] = $fieldNames;

			return( $choices );


		}
	
		
	
		/**
		 * get an array of valid MYSQL primary keys of the selected table
		 *
		 * @param string $tableName name of the selected table
		 *
		 * @return array
		 */

		private function spgfwpdb_get_primary_keys( $tableName )
		{


			global $wpdb;


			/**
			 * retrieve the field-names and build the options
			 */

			$db_results = $wpdb->get_results( "SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY'", ARRAY_N );

			$fieldNames[] = array(
				'value'		 => '',
				'label'		 => '',
			);

			foreach( $db_results as $result ) {
			
				$fieldNames[] = array(
					'value'		 => $result[4],
					'label'		 => esc_html__( $result[4], 'spgfwpdb' ),
				);
				
			}


			$fieldNames = array(
				'label'   => esc_html__( 'Primary Key Name', 'spgfwpdb' ),
				'choices' => $fieldNames
			);


			$choices = array();
			$choices[] = $fieldNames;

			return( $choices );


		}



		/**
		 * retrieve the MySQL column information about a field
		 *
		 * @param string $table name of the selected table
		 * @param string $field name of the selected field
		 *
		 * @return string
		 */

		function spgfwpdb_get_field_type( $table, $field )
		{


			global $wpdb;
			
			$db_results = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}` LIKE '{$field}';", ARRAY_N );
			if( $db_results ) {

				$pos = strpos( $db_results[ 0 ][ 1 ], '(' );
				if( $pos === false ) 
					$pos = strlen( $db_results[ 0 ][ 1 ] );
							
				$fieldType = substr( $db_results[ 0 ][ 1 ], 0, $pos );

				switch( $fieldType ) {
						
					case 'float':
					case 'double':
					case 'decimal':
					case 'numeric':
						return( '%f' );
						break;
								
					case 'int':
					case 'bigint':
					case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'integer':
						return( '%d' );
						break;
						
					case 'char':
						return( '%c' );
						break;
								
					default:
						return( '%s' );
						break;
								
				}
					
			}

			
		}
			
			

	}
