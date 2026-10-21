<?php
/**
 * Import products from TradeGecko CSV file
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_TradeGecko_Import_Products extends WP_Importer {

	/**
	 * File attachement ID
	 * @var int
	 */
	private $file_id;

	/**
	 * The URL of the file on the server
	 *
	 * @var string
	 */
	private $import_url;

	/**
	 * The import page name
	 * @var string
	 */
	private $page = 'wc_tradegecko_product_importer';

	/**
	 * CSV Delimiter
	 * @var string
	 */
	private $delimiter = ',';

	/**
	 * The import log contents
	 * @var array
	 */
	private $log = array();

	/**
	 * The ID of the variations parent
	 * @var int
	 */
	private $parent_id;

	/**
	 * The count of the variations to add to a variable product
	 * @var int
	 */
	private $variation_count;

	/**
	 * How many products are imported
	 * @var int
	 */
	private $imported = 0;

	/**
	 * How many variations were imported
	 * @var type
	 */
	private $imported_variations = 0;

	/**
	 * How many products were skipped
	 * @var type
	 */
	private $skipped = 0;

	/**
	 * The attributes to add to the variations parent
	 * @var type
	 */
	private $parent_attributes = array();

	/**
	 * Is the last variation
	 * @var type
	 */
	private $is_addition_variation_end;

	/**
	 * The terms we want to add to a variation
	 * @var type
	 */
	private $terms;

	/**
	 * Check the action we are performing and display the appropriate form
	 */
	function dispatch() {
		$this->header();

		$action = empty( $_GET['action'] ) ? 'upload' : $_GET['action'];
		switch ( $action ) {
			case 'upload':
				$this->upload_file_form();

				break;
			case 'preview':
				check_admin_referer( 'import-upload' );

				$is_uploaded = $this->process_upload();

				if ( $is_uploaded ) {
					// After the file is uploaded, we will parse it and check all required fields
					// then if something is wrong we will notify the user and have him fix it.
					$this->file_preview();
				}

				break;
			case 'add-taxonomies':
				check_admin_referer( 'wc-tg-import-taxonomies' );

				echo '<h3>Importing Attributes</h3>';

				$this->import_taxonomies();
				break;
			case 'execute':
				check_admin_referer( 'wc-tg-import-products' );

				echo '<h3>Importing Products</h3>';

				echo '<div style="" class="import_container"><span class="importing"></span>'. __( 'Please wait while we import your products', WC_TradeGecko_Init::$text_domain ) .'</div>';

				$this->import();
				break;
		}

		$this->footer();
	}

	/**
	 * Output the Header/Title
	 */
	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __( 'WooCommerce TradeGecko Product Importer', WC_TradeGecko_Init::$text_domain ) . '</h2>';
	}

	/**
	 * Output the footer
	 */
	function footer() {
		echo "<script>jQuery( 'div.import_container' ).hide();</script>";
		echo '</div>';
	}

	/**
	 * Show the upload file form
	 */
	function upload_file_form() {
		echo '<div class="narrow">';
		echo '<p>' . __( 'Hi! Here you will be able to import your TradeGecko Products from the TradeGecko Export CSV file.', WC_TradeGecko_Init::$text_domain ) . '</p>';
		echo '<p>' . __( 'Please choose the CSV file you want to import, then click "Upload file".', WC_TradeGecko_Init::$text_domain ) . '</p>';

		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?><div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', WC_TradeGecko_Init::$text_domain ); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
		else :
			$action = esc_url( wp_nonce_url( 'admin.php?import='. $this->page .'&action=preview', 'import-upload' ) );
			?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo $action; ?>">
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="upload"><?php _e( 'Choose a file from your computer:', WC_TradeGecko_Init::$text_domain ); ?></label>
								<small>(<?php echo sprintf( __('Maximum size: %s', WC_TradeGecko_Init::$text_domain ), $size ); ?>)</small>
							</th>
							<td>
								<input type="file" id="upload" name="import" size="25" />
								<input type="hidden" name="action" value="save" />
								<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
							</td>
						</tr>
						<tr>
							<th>
								<label for="file_url"><?php _e( 'OR enter path to the file on your server:', WC_TradeGecko_Init::$text_domain ); ?></label>
							</th>
							<td>
								<?php echo ' ' . ABSPATH . ' '; ?><input type="text" id="import_url" name="import_url" size="25" />
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input id="submit" class="button" type="submit" value="<?php esc_attr( _e( 'Upload file', WC_TradeGecko_Init::$text_domain ) ); ?>" name="submit">
				</p>

			</form>
			<?php
		endif;

		echo '</div>';
	}

	/**
	 * Check the required data in the CSV file and show the form to import taxonomies.
	 */
	private function file_preview() {
		$action = esc_url( wp_nonce_url( 'admin.php?import='. $this->page .'&action=add-taxonomies', 'wc-tg-import-taxonomies' ) );
		?>
		<form action="<?php echo $action; ?>" method="post">
			<?php if ( '' != $this->file_id ) { ?>
					<input type="hidden" name="file_id" value="<?php echo $this->file_id; ?>" />
				<?php
					$file = get_attached_file( $this->file_id );

				} elseif ( '' != $this->import_url ) { ?>
					<input type="hidden" name="import_url" value="<?php echo $this->import_url; ?>" />
				<?php
					$file = ABSPATH . $this->import_url;
				} ?>
			<?php
			$parsed_csv = array();
			$missing_data = array();
			if ( false != $file ) {
				// Try giving us some time
				@set_time_limit(0);
				$parsed_csv = $this->parse_csv( $file );

				$missing_data = $this->check_all_required_data( $parsed_csv );

				if ( empty( $missing_data ) ) {
					?>
					<h3><?php _e( 'File uploaded successfully.', WC_TradeGecko_Init::$text_domain ); ?></h3>
					<p><strong><?php _e( 'IMPORTANT:', WC_TradeGecko_Init::$text_domain ); ?></strong> <?php _e( 'Please back up your database before you begin the product import.', WC_TradeGecko_Init::$text_domain ); ?> </p>
					<h4><?php _e( 'Additional Options', WC_TradeGecko_Init::$text_domain ); ?></h4>
					<p>
						<input type="checkbox" value="1" name="all_variable" id="all_variable" />
						<label for="dry_run"><?php _e( 'Create all products as variable products with variations', WC_TradeGecko_Init::$text_domain ) ?></label><br/>
						<span class="description"><?php _e( 'This option will create all imported products into variable products. Otherwise only the products with multiple variations will be created as variable, all other products will be created as simple products', WC_TradeGecko_Init::$text_domain ); ?></span>
					</p>
					<p class="submit">
						<?php _e( 'There are just two more steps. Please press the button to import all of your product options/attributes.', WC_TradeGecko_Init::$text_domain ); ?><br/>
						<input type="submit" class="button" value="<?php esc_attr( _e( 'Import Attributes', WC_TradeGecko_Init::$text_domain ) ); ?>" />
					</p>
				<?php
				} else {
				?>
					<h3><?php _e( 'Missing Required Data', WC_TradeGecko_Init::$text_domain ); ?></h3>
					<p><?php echo _e('The file uploaded is missing required data. Please correct the csv file and try again.', WC_TradeGecko_Init::$text_domain); ?></p>
					<?php if ( ! empty( $missing_data['headers'] ) ) { ?>
						<p><?php echo _e('Missing required columns:', WC_TradeGecko_Init::$text_domain); ?></p>
						<ul>
						<?php foreach( $missing_data['headers'] as $header ) { ?>
							<li><?php echo $header; ?></li>
						<?php } ?>
						</ul>
					<?php } ?>
					<?php if ( ! empty( $missing_data['rows'] ) ) { ?>
						<p><?php echo _e('Missing required rows:', WC_TradeGecko_Init::$text_domain); ?></p>
						<ul>
						<?php foreach( $missing_data['rows'] as $row ) { ?>
							<li><?php echo $row; ?></li>
						<?php } ?>
						</ul>
					<?php } ?>
				<?php
				}
			} else {
				?>
				<h3><?php _e( 'Sorry, could not open your file. Please go back and try again.', WC_TradeGecko_Init::$text_domain ); ?></h3>
				<?php
			}
			?>
		</form>
		<?php
	}

	/**
	 * Import Taxonomies and show the form to start product import
	 */
	private function import_taxonomies() {
		$action = esc_url( wp_nonce_url( 'admin.php?import='. $this->page .'&action=execute', 'wc-tg-import-products' ) );
		?>
		<form action="<?php echo $action; ?>" method="post">
			<?php if ( isset( $_POST['file_id'] ) && '' != $_POST['file_id'] ) { ?>
				<input type="hidden" name="file_id" value="<?php echo $_POST['file_id']; ?>" />
			<?php
				$file = get_attached_file( $_POST['file_id'] );

				} elseif ( isset( $_POST['import_url'] ) && '' != $_POST['import_url'] ) { ?>
				<input type="hidden" name="import_url" value="<?php echo $_POST['import_url']; ?>" />
			<?php
				$file = ABSPATH . $_POST['import_url'];
				} ?>
			<?php
			$parsed_csv = array();
			if ( false != $file ) {
				// Try giving us some time
				@set_time_limit(0);
				$parsed_csv = $this->parse_csv( $file );

				$this->add_all_product_attributes( $parsed_csv );

				$all_variable = isset( $_POST['all_variable'] ) && $_POST['all_variable'] ? $_POST['all_variable'] : 0;
				$count = count( $parsed_csv['data'] );

				if ( 0 == $all_variable ) {
					$time = wc_format_decimal( ( $count * 0.20 ) / 60, 2 );
				} else {
					$time = wc_format_decimal( ( $count * 0.30 ) / 60, 2 );
				}

				?>
				<h4><?php _e( 'Attributes Uploaded Successfully.', WC_TradeGecko_Init::$text_domain ); ?></h4>
				<p>
					<?php _e( 'Now all that is left is to import your products. Please press the <b>Import Products</b> button to import your products.', WC_TradeGecko_Init::$text_domain ); ?><br/>
					<?php echo sprintf( __( 'Products Import should take about %s minutes.', WC_TradeGecko_Init::$text_domain ), $time ); ?>
					<input type="hidden" value="<?php echo $all_variable; ?>" name="all_variable" id="all_variable" />
				</p>
				<p class="submit">
					<input type="submit" class="button" value="<?php esc_attr( _e( 'Import Products', WC_TradeGecko_Init::$text_domain ) ); ?>" />
				</p>
			<?php
			} else {
				?>
				<h3><?php _e( 'Sorry, could not open your file. Please go back and try again.', WC_TradeGecko_Init::$text_domain ); ?></h3>
				<?php
			}
			?>
		</form>
		<?php
	}

	/**
	 * Check for taxonomies to add to the DB and add them.
	 *
	 * @param array $parsed_csv The entire parsed CSV document
	 */
	private function add_all_product_attributes( $parsed_csv ) {
		foreach( $parsed_csv['data'] as $key => $row ) {
			$product_type = $this->get_row_product_type( $parsed_csv['data'], $row, $key );

			if ( ! empty( $row['option_1_label'] ) ) {
				$this->add_attribute( $row['option_1_label'] );
			}

			if ( ! empty( $row['option_2_label'] ) ) {
				$this->add_attribute( $row['option_2_label'] );
			}

			if ( ! empty( $row['option_3_label'] ) ) {
				$this->add_attribute( $row['option_3_label'] );
			}
		}

		// Add the check if we need to do this
		delete_transient('wc_attribute_taxonomies');
	}

	/**
	 * Add taxonomies to the DB
	 *
	 * @global object $wpdb
	 * @param type $label
	 */
	private function add_attribute( $label ) {
		global $wpdb;

		// Try to find the attribute by name
		$attribute_table = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_label = '%s'", $label ) );

		// If we don't have the attribute created
		if ( '' == $attribute_table ) {
			// Create the attribute
			$attribute = array(
				'attribute_label'   => stripslashes( $label ),
				'attribute_name'    => wc_sanitize_taxonomy_name( stripslashes( $label ) ),
				'attribute_type'    => 'select',
				'attribute_orderby' => 'menu_order',
			);

			$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
		}
	}

	/**
	 * Upload the file or check, if the file exists at the provided server path
	 *
	 * @return boolean
	 */
	private function process_upload() {
		if ( empty( $_POST['import_url'] ) ) {

			// Check the file extention
			if ( isset( $_FILES['import'] ) && ! empty( $_FILES['import'] ) ) {
				$file_ext = strtolower( pathinfo( $_FILES['import']['name'], PATHINFO_EXTENSION ) );

				if ( 'csv' != $file_ext ) {
					echo '<p>' . sprintf( __( 'Error: Please upload a CSV file. Uploaded file extention is: %s', WC_TradeGecko_Init::$text_domain ), $file_ext ) . '</p>';
					return false;
				}
			}

			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				echo '<p><strong>' . __( 'There was an error importing your file.', WC_TradeGecko_Init::$text_domain ) . '</strong><br />';
				echo esc_html( $file['error'] ) . '</p>';
				return false;
			}

			$this->file_id = (int) $file['id'];
		} else {
			$url = $_POST['import_url'];

			if ( file_exists( ABSPATH . $url ) ) {
				$this->import_url = esc_attr( $url );
			} else {
				echo '<p><strong>' . __( "Error: Could not locate the file on your server.", WC_TradeGecko_Init::$text_domain ) . '</strong></p>';
				return false;
			}
		}

		return true;
	}

	/**
	 * Check the CSV file for the required import data
	 *
	 * @param array $csv
	 * @return array
	 */
	function check_all_required_data( $csv ) {
		$missing_data = array();

		if ( ! isset( $csv['headers']['product_name'] ) ) {
			$missing_data['headers'][] = 'Product Name';
		}

		if ( ! isset( $csv['headers']['stock'] ) && ! isset( $csv['headers']['stock_on_hand'] ) ) {
			$missing_data['headers'][] = 'Stock';
		}

		if ( ! isset( $csv['headers']['variant_sku'] ) ) {
			$missing_data['headers'][] = 'Variant SKU';
		}

		// Basically we require the SKU to not be empty
		// Everything else could be empty.
		foreach ( $csv['data'] as $key => $row ) {
			if ( empty( $row['variant_sku'] ) ) {
				$missing_data['rows'][ $key ] = sprintf( __( 'Missing SKU, row: %s', WC_TradeGecko_Init::$text_domain ), $key );
			}
		}

		return $missing_data;
	}

	/**
	 * Open the csv file and put all its contents in an associative array
	 *
	 * @param type $file
	 * @return type
	 */
	function parse_csv( $file ) {
		// Set locale
		$enc = mb_detect_encoding( $file, 'UTF-8, ISO-8859-1', true );
		if ( $enc ) setlocale( LC_ALL, 'en_US.' . $enc );
		@ini_set( 'auto_detect_line_endings', true );

		// Parse $file
		$data = array();

		if ( false !== ( $handle = fopen( $file, "r" ) ) ) {

			$csv_headers = fgetcsv( $handle, 0, $this->delimiter );

			while ( false !== ( $line = fgetcsv( $handle, 0, "," ) ) ) {
				$row = array();

				foreach ( $csv_headers as $key => $heading ) {
					$key_heading = $this->get_key_heading( $heading );

					$row[ $key_heading ] = isset( $line[ $key ] ) ? $this->format_data_from_csv( $line[ $key ], $enc ) : '';

					$headers[ $key_heading ] = $heading;
				}

				$data[] = $row;
			}
			fclose( $handle );
		}

		return array( 'headers' => $headers, 'data' => $data );
	}

	/**
	 * Run the import process
	 */
	private function import() {
		@set_time_limit(0);
		@ob_flush();
		@flush();

		if ( ! empty( $_POST['file_id'] ) ) {
			$file = get_attached_file( $_POST['file_id'] );
		} elseif ( ! empty( $_POST['import_url'] ) ) {
			$file = ABSPATH . $_POST['import_url'];
		}

		if ( ! is_file( $file ) ) {
			echo '<p>' . __( 'There was an error opening the file. Please go back and try again.', WC_TradeGecko_Init::$text_domain ) . '</p>';
			$this->footer();
			exit;
		}

		$parsed_csv = $this->parse_csv( $file );

		$this->add_import_log( sprintf( __( 'Start Importing products', WC_TradeGecko_Init::$text_domain ) ) );

		@ob_start();

		// Import the products from the csv
		$this->create_products_data_from_csv( $parsed_csv['data'] );

		@ob_clean();

		wp_cache_flush();

		$this->show_updated_message();

		$this->import_completed();

		$this->add_import_log( __( 'Import completed.', WC_TradeGecko_Init::$text_domain ) );

		$this->show_import_log();
	}

	/**
	 * Show a banner message when the import is done.<br/>
	 * Will show how many products/variations are imported or skipped
	 */
	private function show_updated_message() {
		?>
		<div id="message" class="updated fade">
			<p>
			<?php
				echo sprintf( __( 'Product import completed. Imported products: %s, imported variations: %s, skipped %s.', WC_TradeGecko_Init::$text_domain ),
					$this->imported,
					$this->imported_variations,
					$this->skipped
				);
			?>
			</p>
		</div>
		<?php
	}

	/**
	 * Messages when import is completed
	 */
	private function import_completed() {
		echo '<p>' . __( 'Import completed.', WC_TradeGecko_Init::$text_domain ) . '</p>';
		echo '<h4>' . __( 'Import Log:', WC_TradeGecko_Init::$text_domain ) . '</h4>';
	}

	/**
	 * Main method to import all products
	 *
	 * @param array $data
	 * @throws Exception
	 */
	private function create_products_data_from_csv( $data ) {
		$all_variable_products = isset( $_POST['all_variable'] ) && $_POST['all_variable'] ? true : false;

		foreach ( $data as $key => $row ) {
			try {
				$variable_product_variation_id = 0;
				$this->add_import_log( sprintf( __( ' > Start processing row %s', WC_TradeGecko_Init::$text_domain ), $key ) );

				$product_type = $this->get_row_product_type( $data, $row, $key );
				$this->is_addition_variation_end = $this->is_end_of_variation( $product_type, $data, $key, $all_variable_products );

				// Don't import products that already exist
				$should_skip = $this->should_skip_row_import( $row );
				if ( $should_skip ) {
					throw new Exception( sprintf( __( 'Product with the same SKU: %s was found in your store', WC_TradeGecko_Init::$text_domain ), $row['variant_sku'] ) );
				}

				$attributes = $this->add_product_attributes( $row, $product_type );

				// Create the product main post
				$post_id = $this->create_product_post( $row, $product_type );

				// Add the product meta to the product
				$this->add_product_meta( $row, $post_id, $product_type, $attributes );

				if ( 'variable' == $product_type ) {
					// Set the post parent for the variations to come
					$this->parent_id = $post_id;

					// Since the first line is both main product and variation,
					// create the variation product, too.
					$variable_product_variation_id = $this->create_product_post( $row, 'variation' );

					// Add the post meta for the created variation
					$this->add_product_meta( $row, $variable_product_variation_id, 'variation', $attributes );
				}

				// The post_id will be the parent in variable products and the variation in 'variation' products
				if ( $this->is_addition_variation_end ) {
					WC_Product_Variable::sync( $this->parent_id ); // Only at the end of the variation addition

					// Default the variation specific variables, if it is the last variation
					$this->parent_id = '';
					$this->variation_count = 0;
					$this->parent_attributes = array();
				}

				$this->add_import_log( sprintf( __( ' > > %s Product %s imported.', WC_TradeGecko_Init::$text_domain ), ucfirst( $product_type ), $post_id ) );

			} catch ( Exception $e ) {
				// Row is skipped
				$this->add_import_log( sprintf( __( ' > > Skipping row %s. %s', WC_TradeGecko_Init::$text_domain ), $key, $e->getMessage() ) );

				$this->skipped++;

				continue;
			}
		}
	}

	/**
	 * Add the product to the database
	 *
	 * @param array $row The CSV row data
	 * @param string $product_type The type of the product
	 * @return int The ID of the product
	 * @throws Exception
	 */
	private function create_product_post( $row, $product_type ) {
		$date = date( 'Y-m-d H:i:s', time() );
		$is_variation	= ( 'variation' == $product_type ) ? true : false;
		$product_title	= $row['product_name'];
		$product_description = $row['product_description'];
		$post_parent	= 0;
		$comment_status = 'open';

		if ( $is_variation ) {
			// Generate a useful post title
			$product_title = sprintf( __( 'Product #%s Variation', WC_TradeGecko_Init::$text_domain ), esc_html( get_the_title( $this->parent_id ) ) );
			$product_description = '';
			$post_parent = $this->parent_id;
			$comment_status = 'closed';
		}


		// Build the post insert data
		$postdata = array(
			'product_type'		=> $product_type,
			'post_author'		=> get_current_user_id(),
			'post_date'		=> $date,
			'post_date_gmt'		=> $date,
			'post_content'		=> $product_description,
			'post_excerpt'		=> '',
			'post_title'		=> $product_title,
			'post_status'		=> 'publish',
			'post_parent'		=> $post_parent,
			'comment_status'	=> $comment_status,
			'ping_status'		=> 'closed',
			'post_type'		=> 'variation' == $product_type ? 'product_variation' : 'product',
			'menu_order'		=> ( $this->variation_count > 0 ) ? $this->variation_count : 0,
		);

		// Add the product post to DB
		$post_id = wp_insert_post( $postdata );

		// Skip if you can't create the product
		if ( 0 === $post_id ) {
			throw new Exception( __( 'Product could not be created.', WC_TradeGecko_Init::$text_domain ) );
		}

		// Add the count after the product is added to the DB
		if ( $is_variation ) {
			$this->imported_variations++;
		} else {
			$this->imported++;
		}

		return $post_id;
	}

	/**
	 * Add the product meta
	 *
	 * @param array $row The CSV row data
	 * @param int $post_id The product id
	 * @param string $product_type The type of the product
	 * @param array $attributes The Attributes information to be added to the product
	 */
	private function add_product_meta( $row, $post_id, $product_type, $attributes ) {
		$is_variation	= ( 'variation' == $product_type ) ? true : false;
		$is_variable	= ( 'variable' == $product_type ) ? true : false;
		$is_simple	= ( 'simple' == $product_type ) ? true : false;

		if ( ! $is_variation ) {
			// Meta for variable and simple products

			// Set the product type
			wp_set_object_terms( $post_id, $product_type, 'product_type' );

			update_post_meta( $post_id, '_sold_individually', '' );
			update_post_meta( $post_id, '_backorders', 'no' );
			update_post_meta( $post_id, '_visibility', 'visible' );
		} else {
			// Meta for variation products
			foreach ( $attributes as $attribute ) {
				if ( $attribute['attributes']['is_variation'] ) {
					$value = sanitize_title( trim( stripslashes( sanitize_title( $attribute['terms']['term_slug'] ) ) ) );

					// The attributes of for the variation
					update_post_meta( $post_id, 'attribute_' . sanitize_title( $attribute['terms']['taxonomy'] ), $value );
				}
			}

			// Default attributes selection
			update_post_meta( $post_id, '_default_attributes', array() );
		}

		if( $is_variation ) {
			$update_id = $this->parent_id;
		} else {
			$update_id = $post_id;
		}

		// Add taxonomy attributes only for variable parent products
		$product_attributes = maybe_unserialize( get_post_meta( $update_id, '_product_attributes', true ) );

		foreach ( $attributes as $attribute ) {
			$term = sanitize_title( $attribute['terms']['term_slug'] );
			if ( taxonomy_exists( $attribute['terms']['taxonomy'] ) ) {
				$this->terms[ $attribute['terms']['taxonomy'] ][] = $term;
			}

			// Add the attributes to the array of attributes of the product
			$product_attributes[ sanitize_title( $attribute['terms']['taxonomy'] ) ] = $attribute['attributes'];
		}

		if ( $this->is_addition_variation_end && $is_variation ) {
			foreach ( $this->terms as $taxonomy => $value ) {
				wp_set_object_terms( $update_id, $value, $taxonomy );
			}
			$this->terms = array();
		}

		if ( $is_simple ) {
			foreach ( $this->terms as $taxonomy => $value ) {
				wp_set_object_terms( $update_id, $value, $taxonomy );
			}
			$this->terms = array();
		}

		update_post_meta( $update_id, '_product_attributes', $product_attributes );

		##### Meta for all types of products #####

		// Set the shipping class terms
		wp_set_object_terms( $post_id, '', 'product_shipping_class');

		// Dimentions
		update_post_meta( $post_id, '_weight', '' );
		update_post_meta( $post_id, '_length', '' );
		update_post_meta( $post_id, '_width', '' );
		update_post_meta( $post_id, '_height', '' );

		if ( ! $is_variable ) {
			// Add the SKU
			update_post_meta( $post_id, '_sku', $row['variant_sku'] );

			// Manage Stock. Stock will be managed only for variations and simple products
			$manage_stock = isset( $row['manage_stock'] ) ? true == $row['manage_stock'] ? 'yes' : 'no' : 'no';
			update_post_meta( $post_id, '_manage_stock', $manage_stock );
		}

		// Prices
		$price = isset( $row['retail_price'] ) ? $row['retail_price'] : 0;
		update_post_meta( $post_id, '_regular_price', wc_format_decimal( $price ) );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_price', wc_format_decimal( $price ) );

		// Set the stock
		$stock = isset( $row['stock'] ) ? '' == $row['stock'] ? 0 : $row['stock'] : isset( $row['stock_on_hand'] ) ? $row['stock_on_hand'] : 0;
		wc_update_product_stock( $post_id, intval( $stock ) );

		// Is the product downloadable or virtual
		update_post_meta( $post_id, '_virtual', 'no' );
		update_post_meta( $post_id, '_downloadable', 'no' );
	}

	/**
	 * Check we this is the last variation from this set
	 *
	 * @param string $product_type The type of the product
	 * @param array $data The entire parsed CSV document
	 * @param int $key The current row key
	 * @param int $all_variable_products Are we importing all products as variations
	 * @return boolean
	 */
	private function is_end_of_variation( $product_type, $data, $key, $all_variable_products ) {
		if ( ( ( 'variation' == $product_type || ( 'variable' == $product_type && $all_variable_products ) ) &&
		! empty( $data[ $key + 1 ]['product_name'] ) ) || $this->is_last_key( $data, $key ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the key matches the last row of the CSV file
	 *
	 * @param array $data The entire parsed CSV document
	 * @param mixed $key_to_check The key we are checking
	 * @return boolean <b>TRUE</b>, if the key is the last row. <b>FALSE</b>, if the key is not the last row.
	 */
	private function is_last_key( $data, $key_to_check ) {
		end($data);
		$key = key($data);

		if ( $key == $key_to_check ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the product type of the product to import,
	 * based on the csv row information
	 *
	 * @param array $csv The entire parsed CSV document
	 * @param array $row The row we want to check
	 * @param int $key The key of the row we are checking
	 * @return string The product type
	 */
	private function get_row_product_type( $csv, $row, $key ) {
		$all_variable_products = isset( $_POST['all_variable'] ) && $_POST['all_variable'] ? true : false;
		// First we want to check if the product is variable or simple
		if ( ! empty( $row['product_name'] ) ) {
			// Because for products with more than one variation,
			// TG exports the CSV with missing product name for the variations,
			// we will determine that the WC product should be variable,
			// when the product name of the next row is missing
			If ( ( empty( $csv[ $key + 1 ]['product_name'] ) && ! $this->is_last_key( $csv, $key ) ) || $all_variable_products ) {
				$product_type = 'variable';
				$this->variation_count = 0;
			} else {
				$product_type = 'simple';
			}
		} else {
			$product_type = 'variation';
			$this->variation_count += 1;
		}

		return $product_type;
	}

	/**
	 * Check if the SKU of the product to import does not exist in the database
	 *
	 * @global object $wpdb
	 * @param array $row
	 * @return boolean
	 */
	private function should_skip_row_import( $row ) {
		global $wpdb;

		$sku = $wpdb->get_var( $wpdb->prepare("
			SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
			WHERE $wpdb->posts.post_type IN ( 'product', 'product_variation' )
			AND $wpdb->posts.post_status = 'publish'
			AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
			", $row['variant_sku'] )
		);

		if ( empty( $sku ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add the product attributes
	 *
	 * @param array $row The CSV row
	 * @param string $product_type The type of the product
	 * @return array
	 */
	private function add_product_attributes( $row, $product_type ) {
		$attributes = array();
		$i = 1;

		if ( ! empty( $row['option_1_value'] ) ) {
			if ( '' != $row['option_1_label'] ) {
				$label = $row['option_1_label'];
			} else {
				$label = isset( $this->parent_attributes[ $i ]['label'] ) ? $this->parent_attributes[ $i ]['label'] : '';
			}

			$value = $row['option_1_value'];

			$this->add_terms( $label, $value, $i, $product_type, $attributes );
			$i++;
 		}

		if ( ! empty( $row['option_2_value'] ) ) {
			if ( '' != $row['option_2_label'] ) {
				$label = $row['option_2_label'];
			} else {
				$label = isset( $this->parent_attributes[ $i ]['label'] ) ? $this->parent_attributes[ $i ]['label'] : '';
			}

			$value = $row['option_2_value'];

			$this->add_terms( $label, $value, $i, $product_type, $attributes );
			$i++;
		}

		if ( ! empty( $row['option_3_value'] ) ) {
			if ( '' != $row['option_3_label'] ) {
				$label = $row['option_3_label'];
			} else {
				$label = isset( $this->parent_attributes[ $i ]['label'] ) ? $this->parent_attributes[ $i ]['label'] : '';
			}

			$value = $row['option_3_value'];

			$this->add_terms( $label, $value, $i, $product_type, $attributes );
			$i++;
		}

		return $attributes;
	}

	/**
	 * Add the options/attribute values to the taxonomies
	 *
	 * @global object $wpdb
	 * @param string $label Taxonomy Label/Name
	 * @param string $value Term Name
	 * @param int $key The key we want to add the parent attribute
	 * @param string $product_type The type of the product
	 * @param array $attributes (reference) The attributes information needed to add the term to the product
	 * @throws Exception
	 */
	private function add_terms( $label, $value, $key, $product_type, &$attributes ) {
		global $wpdb;

		// Try to find the attribute by name
		$attribute_table = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_label = '%s'", $label ) );


		if ( ! empty( $attribute_table ) ) {
			$taxonomy = 'pa_'. wc_sanitize_taxonomy_name( stripslashes( $attribute_table->attribute_name ) );

			if ( 'variable' == $product_type ) {
				$this->parent_attributes[ $key ] = array(
					'label'		=> $attribute_table->attribute_label,
				);
			}
		} else {
			throw new Exception( __( 'Taxonomy was not created.', WC_TradeGecko_Init::$text_domain ) );
		}

		$is_visible	= 'simple' == $product_type ? 1 : 0;
		$is_variation	= 'simple' == $product_type ? 0 : 1;

		// If its an existing term,
		// check to see if we have the attribute for it already
		$all_terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
		$found = false;
		if ( $all_terms ) {
			foreach ( $all_terms as $term ) {
				if ( $value == $term->name ) {
					$found = true;
					$attributes[] = array(
						'terms'		=> array(
							'term_slug'	=> $term->slug,
							'term_id'	=> $term->term_id,
							'taxonomy'	=> $term->taxonomy
						),
						'attributes'	=> array(
							'name' 		=> wc_clean( $term->taxonomy ),
							'value' 	=> '',
							'position' 	=> $key,
							'is_visible' 	=> $is_visible,
							'is_variation' 	=> $is_variation,
							'is_taxonomy' 	=> '1'
						)
					);
				}
			}
		}

		if ( ! $found ) {
			$term_created = wp_insert_term( $value, $taxonomy, array( 'slug' => wc_sanitize_taxonomy_name( stripslashes( $value ) ) ) );

			// Skip the variation, if we could not create the attributes
			if( is_wp_error( $term_created ) ) {
				throw new Exception( $term_created->get_error_message() );
			} else {
				$attributes[] = array(
					'terms'		=> array(
						'term_slug'	=> wc_sanitize_taxonomy_name( stripslashes( $value ) ),
						'term_id'	=> $term_created['term_id'],
						'taxonomy'	=> $taxonomy
					),
					'attributes'	=> array(
						'name' 		=> wc_clean( $taxonomy ),
						'value' 	=> '',
						'position' 	=> $key,
						'is_visible' 	=> $is_visible,
						'is_variation' 	=> $is_variation,
						'is_taxonomy' 	=> '1'
					)
				);

			}
		}
	}

	/**
	 * Encode the CSV data to UTF-8, if it is not.
	 *
	 * @param array $data
	 * @param string $enc
	 * @return array
	 */
	private function format_data_from_csv( $data, $enc ) {
		$data = ( 'UTF-8' == $enc ) ? $data : utf8_encode( $data );

		return trim( $data );
	}

	/**
	 * Format the headers, removing the spaces and replacing them with _
	 *
	 * @param string $heading
	 * @return string
	 */
	private function get_key_heading( $heading ) {

		return strtolower( str_replace(' ', '_', $heading) );

	}

	/**
	 * Add an import message
	 *
	 * @param string $message
	 */
	private function add_import_log( $message ) {
		$this->log[] = $message;
	}

	/**
	 * Show the import messages after import is done
	 */
	private function show_import_log() {
		?>
		<div class="postbox">
			<div class="inside">
				<textarea class="import_log" readonly="readonly">
				<?php
					foreach ( $this->log as $log ) {
						echo $log . "\n";
					}
				?>
				</textarea>
			</div>
		</div>
		<?php
		$this->log = array();
	}

}