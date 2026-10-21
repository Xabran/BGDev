<?php
/**
 * TradeGecko export products
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_TradeGecko_Export_Products_CSV {

	/**
	 * CSV file headers
	 * @var array
	 */
	public $headers;

	/**
	 * Product IDs to export
	 * @var array
	 */
	private $product_ids;

	/**
	 * The CSV delimiter
	 * @var string
	 */
	private $delimiter;

	/**
	 * The enclosure of the CSV values
	 * @var string
	 */
	private $enclosure;

	/**
	 * File handle
	 * @var type
	 */
	private $handle;

	public function __construct( $product_ids ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		$this->product_ids = $product_ids;

		$this->delimiter = ',';

		$this->enclosure = '"';

		$this->handle = fopen( 'php://output', 'w' );
		ob_start();

	}

	/**
	 * Generate a CSV file with the given product IDs
	 *
	 * @return string The generated CSV file
	 */
	public function get_products_csv() {

		$this->headers = $this->get_product_csv_headers();

		// Put into csv format
		$this->write_csv( $this->headers );

		foreach ( $this->product_ids as $product_id ) {

			$row_data = $this->get_csv_per_product( $product_id );

			$first_element = reset( $row_data );

			// If we export variations we will have array of arrays
			if ( is_array( $first_element ) ) {
				foreach( $row_data as $row ) {
					// Write into csv
					$this->write_csv( $row );
				}

			} else {
				// Write into csv
				$this->write_csv( $row_data );
			}
		}

		return $this->get_csv();
	}

	/**
	 * Define the headers to match the TG format
	 *
	 * @return array Headers in array form
	 */
	private function get_product_csv_headers() {

		$headers = array(
			'product_name'		=> 'Product Name',
			'product_type'		=> 'Product Type',
			'product_description'	=> 'Product Description',
			'supplier'		=> 'Supplier',
			'brand'			=> 'Brand',
			'tags'			=> 'Tags',
			'option_1_label'	=> 'Option 1 Label',
			'option_1_value'	=> 'Option 1 Value',
			'option_2_label'	=> 'Option 2 Label',
			'option_2_value'	=> 'Option 2 Value',
			'option_3_label'	=> 'Option 3 Label',
			'option_3_value'        => 'Option 3 Value',
			'variant_name'		=> 'Variant Name',
			'variant_sku'		=> 'Variant SKU',
			'wholesale_price'	=> 'Wholesale Price',
			'retail_price'		=> 'Retail Price',
			'buy_price'		=> 'Buy Price',
			'stock'			=> 'Stock',
			'publish_online'	=> 'Publish Online',
			'online_ordering'	=> 'Online Ordering',
			'barcode'		=> 'Barcode',
			'supplier_code'		=> 'Supplier Code',
			'variant_description'	=> 'Variant Description',
			'taxable'		=> 'Taxable',
			'initial_cost_price'	=> 'Initial Cost Price',
			'manage_stock'		=> 'Manage Stock',
			'reorder_point'		=> 'Reorder Point',
			'stock_on_hand'		=> 'Stock On Hand',
			'committed_stock'	=> 'Committed Stock',
			'uncommitted_stock'	=> 'Uncommitted Stock',
		);

		return $headers;
	}

	/**
	 * Get the CSV for each product.
	 * Since variations are exported with additional information,
	 * we are will export them in a different method
	 *
	 * @param int $product_id
	 * @return array An Array of the product CSV rows
	 */
	private function get_csv_per_product( $product_id ) {

		$product = WC_Compat_TG::wc_get_product( $product_id );

		if ( $product instanceof WC_Product_Variable ) {
			$data = $this->get_csv_row_for_variable_products( $product );
		} else {
			$data = $this->get_csv_product_row( $product );
		}

		// TODO: filter to modify the data
		return $data;
	}

	/**
	 * Export variable products row
	 *
	 * @param \WC_Product_Variable $product
	 * @return array An Array of the product CSV rows
	 */
	private function get_csv_row_for_variable_products( \WC_Product_Variable $product ) {
		$product_data = array();

		// All Variable products need to have children
		// or they won't have enough data to export
		if ( ! $product->has_child() ) {
			return $product_data;
		}

		$children = $product->get_children();

		$first = true;
		foreach ( $children as $child_id ) {

			$child_product = WC_Compat_TG::wc_get_product( $child_id );

			$attributes = $this->get_variation_attributes( $child_product );

			$sku = '' != $child_product->get_sku() ? $child_product->get_sku() : $child_product->id;

			$product_data[] = array(
				'product_name'		=> $first ? $this->value_csv_clean( $child_product->get_title() ) : '',
				'product_type'		=> '',
				'product_description'	=> $first ? $this->value_csv_clean( $child_product->post->post_content ) : '',
				'supplier'		=> '',
				'brand'			=> '',
				'tags'			=> $first ? $this->value_csv_clean( $this->get_tags( $product->id ) ) : '',
				'option_1_label'	=> $first ? $this->value_csv_clean( isset( $attributes[0] ) ? $attributes[0]['label'] : '' ) : '',
				'option_1_value'	=> $this->value_csv_clean( isset( $attributes[0] ) ? $attributes[0]['value'] : '' ),
				'option_2_label'	=> $first ? $this->value_csv_clean( isset( $attributes[1] ) ? $attributes[1]['label'] : '' ) : '',
				'option_2_value'	=> $this->value_csv_clean( isset( $attributes[1] ) ? $attributes[1]['value'] : '' ),
				'option_3_label'	=> $first ? $this->value_csv_clean( isset( $attributes[2] ) ? $attributes[2]['label'] : '' ) : '',
				'option_3_value'        => $this->value_csv_clean( isset( $attributes[2] ) ? $attributes[2]['value'] : '' ),
				'variant_name'		=> $this->value_csv_clean( $child_product->get_title() ),
				'variant_sku'		=> $this->value_csv_clean( $sku ),
				'wholesale_price'	=> '',
				'retail_price'		=> WC_Compat_TG::wc_format_decimal( $child_product->get_price(), 2 ),
				'buy_price'		=> '',
				'stock'			=> $child_product->managing_stock() ? $child_product->get_total_stock() : '0',
				'publish_online'	=> 'TRUE',
				'online_ordering'	=> 'TRUE', // What is this exactly
				'barcode'		=> '',
				'supplier_code'		=> '',
				'variant_description'	=> '',
				'taxable'		=> $child_product->is_taxable() ? 'TRUE' : 'FALSE',
				'initial_cost_price'	=> WC_Compat_TG::wc_format_decimal( $child_product->get_price(), 2 ),
				'manage_stock'		=> $child_product->managing_stock() ? 'TRUE' : 'FALSE',
				'reorder_point'		=> '',
				'stock_on_hand'		=> $child_product->managing_stock() ? $child_product->get_total_stock() : '0',
				'committed_stock'	=> '0',
				'uncommitted_stock'	=> '0',
			);

			$first = false;
		}

		// TODO: add filter
		return $product_data;
	}

	/**
	 * Export product CSV row. All products except variable
	 *
	 * @param \WC_Product $product
	 * @return array
	 */
	private function get_csv_product_row( \WC_Product $product ) {
		$attributes = $this->get_product_attributes( $product );

		$sku = '' != $product->get_sku() ? $product->get_sku() : $product->id;

		$product_data = array(
			'product_name'		=> $this->value_csv_clean( $product->get_title() ),
			'product_type'		=> '',
			'product_description'	=> $this->value_csv_clean( $product->post->post_content ),
			'supplier'		=> '',
			'brand'			=> '',
			'tags'			=> $this->value_csv_clean( $this->get_tags( $product->id ) ),
			'option_1_label'	=> $this->value_csv_clean( isset( $attributes[0] ) ? $attributes[0]['label'] : '' ),
			'option_1_value'	=> $this->value_csv_clean( isset( $attributes[0] ) ? $attributes[0]['value'] : '' ),
			'option_2_label'	=> $this->value_csv_clean( isset( $attributes[1] ) ? $attributes[1]['label'] : '' ),
			'option_2_value'	=> $this->value_csv_clean( isset( $attributes[1] ) ? $attributes[1]['value'] : '' ),
			'option_3_label'	=> $this->value_csv_clean( isset( $attributes[2] ) ? $attributes[2]['label'] : '' ),
			'option_3_value'        => $this->value_csv_clean( isset( $attributes[2] ) ? $attributes[2]['value'] : '' ),
			'variant_name'		=> $this->value_csv_clean( $product->get_title() ),
			'variant_sku'		=> $this->value_csv_clean( '' != $sku ? $sku : $product->id ),
			'wholesale_price'	=> '',
			'retail_price'		=> WC_Compat_TG::wc_format_decimal( $product->get_price(), 2 ),
			'buy_price'		=> '',
			'stock'			=> $product->managing_stock() ? $product->get_total_stock() : '0',
			'publish_online'	=> 'TRUE',
			'online_ordering'	=> 'TRUE', // What is this exactly
			'barcode'		=> '',
			'supplier_code'		=> '',
			'variant_description'	=> '',
			'taxable'		=> $product->is_taxable() ? 'TRUE' : 'FALSE',
			'initial_cost_price'	=> WC_Compat_TG::wc_format_decimal( $product->get_price(), 2 ),
			'manage_stock'		=> $product->managing_stock() ? 'TRUE' : 'FALSE',
			'reorder_point'		=> '',
			'stock_on_hand'		=> $product->managing_stock() ? $product->get_total_stock() : '0',
			'committed_stock'	=> '0',
			'uncommitted_stock'	=> '0',
		);

		// TODO: add filter
		return $product_data;
	}

	/**
	 * Get product attributes
	 *
	 * @param \WC_Product $product
	 * @return array
	 */
	private function get_product_attributes( \WC_Product $product ) {

		// Array of defined attribute taxonomies
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
		$attributes = $product->get_attributes();

		$attributes_found = array();

		foreach ( $attribute_taxonomies as $tax ) {
			// Get name of taxonomy
			$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );

			// Get product data values for current taxonomy - this contains ordering and visibility data
			if ( isset( $attributes[ sanitize_title( $attribute_taxonomy_name ) ] ) )
				$attribute = $attributes[ sanitize_title( $attribute_taxonomy_name ) ];

			// Get terms of this taxonomy associated with current product
			$post_terms = wp_get_post_terms( $product->id, $attribute_taxonomy_name );

			$has_terms = ( is_wp_error( $post_terms ) || ! $post_terms || sizeof( $post_terms ) == 0 ) ? 0 : 1;

			if ( 0 == $has_terms ) {
				continue;
			}

			$values = array();

			$all_terms = get_terms( $attribute_taxonomy_name, 'orderby=name&hide_empty=0' );

			if ( $all_terms ) {
				foreach ( $all_terms as $term ) {
					$has_term = has_term( (int) $term->term_id, $attribute_taxonomy_name, $product->id ) ? 1 : 0;
					if ( $has_term ) {
						$values[] = $term->name;
					}
				}
				$attributes_found[] = array(
					'label' => $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name,
					'value'	=> implode( '|', $values )
				);
			}
		}

		// Get all non taxonomy attributes into the array
		if ( $product->has_attributes() ) {
			foreach ( $attributes as $attr_name => $attr_data ) {
				if ( true == $attr_data['is_taxonomy'] ) {
					continue;
				}

				$attributes_found[] = array(
					'label' => wc_attribute_label( $attr_data['name'] ),
					'value'	=> $attr_data['value']
				);
			}
		}

		return $attributes_found;
	}

	/**
	 * Get Variable product attributes
	 *
	 * @param \WC_Product_Variation $variation
	 * @return array
	 */
	private function get_variation_attributes( \WC_Product_Variation $variation ) {
		$attributes = array();

		$child_attr = $variation->get_variation_attributes();

		foreach ( $child_attr as $name => $value ) {
			$formatted_name = str_replace( 'attribute_', '', $name );
			$term = get_term_by( 'slug', $value, $formatted_name );
			$value = is_object( $term ) ? $term->name : $value;

			$attributes[] = array(
				'label'	=> wc_attribute_label( $formatted_name ),
				'value'	=> $value
			);
		}

		return $attributes;
	}

	/**
	 * Write a row in csv format into the file handle
	 *
	 * @param array $row
	 */
	private function write_csv( $row ) {

		$data = array();

		foreach ( $this->headers as $header_key => $_ ) {

			if ( ! isset( $row[ $header_key ] ) ) {
				$row[ $header_key ] = '';
			}

			$data[] = ( '' !== $row[ $header_key ] ) ? $row[ $header_key ] : '';
		}

		fputcsv( $this->handle, $data, $this->delimiter, $this->enclosure );
	}

	/**
	 * Return the generated CSV file and close the opened file pointer.
	 *
	 * @return string
	 */
	private function get_csv() {

		$csv = ob_get_clean();

		fclose( $this->handle );

		return $csv;
	}

	/**
	 * Generate a file name for the CSV file
	 *
	 * @return string The file name
	 */
	private function get_csv_filename() {

		// TODO: filters
		$filename = 'wc-tradegecko-products-%%date%%-%%time%%.csv';

		$search   = array( '%%date%%', '%%time%%' );
		$replace = array( date( 'Y_m_d' ), date( 'H_i_s' ) );

		$filename = str_replace( $search, $replace, $filename );

		// TODO: filters
		return $filename;
	}

	/**
	 * Remove any csv not allowed characters
	 *
	 * @param type $value
	 * @return type
	 */
	private function value_csv_clean( $value ) {

		$value = str_replace( array( "\r", "\r\n", "\n" ), '', $value );

		return $value;
	}

	/**
	 * Group into a string all product tags
	 *
	 * @param type $id
	 * @return type
	 */
	private function get_tags( $id ) {
		$post_tags = get_the_terms( $id, 'product_tag' );
		$tags = array();

		if ( $post_tags ) {
			foreach ( $post_tags as $tag ) {
				$tags[] = $tag->name;
			}
		}

		return implode(', ', $tags);
	}

	/**
	 * Add the headers and output the csv file
	 *
	 * @param type $csv
	 */
	public function get_csv_file( $csv ) {
		$filename = $this->get_csv_filename();

		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ) );
		header( sprintf( 'Content-Disposition: attachment; filename="%s"', $filename ) );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		@ob_clean();

		$file = fopen( 'php://output', 'w' );

		fwrite( $file, $csv );

		fclose( $file );

		exit;
	}
}
