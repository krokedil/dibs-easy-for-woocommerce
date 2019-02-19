<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Payment_Methods {

	public static function get_invoice_fees() {
		$dibs_settings  = get_option( 'woocommerce_dibs_easy_settings' );
		$invoice_fee_id = $dibs_settings['dibs_invoice_fee'];
		if ( $invoice_fee_id ) {
			$product       = wc_get_product( $invoice_fee_id );
			$regular_price = $product->get_regular_price();
			$tax_data      = self::get_tax_data( $product );
			$regular_price = intval( round( $product->get_regular_price(), 2 ) * 100 );
			$invoice_items = array(
				'name' => 'easyinvoice',
				'fee'  => array(
					'reference'        => self::get_sku( $product, $invoice_fee_id ),
					'name'             => wc_dibs_clean_name( $product->get_name() ),
					'quantity'         => 1,
					'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
					'unitPrice'        => $regular_price,
					'taxRate'          => $tax_data['tax_rate'],
					'taxAmount'        => $tax_data['tax_amount'],
					'grossTotalAmount' => $regular_price + $tax_data['tax_amount'],
					'netTotalAmount'   => $regular_price,
				),
			);
			$items         = array();
			$items[]       = $invoice_items;
		}
		return $items;
	}

	public static function get_tax_data( $product ) {
		$_tax      = new WC_Tax();
		$tmp_rates = $_tax->get_base_tax_rates( $product->get_tax_class() );
		$_vat      = array_shift( $tmp_rates );
		$item_tax  = array();
		if ( $product->is_taxable() && isset( $_vat['rate'] ) ) {
			$item_tax['tax_rate']   = intval( round( $_vat['rate'] ) * 100 );
			$item_tax['tax_amount'] = intval( round( ( $_vat['rate'] * 0.01 ) * $product->get_regular_price(), 2 ) * 100 );
		} else {
			$item_tax['tax_rate']   = 0;
			$item_tax['tax_amount'] = 0;
		}
		return $item_tax;
	}

	public static function get_sku( $product, $invoice_fee_id ) {
		if ( get_post_meta( $invoice_fee_id, '_sku', true ) !== '' ) {
			$part_number = $product->get_sku();
		} else {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}
}
