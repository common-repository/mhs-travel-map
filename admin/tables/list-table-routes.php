<?php

/* == NOTICE ===================================================================
 * Please do not alter this file. Instead: make a copy of the entire plugin, 
 * rename it, and work inside the copy. If you modify this plugin directly and 
 * an update is released, your changes will be lost!
 * ========================================================================== */



/* * ************************* LOAD THE BASE CLASS *******************************
 * ******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary. In this tutorial, we are
 * going to use the WP_List_Table class directly from WordPress core.
 *
 * IMPORTANT:
 * Please note that the WP_List_Table class technically isn't an official API,
 * and it could change at some point in the distant future. Should that happen,
 * I will update this plugin with the most current techniques for your reference
 * immediately.
 *
 * If you are really worried about future compatibility, you can make a copy of
 * the WP_List_Table class (file path is shown just below) to use and distribute
 * with your plugins. If you do that, just remember to change the name of the
 * class to avoid conflicts with core.
 *
 * Since I will be keeping this tutorial up-to-date for the foreseeable future,
 * I am going to work with the copy of the class provided in WordPress core.
 */
if ( !class_exists( 'WP_List_Table_My' ) ) {
	require_once( MHS_TM_ABSPATH . '/admin/tables/class-wp-list-table-my.php' );
}




/* * ************************ CREATE A PACKAGE CLASS *****************************
 * ******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be movies.
 */

class List_Table_Routes extends WP_List_Table_My {

