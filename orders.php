<?php

$order_id = sanitize_text_field($_GET['order_id']);

// WP Globals
global $table_prefix, $wpdb;

$plugin_table = $table_prefix . 'invoices';
$invoice = $wpdb->get_results( 
    $wpdb->prepare("SELECT * FROM `$plugin_table` WHERE invoice_order_id = %d", $order_id)
);


if(empty($invoice)) {
    $data = array('invoice_order_id' => $order_id);
    $format = array('%d');
    $wpdb->insert($plugin_table,$data,$format);
    $invoice_id = $wpdb->insert_id;
    $invoice_year = date('Y');
    $invoice_full_time = date('Y-m-d H:i:s');
}else {
    $invoice = $invoice[0];
    $invoice_id = $invoice->invoice_id;
    $invoice_year = date('Y', strtotime($invoice->invoice_date));
    $invoice_full_time = date('Y-m-d H:i:s', strtotime($invoice->invoice_date));
}


if (is_null($order_id)) {
    echo <<<HTML
        <div class="error"> <p>Тази страница се използва само за принтиране на определена поръчка! Не сте задали конкретна поръчка</p></div>
    HTML;
    exit;
}

if (!current_user_can('edit_post', $order_id)) {
    echo <<<HTML
    <div class="error"> <p>Нямате права да принтирате</p></div>
HTML;
    exit;
}

use WPML\PB\Gutenberg\StringsInBlock\HTML;

/**
 * Heredoc inline function
 * @param $data 
 * Using {$fn(fnName($arguments, $args))} example of using inline function with Heredoc
 */
$fn = function ($data = NULL) {
    return $data;
};

/** 
 * Register scripts and css 
 */
wp_register_style('gplugin_style', plugins_url('dist/css/main.css', __FILE__));
wp_enqueue_style('gplugin_style');

wp_register_script('gplugin_script', plugins_url('dist/js/main.js', __FILE__));
wp_enqueue_script('gplugin_script');


// Initialize the order
$order = wc_get_order($order_id);
$order_status  = $order->get_status();

if ($order_status != "completed" && $order_status != "processing" ) {
    echo <<<HTML
    <div class="error"> <p>Тази поръчка не е в статус за генериране и принтиране на фактура и не може да принтирате фактура</p></div>
HTML;
    exit;
}


?>
<div class="page-container shadow">
    <div class="page-wrapper wrapper">
        <?php
        $header = <<<HTML
    <div class="header text-center">
    <div class="header-logo-container">
        <img src="https://rodinashop.de/wp-content/uploads/2020/12/Bulgarische-Lebensmittel.png" alt="Logo" />
        <p class="header-thanks">Danke, dass Sie uns gewählt haben!</p>
        <p class="header-website">WWW.RODINASHOP.DE</p>

    </div>
</div>
HTML;
        echo $header;

        $invoice_info = <<<HTML
<section class="invoice-info">
    <div class="container">
        <div class="row">
            <div class="col invoice-sender">
                <ul class="ul-invoice-sender">
                    <li><span>BG MARKET RODINA</span> Sadife Cherkez</li>
                    <li> Flughafenstrasse 17, 12053</li>
                    <li>Berlin, Deutschland DE815466202</li>
                    <li>+49 175 7788078</li>
                    <li>info@rodinashop.de</li>
                </ul>
            </div>
            <div class="col invoice-reciever">
            <ul class="ul-invoice-reciever">
                <li>Rehnung für {$order->get_formatted_billing_address()}</li>      
                <li>Phone: {$order->get_billing_phone()}</li>
                </ul>
            </div>
            <div class="col">
                <ul class="ul-invoice-delivery">
                    <li>Lieferanschrift</li>
                    <li>{$order->get_formatted_shipping_address()}</li>      
                </ul>
                <ul class="ul-invoice-info">
                    <li>Rechnung</li>
                    <li> $invoice_id - $invoice_year $invoice_full_time</li>
                    <li>Bestellnummer: $order_id </li>
                    <li>Datum der Bestellung: $order->order_date</li>
                </ul>
            </div>
        </div>
    </div>
</section>
HTML;
        echo $invoice_info;

        // Init the table 
        $table = <<<HTML
        <div class="table-container" >
        <!-- wp-list-table widefat fixed striped - not this classes --> 
            <table class="products-table">
                <thead>
                    <tr>
                        <th class="manage-column column-columnname">Anzahi</th>
                        <th class="manage-column column-columnname">Bezeignung</th>
                        <th class="manage-column column-columnname">Mwst</th>
                        <th class="manage-column column-columnnamee">Enzelpreis</th>
                        <th class="manage-column column-columnname  ">Gesamtpreis</th>
                    </tr>
                </thead>
                <tbody>
