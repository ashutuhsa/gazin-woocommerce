<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Automattic\WooCommerce\Client;

class WooCommerceController extends Controller
{
    private $woocommerce = null;
    public function startAPI()
    {
        $this->woocommerce = new Client(
            env('WEBSITE_URL'),
            env('WOOCOMMERCE_CK'),
            env('WOOCOMMERCE_CS'),
            [
                'wp_json' => true,
                'version' => 'wc/v2',
                'query_string_auth' => true
            ]
        );
    }

    /**
     * @param     $name
     * @param int $parent
     *
     * @return object
     */
    public function save_category($name, $parent =0)
    {
        $this->startAPI();

        try {
            $data = [
                'name' => $name,
                'parent' => $parent
            ];

            return $this->woocommerce->post('products/categories', $data);

        } catch (HttpClientException $e) {
            echo '<pre><code>' . print_r( $e->getMessage(), true ) . '</code><pre>'; // Error message.
            echo '<pre><code>' . print_r( $e->getRequest(), true ) . '</code><pre>'; // Last request data.
            echo '<pre><code>' . print_r( $e->getResponse(), true ) . '</code><pre>'; // Last response data.
        }
    }



    public function test()
    {
        $this->startAPI();

        try {
            $data = [
                'code' => '10off',
                'discount_type' => 'percent',
                'amount' => '10',
                'individual_use' => true,
                'exclude_sale_items' => true,
                'minimum_amount' => '666.00'
            ];

            print_r($this->woocommerce->post('coupons', $data));

        } catch (HttpClientException $e) {
            echo '<pre><code>' . print_r( $e->getMessage(), true ) . '</code><pre>'; // Error message.
            echo '<pre><code>' . print_r( $e->getRequest(), true ) . '</code><pre>'; // Last request data.
            echo '<pre><code>' . print_r( $e->getResponse(), true ) . '</code><pre>'; // Last response data.
        }
    }
}
