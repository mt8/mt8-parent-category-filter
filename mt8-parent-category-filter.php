<?php
/*
	Plugin Name: MT8 Parent Category Filter
	Plugin URI: https://github.com/mt8/mt8-parent-category-filter
	Description: Can filter by the top-level parent category in category screen.
	Author: mt8.biz
	Version: 1.0
	Author URI: http://mt8.biz
	Domain Path: /languages
	Text Domain: mt8-parent-category-filter
*/	

	$mt8_pcf = new Mt8_Parent_Category_Filter();
	$mt8_pcf->register_hooks();

	class Mt8_Parent_Category_Filter {
		
		const TEXT_DOMAIN = 'mt8-parent-category-filter';
		
		public $allow_taxonomies = array();
		
		public $taxonomy = '';
		
		public function __construct() {
			
			$this->allow_taxonomies = array( 'category' );
			
		}
		
		public function is_target_taxonomy( $taxonomy ) {
			
			return in_array( $taxonomy, apply_filters( 'mt8-parent-category-filter-allow-taxonomies', $this->allow_taxonomies ) );
			
		}
		
		public function register_hooks() {
			
			add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
			add_action( 'load-edit-tags.php', array( &$this, 'edit_tags_php' ) );
			
		}
		
		public function plugins_loaded() {
			
			load_plugin_textdomain( self::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ).'/languages' );
			
		}
		
		public function edit_tags_php() {

			$this->taxonomy = ( isset( $_REQUEST['taxonomy'] ) ) ? $_REQUEST['taxonomy'] : '';
			if ( '' === $this->taxonomy ) {
				return;
			}
			if ( ! $this->is_target_taxonomy( $this->taxonomy ) ) {
				return;
			}
			add_filter( 'get_terms_args', array( &$this, 'get_terms_args' ), 10, 2 );
			add_action( "after-{$this->taxonomy}-table", array( &$this, 'after_taxonomy_table' ) );
			
		}
		
		public function get_terms_args( $args, $taxonomies ) {

			if ( '' === $this->parent_search() ) {
				return $args;
			}
			
			/**
			 * ignore when "wp_dropdown_categories()" 
			 *   in "/[wp-admin]/edit-tags.php"
			 */
			if ( array_key_exists( 'selected', $args ) && $this->call_from( 'wp_dropdown_categories' ) ) {
				return $args;
			}

			/**
			 * set 'parent' when "wp_count_terms()"
			 *   in "WP_Terms_List_Table::prepare_items()"
			 */
			if ( 'count' === $args[ 'fields' ] && $this->call_from( 'prepare_items' ) ) {
				$args['parent'] = $this->parent_search();
			}

			/**
			 * set "WP_Terms_List_Table::callback_args['orderby']" value ' '(temporary)
			 *   because pass to condition "if ( is_taxonomy_hierarchical( $taxonomy ) && ! isset( $args['orderby'] ) )"
			 *     in "WP_Terms_List_Table::display_rows_or_placeholder()"
			 *     via "WP_Terms_List_Table::display()"
			 */
			if ( $this->call_from( 'prepare_items' ) ) {
				global $wp_list_table;
				if ( $wp_list_table ) {
					$wp_list_table->callback_args['orderby'] = ' ';
				}
			}

			/**
			 * set 'child_of' when "get_terms" 
			 *   in "WP_Terms_List_Table::display_rows_or_placeholder()"
			 *   via "WP_Terms_List_Table::display()"
			 */
			if ( 'all' === $args['fields'] && $this->call_from( 'display_rows_or_placeholder' ) ) {
				$args['child_of'] = $this->parent_search();
			}
			
			return $args;

		}
		
		public function after_taxonomy_table( $taxonomy ) {
			
			global $post_type;
			?>
			<div id="parent_search_wrap" style="display: none;">
				<form id="parent_search_form" class="search-form" method="get">
				<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
				<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />
				<label for="parent_search"><?php _e( 'Filter by top parent', self::TEXT_DOMAIN ) ?></label>
				<?php
				$dropdown_args = array(
					'depth'            => 2,					
					'hide_empty'       => 0,
					'hide_if_empty'    => true,
					'taxonomy'         => $taxonomy,
					'name'             => 'parent_search',
					'orderby'          => 'name',
					'hierarchical'     => true,
					'show_option_none' => __( 'All' ),
					'selected'         => $this->parent_search(),					
				);
				wp_dropdown_categories( $dropdown_args );
				
				?>
				</form>
			</div>

			<?php
				wp_enqueue_script(
					'mt8-parent-category-filter',
					plugins_url( '/assets/js/mt8-parent-category-filter.js' , __FILE__ ),
					array('jquery'),
					false,
					true
				);
		}
		
		public function call_from( $function ) {
			
			$db = debug_backtrace();
			if ( ! is_array( $db ) ) {
				return false;
			}
			foreach ( $db as $call_from  ) {
				if ( ! is_array( $call_from ) ) {
					continue;
				}
				if ( ! array_key_exists( 'function', $call_from ) ) {
					continue;
				}
				if ( $function === $call_from['function'] ) {
					return true;
				}
			}
			return false;
			
		}
		
		public function parent_search() {

			if ( ! isset( $_REQUEST['parent_search'] ) ) {
				return '';
			}

			if ( (int)$_REQUEST['parent_search'] <= 0 ) {
				return '';
			}

			$tax = get_term( (int)$_REQUEST['parent_search'], $this->taxonomy );

			$chilren = get_term_children( $tax->term_id, $tax->taxonomy );

			if ( ! $chilren  ) {
				return '';
			} else {
				return (int)$_REQUEST['parent_search'];
			}

		}
		
	}
	