HTML;


        // Total price 19%
        $total_no_vat_big_percent = 0;
        $total_vat_big_percent = 0;

        // Total price 7%
        $total_vat_small_percent = 0;
        $total_no_vat_small_percent = 0;


        // Total sum 
        $total_sum_no_vat = 0;

        // Get and Loop Over Order Items
        foreach ($order->get_items() as $item_id => $item) {

            $name = $item->get_name();
            $quantity = $item->get_quantity();
            $subtotal = $item->get_subtotal();
            $total = $item->get_total();
            $taxclass = $item->get_tax_class();


            if ($taxclass == "alkohol" || $taxclass == "promishleni") {
                $vat_percent = 19;
                $single_product_price_no_vat = ($subtotal / $quantity) / 1.19;
                $total_product_price_no_vat = formatPrice($single_product_price_no_vat) * $quantity;
                $total_sum_no_vat += formatPrice($total_product_price_no_vat);
                $total_no_vat_big_percent += formatPrice($total_product_price_no_vat);
                $total_vat_big_percent += $total;
            } else {
                // var_dump( $taxclass);
                $vat_percent = 7;
                $single_product_price_no_vat = ($subtotal / $quantity) / 1.07;
                $total_product_price_no_vat = formatPrice($single_product_price_no_vat) * $quantity;
                $total_sum_no_vat += formatPrice($total_product_price_no_vat);
                $total_no_vat_small_percent += formatPrice($total_product_price_no_vat);
                $total_vat_small_percent += $total;
            }




            $table .= <<<HTML
        <tr>
            <td>$quantity </td>
            <td> $name </td> 
            <td> {$vat_percent}% </td>         
            <td>{$fn(formatPrice($single_product_price_no_vat))}€</td>            
            <td>{$fn(formatPrice($total_product_price_no_vat))}€ </td>
        </tr>
    HTML;
        }

        #-> Adding Shipment

        //Shipping prices
        $total_shipping_with_vat = formatPrice($order->get_shipping_total());
        $total_shipping_no_vat =  formatPrice(($total_shipping_with_vat / 1.19));
        $shipping_vat = $total_shipping_with_vat - $total_shipping_no_vat;

        $total_sum_no_vat += $total_shipping_no_vat;
        $total_vat_big_percent += $total_shipping_with_vat;
        $total_no_vat_big_percent += $total_shipping_no_vat;
        $table .= <<<HTML
    <tr>
            <td>1 </td>
            <td> Lieferung  </td>  
            <td> 19% </td>         
            <td>{$total_shipping_no_vat}€</td>            
            <td>{$total_shipping_no_vat}€</td>    
        </tr>
HTML;


        $table .= <<<HTML
            </tbody>
    </table>
</div>
HTML;
        echo $table;

        // Sum of 7% VAT - only sum of the VAT, does not include the price 
        $only_vat_small_vat = formatPrice($total_vat_small_percent) - formatPrice($total_no_vat_small_percent);

        // Sum of 19% VAT - only sum of the VAT, does not include the price 
        $only_vat_big_vat =  formatPrice($total_vat_big_percent) - formatPrice($total_no_vat_big_percent);

        // Total price with VAT 
        $total_invoice = formatPrice($total_vat_big_percent + $total_vat_small_percent);

        # -> Section total-invoice-info
        $total_invoice_info = <<<HTML
<section class="total-invoice-info">
    <div class="section">
        <div class="container">
            <div class="row">
                <div class="col">SUMME</div>
                <div class="col">{$total_sum_no_vat}€</div>
            </div>
            <div class="row">
                <div class="col">Mehrwertsteuer 7% auf {$fn(formatPrice($total_no_vat_small_percent))}€</div>
                <div class="col">{$only_vat_small_vat}€</div>
            </div>
            <div class="row">
                <div class="col">Mehrwertsteuer 19% auf {$fn(formatPrice($total_no_vat_big_percent))}€</div>
                <div class="col">{$only_vat_big_vat}€</div>
            </div>
        </div>  
</section>
HTML;
        echo $total_invoice_info;

        $invoice_total = <<<HTML
</div>  <!-- ./ Page wrapper  -->
    <div class="total-invoice-price-container">
        <div class="container">
            <div class="row">
                <div class="col">Gesambeterag</div>
                <div class="col text-right"><span class="total-invoice-price-number">{$total_invoice}€</span></div>
            </div>
        </div>
    </div>
HTML;
        echo $invoice_total;
        ?>
    </div> <!-- ./ Page container  -->

    <?php

    /**
     * @param price
     * Get price - format the number with two decimals and round them, then add EURO (€) sign after the price
     */
    function formatPrice($price, $decimals = 2)
    {

        return number_format((float)$price, $decimals);
    }