	/**	 * ***********************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We 
	 * use the parent reference to set some default configs.
	 * ************************************************************************* */
	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
			'singular'	 => 'Route', //singular name of the listed records
			'plural'	 => 'Routes', //plural name of the listed records
			'ajax'		 => false		//does this table support ajax?
		) );
	}

	/**	 * ***********************************************************************
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title() 
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as 
	 * possible. 
	 * 
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 * 
	 * For more detailed insight into how columns are handled, take a look at 
	 * WP_List_Table::single_row_columns()
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 * ************************************************************************ */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'short_code':
			case 'date':
			case 'name':
			case 'country':
			case 'class':
			case 'update':
			case 'route_start_date':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	function no_items() {
		_e( 'No routes found!' );
	}

	/**	 * ***********************************************************************
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named 
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 * 
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 * 
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 * ************************************************************************ */
	function column_name( $item ) {
		$delete_nonce    = wp_create_nonce( 'mhs_tm_delete_route_' . absint( $item['id'] ) );
		$duplicate_nonce = wp_create_nonce( 'mhs_tm_duplicate_route_' . absint( $item['id'] ) );

		//Build row actions
		$actions = array(
			'edit'		=> sprintf( '<a href="?page=%s&todo=edit&id=%s">Edit</a>', esc_attr( $_REQUEST['page'] ), absint( $item['id'] ) ),
			'duplicate'	=> sprintf( '<a href="?page=%s&action=duplicate&id=%s&_wpnonce=%s">Duplicate</a>', esc_attr( $_REQUEST['page'] ), absint( $item['id'] ), $duplicate_nonce ),
			'delete'	=> sprintf( '<a onclick="if ( confirm(\'Really delete %s?\') ) { return true; } return false;"' .
                            'href="?page=%s&action=delete&id=%s&_wpnonce=%s">Delete</a>', esc_html( $item['name'] ), esc_attr( $_REQUEST['page'] ), absint( $item['id'] ), $delete_nonce ),
			'mhs_tm_info'	 => sprintf( '<a id="mhs_tm_info_%s" href="javascript:void(0);">Info</a>', absint( $item['id'] ) ),
		);

		//Return the title contents
		return sprintf( '%1$s %2$s',
		/* $1%s */ esc_html( $item['name'] ),
		/* $2%s */ $this->row_actions( $actions )
		);
	}

	/**	 * ***********************************************************************
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 * ************************************************************************ */
	function column_cb( $item ) {
		return sprintf(
		'<input type="checkbox" name="route_id[]" value="%s" />', absint( $item['id'] )
		// '<input type="checkbox" name="%1$s[]" value="%2$s" />',
		// /*$1%s*/ $this->_args['singular'],  //$this->_args['singular'] Let's simply repurpose the table's singular label ("movie")
		// /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
		);
	}

	/**	 * ***********************************************************************
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value 
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 * 
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 * ************************************************************************ */
	function get_columns() {

		$columns = array(
			'cb'	           => '<input type="checkbox" />', //Render a checkbox instead of text
			'name'	           => 'Name',
			'country'          => 'Country',
			'class'            => 'Tranport class',
			'route_start_date' => 'Start date',
			'update'           => 'Last updated',
			'date'	           => 'Create date',
			'short_code'	   => 'Shortcode and ID'
		);
		return $columns;
	}

	/**	 * ***********************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
	 * you will need to register it here. This should return an array where the 
	 * key is the column that needs to be sortable, and the value is db column to 
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 * 
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 * 
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 * ************************************************************************ */
	function get_sortable_columns() {

		$sortable_columns = array(
			'short_code'	   => array( 'short_code', false ),
			'date'	           => array( 'date', false ), //true means it's already sorted 
			'update'           => array( 'update', false ),
			'route_start_date' => array( 'route_start_date', false ),
			'country'          => array( 'country', false ),
			'class'            => array( 'class', false ),
			'name'	           => array( 'name', false )
		);
		return $sortable_columns;
	}

	function get_primary_column_name() {
		$default_primary_column_name = 'name';

		return $default_primary_column_name;
	}

	function extra_tablenav( $which ) {
		if ( 'top' == $which ) {
			
		}
		if ( 'bottom' == $which ) {
			//The code that goes after the table is there
		}
	}

	/**	 * ***********************************************************************
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 * 
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 * 
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 * 
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * ************************************************************************ */
	function get_bulk_actions() {

		$actions = array(
			'delete_bulk'		=> 'Delete',
			'duplicate_bulk'	=> 'Duplictae'
		);
		return $actions;
	}

	/**	 * ***********************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 * 
	 * @see $this->prepare_items()
	 * ************************************************************************ */
	function process_bulk_action() {
		global $wpdb, $MHS_TM_Admin, $MHS_TM_Admin_Utilities, $MHS_TM_Maps, $MHS_TM_Admin_Routes;
		$table_name = $wpdb->prefix . 'mhs_tm_routes';

                if ( isset( $_GET['_wpnonce'] ) ) {
                    $id         = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : null;
                    $route_ids  = isset( $_GET['route_id'] ) ? $MHS_TM_Admin_Utilities->sanitize_id_array( $_GET['route_id'] ) : null;
                    $nonce      = isset( $_GET['_wpnonce'] ) ? esc_attr( $_GET['_wpnonce'] ) : null;
                } else { 
                    $id         = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : null;
                    $route_ids  = isset( $_POST['route_id'] ) ? $MHS_TM_Admin_Utilities->sanitize_id_array( $_POST['route_id'] ) : null;
                    $nonce      = isset( $_POST['_wpnonce'] ) ? esc_attr( $_POST['_wpnonce'] ) : null;
                }

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			if ( is_numeric( $id ) && wp_verify_nonce( $nonce, 'mhs_tm_delete_route_' . $id ) ) {
				$wpdb->update(
				$table_name, array(
					'active' => 0
				), array( 'id' => $id ), array( '%s' ), array( '%d' )
				);
				$messages[] = array(
					'type'		 => 'updated',
					'message'	 => __( 'Route have been deleted!', 'mhs_tm' )
				);
				echo $MHS_TM_Admin->convert_messages( $messages );
			} else {
				$messages[] = array(
					'type'		 => 'error',
					'message'	 => __( 'Something went wrong!', 'mhs_tm' )
				);
				echo $MHS_TM_Admin->convert_messages( $messages );
			}
		}

		if ( 'delete_bulk' === $this->current_action() ) {
			if ( wp_is_numeric_array( $route_ids ) && wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				foreach ( $route_ids as $route_id ) {
					if ( is_numeric( $route_id ) ) {
						$wpdb->update(
						$table_name, array(
							'active' => 0
						), array( 'id' => absint( $route_id ) ), array( '%s' ), array( '%d' )
						);
					}
				}
				$messages[] = array(
					'type'		 => 'updated',
					'message'	 => __( 'Routes have been deleted!', 'mhs_tm' )
				);
				echo $MHS_TM_Admin->convert_messages( $messages );
			} else {
				$messages[] = array(
					'type'		 => 'error',
					'message'	 => __( 'Something went wrong!', 'mhs_tm' )
				);
				echo $MHS_TM_Admin->convert_messages( $messages );
			}
		}
		
		if ( 'duplicate' === $this->current_action() ) {
			if ( is_numeric( $id ) && wp_verify_nonce( $nonce, 'mhs_tm_duplicate_route_' . $id ) ) {
				$coordinates = array();
				$coordinates = $MHS_TM_Maps->get_coordinates( $id, 'route' );
				
				$coordinates[0]['options']['name']		= $coordinates[0]['options']['name'] . ' (Copy)';

				$options = wp_json_encode( $coordinates[0]['options'] );
				$coordinates = wp_json_encode( $coordinates[0]['coordinates'] );
				
				// insert duplicated route
				$wpdb->insert(
				$wpdb->prefix . 'mhs_tm_routes', array(
					'active'		 => 1,
					'options'		 => $options,
					'coordinates'	 => $coordinates,
					'create_date'	 => date( 'Y-m-d H:i:s' )
				), array( '%d', '%s', '%s', '%s' )
				);
				
				$messages[] = array(
					'type'		 => 'updated',
					'message'	 => __( 'Route have been duplicated!', 'mhs_tm' )
				);

				$routes = $wpdb->get_results(
					'SELECT * FROM ' . $wpdb->prefix . 'mhs_tm_routes' .
					' WHERE active = 1 order by updated DESC', ARRAY_A
				);

				echo $MHS_TM_Admin->convert_messages( $messages );
			} else {
				$messages[] = array(
					'type'		 => 'error',
					'message'	 => __( 'Something went wrong!', 'mhs_tm' )
				);
				echo $MHS_TM_Admin->convert_messages( $messages );
			}
		}

		if ( 'duplicate_bulk' === $this->current_action() ) {
			if ( wp_is_numeric_array( $route_ids ) && wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				foreach ( $route_ids as $route_id ) {
					if ( is_numeric( $route_id ) ) {
						$coordinates = array();
						$coordinates = $MHS_TM_Maps->get_coordinates( $route_id, 'route' );

						$coordinates[0]['options']['name']		= $coordinates[0]['options']['name'] . ' (Copy)';

						$options = wp_json_encode( $coordinates[0]['options'] );
						$coordinates = wp_json_encode( $coordinates[0]['coordinates'] );
						
						// insert duplicated route
						$wpdb->insert(
						$wpdb->prefix . 'mhs_tm_routes', array(
							'active'		 => 1,
							'options'		 => $options,
							'coordinates'	 => $coordinates,
							'create_date'	 => date( 'Y-m-d H:i:s' )
						), array( '%d', '%s', '%s', '%s' )
						);
					}
				}
				$messages[] = array(
					'type'		 => 'updated',
					'message'	 => __( 'Routes have been duplicated!', 'mhs_tm' )
				);
				echo $MHS_TM_Admin->convert_messages( $messages );

				$routes = $wpdb->get_results(
					'SELECT * FROM ' . $wpdb->prefix . 'mhs_tm_routes' .
					' WHERE active = 1 order by updated DESC', ARRAY_A
				);
				
				wp_localize_script( 'mhs_tm_map', 'mhs_tm_app_vars_0', array(
					'coordinates'		 => $routes,
					'coord_center_lat'	 => 54.023884,
					'coord_center_lng'	 => 9.377068,
					'auto_load'			 => false,
					'map_id'			 => 0,
					'plugin_dir'	     => MHS_TM_RELPATH,
					'ajax_url'			 => admin_url( 'admin-ajax.php' ),
				) );
			} else {
				$messages[] = array(
					'type'		 => 'error',
					'message'	 => __( 'Something went wrong!', 'mhs_tm' )
				);
				echo $MHS_TM_Admin->convert_messages( $messages );
			}
		}
	}

	/**	 * ***********************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 * 
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 * ************************************************************************ */
	function prepare_items() {
		global $wpdb, $MHS_TM_Maps, $MHS_TM_Admin_Utilities; 
		$table_name = $wpdb->prefix . 'mhs_tm_routes';

		/**
		 * First, lets decide how many records per page to show
		 */
                $user = get_current_user_id();
                $screen = get_current_screen();
                $option = $screen->get_option('per_page', 'option');

                $per_page = get_user_meta($user, $option, true);

                if ( empty ( $per_page) || $per_page < 1 ) {

                    $per_page = $screen->get_option( 'per_page', 'default' );

                }


		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns	 = $this->get_columns();
		$hidden		 = array();
		$sortable	 = $this->get_sortable_columns();
		$primary	 = $this->get_primary_column_name();


		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column 
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
                $this->_column_headers = $this->get_column_info();

		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();


		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example 
		 * package slightly different than one you might build on your own. In 
		 * this example, we'll be using array manipulation to sort and paginate 
		 * our data. In a real-world implementation, you will probably want to 
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		$routes = $wpdb->get_results(
                    'SELECT * FROM ' . $table_name .
                    ' WHERE active = 1 order by updated DESC', ARRAY_A
		);

		$id = 0;
		$data = [];
		foreach ( $routes as $route ) {

			$date			 = $route['create_date'];
			$update			 = $route['updated'];
			$route_options		 = array();
			$route_options		 = $MHS_TM_Maps->sanitize_coordinate_option_array( json_decode( $route['options'], true ) );
			$route_coordinates	 = array();
			$route_coordinates	 = $MHS_TM_Maps->sanitize_coordinates_array( json_decode( $route['coordinates'], true ) );
                        
			If( $route_coordinates == null ) {
				$route_coordinates[0] = [];
				$route_coordinates[0]['starttime'] = '0000000000';
			}
			date_default_timezone_set( 'Europe/London' );

                        $data[ $id ]['country']	         = $route_coordinates[0]['country'];
			$data[ $id ]['date']	         = $date;
			$data[ $id ]['update']           = $update;
			$data[ $id ]['name']	         = $route_options['name'];
                        $data[ $id ]['class']            = $MHS_TM_Admin_Utilities->get_route_class_name( $route_options['transport_class'] );
			$data[ $id ]['route_start_date'] = date( 'Y-m-d', $route_coordinates[0]['starttime'] );
			$data[ $id ]['id']	         = $route['id'];
			$data[ $id ]['short_code']	 = '[mhs-travel-map type=route map_id=' . $route['id'] . ']';
			$id				 = $id + 1;
		}

		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 * 
		 * In a real-world situation involving a database, you would probably want 
		 * to handle sorting by passing the 'orderby' and 'order' values directly 
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 */
		function usort_reorder( $a, $b ) {
			$orderby_options = ['update', 'date', 'name', 'id', 'route_start_date', 'short_code', 'country', 'class'];
			$order_options   = ['DESC', 'ASC', 'desc', 'asc'];
			
			if ( isset( $_GET['orderby'], $_GET['order'] ) && in_array( $_GET['orderby'], $orderby_options ) && in_array( $_GET['order'], $order_options ) ) {
				$orderby = esc_attr( $_GET['orderby'] );
				$order   = esc_attr( $_GET['order'] );
			} else {
				$orderby = 'update';
				$order   = 'desc';
			}
			
			$result	 = strcmp( $a[ $orderby ], $b[ $orderby ] ); //Determine sort order
			return ( $order === 'asc' ) ? $result : -$result; //Send final sort direction to usort
		}

		if ( isset( $data ) && NULL != $data ) {
			usort( $data, 'usort_reorder' );
		}

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently 
		 * looking at. We'll need this later, so you should always include it in 
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array. 
		 * In real-world use, this would be the total number of items in your database, 
		 * without filtering. We'll need this later, so you should always include it 
		 * in your own package classes.
		 */
		$total_items = count( $data );


		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to 
		 */
		if ( NULL != $data ) {
			$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}




		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;


		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items'	 => $total_items, //WE have to calculate the total number of items
			'per_page'		 => $per_page, //WE have to determine how many items to show on a page
			'total_pages'	 => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
		) );
	}

}

//class
?>