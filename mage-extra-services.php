<?php
/*
* Plugin Name: Custom Functions for Anokijig
* Version: 0.0.1
* Author: Businet
* Description: This plugin will add custom functions for Anokijig.
* Author URI: https://www.businet.dev/
* Text Domain: mep-extra-service
*/


/*
* show erros ajax
*
error_reporting(E_ALL); 
ini_set("display_errors", 1);
*/

if (!defined('ABSPATH')) {
  die;
} // Cannot access pages directly.
// Checking woo
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (!defined('MPWEM_CUSTOM_PLUGIN_DIR')) {
  define('MPWEM_CUSTOM_PLUGIN_DIR', dirname(__FILE__));
}

if (!function_exists('mep_extra_service_template_file_path')) {
  function mep_extra_service_template_file_path($file_name)
  {
    $template_path = get_stylesheet_directory() . '/mage-events/';
    $default_path = plugin_dir_path(__FILE__) . 'templates/';

    $thedir = is_dir($template_path) ? $template_path : $default_path;
    $themedir = $thedir . $file_name;
    $the_file_path = locate_template(array('mage-events/' . $file_name)) ? $themedir : $default_path . $file_name;
    return $the_file_path;
  }
}


add_action('woocommerce_checkout_create_order', 'create_cabin_data', 10, 1);
//add_action('woocommerce_thankyou_cod', 'create_cabin_data', 10, 1);
function create_cabin_data( $order ) {
  try{
    $order_id = $order->get_id();
    //$myfile = fopen(MPWEM_CUSTOM_PLUGIN_DIR."/new_order_log_".$order_id.".txt", "a+") or die("Unable to open file!");
    $txt = "Order: ". ($order_id - 1)  ."\n";
    global $wpdb;
    // $order = wc_get_order( $order_id - 1 );
    $table_name = 'cabin_management';
    $customerId = $order->get_customer_id();
    $customer = new WC_Customer( $customerId );
    $txt.= "customer id: ". $customerId ."\n";
    foreach ( $order->get_items() as $item_id => $item ) {
      $txt.= "item id ". $item_id ."\n";
      $isCamp = $item->legacy_values['iscamp'];
      $isPrincipal = $item->legacy_values['iscamp'];   
      $txt.= "item data: ". print_r($item, true) ."\n";
      $txt.= "is camp: ". $isCamp ."\n";
      $txt.= "is principal: ". $isPrincipal ."\n";
      if($isCamp && $isPrincipal){
        $txt.= "is camp and is principal \n";
        $txt .= "item data object: ". print_r($item, true) ."\n";
        $product_id = $item->get_product_id();
        $txt.= "product id: ". $product_id ."\n";
        $session = new WP_Query(array(
          'post_type'        => 'session',
          'posts_per_page'   => -1,
          'suppress_filters' => 0,
          'meta_query'       => array(
                  array(
                      'key'      => 'camps',
                      'value'    => '"'.$product_id.'"',
                      'compare'  => 'LIKE'
                  )
              ),
              'order' => 'ASC',
              'orderby' => 'title',
              'post_status' => 'publish',
          )); 
        // print sql of $sesion wp query.
        $txt.= "session sql: ". $session->request ."\n";
        $session = $session->posts;
        $txt.= "session: ". $session[0]->post_title ."\n";
        $item_id = $item->get_id();
        $insert_response = $wpdb->insert( 
          $table_name, 
          array( 
            'id_order_item' => $item_id,
            'OrderId' => $order->get_id(), 
            'CustomerName' => $customer->get_display_name(),
            'FirstNameCamper' => get_user_meta($customerId, 'afreg_additional_2121', true), 
            'LastNameCamper' => get_user_meta($customerId, 'afreg_additional_2122', true), 
            'Gender' => get_user_meta($customerId, 'afreg_additional_2124', true),  
            'Grade' => get_user_meta($customerId, 'afreg_additional_2127', true), 
            'FriendName' => get_user_meta($customerId, 'afreg_additional_2137', true),
            'Cabin' => "", 
            'ProductName' => $item->get_name(), 
            'ProductCategory' => '', 
            'Session' => !empty($session) ? $session[0]->post_title : '',
            'SessionId' => !empty($session) ? $session[0]->ID : ''
          ) 
        );
        $txt.= "insert response: ". $insert_response ."\n";
      }
      
    }
    //fwrite($myfile, $txt);
    //fclose($myfile);
  }catch(Exception $e){
    //$myfile = fopen(MPWEM_CUSTOM_PLUGIN_DIR."/logs-ivan.txt", "wr") or die("Unable to open file!");
    $txt= "Error: ". $e->getMessage() ."\n";
    //fwrite($myfile, $txt);
    //fclose($myfile);
    error_log("@@ Error report: ". $e->getMessage() );
  }
}

require_once MPWEM_CUSTOM_PLUGIN_DIR . "/mep_query.php";
if (is_plugin_active('woocommerce/woocommerce.php')) {
  // require_once MPWEM_CUSTOM_PLUGIN_DIR . '/support/elementor/elementor-support.php';
}

function mep_event_extra_price_option($post_id)
{
  $mep_events_linked_extra_prices = get_post_meta($post_id, 'mep_events_linked_extra_prices', true);
  wp_nonce_field('mep_events_linked_extra_price_nonce', 'mep_events_linked_extra_price_nonce');
?>
  <div id="extra-services-admin">
    <p class="event_meta_help_txt"><?php esc_html_e('Extra Service as Product that you can sell and it is not included on event package', 'mage-eventpress'); ?></p>
    <div class="mp_ticket_type_table">
      <table id="repeatable-fieldset-one-extra-service">
        <thead>
          <tr>
            <th title="<?php esc_attr_e('Extra Service', 'mage-eventpress'); ?>"><?php esc_html_e('Service', 'mage-eventpress'); ?></th>
            <th title="<?php esc_attr_e('Qty Box Type', 'mage-eventpress'); ?>" style="min-width: 140px;"><?php esc_html_e('Qty Box', 'mage-eventpress'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody class="mp_event_type_sortable">
          <?php

          if ($mep_events_linked_extra_prices) :
            foreach ($mep_events_linked_extra_prices as $field) {
              $qty_type  = array_key_exists('extra_service_option_qty_type', $field) ? esc_attr($field['extra_service_option_qty_type']) : 'inputbox';
          ?>
              <tr>
                <td>
                  <select name="extra_service_option_service[]" class='mp_formControl'>
                    <?php
                    $args = array(
                      'post_type' => 'product',
                      'post_status' => 'publish',
                      'posts_per_page' => -1,
                      'tax_query' => array(
                        array(
                          'taxonomy' => 'product_cat',
                          'field' => 'slug',
                          'terms' => ['day-trips'],
                        )
                      )
                    );
                    $the_query = new WP_Query($args);

                    if ($the_query->have_posts()) {
                      while ($the_query->have_posts()) {
                        $the_query->the_post();
                        $id = get_the_ID();
                        echo '<option value="' . $id . '" ' . selected($id, $field['extra_service_option_service'], false) . '>' . get_the_title() . '</option>';
                      }
                    }
                    ?>
                  </select>
                </td>
                <td align="center">
                  <select name="extra_service_option_qty_type[]" class='mp_formControl'>
                    <option value="inputbox" <?php if ($qty_type == 'inputbox') {
                                                echo esc_attr("Selected");
                                              } ?>><?php esc_html_e('Input Box', 'mage-eventpress'); ?></option>
                    <option value="dropdown" <?php if ($qty_type == 'dropdown') {
                                                echo esc_attr("Selected");
                                              } ?>><?php esc_html_e('Dropdown List', 'mage-eventpress'); ?></option>
                  </select>
                </td>
                <td>
                  <div class="mp_event_remove_move">
                    <button class="button remove-row" type="button"><i class="fas fa-trash"></i></button>
                    <div class="mp_event_type_sortable_button"><i class="fas fa-grip-vertical"></i></div>
                  </div>
                </td>
              </tr>
          <?php
            }
          else :
          // show a blank one
          endif;
          ?>
          <!-- empty hidden one for jQuery -->
          <tr class="empty-row-extra-services screen-reader-text">
            <td>
              <select name="extra_service_option_service[]" class='mp_formControl'>
                <option value=""><?php esc_html_e('Please Select Service', 'mage-eventpress'); ?></option>
                <?php
                $args = array(
                  'post_type' => 'product',
                  'post_status' => 'publish',
                  'posts_per_page' => -1,
                  'tax_query' => array(
                    array(
                      'taxonomy' => 'product_cat',
                      'field' => 'slug',
                      'terms' => ['day-trips'],
                    )
                  )
                );
                $the_query = new WP_Query($args);

                if ($the_query->have_posts()) {
                  while ($the_query->have_posts()) {
                    $the_query->the_post();
                    $id = get_the_ID();
                    echo '<option value="' . $id . '"' . '>' . get_the_title() . '</option>';
                  }
                }
                ?>
              </select>
            </td>
            <td><select name="extra_service_option_qty_type[]" class='mp_formControl'>
                <option value=""><?php esc_html_e('Please Select Type', 'mage-eventpress'); ?></option>
                <option value="inputbox"><?php esc_html_e('Input Box', 'mage-eventpress'); ?></option>
                <option value="dropdown"><?php esc_html_e('Dropdown List', 'mage-eventpress'); ?></option>
              </select></td>
            <td>
              <button class="button remove-row-extra-services"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p>
      <button id="add-row-extra-services" class="button"><i class="fas fa-plus-circle"></i> <?php esc_html_e('Add Extra Price', 'mage-eventpress'); ?></button>
    </p>
  </div>
<?php
}

/**
 * Añade contenido antes de la lista
 */
//add_action('mep_event_tab_after_ticket_pricing', 'custom_mep_event_tab_after_ticket_pricing');

function custom_mep_event_tab_after_ticket_pricing()
{ ?>

  <h3><?php _e('Linked extra services', 'text-domain'); ?></h3>
<?php $mep_events_linked_extra_prices = get_post_meta(get_the_ID(), 'mep_events_linked_extra_prices', true);
  mep_event_extra_price_option(get_the_ID());
}


if (!defined('MPWEM_EXTRA_SERVICE_PLUGIN_URL')) {
  define('MPWEM_EXTRA_SERVICE_PLUGIN_URL', plugins_url() . '/' . plugin_basename(dirname(__FILE__)));
}
function wp_enqueue($hook)
{
  /*global $post;
  // Load Only when the New Event Add Page Open.
  if ($hook == 'post-new.php' || $hook == 'post.php') {
    if ('mep_events' === $post->post_type) {
      wp_enqueue_script('gmap-scripts-extra-services', MPWEM_EXTRA_SERVICE_PLUGIN_URL . '/assets/admin/mkb-admin.js', array('jquery', 'jquery-ui-core'), time(), true);
    }
  }*/
  wp_enqueue_script('multistep-form', MPWEM_EXTRA_SERVICE_PLUGIN_URL . '/assets/front/multistep-form.js', array('jquery'), '1.0', true);
  wp_localize_script('multistep-form', 'ajaxurl', array(
    'ajaxurl' => admin_url('admin-ajax.php')
  ));
  // if page is /registration
  if (is_page('registration') || is_page('my-account')) {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', array(), time());
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '1.0', true);
  }
}
add_action('wp_enqueue_scripts', 'wp_enqueue', 90);

//wp-admin eneque css
function mep_extra_service_admin_enqueue()
{
  wp_enqueue_style('mep_extra_service_admin_css', MPWEM_EXTRA_SERVICE_PLUGIN_URL . '/assets/admin/mkb-admin.css', array(), time());
}
add_action('admin_enqueue_scripts', 'mep_extra_service_admin_enqueue', 90);

/*
if(function_exists('the_plugin_custom_function_call')){
	remove_action('mep_events_repeatable_meta_box_save', 'mep_events_repeatable_meta_box_save');
	add_action('save_post','mep_events_repeatable_meta_box_save_2');
}else{
  add_action('save_post', 'mep_events_repeatable_meta_box_save_2', 100);
} */
// add_action('save_post', 'mep_events_repeatable_meta_box_save_2');
function mep_events_repeatable_meta_box_save_2($post_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'event_extra_options';
  if (
    !isset($_POST['mep_events_linked_extra_price_nonce']) ||
    !wp_verify_nonce($_POST['mep_events_linked_extra_price_nonce'], 'mep_events_linked_extra_price_nonce')
  ) {
    return;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  if (get_post_type($post_id) == 'mep_events') {
    $old     = get_post_meta($post_id, 'mep_events_linked_extra_prices', true);
    $new     = array();
    $extra_service_option_service     = isset($_POST['extra_service_option_service']) ? mage_array_strip($_POST['extra_service_option_service'])  : [];
    $extra_service_option_qty_type     = isset($_POST['extra_service_option_qty_type']) ? mage_array_strip($_POST['extra_service_option_qty_type'])  : [];

    $order_id = 0;
    $count    = count($extra_service_option_service);

    for ($i = 0; $i < $count; $i++) {
      if ($extra_service_option_service[$i] != '') :
        $new[$i]['extra_service_option_service'] = stripslashes(strip_tags($extra_service_option_service[$i]));
      endif;

      if ($extra_service_option_qty_type[$i] != '') :
        $new[$i]['extra_service_option_qty_type'] = stripslashes(strip_tags($extra_service_option_qty_type[$i]));
      endif;
    }

    if (!empty($new) && $new != $old) {
      update_post_meta($post_id, 'mep_events_linked_extra_prices', $new);
    } elseif (empty($new) && $old) {
      delete_post_meta($post_id, 'mep_events_linked_extra_prices', $old);
    }
  }
}

// add_action('mep_event_extra_service', 'mep_ev_linked_extra_serv', 10, 2);
if (!function_exists('mep_ev_linked_extra_serv')) {
  function mep_ev_linked_extra_serv($post_id, $extra_service_label)
  {
    global $post, $product;
    $post_id                        = $post_id;
    $count                      = 1;
    $mep_events_linked_extra_prices    = get_post_meta($post_id, 'mep_events_linked_extra_prices', true) ? get_post_meta($post_id, 'mep_events_linked_extra_prices', true) : array();
    $event_date                 = get_post_meta($post_id, 'event_start_date', true) . ' ' . get_post_meta($post_id, 'event_start_time', true);
    $mep_available_seat         = get_post_meta($post_id, 'mep_available_seat', true) ? get_post_meta($post_id, 'mep_available_seat', true) : 'on';
    ob_start();

    if (sizeof($mep_events_linked_extra_prices) > 0) {
      require(mep_extra_service_template_file_path('single/linked_extra_service_list.php'));
    }
    $content = ob_get_clean();
    $event_meta = get_post_custom($post_id);
    echo apply_filters('mage_event_linked_extra_service_list', $content, $post_id, $event_meta, $event_date);
  }
}
if (!function_exists('mep_cart_event_linked_extra_service')) {
  function mep_cart_event_linked_extra_service($type, $total_price, $product_id)
  {
    $mep_event_ticket_type      = get_post_meta($product_id, 'mep_event_ticket_type', true) ? get_post_meta($product_id, 'mep_event_ticket_type', true) : array();

    $mep_event_start_date_es        = isset($_POST['mep_event_start_date_es']) ? mage_array_strip($_POST['mep_event_start_date_es']) : array();
    $extra_service_name             = isset($_POST['linked_event_extra_service_name']) ? mage_array_strip($_POST['linked_event_extra_service_name']) : array();
    $extra_service_qty              = isset($_POST['linked_event_extra_service_qty']) ? mage_array_strip($_POST['linked_event_extra_service_qty']) : array();
    $extra_service_price            = isset($_POST['linked_event_extra_service_price']) ? mage_array_strip($_POST['linked_event_extra_service_price']) : array();
    $extra_service_price            = $mep_event_ticket_type[0]["option_price_t"];
    $event_extra                    = [];

    if ($extra_service_name) {
      for ($i = 0; $i < count($extra_service_name); $i++) {
        if ($extra_service_qty[$i] > 0) {
          $event_extra[$i]['service_name']    = !empty($extra_service_name[$i]) ? stripslashes(strip_tags($extra_service_name[$i])) : '';
          $event_extra[$i]['service_price']   = !empty($extra_service_price[$i]) ? stripslashes(strip_tags($extra_service_price[$i])) : '';
          $event_extra[$i]['service_qty']     = !empty($extra_service_qty[$i]) ? stripslashes(strip_tags($extra_service_qty[$i])) : '';
          $event_extra[$i]['event_date']      = !empty($mep_event_start_date_es[$i]) ? stripslashes(strip_tags($mep_event_start_date_es[$i])) : '';
          $extprice                           = ((float) $extra_service_price[$i] * (float) $extra_service_qty[$i]);
          $total_price                        = ((float) $total_price + (float) $extprice);
        }
      }
    }
    if ($type == 'ticket_price') {
      return $total_price;
    } else {
      return $event_extra;
    }
  }
}

function mep_add_custom_fields_text_to_cart_item2($cart_item_data, $product_id, $variation_id)
{
  $cart_item_data['pr_field'] = "test";
  return $cart_item_data;
  /*
  $linked_event_id   = get_post_meta($product_id, 'link_mep_event', true) ? get_post_meta($product_id, 'link_mep_event', true) : $product_id;
  $product_id        = mep_product_exists($linked_event_id) ? $linked_event_id : $product_id;
  $recurring         = get_post_meta($product_id, 'mep_enable_recurring', true) ? get_post_meta($product_id, 'mep_enable_recurring', true) : 'no';
  error_log(get_post_type($product_id));
  if (get_post_type($product_id) == 'mep_events') {
    $total_price            = get_post_meta($product_id, '_price', true);
    $form_position          = mep_get_option('mep_user_form_position', 'general_attendee_sec', 'details_page');
    $mep_event_start_date   = isset($_POST['mep_event_start_date']) ? mage_array_strip($_POST['mep_event_start_date']) : array();
    $event_cart_location    = isset($_POST['mep_event_location_cart']) ? sanitize_text_field($_POST['mep_event_location_cart']) : array();
    $event_cart_date        = isset($_POST['mep_event_date_cart']) ? mage_array_strip($_POST['mep_event_date_cart']) : array();
    $recurring_event_date   = $recurring == 'yes' && isset($_POST['recurring_event_date']) ? mage_array_strip($_POST['recurring_event_date']) : array();
    $ticket_type_arr        = mep_cart_ticket_type('ticket_type', $total_price, $product_id);
    $total_price            = mep_cart_ticket_type('ticket_price', $total_price, $product_id);
    $event_extra            = mep_cart_event_extra_service('event_extra_service', $total_price, $product_id);
    //$event_linkend_extra    = mep_cart_event_linked_extra_service('linked_event_extra_service', $total_price, $product_id);
    $transportation    = mep_cart_event_transportation('event_extra_service', $total_price, $product_id);
    $transportation_total = 0;
    foreach ($transportation as $key => $value) {
      $transportation_total += (float)$value['service_price'];
    }
    $total_price            = $transportation_total + mep_cart_event_extra_service('ticket_price', $total_price, $product_id);
    $user                   = $form_position == 'details_page' ? mep_save_attendee_info_into_cart($product_id) : array();
    $validate               = mep_cart_ticket_type('validation_data', $total_price, $product_id);
    $time_slot_text = isset($_REQUEST['time_slot_name']) ? sanitize_text_field($_REQUEST['time_slot_name']) : '';
    if (!empty($time_slot_text)) {
      $cart_item_data['event_everyday_time_slot']  = $time_slot_text;
    }

    $cart_item_data['event_ticket_info']        = $ticket_type_arr;
    $cart_item_data['event_validate_info']      = $validate;
    $cart_item_data['event_user_info']          = $user;
    $cart_item_data['event_tp']                 = $total_price;
    $cart_item_data['line_total']               = $total_price;
    $cart_item_data['line_subtotal']            = $total_price;
    $cart_item_data['event_extra_service']      = array_merge($event_extra, $transportation);
    $cart_item_data['event_cart_location']      = $event_cart_location;
    $cart_item_data['event_cart_date']          = $mep_event_start_date[0];
    $cart_item_data['event_recurring_date']     = array_unique($recurring_event_date);
    $cart_item_data['event_recurring_date_arr'] = $recurring_event_date;
    $cart_item_data['event_cart_display_date']  = $mep_event_start_date[0];
    do_action('mep_event_cart_data_reg');

    $cart_item_data['event_id']                 = $product_id;
    return apply_filters('mep_event_cart_item_data', $cart_item_data, $product_id, $total_price, $user, $ticket_type_arr, $event_extra);
  } else {
    return $cart_item_data;
  }*/
}
// add_filter('woocommerce_add_cart_item_data', 'mep_add_custom_fields_text_to_cart_item2', 99, 3);

//add_action('woocommerce_review_order_before_payment', 'action_function_name_873');
function action_function_name_873()
{
  global $woocommerce;
  $items = $woocommerce->cart->get_cart();
  foreach ($items as $item => $values) {
    $product_id = $values['data']->get_id();
    $age_restriction = get_field('age_restriction', $product_id);
    if ($age_restriction) {
      echo '<p><strong>Grade Restriction: </strong>' . $age_restriction . '</p>';
    }

    $_product =  wc_get_product($values['data']->get_id());
    $term_ids = wp_get_post_terms($values['data']->get_id(), "", array('fields' => 'ids'));
  }
  if (wc_notice_count('error') == 0) {
    wc_add_notice(__('Your error message'), 'error');
  }
}

function custom_checkout_field_process()
{
  wc_add_notice(__('Please enter the value for the custom field'), 'error');
  //if (!$_POST['afreg_additional_2126']) wc_add_notice(__('Please enter the value for the custom field'), 'error');
}

add_action('woocommerce_checkout_create_order ', 'custom_checkout_field_process', 90);

add_action('woocommerce_after_checkout_validation', 'age_validation', 9999, 2);
function age_validation($fields, $errors)
{
  $key = 'afreg_additional_2124';
  $gender = $_POST[$key];
  if (!isset($gender)) {
    $user_id = get_current_user_id();
    $single = true;
    $gender = get_user_meta($user_id, $key, $single);
  }
  $gender = strtoupper($gender);
  $key = 'afreg_additional_2125';
  $birthdate = $_POST[$key];
  if (!isset($birthdate)) {
    $user_id = get_current_user_id();
    $single = true;
    $birthdate = get_user_meta($user_id, $key, $single);
  }
  $key_grade = 'afreg_additional_2126';
  $grade = $_POST[$key_grade];
  if (!isset($grade)) {
    $user_id = get_current_user_id();
    $single = true;
    $grade = get_user_meta($user_id, $key, $single);
  }
  $date = new DateTime($birthdate);
  $now = new DateTime();
  $interval = $now->diff($date);
  $age = $interval->y;
  global $woocommerce;
  $items = $woocommerce->cart->get_cart();
  $age_error = false;
  $grade_error = false;
  $gender_error = false;
  foreach ($items as $item => $values) {
    $product_id = $values['data']->get_id();
    $age_restriction = get_field('age_restriction', $product_id);
    if (isset($age_restriction)) {
      $conditional = get_field('age_conditional', $product_id);
      $age_restric = get_field('age', $product_id);
      /*if (isset($item["event_user_info"][0]["age-resident-camp"])) {
        $age = $item["event_user_info"][0]["age-resident-camp"];
      }*/
      if ($conditional == '<') {
        if (!((int)$age < (int)$age_restric)) {
          $age_error = true;
        }
      } else if ($conditional == '>') {
        if (!((int)$age > (int)$age_restric)) {
          $age_error = true;
        }
      } else if ($conditional == '>=') {

        if (!((int)$age >= (int)$age_restric)) {
          $age_error = true;
        }
      } else if ($conditional == '<=') {
        if (!((int)$age <= (int)$age_restric)) {
          $age_error = true;
        }
      }
    }
    $grade_restriction = get_field('grade_restriction', $product_id);
    if (isset($grade_restriction)) {
      $conditional = get_field('grade_conditional', $product_id);
      $grade_restric = get_field('grade', $product_id);
      if (isset($item["event_user_info"][0]["[grade_resident_camp"])) {
        $grade = $item["event_user_info"][0]["[grade_resident_camp"];
      }
      if ($conditional == '<') {
        if (!((int)$grade < (int)$grade_restric)) {
          $grade_error = true;
        }
      } else if ($conditional == '>') {
        if (!((int)$grade > (int)$grade_restric)) {
          $grade_error = true;
        }
      } else if ($conditional == '>=') {

        if (!((int)$grade >= (int)$grade_restric)) {
          $grade_error = true;
        }
      } else if ($conditional == '<=') {
        if (!((int)$grade <= (int)$grade_restric)) {
          $grade_error = true;
        }
      }
    }
  }

  // filter if one of product has "Week Camp Program" category
  $week_camp_program = false;
  $session_already_added = false;
  $count_week_programs = 0;
  $sessions = [];
  foreach ($items as $item => $values) {
    $product_id = $values['data']->get_id();
    $terms = get_the_terms($product_id, 'product_cat');
    $is_week_camp_program = false;
    $product_session = null;
    foreach ($terms as $term) {
      if ($term->slug == 'week-camp-program') {
        $count_week_programs++;
        $is_week_camp_program = true;
        if ($count_week_programs > 1) {
          $week_camp_program = true;
        }
      }
      $term_name = $term->name;
      $term_name = explode(" ", $term_name);
      if (in_array("Session", $term_name)) {
        $product_session = $term_name[1];
      }
    }
    if ($is_week_camp_program && $product_session) {
      $sessions[] = $product_session;
      $session_already_added = count($sessions) !== count(array_unique($sessions));
    }
  }


  if ($age_error) {
    $errors->add('woocommerce_password_error', __('You are not old enough to purchase this product.'));
  }

  if ($grade_error) {
    $errors->add('woocommerce_password_error', __('You are not correct grade to purchase this product.'));
  }

  if ($gender_error) {
    $errors->add('woocommerce_password_error', __('You are not correct gender to purchase this product.'));
  }

  if ($session_already_added) {
    $repeated_session = array_unique(array_diff_assoc($sessions, array_unique($sessions)));
    $repeated_session = $repeated_session[array_key_first($repeated_session)];
    $errors->add('woocommerce_password_error', __('You can only purchase one week camp program session ' . $repeated_session[0] . ' at a time.'));
  }
}
if (!function_exists('mep_custom_template_file_path')) {
  function mep_custom_template_file_path($file_name)
  {
    $template_path = get_stylesheet_directory() . '/mage-events/';
    $default_path = plugin_dir_path(__DIR__) . 'templates/';

    $thedir = is_dir($template_path) ? $template_path : $default_path;
    $themedir = $thedir . $file_name;
    $the_file_path = locate_template(array('mage-events/' . $file_name)) ? $themedir : $default_path . $file_name;
    return $the_file_path;
  }
}


/*
* Event List Custom
*/

/** 
 * The Magical & The Main Event Listing Shortcode is Here, You can check the details with demo here https://wordpress.org/plugins/mage-eventpress/
 */
// add_shortcode('event-list-custom', 'mep_event_list_custom');
/*
function mep_event_list_custom($atts, $content = null)
{
  $defaults = array(
    "cat"           => "0",
    "org"           => "0",
    "style"         => "grid",
    "column"        => 3,
    "cat-filter"    => "no",
    "org-filter"    => "no",
    "show"          => "-1",
    "pagination"    => "no",
    "pagination-style"    => "load_more",
    "city"          => "",
    "country"       => "",
    "carousal-nav"  => "no",
    "carousal-dots" => "yes",
    "carousal-id" => "102448",
    "timeline-mode" => "vertical",
    'sort'          => 'ASC',
    'status'        => 'upcoming',
    'search-filter' => '',
    'title-filter' => 'yes',
    'category-filter' => 'yes',
    'organizer-filter' => 'yes',
    'city-filter' => 'yes',
    'date-filter' => 'yes'
  );
  $params         = shortcode_atts($defaults, $atts);
  $cat            = $params['cat'];
  $org            = $params['org'];
  $style          = $params['style'];
  $cat_f          = $params['cat-filter'];
  $org_f          = $params['org-filter'];
  $show           = $params['show'];
  $pagination     = $params['pagination'];
  $sort           = $params['sort'];
  $column         = $style != 'grid' ? 1 : $params['column'];
  $nav            = $params['carousal-nav'] == 'yes' ? 1 : 0;
  $dot            = $params['carousal-dots'] == 'yes' ? 1 : 0;
  $city           = $params['city'];
  $country        = $params['country'];
  $cid            = $params['carousal-id'];
  $status            = $params['status'];

  $filter = $params['search-filter'];
  $show = ($filter == 'yes' || $pagination == 'yes' && $style != 'timeline') ? -1 : $show;

  $main_div       = $pagination == 'carousal' ? '<div class="mage_grid_box owl-theme owl-carousel"  id="mep-carousel' . $cid . '">' : '<div class="mage_grid_box">';

  $time_line_div_start    = $style == 'timeline' ? '<div class="timeline"><div class="timeline__wrap"><div class="timeline__items">' : '';
  $time_line_div_end      = $style == 'timeline' ? '</div></div></div>' : '';

  $flex_column    = $column;
  $mage_div_count = 0;
  $event_expire_on = mep_get_option('mep_event_expire_on_datetimes', 'general_setting_sec', 'event_start_datetime');
  $unq_id = 'abr' . uniqid();
  ob_start();
  $loop =  mep_event_query_custom($show, $sort, $cat, $org, $city, $country, $status);
  ?>
  <div class='list_with_filter_section mep_event_list'>
    <?php if ($cat_f == 'yes') {
      do_action('mep_event_list_cat_names', $cat, $unq_id);
    }
    if ($org_f == 'yes') {
      do_action('mep_event_list_org_names', $org, $unq_id);
    }
    if ($filter == 'yes' && $style != 'timeline') {
      do_action('mpwem_list_with_filter_section', $loop, $params);
    }
    ?>

    <div class="all_filter_item mep_event_list_sec" id='mep_event_list_<?php echo esc_attr($unq_id); ?>'>
      <?php
      $total_item = $loop->post_count;
      echo wp_kses_post($main_div);
      echo wp_kses_post($time_line_div_start);
      while ($loop->have_posts()) {
        $loop->the_post();
        mep_update_event_upcoming_date(get_the_id());
        mep_update_event_upcoming_date(get_the_id());
        if ($style == 'grid' && (int)$column > 0 && $pagination != 'carousal') {
          $columnNumber = 'column_style';
          $width = 100 / (int)$column;
        } elseif ($pagination == 'carousal' && $style == 'grid') {
          $columnNumber = 'grid';
          $width = 100;
        } else {
          $columnNumber = 'one_column';
          $width = 100;
        }
        do_action('mep_event_list_shortcode', get_the_id(), $columnNumber, $style, $width, $unq_id);
      }
      wp_reset_postdata();
      echo wp_kses_post($time_line_div_end);
      ?>
    </div>
  </div>
  <?php
  do_action('mpwem_pagination', $params, $total_item);
  ?>
  </div>
  <script>
    jQuery(document).ready(function() {
      var containerEl = document.querySelector('#mep_event_list_<?php echo esc_attr($unq_id); ?>');
      var mixer = mixitup(containerEl, {
        selectors: {
          target: '.mep-event-list-loop',
          control: '[data-mixitup-control]'
        }
      });
      <?php if ($pagination == 'carousal') { ?>
        jQuery('#mep-carousel<?php echo esc_attr($cid); ?>').owlCarousel({
          autoplay: <?php echo mep_get_option('mep_autoplay_carousal', 'carousel_setting_sec', 'true'); ?>,
          autoplayTimeout: <?php echo mep_get_option('mep_speed_carousal', 'carousel_setting_sec', '5000'); ?>,
          autoplayHoverPause: true,
          loop: <?php echo mep_get_option('mep_loop_carousal', 'carousel_setting_sec', 'true'); ?>,
          margin: 20,
          nav: <?php echo esc_attr($nav); ?>,
          dots: <?php echo esc_attr($dot); ?>,
          responsiveClass: true,
          responsive: {
            0: {
              items: 1,
            },
            600: {
              items: 2,
            },
            1000: {
              items: <?php echo esc_attr($column); ?>,
            }
          }
        });
      <?php } ?>
      <?php do_action('mep_event_shortcode_js_script', $params); ?>
    });
  </script>
  <?php
  $content = ob_get_clean();
  return $content;
}*/

/*
/* Transportations
*/

if (!function_exists('mea_ev_transportations_serv')) {
  function mea_ev_transportations_serv($post_id, $extra_service_label)
  {
    global $post, $event_meta;
    $post_id                        = $post_id;
    $event_date                 = get_post_meta($post_id, 'event_start_date', true) . ' ' . get_post_meta($post_id, 'event_start_time', true);
    $event_meta = get_post_custom($post_id);
    $has_transportation = get_field("transportation_validation", $post_id);
    if ($has_transportation) {
      ob_start();
      require(mep_extra_service_template_file_path('single/transportation_service_list.php'));
      $content = ob_get_clean();
      echo apply_filters('mage_event_transportation_service_list', $content, $post_id, $event_meta, $event_date);
    }
  }
}
// add_action('mep_event_extra_service', 'mea_ev_transportations_serv', 10, 2);


if (!function_exists('mep_cart_event_transportation')) {
  function mep_cart_event_transportation($type, $total_price, $product_id)
  {
    $mep_event_ticket_type      = get_post_meta($product_id, 'mep_event_ticket_type', true) ? get_post_meta($product_id, 'mep_event_ticket_type', true) : array();

    $mep_event_start_date_es        = isset($_POST['mep_event_start_date_es']) ? mage_array_strip($_POST['mep_event_start_date_es']) : array();
    $transportation_pickup_service            = isset($_POST['transportation_pickup_service']) ? mage_array_strip($_POST['transportation_pickup_service']) : array();
    $transportation_pickup_service_name            = isset($_POST['transportation_pickup_name']) ? mage_array_strip($_POST['transportation_pickup_name']) : '';
    $transportation_dropoff_service            = isset($_POST['transportation_dropoff_service']) ? mage_array_strip($_POST['transportation_dropoff_service']) : array();
    $transportation_dropoff_service_name            = isset($_POST['transportation_dropoff_name']) ? mage_array_strip($_POST['transportation_dropoff_name']) : '';
    $event_extra                    = [];
    if ($transportation_pickup_service[0] > 0) {
      $event_extra[0]['service_name']    = 'To Camp - ' . $transportation_pickup_service_name;
      $event_extra[0]['service_price']   = !empty($transportation_pickup_service[0]) ? stripslashes(strip_tags($transportation_pickup_service[0])) : '';
      $event_extra[0]['service_qty']     = '1';
      $total_price                        = $total_price + (float) $transportation_pickup_service[0];
    }
    if ($transportation_dropoff_service[0] > 0) {
      $event_extra[1]['service_name']    = 'To Home - ' . $transportation_dropoff_service_name;
      $event_extra[1]['service_price']   = !empty($transportation_dropoff_service[0]) ? stripslashes(strip_tags($transportation_dropoff_service[0])) : '';
      $event_extra[1]['service_qty']     = '1';
      $total_price                        = $total_price + (float) $transportation_dropoff_service[0];
    }
    if ($type == 'ticket_price') {
      return $total_price;
    } else {
      return $event_extra;
    }
  }
}
/*
add_action("wp_ajax_check_cart_gender", "check_cart_gender");
function check_cart_gender()
{
  if (!wp_verify_nonce($_REQUEST['nonce'], "check_cart_gender_nonce")) {
    exit("No naughty business please");
  }
  global $woocommerce;
  $items = $woocommerce->cart->get_cart();




  $vote_count = get_post_meta($_REQUEST["post_id"], "votes", true);
  $vote_count = ($vote_count == ’) ? 0 : $vote_count;
  $new_vote_count = $vote_count + 1;
  $vote = update_post_meta($_REQUEST["post_id"], "votes", $new_vote_count);
  if ($vote === false) {
    $result['type'] = "error";
    $result['vote_count'] = $vote_count;
  } else {
    $result['type'] = "success";
    $result['vote_count'] = $new_vote_count;
  }

  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $result = json_encode($result);
    echo $result;
  } else {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  }

  die();
}
*/






/*
* Custon condition visibiliti camps

$product_id = 1559;
if(!is_user_logged_in()){return true;}
$age_error = false;
$user_id = get_current_user_id();
$single = true;
$key = 'afreg_additional_2125';
$birthdate = get_user_meta($user_id, $key, $single);

$date = new DateTime($birthdate);
$now = new DateTime();
$interval = $now->diff($date);
$age = $interval->y;
$age_restriction = get_field('age_restriction', $product_id);
if (isset($age_restriction)) {
  $conditional = get_field('age_conditional', $product_id);
  $age_restric = get_field('age', $product_id);
  if ($conditional == '<') {
    if (!((int)$age < (int)$age_restric)) {
      $age_error = true;
    }
  } else if ($conditional == '>') {
    if (!((int)$age > (int)$age_restric)) {
      $age_error = true;
    }
  } else if ($conditional == '>=') {

    if (!((int)$age >= (int)$age_restric)) {
      $age_error = true;
    }
  } else if ($conditional == '<=') {
    if (!((int)$age <= (int)$age_restric)) {
      $age_error = true;
    }
  }
}
return !$age_error;
*/

add_action('update_users_age', '_update_users_age');
function _update_users_age()
{
  $users = get_users();
  foreach ($users as $user) {
    $user_id = $user->ID;
    $single = true;
    $key = 'afreg_additional_2125';
    $birthdate = get_user_meta($user_id, $key, $single);
    if ($birthdate) {
      $date = new DateTime($birthdate);
      $now = new DateTime();
      $interval = $now->diff($date);
      $age = $interval->y;
      $age_user_meta = get_user_meta($user_id, 'age', $single);
      if ($age_user_meta) {
        update_user_meta($user_id, 'age', $age);
      } else {
        add_user_meta($user_id, 'age', $age);
      }
    }
  }
}

add_action('user_register', 'update_age_registration_save', 90);
add_action('personal_options_update', 'update_age_registration_save', 90);
add_action('edit_user_profile_update', 'update_age_registration_save', 90);
add_action('woocommerce_save_account_details', 'update_age_registration_save', 90);
function update_age_registration_save()
{
  _update_users_age();
}

function multistep_form_shortcode()
{
  $step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : '';
  if (isset($_COOKIE['is_manager'])) {
    $cookie_value_manager = sanitize_text_field($_COOKIE['is_manager']);
    $cookie_parts = explode('|', $cookie_value_manager);
    if (count($cookie_parts) !== 4) {
      return '';
    }
    $user_session_token = $cookie_parts[2];
    $is_or_was_manager = get_transient('sfwc_is_or_was_manager_' . $user_session_token);
  }
  if (isset($cookie_value_manager) && ($cookie_value_manager !== $is_or_was_manager) && !isset($cookie_value_supervisor)) {
    wp_logout();
  }
  if (!is_user_logged_in()) {
    return multistep_form_step_one();
  } else {
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $children_ids = get_user_meta($user_id, 'sfwc_children', true);
    $existing_children_ids = array();
    if (!empty($children_ids)) {
      foreach ($children_ids as $single_id) {
        $user_exists = get_userdata($single_id);
        if ($user_exists !== false) {
          if (sfwc_is_user_role_valid($single_id) && sfwc_is_user_role_enabled($single_id)) {
            $existing_children_ids[] = $single_id;
          }
        }
      }
    }
    $user_account_level_type = get_user_meta($user_id, 'sfwc_account_level_type', true);
    // POST add_new_child
    $add_new_child = isset($_POST['add_new_child']) ? sanitize_text_field($_POST['add_new_child']) : '';
    wc_print_notices();
    if (
      $user_account_level_type === 'manager' &&
      ($step == 'add_camper' ||
        empty($existing_children_ids) ||
        $add_new_child == 'yes')
    ) {
      return campanokijig_add_camper_section();
    } else if (
      $user_account_level_type != 'manager' &&
      !isset($step)
    ) {
      return campanokijig_registration_form_section();
    } else if (
      validate_all_required_fields_of_profile() &&
      $user_account_level_type != 'manager' &&
      !isset($step)
    ) {
      return multistep_form_step_two();
    } else {
      if (
        $step == "registration_form"
        && $user_account_level_type != 'manager'
      ) {
        return campanokijig_registration_form_section();
      } else if (
        $step == "add_sessions" &&
        validate_all_required_fields_of_profile() &&
        $user_account_level_type != 'manager'
      ) {
        return multistep_form_step_two();
      } else {
        campanokijig_select_camper_section($user_account_level_type, $existing_children_ids, $user_id);
      }
    }
  }
}

function campanokijig_select_camper_section($user_account_level_type, $existing_children_ids, $user_id)
{
  $current_user = wp_get_current_user();
  $sfwc_options = (array) get_option('sfwc_options');
  $sfwc_option_selected_roles = (isset($sfwc_options['sfwc_option_selected_roles'])) ? $sfwc_options['sfwc_option_selected_roles'] : array('customer', 'subscriber');
  $args_supervisor = array(
    'role__in' => $sfwc_option_selected_roles,
    'orderby' => 'ID',
    'order' => 'ASC',
    'meta_key' => 'sfwc_account_level_type',
    'meta_value' => 'manager',
    'meta_query' => array(
      array(
        'key' => 'sfwc_children',
        'value' => '"' . $user_id . '"',
        'compare' => 'LIKE',
      ),
    ),
  );
  $user_query_supervisor = new WP_User_Query($args_supervisor);
  $supervisor_id = $user_query_supervisor->get_results()[0]->ID;

  echo ccampanikijig_steps('select_camper');
  $sfwc_options = (array) get_option('sfwc_options');
  $sfwc_option_display_name = (isset($sfwc_options['sfwc_option_display_name'])) ? $sfwc_options['sfwc_option_display_name'] : 'username';
  $sfwc_switcher_appearance = (array) get_option('sfwc_switcher_appearance');
  $sfwc_switcher_pane_bg_color = (isset($sfwc_switcher_appearance['sfwc_switcher_pane_bg_color'])) ? $sfwc_switcher_appearance['sfwc_switcher_pane_bg_color'] : '#def6ff';
  $sfwc_switcher_pane_headline_color = (isset($sfwc_switcher_appearance['sfwc_switcher_pane_headline_color'])) ? $sfwc_switcher_appearance['sfwc_switcher_pane_headline_color'] : '#0088cc';
  $sfwc_switcher_pane_text_color = (isset($sfwc_switcher_appearance['sfwc_switcher_pane_text_color'])) ? $sfwc_switcher_appearance['sfwc_switcher_pane_text_color'] : '#3b3b3b';
  $sfwc_switcher_pane_select_bg_color = (isset($sfwc_switcher_appearance['sfwc_switcher_pane_select_bg_color'])) ? $sfwc_switcher_appearance['sfwc_switcher_pane_select_bg_color'] : '#0088cc';
  $sfwc_switcher_pane_select_text_color = (isset($sfwc_switcher_appearance['sfwc_switcher_pane_select_text_color'])) ? $sfwc_switcher_appearance['sfwc_switcher_pane_select_text_color'] : '#ffffff';
  echo '<div id="sfwc-user-switcher-pane" style="background-color:' . esc_attr($sfwc_switcher_pane_bg_color) . ';">';
  echo '<h3 style="color:' . esc_attr($sfwc_switcher_pane_headline_color) . ';"><span style="font-size:18px">' . esc_html__('You are currently logged in as:', 'subaccounts-for-woocommerce') . '</span> ' . esc_html($current_user->user_login) . '</h3>';
?>
  <?php
  if ($user_account_level_type == 'manager') {
  ?>
    <h4 style="color:<?php echo esc_attr($sfwc_switcher_pane_headline_color); ?>;">Select camper</h4>
    <form method="post">
      <?php
      foreach ($existing_children_ids as $key => $value) {
      ?>
        <button type="submit" name="campanokijig_frontend_children" value="<?php echo esc_attr($value); ?>" style="background-color:<?php echo esc_attr($sfwc_switcher_pane_select_bg_color); ?>; color:<?php echo esc_attr($sfwc_switcher_pane_select_text_color); ?>; border:none; padding: 8px 20px; margin:20px; cursor:pointer; border-radius: 8px;"><?php echo esc_html(get_userdata($value)->user_login); ?></button>
      <?php
      } ?>
      <button type="submit" name="add_new_child" value="yes" style="background-color:<?php echo esc_attr($sfwc_switcher_pane_select_bg_color); ?>; color:<?php echo esc_attr($sfwc_switcher_pane_select_text_color); ?>; border:none; padding: 8px 20px; margin:20px; cursor:pointer; border-radius: 8px;">+ Add new camper</button>
      <input name="setc" value="submit" type="submit" style="display:none;">
    </form>
  <?php
  } else {
  ?>
    <form method="post">
      <input type="hidden" name="stay_here" value="true" />
      <button type="submit" name="campanokijig_frontend_children" value="<?php echo esc_attr($supervisor_id); ?>" style="background-color:<?php echo esc_attr($sfwc_switcher_pane_select_bg_color); ?>; color:<?php echo esc_attr($sfwc_switcher_pane_select_text_color); ?>; border:none; padding: 8px 20px; margin:20px; cursor:pointer; border-radius: 8px;">Select another camper</button>
      <input name="setc" value="submit" type="submit" style="display:none;">
    </form>
  <?php
  }
  echo '</div>';
}

function campanokijig_registration_form_section()
{
  echo ccampanikijig_steps('registration_form');
  $custom_edit_account_form = include(MPWEM_CUSTOM_PLUGIN_DIR . '/templates/myaccount/form-edit-account.php');
  return $custom_edit_account_form;
}

function campanokijig_add_camper_section()
{
  echo ccampanikijig_steps('add_camper');
  echo do_shortcode('[sfwc_add_subaccount_custom_shortcode]');
}

function filter_woocommerce_registration_redirect($redirect_to)
{
  $user_id = get_current_user_id();
  $user = get_user_by('id', $user_id);
  $user_login = $user->user_login;
  $user_password = $user->user_pass;
  $creds = array(
    'user_login'    => $user_login,
    'user_password' => $user_password,
    'remember'      => true
  );
  $user = wp_signon($creds, false);
  $user_parent_account_type = get_user_meta($user_id, 'sfwc_account_level_type', true);
  $list_of_children_for_all_managers = get_user_meta($user_id, 'sfwc_children', true);
  if (
    $user_parent_account_type == "default" ||
    $user_parent_account_type == ""  ||
    empty($list_of_children_for_all_managers)
  ) {
    update_user_meta($user_id, 'sfwc_account_level_type', 'manager');
  }
  if (is_wp_error($user)) {
    echo $user->get_error_message();
  }
  $current_page = $_SERVER['REQUEST_URI'];
  if (strpos($current_page, 'registration') > 0) {
    $redirect_to = '/registration\/';
  }
  return $redirect_to;
}
add_filter('woocommerce_registration_redirect', 'filter_woocommerce_registration_redirect', 999, 1);

// change on login custom redirect
add_filter('woocommerce_login_redirect', 'wc_login_redirect');
function wc_login_redirect($redirect_to)
{
  // get current page
  $current_page = $_SERVER['REQUEST_URI'];
  if ($current_page == '/registration') {
    $redirect_to = '/registration/?step=select_camper';
    return $redirect_to;
  }
  return $redirect_to;
}

// change on edit account custom redirect
add_filter('woocommerce_save_account_details', 'wc_save_account_details_redirect', 99, 1);
function wc_save_account_details_redirect($redirect_to)
{
  // get current page
  $current_page = $_SERVER['REQUEST_URI'];
  if (strpos($current_page, 'registration') > 0) {
    $redirect_to = '/registration/?step=add_sessions';
    wp_safe_redirect($redirect_to);
    exit();
  }
  wp_safe_redirect($redirect_to);
  exit();
}

add_filter('insert_user_meta', 'wbb_profile_update', 90, 3);
function wbb_profile_update($meta, $user, $update)
{
  if (true !== $update) return $meta;
  if (!is_checkout()) {
    $key_gender = 'afreg_additional_2124';
    $single = true;
    $oldgender = get_user_meta($user->ID, $key_gender, $single);
    if ($oldgender !== $_POST['afreg_additional_2124']) {
      // remove all camps from cart.
      $cart = WC()->cart;
      $cart_items = $cart->get_cart();
      foreach ($cart_items as $cart_item_key => $cart_item) {
        if ($cart_item["iscamp"]) {
          $cart->remove_cart_item($cart_item_key);
        }
      }
    }
  }
  return $meta;
}

remove_action('template_redirect', 'sfwc_add_subaccount_form_handler', 10);
add_action('template_redirect', 'sfwc_add_subaccount_redirect', 10);
function sfwc_add_subaccount_redirect()
{
  $current_page = $_SERVER['REQUEST_URI'];
  if (
    strpos($current_page, 'registration') > 0
  ) {
    sfwc_custom_add_subaccount_form_handler(true);
  }
  sfwc_custom_add_subaccount_form_handler();
}

function sfwc_custom_add_subaccount_form_handler($is_registration_flow = false)
{
  $sfwc_options = (array) get_option('sfwc_options');
  $sfwc_option_selected_roles = (isset($sfwc_options['sfwc_option_selected_roles'])) ? $sfwc_options['sfwc_option_selected_roles'] : array('customer', 'subscriber');
  $user_parent = get_current_user_id();
  $user_parent_data = get_userdata($user_parent);
  $user_login = ""; // For validation and sanitization see below
  $email = ""; // For validation and sanitization see below

  $first_name = isset($_POST['first_name']) && $_POST['first_name'] != "" ? sanitize_text_field($_POST['first_name']) : "";
  $last_name = isset($_POST['last_name']) && $_POST['last_name'] != "" ? sanitize_text_field($_POST['last_name']) : "";
  $company = isset($_POST['company']) && $_POST['company'] != "" ? sanitize_text_field($_POST['company']) : "";
  if (isset($_POST['user_login']) && $_POST['user_login'] == "") {
    wc_add_notice(esc_html__('Username is required.', 'subaccounts-for-woocommerce'), 'error');
  } elseif (isset($_POST['user_login']) && $_POST['user_login'] != "") {

    if (!validate_username($_POST['user_login'])) {
      wc_add_notice(esc_html__('Username is not valid.', 'subaccounts-for-woocommerce'), 'error');
    } else {
      $user_login = sanitize_user($_POST['user_login']);
    }
  }
  if ($is_registration_flow && isset($_POST['user_login'])) {
    $_POST['email'] = $_POST['user_login'] . '@campanokijig.org';
  }
  if (isset($_POST['email']) && $_POST['email'] == "") {
    wc_add_notice(esc_html__('Email is required.', 'subaccounts-for-woocommerce'), 'error');
  } elseif (isset($_POST['email']) && $_POST['email'] != "") {
    if (!is_email($_POST['email'])) {
      wc_add_notice(esc_html__('Email is not valid.', 'subaccounts-for-woocommerce'), 'error');
    } else {
      $email = sanitize_email($_POST['email']);
    }
  }
  if ((isset($user_login) && $user_login != "" && validate_username($user_login)) && (isset($email) && $email != "" && is_email($email))) {
    // Check if nonce is in place and verfy it.
    if (!isset($_POST['sfwc_add_subaccount_frontend']) || isset($_POST['sfwc_add_subaccount_frontend']) && !wp_verify_nonce($_POST['sfwc_add_subaccount_frontend'], 'sfwc_add_subaccount_frontend_action')) {
      wc_add_notice(esc_html__('Nonce could not be verified.', 'subaccounts-for-woocommerce'), 'error');
    } else {
      $password = wp_generate_password();
      $userinfo = array(
        'user_login' => $user_login,
        'user_email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'user_pass' => $password,
        //'role' => 'customer'			// Leave commented, this way 
        // Settings > General > New User Default Role 
        // will apply (E.g. customer || subscriber)
      );
      $default_user_role = get_option('default_role');
      if ($default_user_role !== 'customer' && $default_user_role !== 'subscriber') {
        $userinfo['role'] = 'customer';
      }
      $user_id = wp_insert_user($userinfo);
      if (!$user_id || is_wp_error($user_id)) {
        wc_add_notice($user_id->get_error_message(), 'error');
      } else {
        update_user_meta($user_id, 'billing_company', $company); // Sanitized @ 1225.
        wc_add_notice('<strong>' . esc_html__('Subaccount successfully added.', 'subaccounts-for-woocommerce') . '</strong><br>' . esc_html__('You can now switch to the newly added subaccount by selecting it from the drop-down menu.', 'subaccounts-for-woocommerce'), 'success');
        $already_children = get_user_meta($user_parent, 'sfwc_children', true);   // We need to get the value of the array and update it by adding the new ID,
        // otherwise array values which are already present will be overwritten and only the last ID will be added.
        // Check to see if thare are children already set...
        if (is_array($already_children) && !empty($already_children)) {
          array_push($already_children, (string)$user_id);
        } else {
          $already_children = array();
          $already_children[] = (string)$user_id;
        }
        update_user_meta($user_parent, 'sfwc_children', $already_children);
        $args_are_managers = array(
          //'role' => 'customer',
          //'role__in' => ['customer', 'subscriber'],
          'role__in' => $sfwc_option_selected_roles,
          'orderby' => 'ID',
          'order' => 'ASC',
          'meta_query' => array(
            array(
              'key' => 'sfwc_account_level_type',
              'value' => 'manager',
              'compare' => '=',
            ),
          ),
        );
        // The User Query
        $customers_are_managers = new WP_User_Query($args_are_managers);
        if (!empty($customers_are_managers->get_results())) {
          foreach ($customers_are_managers->get_results() as $user) {
            $list_of_children_for_single_user = get_user_meta($user->ID, 'sfwc_children', true);
            if (!empty($list_of_children_for_single_user)) {
              foreach ($list_of_children_for_single_user as $single_id) {
                $list_of_children_for_all_managers[] = $single_id;
              }
            }
          }
        }
        $user_parent_account_type = get_user_meta($user_parent, 'sfwc_account_level_type', true);
        if ((($user_parent_account_type == "default" || $user_parent_account_type == "") && !isset($list_of_children_for_all_managers)) ||
          (($user_parent_account_type == "default" || $user_parent_account_type == "") && (isset($list_of_children_for_all_managers) && is_array($list_of_children_for_all_managers) && !in_array($user_parent, $list_of_children_for_all_managers)))
        ) {
          update_user_meta($user_parent, 'sfwc_account_level_type', 'manager');
        }
        // Check account type of parent account.
        $check_account_type = get_user_meta($user_parent, 'sfwc_account_level_type', true);

        if ($check_account_type == 'supervisor') {

          update_user_meta($user_id, 'sfwc_account_level_type', 'manager');
        }
        do_action('sfwc_frontend_after_add_subaccount_validation', $user_id);
        $emails = WC()->mailer()->get_emails();

        // Send "Customer New Account" email notification.
        $emails['WC_Email_Customer_New_Account']->trigger($user_id, $password, true);
        if ($is_registration_flow) {
          wc_empty_cart();
          wp_clear_auth_cookie();
          wp_set_current_user($user_id);
          wp_set_auth_cookie($user_id);
          wc_setcookie('woocommerce_cart_hash', md5(json_encode(WC()->cart->get_cart())));
        }
        if (isset($user_id) && !is_wp_error($user_id)) {
          $_POST = array();
          if ($is_registration_flow) {
            $redirect_to = '/registration/?step=registration_form';
            wp_safe_redirect($redirect_to);
            die();
          }
        }
      }
    }
  }
}


// validate all profile required fields are filled.
function validate_all_required_fields_of_profile()
{
  // get all woocommerce edit account form required fields.
  $required_fields = wc_get_account_fields();
  $user_id = get_current_user_id();
  $user = get_user_by('id', $user_id);
  $user_meta = get_user_meta($user_id);
  $display_name = $user->display_name;
  $user_email = $user->user_email;
  // check if user required fields are filled.
  $user_required_fields = array();
  foreach ($required_fields as $required_field) {
    if (isset($user_meta[$required_field])) {
      $user_required_fields[$required_field] = $user_meta[$required_field];
    }
  }
  $user_required_fields = array_filter($user_required_fields);
  if (empty($display_name) || empty($user_email)) {
    return false;
  }
  if (count($user_required_fields) !== count($required_fields)) {
    return false;
  }
  return true;
}

function wc_get_account_fields()
{
  // retrieve all afreg required fields
  $afreg_args = array(
    'posts_per_page' => -1,
    'post_type' => 'afreg_fields',
    'post_status' => 'publish',
    'orderby' => 'menu_order',
    'suppress_filters' => false,
    'order' => 'ASC'
  );
  $afreg_extra_fields = get_posts($afreg_args);
  $required_fields = array(
    "first_name",
    "last_name"
  );
  if (!empty($afreg_extra_fields)) {
    foreach ($afreg_extra_fields as $afreg_field) {
      $afreg_field_required = get_post_meta(intval($afreg_field->ID), 'afreg_field_required', true);
      $afreg_field_show_in_registration_form = get_post_meta($afreg_field->ID, 'afreg_field_show_in_registration_form', true);
      if ($afreg_field_required == 'on') {
        $required_fields[] = 'afreg_additional_' . intval($afreg_field->ID);
      }
    }
  }
  return $required_fields;
}


function ccampanikijig_steps($current_step = '')
{
  $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : $current_step;
  // create a html stylised steps view
  $user_account_level_type = get_user_meta(get_current_user_id(), 'sfwc_account_level_type', true);
  $existing_children_ids = get_user_meta(get_current_user_id(), 'sfwc_children', true);
  $add_new_child = isset($_POST['add_new_child']) ? sanitize_text_field($_POST['add_new_child']) : '';
  $note = get_field('note', 'option');

  $steps = array(
    'add_camper' => [
      'title' => get_field('step_1', 'option'),
      'icon' => 'fas fa-user-plus',
      'number' => '1',
      'disabled' => $user_account_level_type != 'manager'
    ],
    'select_camper' => [
      'title' => get_field('step_2', 'option'),
      'icon' => 'fas fa-user',
      'number' => '2'
    ],
    'registration_form' => [
      'title' => get_field('step_3', 'option'),
      'icon' => 'fas fa-file-alt',
      'number' => '3',
      'disabled' => $user_account_level_type == 'manager'
    ],
    'add_sessions' => [
      'title' => get_field('step_4', 'option'),
      'icon' => 'fas fa-calendar-alt',
      'number' => '4',
      'disabled' => $user_account_level_type == 'manager' || !validate_all_required_fields_of_profile()
    ]
  );
  // on click same url with ster parameter in url.
  $html = '<div id="steps-registration" class="steps" style="display:flex">';
  foreach ($steps as $key => $value) {
    $html .= '<div data-disabled="' . ($value['disabled'] ? 'yes' : 'no') . '" data-step="' . $key . '" class="step ' . ($step == $key ? 'active' : '') . '">';
    $html .= '<div class="step-icon"><i class="' . $value['icon'] . '"></i></div>';
    $html .= '<div class="step-title">' . $value['title'] . '</div>';
    $html .= '<div class="step-number">' . $value['number'] . '</div>';
    $html .= '</div>';
  }
  $html .= '<div> <p><strong> Note:</strong> ' . $note . '</p>   </div>';
  $html .= '</div>';
  // jquery on click change url with step parameter
  $html .= '<script>
    jQuery(document).ready(function(){
      jQuery(".step").click(function(){
        var step = jQuery(this).attr("data-step");
        var disabled = jQuery(this).attr("data-disabled");
        var url = window.location.href;
        var url = url.split("?")[0];
        if(disabled == "yes"){
          return;
        }
        window.location.href = url + "?step=" + step;
      });
    });
  </script>';
  return $html;
}

function multistep_form_step_one()
{
  return separate_login_form();
}
add_shortcode('wc_login_form', 'separate_login_form');

function separate_login_form()
{
  global $wp;
  if (is_user_logged_in()) return '<p>You are already logged in</p>';
  ob_start();
  echo '<div style="display:flex" class="autentication-form-container">';
  echo '<div style="min-width:50%">';
  echo '<h2>' . get_field('login_title', 'option') . '</h2>';
  echo '<p>' . get_field('login_description', 'option') . '</p>';
  do_action('woocommerce_before_customer_login_form');
  $current_url =  home_url($wp->request);
  woocommerce_login_form(array('redirect' => $current_url));
  echo '</div>';
  echo '<div style="min-width:50%">';
  echo '<h2>' . get_field('register_title', 'option') . '</h2>';
  echo '<p>' . get_field('register_description', 'option') . '</p>';
  $html = wc_get_template_html('myaccount/form-login.php');
  $dom = new DOMDocument();
  $dom->encoding = 'utf-8';
  $dom->loadHTML(utf8_decode($html));
  $xpath = new DOMXPath($dom);
  $form = $xpath->query('//form[contains(@class,"register")]');
  $form = $form->item(0);
  echo $dom->saveXML($form);
  echo '</div>';
  echo '</div>';
  return ob_get_clean();
}

function render_input_program($camps_arr, $index, $wc_cart, $gender, $variable = false)
{
  ?>
  <?php
  foreach ($camps_arr as $key => $camp_item) {
    $product = null;
    $variations = null;
    $product_variation = null;
    $product_id = null;
    $variation_obj = null;
    $price = null;
    $stock = 0;
    $product = wc_get_product($camp_item->ID);
    $variable = $product->is_type('variable');
    if ($variable) {
      $product = new WC_Product_Variable($camp_item->ID);
      $variations = $product->get_available_variations();
      // find product by gender
      $product_variation = array_filter(
        $variations,
        function ($item) use ($gender) {
          return $item['attributes']['attribute_gender'] == $gender;
        }
      );
      if (count($product_variation) == 0) {
        continue;
      }
      $product_variation = reset($product_variation);
      $product_id = $product_variation['variation_id'];
      $deposit_amount = wc_deposits_get_product_deposit_amount($product_id);
      $variation_obj = new WC_Product_variation($product_id);
      $price = $variation_obj->get_price();
      // $start_date = get_field('start_date', $camp_item->ID);
      // $end_date = get_field('end_date', $camp_item->ID);
      $stock = $variation_obj->get_stock_quantity();
    } else {
      $product_id = $camp_item->ID;
      $product = new WC_Product($product_id);
      $deposit_amount = wc_deposits_get_product_deposit_amount($product_id);
      $price = $product->get_price();
      $stock = $product->get_stock_quantity();
    }
  ?>

    <input class="<?php echo $stock < 1 ? 'empty' : ''; ?>" <?php
                                                            if ($wc_cart) {
                                                              foreach ($wc_cart as $cart_item_key => $cart_item) {
                                                                if ($cart_item['product_id'] == $camp_item->ID) {
                                                                  echo 'checked';
                                                                }
                                                              }
                                                            }
                                                            ?> <?php echo $stock > 0 ? '' : 'disabled'; ?> type="radio" name="basic-program[<?php echo $index; ?>]" value="<?php echo esc_attr($product_id); ?>" id="<?php echo esc_attr($product_id); ?>" />
    <label class="<?php echo $stock > 0 ? '' : 'disabled'; ?>" for="<?php echo esc_attr($product_id); ?>">
      <span class="program-name"><?php echo esc_html($camp_item->post_title); ?></span>: <b class="program-price">$<?php echo esc_html($price); ?></b> <span class="deposit"><small>deposit: <b>$<span class="deposit-amount"><?php echo esc_html($deposit_amount); ?></span></b></small></span>
      - <small>left: <?php
                      if ($stock > 0) {
                        echo esc_html($stock);
                      } else {
                        $page = get_page_by_path('wait-list');
                        $page_url = 'https://www.anokijig.com/webforms/wait-list';
                        echo '<span>full <a href="' . $page_url . '" target="_blank">go to waitlist</a></span>';
                      }
                      ?>
      </small>
    </label><br />
  <?php
  }
}
function render_input_program_disabled($camps_arr, $index, $wc_cart, $gender, $variable = false)
{
  ?>
  <?php
  foreach ($camps_arr as $key => $camp_item) {
    $product = null;
    $variations = null;
    $product_variation = null;
    $product_id = null;
    $variation_obj = null;
    $price = null;
    $stock = 0;
    $product = new WC_Product($product_id);
    $variable = $product->is_type('variable');
    if ($variable) {
      $product = new WC_Product_Variable($camp_item->ID);
      $variations = $product->get_available_variations();
      $product_variation = array_filter(
        $variations,
        function ($item) use ($gender) {
          return $item['attributes']['attribute_gender'] == $gender;
        }
      );
      $product_variation = reset($product_variation);
      $product_id = $product_variation['variation_id'];
      $variation_obj = new WC_Product_variation($product_id);
      $price = $variation_obj->get_price();
      // $start_date = get_field('start_date', $camp_item->ID);
      // $end_date = get_field('end_date', $camp_item->ID);
      $stock = $variation_obj->get_stock_quantity();
    } else {
      $product_id = $camp_item->ID;
      $product = new WC_Product($product_id);
      $price = $product->get_price();
      $stock = $product->get_stock_quantity();
    }
  ?>
    <input disabled type="radio" />
    <label class="<?php echo $stock > 0 ? '' : 'disabled'; ?> text-muted" for="<?php echo esc_attr($product_id); ?>">
      <span class="program-name"><?php echo esc_html($camp_item->post_title); ?></span>: <b class="program-price">$<?php echo esc_html($price); ?></b>
      - <small class="text-danger">Not eligible</small>
    </label><br />
  <?php
  }
}
function render_input_add_program($camps_arr, $index, $wc_cart, $gender, $variable = false)
{
  foreach ($camps_arr as $key => $camp_item) {
    $product = null;
    $variations = null;
    $product_variation = null;
    $product_id = null;
    $variation_obj = null;
    $price = null;
    $stock = 0;
    $product = new WC_Product($product_id);
    $variable = $product->is_type('variable');
    if ($variable) {
      $product = new WC_Product_Variable($camp_item->ID);
      $variations = $product->get_available_variations();
      $product_variation = array_filter(
        $variations,
        function ($item) use ($gender) {
          return $item['attributes']['attribute_gender'] == $gender;
        }
      );
      $product_variation = reset($product_variation);
      $product_id = $product_variation['variation_id'];
      $variation_obj = new WC_Product_variation($product_id);
      $price = $variation_obj->get_price();
      // $start_date = get_field('start_date', $camp_item->ID);
      // $end_date = get_field('end_date', $camp_item->ID);
      $stock = $variation_obj->get_stock_quantity();
    } else {
      $product_id = $camp_item->ID;
      $product = new WC_Product($product_id);
      $price = $product->get_price();
      $stock = $product->get_stock_quantity();
    }
  ?>
    <input class="<?php echo $stock < 1 ? 'empty' : ''; ?>" <?php
                                                            if ($wc_cart) {
                                                              foreach ($wc_cart as $cart_item_key => $cart_item) {
                                                                foreach ($cart_item["addons"] as $addon_item_key => $addon) {
                                                                  if ($addon['product_id'] == $product_id) {
                                                                    echo 'checked';
                                                                  }
                                                                }
                                                              }
                                                            }
                                                            ?> <?php echo $stock > 0 ? '' : 'disabled'; ?> type="checkbox" name="program[<?php echo $index; ?>]" value="<?php echo esc_attr($product_id); ?>" id="<?php echo esc_attr($product_id); ?>" />
    <label class="<?php echo $stock > 0 ? '' : 'disabled'; ?>" for="<?php echo esc_attr($product_id); ?>">
      <span class="add-program-name"><?php echo esc_html($camp_item->post_title); ?></span> <b class="add-program-price">$<?php echo esc_html($price); ?></b>
      - <small>left: <?php
                      if ($stock > 0) {
                        echo esc_html($stock);
                      } else {
                        //wp get permalink of page by slug.
                        $page = get_page_by_path('wait-list');
                        $page_url = 'https://www.anokijig.com/webforms/wait-list';
                        echo '<span>full <a href="' . $page_url . '" target="_blank">go to waitlist</a></span>';
                      }
                      ?>
      </small>
    </label><br />
  <?php
  }
}
function render_input_add_program_disabled($camps_arr, $index, $wc_cart, $gender, $variable = false)
{
  foreach ($camps_arr as $key => $camp_item) {
    $product = null;
    $variations = null;
    $product_variation = null;
    $product_id = null;
    $variation_obj = null;
    $price = null;
    $stock = 0;
    $product = new WC_Product($product_id);
    $variable = $product->is_type('variable');
    if ($variable) {
      $product = new WC_Product_Variable($camp_item->ID);
      $variations = $product->get_available_variations();
      $product_variation = array_filter(
        $variations,
        function ($item) use ($gender) {
          return $item['attributes']['attribute_gender'] == $gender;
        }
      );
      $product_variation = reset($product_variation);
      $product_id = $product_variation['variation_id'];
      $variation_obj = new WC_Product_variation($product_id);
      $price = $variation_obj->get_price();
      // $start_date = get_field('start_date', $camp_item->ID);
      // $end_date = get_field('end_date', $camp_item->ID);
      $stock = $variation_obj->get_stock_quantity();
    } else {
      $product_id = $camp_item->ID;
      $product = new WC_Product($product_id);
      $price = $product->get_price();
      $stock = $product->get_stock_quantity();
    }
  ?>
    <input disabled type="checkbox" />
    <label class="<?php echo $stock > 0 ? '' : 'disabled'; ?> text-muted" for="<?php echo esc_attr($product_id); ?>">
      <span class="add-program-name"><?php echo esc_html($camp_item->post_title); ?></span> <b class="add-program-price">$<?php echo esc_html($price); ?></b>
      - <small class="text-danger">Not eligible</small>
    </label><br />
  <?php
  }
}

function render_selects_transportation($camps_arr, $index, $wc_cart)
{
  $froms = array();
  $tos = array();
  foreach ($camps_arr as $key => $camp_item) {
    $product = null;
    $variations = null;
    $product_variation_to = null;
    $product_variation_from = null;
    $variation_obj = null;
    $price = null;
    $stock = 0;
    $product = new WC_Product_Variable($camp_item->ID);
    $product_id = $product->get_id();
    $variations = $product->get_available_variations();
    $product_variation_to = array_filter(
      $variations,
      function ($item) {
        return $item['attributes']['attribute_pa_transportation'] == 'to';
      }
    );
    $product_variation_from = array_filter(
      $variations,
      function ($item) {
        return $item['attributes']['attribute_pa_transportation'] == 'from';
      }
    );
    $product_variation_to = reset($product_variation_to);
    $product_variation_from = reset($product_variation_from);
    $product_id_from = $product_variation_from['variation_id'];
    $product_id_to = $product_variation_to['variation_id'];
    $variation_obj_to = new WC_Product_variation($product_id_to);
    $variation_obj_from = new WC_Product_variation($product_id_from);
    $froms[] = [
      'id' => $product_id_from,
      'product_id' => $product_id,
      'name' => $variation_obj_from->get_name(),
      'price' => $variation_obj_from->get_price(),
      'stock' => $variation_obj_from->get_stock_quantity()
    ];
    $tos[] = [
      'id' => $product_id_to,
      'product_id' => $product_id,
      'name' => $variation_obj_to->get_name(),
      'price' => $variation_obj_to->get_price(),
      'stock' => $variation_obj_to->get_stock_quantity()
    ];
  }
  ?>
  <div class="row">
    <div class="col-12 col-md-6">
      <p><b>Transportation To Camp</b></p>
      <select name="transportation_pickup_service" id="transportation_pickup_service">
        <option value="">Not transport</option>
        <?php foreach ($tos as $key => $item) { ?>
          <option <?php
                  if ($wc_cart) {
                    foreach ($wc_cart as $cart_item_key => $cart_item) {
                      foreach ($cart_item["addons"] as $addon_item_key => $addon) {
                        if ($addon['product_id'] == $item['id'] && $addon['type'] == 'pickup_service') {
                          echo 'selected';
                          break;
                        }
                      }
                    }
                  }
                  ?> value="<?php echo $item['id']; ?>" data-price="<?php echo esc_attr($item['price']); ?>" data-name="<?php echo esc_attr($item['name']); ?>">
            <?php echo esc_html($item['name']); ?> - $<?php echo esc_html($item['price']); ?>
          </option>
        <?php } ?>
      </select>
    </div>
    <div class="col-12 col-md-6">
      <p><b>Transportation From Camp</b></p>
      <select name="transportation_dropoff_service" id="transportation_dropoff_service">
        <option value="">Not transport</option>
        <?php foreach ($froms as $key => $item) { ?>
          <option <?php
                  if ($wc_cart) {
                    foreach ($wc_cart as $cart_item_key => $cart_item) {
                      foreach ($cart_item["addons"] as $addon_item_key => $addon) {
                        if ($addon['product_id'] == $item['id'] && $addon['type'] == 'dropoff_service') {
                          echo 'selected';
                          break;
                        }
                      }
                    }
                  }
                  ?> value="<?php echo $item['id']; ?>" data-price="<?php echo esc_attr($item['price']); ?>" data-name="<?php echo esc_attr($item['name']); ?>">
            <?php echo esc_html($item['name']); ?> - $<?php echo esc_html($item['price']); ?>
          </option>
        <?php } ?>
      </select>
    </div>
  </div>
<?php
}
function multistep_form_step_two()
{
  echo ccampanikijig_steps('add_sessions');
  $key_gender = 'afreg_additional_2124';
  $user_id = get_current_user_id();
  $single = true;
  $gender = get_user_meta($user_id, $key_gender, $single);
  $key_birthdate = 'afreg_additional_2125';
  $birthdate = get_user_meta($user_id, $key_birthdate, $single);
  $key_grade = 'afreg_additional_2127';
  $grade = get_user_meta($user_id, $key_grade, $single);
  $date = new DateTime($birthdate);
  $now = new DateTime();
  $interval = $now->diff($date);
  $age = $interval->y;
  if (isset(WC()->cart)) {
    $wc_cart = WC()->cart->get_cart();
  }
  function validate_age($age, $product_id)
  {
    $age_restriction = get_field('age_restriction', $product_id);
    $age_error = false;
    if (isset($age_restriction)) {
      $conditional = get_field('age_conditional', $product_id);
      $age_restric = get_field('age', $product_id);
      if ($conditional == '<') {
        if (!((int)$age < (int)$age_restric)) {
          $age_error = true;
        }
      } else if ($conditional == '>') {
        if (!((int)$age > (int)$age_restric)) {
          $age_error = true;
        }
      } else if ($conditional == '>=') {
        if (!((int)$age >= (int)$age_restric)) {
          $age_error = true;
        }
      } else if ($conditional == '<=') {
        if (!((int)$age <= (int)$age_restric)) {
          $age_error = true;
        }
      }
    }
    return !$age_error;
  }
  function validate_grade($grade, $product_id)
  {
    $grade_restriction = get_field('grade_restriction', $product_id);
    $grade_error = false;
    if (isset($grade_restriction)) {
      $conditional = get_field('grade_conditional', $product_id);
      $grade_restric = get_field('grade', $product_id);
      if ($conditional == '<') {
        if (!((int)$grade < (int)$grade_restric)) {
          $grade_error = true;
        }
      } else if ($conditional == '>') {
        if (!((int)$grade > (int)$grade_restric)) {
          $grade_error = true;
        }
      } else if ($conditional == '>=') {

        if (!((int)$grade >= (int)$grade_restric)) {
          $grade_error = true;
        }
      } else if ($conditional == '<=') {
        if (!((int)$grade <= (int)$grade_restric)) {
          $grade_error = true;
        }
      }
    }
    return !$grade_error;
  }
  ob_start();
?>
  <div class="container">
    <div class="row">
      <div class="col-8">
        <form id="multistep-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="multistep_form">
          <input type="hidden" name="step" value="3">
          <h4 class="h4">Add Sessions</h4>
          <?php
          //get CPT seasons
          $args = array(
            'post_type' => 'session',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
          );
          $seasons = new WP_Query($args);
          $index_session = 0;
          if ($seasons->have_posts()) :
            while ($seasons->have_posts()) : $seasons->the_post();
              $season_id = get_the_ID();
              $season_name = get_the_title();

          ?>
              <div class="accordion" id="accordion-<?php echo $season_id; ?>">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $season_id; ?>" aria-expanded="true" aria-controls="collapse-<?php echo $season_id; ?>">
                      <?php echo $season_name; ?>
                    </button>
                  </h2>
                  <div id="collapse-<?php echo $season_id; ?>" class="accordion-collapse collapse show" data-bs-parent="#accordion-<?php echo $season_id; ?>">
                    <div class="accordion-body">
                      <?php
                      $camps = get_field('camps');
                      // do a for of acf relational cpt camps
                      $general_camp_programs = [];
                      $specialty_camp_programs = [];
                      $half_day_trips = [];
                      $full_day_trips = [];
                      $stayover = [];
                      $general_camp_programs_disabled = [];
                      $specialty_camp_programs_disabled = [];
                      $half_day_trips_disabled = [];
                      $full_day_trips_disabled = [];
                      $stayover_disabled = [];
                      $transportation = [];
                      foreach ($camps as $key => $camp) {
                        $product_id = $camp->ID;
                        $camps[$key]->product_id = $product_id;
                        $get_terms_camp = get_the_terms($camp->ID, 'product_cat');
                        if (
                          validate_age($age, $product_id) &&
                          validate_grade($grade, $product_id)
                        ) {
                          // if includes "general-camp-programs" category slug
                          if (is_array($get_terms_camp) && in_array('general-camp-programs', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $general_camp_programs[] = $camp;
                          }
                          // if includes "specialty-camp-programs" category slug
                          if (is_array($get_terms_camp) && in_array('specialty-camp-programs', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $specialty_camp_programs[] = $camp;
                          }
                          // if includes "half-day-trips" category slug
                          if (is_array($get_terms_camp) && in_array('half-day-trips', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $half_day_trips[] = $camp;
                          }
                          // if includes "full-day-trips" category slug
                          if (is_array($get_terms_camp) && in_array('full-day-trips', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $full_day_trips[] = $camp;
                          }
                          // if includes "stayover" category slug
                          if (is_array($get_terms_camp) && in_array('stayover', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $stayover[] = $camp;
                          }
                        } else {
                          $get_terms_camp = get_the_terms($camp->ID, 'product_cat');
                          if (is_array($get_terms_camp) && in_array('general-camp-programs', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $general_camp_programs_disabled[] = $camp;
                          }
                          if (is_array($get_terms_camp) && in_array('specialty-camp-programs', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $specialty_camp_programs_disabled[] = $camp;
                          }
                          if (is_array($get_terms_camp) && in_array('half-day-trips', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $half_day_trips_disabled[] = $camp;
                          }
                          if (is_array($get_terms_camp) && in_array('full-day-trips', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $full_day_trips_disabled[] = $camp;
                          }
                          if (is_array($get_terms_camp) && in_array('stayover', wp_list_pluck($get_terms_camp, 'slug'))) {
                            $stayover_disabled[] = $camp;
                          }
                        }
                        if (is_array($get_terms_camp) && in_array('transportation', wp_list_pluck($get_terms_camp, 'slug'))) {
                          $transportation[] = $camp;
                        }
                      }
                      ?>
                      <?php if (count($general_camp_programs) > 0 || count($specialty_camp_programs) > 0 || count($general_camp_programs_disabled) > 0 || count($specialty_camp_programs_disabled) > 0) { ?>
                        <h5 class="h5">Camp Programs</h5>
                        <p><b><?php echo get_field('deposit_option_label', 'option'); ?></b></p>
                        <?php
                        $deposit = true;
                        if ($wc_cart) {
                          foreach ($general_camp_programs as $camp_item) {
                            foreach ($wc_cart as $cart_item_key => $cart_item) {
                              if ($cart_item['product_id'] == $camp_item->ID) {
                                if (isset($cart_item['deposit'])) {
                                  if ($cart_item['deposit']['enable'] == 'yes') {
                                    $deposit = true;
                                  }
                                  if ($cart_item['deposit']['enable'] == 'no') {
                                    $deposit = false;
                                  }
                                }
                              }
                            }
                          }
                        }
                        ?>
                        <input type="radio" name="deposit[<?php echo $index_session; ?>]" class="deposit-checkbox" value="yes" id="deposit-yes-[<?php echo $index_session; ?>]" data-session="<?php echo $season_id; ?>" <?php echo $deposit ? 'checked' : ''; ?> />
                        <label for="deposit-yes-[<?php echo $index_session; ?>]" style="margin-right:15px">
                          <?php echo get_field('deposit_yes_label', 'option'); ?></b>
                        </label>
                        <input type="radio" name="deposit[<?php echo $index_session; ?>]" class="deposit-checkbox" value="no" id="deposit-no-[<?php echo $index_session; ?>]" data-session="<?php echo $season_id; ?>" <?php echo $deposit ? '' : 'checked'; ?> />
                        <label for="deposit-no-[<?php echo $index_session; ?>]">
                          <?php echo get_field('deposit_no_label', 'option'); ?></b>
                        </label><br />

                        <p>Select one of the available options from the General Camp Program, Specialty Camp Program, or Weeklong Adventure Trip Programs.</p>
                        <input type="radio" name="basic-program[<?php echo $index_session; ?>]" class="no-program" value="" id="empty-program[<?php echo $index_session; ?>]" checked />
                        <label for="empty-program[<?php echo $index_session; ?>]">
                          No program</b>
                        </label><br />

                        <?php
                        if (count($general_camp_programs) > 0 || count($general_camp_programs_disabled) > 0) { ?>
                          <p class="mb-1 mt-3"><b>General Camp Programs</b></p>
                          <?php render_input_program($general_camp_programs, $index_session, $wc_cart, $gender, true); ?>
                          <?php render_input_program_disabled($general_camp_programs_disabled, $index_session, $wc_cart, $gender, true); ?>
                        <?php } ?>

                        <?php if (count($specialty_camp_programs) > 0 || count($specialty_camp_programs_disabled) > 0) { ?>
                          <p class="mb-1 mt-3"><b>Specialty Camp Programs</b></p>
                          <?php render_input_program($specialty_camp_programs, $index_session, $wc_cart, $gender, true); ?>
                          <?php render_input_program_disabled($specialty_camp_programs_disabled, $index_session, $wc_cart, $gender, true); ?>

                        <?php } ?>
                      <?php }
                      ?>

                      <?php if (count($half_day_trips) > 0 || count($full_day_trips) > 0 || count($half_day_trips_disabled) > 0 || count($full_day_trips_disabled) > 0) { ?>
                        <h5 class="h5">Day Trips</h5>
                        <p>Pick as many available half-day and full-day trips as are available.</p>
                        <?php if (count($half_day_trips) > 0 || count($half_day_trips_disabled) > 0) { ?>
                          <p class="mb-1 mt-3"><b>Half-Day Trips</b></p>
                          <?php render_input_add_program($half_day_trips, $index_session, $wc_cart, $gender); ?>
                          <?php render_input_add_program_disabled($half_day_trips_disabled, $index_session, $wc_cart, $gender); ?>
                        <?php } ?>
                        <?php if (count($full_day_trips) > 0 || count($full_day_trips_disabled) > 0) { ?>
                          <p class="mb-1 mt-3"><b>Full-Day Trips</b></p>
                          <?php render_input_add_program($full_day_trips, $index_session, $wc_cart, $gender); ?>
                          <?php render_input_add_program_disabled($full_day_trips_disabled, $index_session, $wc_cart, $gender); ?>
                        <?php } ?>
                      <?php } ?>

                      <?php if (count($stayover) > 0 || count($stayover_disabled) > 0) { ?>
                        <h5 class="h5">Weekend Stayover</h5>
                        <p>You can opt in for a camper to stay Saturday night. For more information about Weekend Stayover, call us</p>
                        <p class="mb-1 mt-3"><b>Stayover options</b></p>
                        <?php render_input_add_program($stayover, $index_session, $wc_cart, $gender, true); ?>
                        <?php render_input_add_program_disabled($stayover_disabled, $index_session, $wc_cart, $gender, true); ?>
                      <?php } ?>
                      <h5 class="h5 mt-3">Transportation</h5>
                      <p>Please select optional transportation to camp, from camp, or both.</p>
                      <?php if (count($transportation) > 0) {
                        render_selects_transportation($transportation, $index_session, $wc_cart);
                      } ?>
                    </div>
                  </div>
                </div>
              </div>
          <?php
              $index_session++;
            endwhile;
          endif;
          wp_reset_postdata();

          ?>
          <!-- Loading indicator -->
          <div class="mb-4"></div>
        </form>
      </div>
      <div class="col-4">
        <div class="card p-3 bg-white">
          <h4 class="h4 mb-3">Sessions Summary</h4>
          <!-- loading element -->
          <div id="sessions-summary"></div>
          <div class="row justify-content-end mt-4">
            <div class="col-auto">
              <button id="add-tocart-next" type="submit">
                <div class="d-flex">
                  <div class="loading-indicator">
                    <img src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" alt="Loading...">
                  </div>
                  <span class="ml-3">Review and pay</span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Create script JQuery for update sessions summary with programe name, price has parent of add-programs and transportation pickup and dropoff. add total to final -->
    <script>
      (function($) {
        "use strict";
        jQuery(document).ready(function($) {
          //hide loading
          $('.loading-indicator').hide();
          // Create script JQuery for update sessions summary area getting by class add-programs and transportation pickup and dropoff and add total to final.
          function updateSessionsSummary() {
            var sessionsSummary = $('#sessions-summary');
            var basicProgram = $('input[name^="basic-program"]:checked').not('.no-program');
            var total = 0;
            basicProgram.each((index, program) => {
              var product_id = $(program).val();
              var label = $(program).siblings('label[for="' + product_id + '"]');
              var programName = $(label).find('.program-name').text();
              var isDeposit = $(program).closest('.accordion-body').find('.deposit-checkbox:checked').val() == 'yes';
              var deposit = $(label).find('.deposit .deposit-amount').text();
              var programPrice = $(label).find('.program-price').text().replace('$', '');
              if (programName) {
                sessionsSummary.append('<div class="row"><div class="col">' + programName + '</div><div class="col-auto">$' + Number(programPrice).toFixed(2) + '</div>');
              }
              console.log(deposit)
              if (isDeposit) {
                sessionsSummary.append('<div class="row px-2 " style="font-size:14px; margin-bottom: 15px; color: #888888"><div class="col">Deposit</div><div class="col-auto">$' + Number(deposit).toFixed(2) + '</div>');
              }
              var parentContainer = $(program).parents('.accordion-body');
              var addPrograms = parentContainer.find('input[name^="program"]:checked');
              addPrograms.each((index, addProgram) => {
                var product_id = $(addProgram).val();
                var label = $(addProgram).siblings('label[for="' + product_id + '"]');
                var addProgramName = $(label).find('.add-program-name').text();
                var addProgramPrice = $(label).find('.add-program-price').text().replace('$', '');
                sessionsSummary.append('<div class="row px-2" style="font-size:14px"><div class="col">' + addProgramName + '</div><div class="col-auto">$' + Number(addProgramPrice).toFixed(2) + '</div>');
              });
              var pickupService = parentContainer.find('select[name="transportation_pickup_service"] option:selected');
              var pickupServiceName = pickupService.attr('data-name');
              var pickupServicePrice = pickupService.attr('data-price');
              if (pickupServiceName) {
                sessionsSummary.append('<p class="px-2 mb-0 mt-2" style="font-size:15px">Transportation To Camp:</p>');
                sessionsSummary.append('<div class="row px-2" style="font-size:14px"><div class="col">' + pickupServiceName + '</div><div class="col-auto">$' + Number(pickupServicePrice).toFixed(2) + '</div>');
              }
              var dropoffService = parentContainer.find('select[name="transportation_dropoff_service"] option:selected');
              var dropoffServiceName = dropoffService.attr('data-name');
              var dropoffServicePrice = dropoffService.attr('data-price');
              if (dropoffServiceName) {
                sessionsSummary.append('<p class="px-2 mb-0 mt-2" style="font-size:15px">Transportation From Camp:</p>');
                sessionsSummary.append('<div class="row px-2" style="font-size:14px"><div class="col">' + dropoffServiceName + '</div><div class="col-auto">$' + Number(dropoffServicePrice).toFixed(2) + '</div>');
              }

              total += programPrice ? parseFloat(programPrice?.replace("$", "")) : 0;
              total += addPrograms.toArray().reduce((total, addProgram) => {
                var product_id = $(addProgram).val();
                var label = $(addProgram).siblings('label[for="' + product_id + '"]');
                var addProgramPrice = $(label).find('.add-program-price').text();
                return total + parseFloat(addProgramPrice.replace('$', ''));
              }, 0);
              if (pickupServiceName) {
                total = total + parseFloat(pickupServicePrice);
              }
              if (dropoffServiceName) {
                total = total + parseFloat(dropoffServicePrice);
              }
              if (programName) {
                sessionsSummary.append('<hr class="my-3">');
              }
            });
            sessionsSummary.append('<div class="row h6"><div class="col"><b>Sessions Total</b></div><div class="col-auto"><b>$' + total.toFixed(2) + '</b></div>');
          }
          // create function if basic program is empty disable addons and transportation.
          function disableAddonsAndTransportation() {
            var basicProgram = $('input[name^="basic-program"]:checked');
            if (basicProgram.length < 1) {
              $('input[name^="program"]').prop('disabled', true);
              $('select[name="transportation_pickup_service"]').prop('disabled', true);
              $('select[name="transportation_dropoff_service"]').prop('disabled', true);
            }
            basicProgram.each((index, program) => {
              var product_id = $(program).val();
              var label = $(program).siblings('label[for="' + product_id + '"]');
              var programName = $(label).find('.program-name').text();
              var programPrice = $(label).find('.program-price').text();
              if (!programName) {
                $(program).parents('.accordion-body').find('input[name^="program"][class!="empty"]').prop('disabled', true);
                $(program).parents('.accordion-body').find('select[name="transportation_pickup_service"]').prop('disabled', true);
                $(program).parents('.accordion-body').find('select[name="transportation_dropoff_service"]').prop('disabled', true);
                // set unselected inputs
                $(program).parents('.accordion-body').find('input[name^="program"][class!="empty"]').prop('checked', false);
                $(program).parents('.accordion-body').find('select[name="transportation_pickup_service"]').prop('selectedIndex', 0);
                $(program).parents('.accordion-body').find('select[name="transportation_dropoff_service"]').prop('selectedIndex', 0);
              } else {
                $(program).parents('.accordion-body').find('input[name^="program"][class!="empty"]').prop('disabled', false);
                $(program).parents('.accordion-body').find('select[name="transportation_pickup_service"]').prop('disabled', false);
                $(program).parents('.accordion-body').find('select[name="transportation_dropoff_service"]').prop('disabled', false);
              }
            });
          }
          // create function to clean all inputs
          function cleanAllInputs() {
            $('input[name^="basic-program"]').prop('checked', false);
            $('input[id^="empty-program"]').prop('checked', true);
            $('input[id^="empty-program"]').each((index, element) => {
              $(element).click();
            })
            $('input[name^="program"]').prop('checked', false);
            $('select[name="transportation_pickup_service"]').prop('selectedIndex', 0);
            $('select[name="transportation_dropoff_service"]').prop('selectedIndex', 0);
          }

          function closeAccordion() {
            var accordionButtons = document.querySelectorAll('.accordion-button');
            var accordionCollapse = document.querySelectorAll('.accordion-collapse');
            for (var i = 0; i < accordionButtons.length; i++) {
              accordionButtons[i].classList.add('collapsed');
            }
            for (var i = 0; i < accordionCollapse.length; i++) {
              accordionCollapse[i].classList.remove('show');
            }
          }

          function updateDeposits() {
            var deposits = $('input[name^="deposit"]:checked');
            deposits.each((index, deposit) => {
              var sessionID = $(deposit).attr('data-session');
              var value = $(deposit).val();
              if (value === 'yes') {
                $('#accordion-' + sessionID + ' span.deposit').show();
              } else {
                $('#accordion-' + sessionID + ' span.deposit').hide();
              }
            });
          }
          updateDeposits();

          // Update sessions summary when change basic program
          $('input[name^="basic-program"]').change(function() {
            $('#sessions-summary').html('');
            disableAddonsAndTransportation();
            updateSessionsSummary();
            ajaxToSaveCart();
          });

          $('input[name^="deposit"]').change(function() {
            let sessionID = $(this).attr('data-session');
            let value = $(this).val();
            if (value === 'yes') {
              $('#accordion-' + sessionID + ' span.deposit').show();
            } else {
              $('#accordion-' + sessionID + ' span.deposit').hide();
            }

            $('#sessions-summary').html('');
            updateSessionsSummary();
            ajaxToSaveCart();
          });
          // Update sessions summary when change add program
          $('input[name^="program"]').change(function() {
            $('#sessions-summary').html('');
            updateSessionsSummary();
            ajaxToSaveCart();
          });
          // Update sessions summary when change transportation pickup service
          $('select[name="transportation_pickup_service"]').change(function() {
            $('#sessions-summary').html('');
            updateSessionsSummary();
            ajaxToSaveCart();
          });
          // Update sessions summary when change transportation dropoff service
          $('select[name="transportation_dropoff_service"]').change(function() {
            $('#sessions-summary').html('');
            updateSessionsSummary();
            ajaxToSaveCart();
          });
          /*cleanAllInputs();
          window.addEventListener('unload', function(event) {
            cleanAllInputs();
          }, false);*/
          updateSessionsSummary();
          disableAddonsAndTransportation();
          closeAccordion();
        });

        function ajaxToSaveCart(goCheckout = false) {
          var products = [];
          var basicProgram = $('input[name^="basic-program"]:checked');
          basicProgram.each((index, program) => {
            var product_id = $(program).val();
            var label = $(program).siblings('label[for="' + product_id + '"]');
            var isDeposit = $(program).closest('.accordion-body').find('.deposit-checkbox:checked').val() == 'yes';
            var depositPrice = $(label).find('.deposit .deposit-amount').text();
            var programName = $(label).find('.program-name').text();
            var programPrice = $(label).find('.program-price').text();
            if (programName) {
              var basic_program = {
                product_id: product_id,
                quantity: 1,
                [product_id + '-deposit-radio']: isDeposit ? 'deposit' : 'full',
                price: parseFloat(programPrice?.replace('$', '')) || 0,
                isDeposit: isDeposit,
                depositPrice: parseFloat(depositPrice) || 0,
                addons: [],
                transportation: []
              };
              var addPrograms = $(program).parents('.accordion-body').find('input[name^="program"]:checked');
              addPrograms.each((index, addProgram) => {
                var product_id = $(addProgram).val();
                var label = $(addProgram).siblings('label[for="' + product_id + '"]');
                var addProgramName = $(label).find('.add-program-name').text();
                var addProgramPrice = $(label).find('.add-program-price').text();
                var add_program = {
                  product_id: product_id,
                  product_name: addProgramName,
                  type: "add_program",
                  quantity: 1,
                  price: addProgramPrice?.replace('$', '') || 0
                };
                basic_program.addons.push(add_program);
              });
              var pickupService = $(program).parents('.accordion-body').find('select[name="transportation_pickup_service"] option:selected');
              var pickupServiceName = pickupService.attr('data-name');
              var pickupServicePrice = pickupService.attr('data-price');
              if (pickupServiceName) {
                var pickup_service = {
                  product_id: pickupService.val(),
                  product_name: pickupServiceName,
                  type: "pickup_service",
                  quantity: 1,
                  price: pickupServicePrice
                };
                basic_program.transportation.push(pickup_service);
              }
              var dropoffService = $(program).parents('.accordion-body').find('select[name="transportation_dropoff_service"] option:selected');
              var dropoffServiceName = dropoffService.attr('data-name');
              var dropoffServicePrice = dropoffService.attr('data-price');
              if (dropoffServiceName) {
                var dropoff_service = {
                  product_id: dropoffService.val(),
                  product_name: dropoffServiceName,
                  type: "dropoff_service",
                  quantity: 1,
                  price: dropoffServicePrice
                };
                basic_program.transportation.push(dropoff_service);
              }
              products.push(basic_program);
            }
          });
          console.log(" products ", products)
          $(".loading-indicator").show();
          $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
              action: 'add_cart_sessions',
              products: products
            },
            success: function(data) {
              const response = JSON.parse(data);
              if (response.error) {
                alert(response.message);
              } else {
                if (goCheckout) window.location.href = '<?php echo get_permalink(get_page_by_path('checkout')); ?>';
              }
            },
            error: function(error) {
              console.log(error);
            },
            complete: function() {
              $(".loading-indicator").hide();
            }
          });
        }
        // ajax add to cart items.
        $("#add-tocart-next").on('click', function(e) {
          e.preventDefault();
          ajaxToSaveCart(true);
        })
      })(jQuery)
    </script>
  </div>
<?php
  return ob_get_clean();
}


function multistep_form_step_three()
{
  // Display fourth step
  ob_start();
?>
<?php
  return ob_get_clean();
}

function multistep_form_handler()
{
  if (isset($_POST['step'])) {
    $step = intval($_POST['step']);
    $response = array("html" => "", "step" => $step);
    switch ($step) {
      case 2:
        $response['html'] = multistep_form_step_two();
        break;
      case 3:
        $response['html'] = multistep_form_step_three();
        break;
      case 4:
        // Process payment
        $response['html'] = 'Payment processed.';
        break;
      default:
        // Invalid step
        $response['html'] = 'Invalid step.';
        break;
    }
    wp_send_json($response);
  } else {
    // No step specified
    return multistep_form_step_two();
  }
}
add_shortcode('multistep_form', 'multistep_form_shortcode');
add_action('wp_ajax_multistep_form', 'multistep_form_handler');

add_action('wp_ajax_add_cart_sessions', 'prefix_ajax_add_cart_sessions');
add_action('wp_ajax_nopriv_add_cart_sessions', 'prefix_ajax_add_cart_sessions');
function prefix_ajax_add_cart_sessions()
{
  // get cart and have isCamp = true remove.
  $cart = WC()->cart->get_cart();
  foreach ($cart as $key => $item) {
    $title = $item['data']->get_title();
    if (isset($item['iscamp']) && $item['iscamp'] == true) {
      $removeItem = WC()->cart->remove_cart_item($key);
    }
  }
  $products = $_POST['products'];
  $validation = true || validate_one_program_per_session($products);
  if ($validation) {
    foreach ($products as $session => $product) {
      if (isset($product) && !empty($product)) {
        $product_id = $product['product_id'];
        $quantity = $product['quantity'];
        $variation_id = 0;
        $variation = [];
        $cart_item_data = [
          "custom_price" => $product['price'],
          "session" => $session,
          "iscamp" => true,
          "isPrincipal" => true
        ];
        $cart_item_data_addon = [
          "session" => $session,
          "iscamp" => true,
          "isPrincipal" => false
        ];
        if ($product['isDeposit'] == 'true') {
          $depositCamp = $product['depositPrice'];
          $depositPrice = $product['depositPrice'];
          $addons_total = 0;
          foreach ($product['addons'] as $addon) {
            // $depositPrice += $addon['price'];
            $addons_total += $addon['price'];
          }
          foreach ($product['transportation'] as $addon) {
            // $depositPrice += $addon['price'];
            $addons_total += $addon['price'];
          }
          $cart_item_data['deposit-registration'] = array(
            'enable' => 'yes',
            'deposit' => $depositPrice,
            'deposit-camp' => $depositCamp,
            'remaining' => $product['price'] + $addons_total - $depositPrice,
            'total' => $product['price'] + $addons_total,
            'tax_total' => 0,
            'tax' => 0,
            'payment_schedule' => [
              [
                'timestamp' => 'unlimited',
                'amount' => $product['price'] + $addons_total - $depositPrice,
                'tax' => 0
              ]
            ]
          );
        } else {
          $cart_item_data['deposit-registration'] = array(
            'enable' => 'no'
          );
        }
        foreach ($product['addons'] as $addon) {
          $cart_item_data['addons'][] = $addon;
        }
        foreach ($product['transportation'] as $addon) {
          $cart_item_data['addons'][] = $addon;
        }
        $cart_item_data['addons'][] = [
          'product_name' => 'Deposit Amount',
          'type' => 'deposit',
          'quantity' => 1,
          'price' => $depositCamp
        ];
        // check if product id is in cart and remove before re-add
        WC()->cart->add_to_cart($product_id, 1, $variation_id, $variation, $cart_item_data);
        foreach ($product['addons'] as $addon) {
          WC()->cart->add_to_cart($addon['product_id'], 1, $variation_id, $variation, $cart_item_data_addon);
        }
        foreach ($product['transportation'] as $addon) {
          WC()->cart->add_to_cart($addon['product_id'], 1, $variation_id, $variation, $cart_item_data_addon);
        }
      }
    }
    /*echo '<pre>';
    print_r(WC()->cart->get_cart());
    echo '</pre>';
    die();*/
    $jsonSuccess = array(
      'success' => true,
      'message' => 'Product added to cart successfully.'
    );
    die(json_encode($jsonSuccess));
  } else {
    // return json with error message.
    $jsonErr = array(
      'error' => true,
      'message' => 'You can only add one program per session to cart.'
    );
    die(json_encode($jsonErr));
  }
}

// pass addons data to order
add_action('woocommerce_checkout_create_order_line_item', 'add_custom_order_line_item_meta_data', 20, 4);
function add_custom_order_line_item_meta_data($item, $cart_item_key, $values, $order)
{
  // add custom_price
  $item->add_meta_data('custom_price', $values['custom_price'], true);
  if (isset($values['addons'])) {
    $addons = $values['addons'];
    $add_programs = [];
    foreach ($addons as $addon) {
      if ($addon['type'] == 'add_program') {
        $add_programs[] = $addon;
      } else {
        $item->add_meta_data($addon['type'], [
          'product_id' => $addon['product_id'],
          'product_name' => $addon['product_name'],
          'quantity' => $addon['quantity'],
          'price' => $addon['price']
        ], true);
      }
    }
    if (count($add_programs) > 0) {

      $item->add_meta_data('add_program', $add_programs, true);
    }
  }
}

add_filter('woocommerce_checkout_cart_item_quantity', 'twf_display_custom_quantity', 1, 3);
function twf_display_custom_quantity($product_name, $values, $cart_item_key)
{
  return '';
}
// add_filter('woocommerce_checkout_cart_item_name', 'twf_display_custom_data_in_cart', 1, 3);
// add_filter('woocommerce_cart_item_name', 'twf_display_custom_data_in_cart', 1, 3);
// add_filter('woocommerce_order_item_name', 'twf_display_custom_data_in_cart', 1, 3);

function twf_display_custom_data_in_cart($product_name, $values, $cart_item_key)
{
  global $wpdb;
  //echo '<pre>';
  //print_r($values);
  //echo '</pre>';
  if (!empty($values['addons'])) {
    $return_string = "<p>" . $product_name . " x " . $values['quantity'] . "</p><div style='width:100%; font-size:12px'>";
    $return_string .= "<div style='padding: 0px;line-height: 20px; width:100%; position:relative;'>
                          <div style='padding: 0px; color:#777777;'>" . get_the_title($values['product_id']) . "</div>
                          <div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>" . number_format($values['custom_price'], 2, '.', ',') . "</div>
                        </div>
      ";
    for ($i = 0; $i < count($values['addons']); $i++) {
      $return_string .= "<div style='padding: 0px;line-height: 20px; width:100%; position:relative;'>";
      $type_string = "";
      if ($values['addons'][$i]['type'] == 'pickup_service') {
        $type_string = "Transportation To Camp: ";
      } else if ($values['addons'][$i]['type'] == 'dropoff_service') {
        $type_string = "Transportation From Camp: ";
      }
      $return_string .= "<div style='padding: 0px 50px 0px 0px; color:#777777;'>" . $type_string . $values['addons'][$i]['product_name'] . "</div>";
      if ($values['addons'][$i]['type'] == 'deposit') {
        $return_string .= "<div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>($" . number_format(((float)$values['addons'][$i]['price'] * (int)$values['addons'][$i]['quantity']), 2, '.', ',') . ")</div>";
      } else {
        $return_string .= "<div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>$" . number_format(((float)$values['addons'][$i]['price'] * (int)$values['addons'][$i]['quantity']), 2, '.', ',') . "</div>";
      }
      $return_string .= "</div>";
    }
    $return_string .= "</div>";
    return $return_string;
  } else {
    if (is_object($values) && method_exists($values, 'get_meta_data')) {
      $meta_data = $values->get_meta_data();
      $meta_data_add_programs = [];
      $meta_data_pickup_service = [];
      $meta_data_dropoff_service = [];
      $meta_data_custom_price = [];
      $meta_data_deposit = false;
      foreach ($meta_data as $key => $meta) {
        if ($meta->key == 'add_program') {
          $meta_data_add_programs = $meta;
        }
        if ($meta->key == 'pickup_service') {
          $meta_data_pickup_service = $meta;
        }
        if ($meta->key == 'dropoff_service') {
          $meta_data_dropoff_service = $meta;
        }
        if ($meta->key == 'custom_price') {
          $meta_data_custom_price = $meta;
        }
        if ($meta->key == 'deposit') {
          $meta_data_deposit = $meta;
        }
      }
    }
    if (!empty($meta_data_add_programs) || !empty($meta_data_pickup_service) || !empty($meta_data_dropoff_service) || !empty($meta_data_custom_price)) {
      try {
        $return_string = "<p>" . $product_name . " x " . $values['quantity'] . "</p><div style='width:100%; font-size:12px'>";
        if (!empty($meta_data_custom_price)) {
          $return_string .= "<div style='padding: 0px;line-height: 20px; width:100%; position:relative;'>
                            <div style='padding: 0px; color:#777777;'>" . get_the_title($values['product_id']) . "</div>
                            <div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>" . number_format($meta_data_custom_price->value, 2, '.', ',') . "</div>
                          </div>
          ";
        }
        if (!empty($meta_data_add_programs)) {
          foreach ($meta_data_add_programs->value as $key => $meta_data_add_program) {
            $return_string .= "<div style='padding: 0px;line-height: 20px; width:100%; position:relative;'>";
            $return_string .= "<div style='padding: 0px; color:#777777;'>" . $meta_data_add_program["product_name"] . "</div>";
            $return_string .= "<div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>$" . number_format(((float)$meta_data_add_program["price"] * (int)$meta_data_add_program["quantity"]), 2, '.', ',') . "</div>";
            $return_string .= "</div>";
          }
        }
        if (!empty($meta_data_pickup_service)) {
          $return_string .= "<div style='padding: 0px;line-height: 20px; width:100%; position:relative;'>";
          $return_string .= "<div style='padding: 0px; color:#777777;'>Transportation To Camp: " . $meta_data_pickup_service->value["product_name"] . "</div>";
          $return_string .= "<div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>$" . number_format(((float)$meta_data_pickup_service->value["price"] * (int)$meta_data_pickup_service->value["quantity"]), 2, '.', ',') . "</div>";
          $return_string .= "</div>";
        }
        if (!empty($meta_data_dropoff_service)) {
          $return_string .= "<div style='padding: 0px;line-height: 20px; width:100%; position:relative;'>";
          $return_string .= "<div style='padding: 0px; color:#777777;'>Transportation From Camp: " . $meta_data_dropoff_service->value["product_name"] . "</div>";
          $return_string .= "<div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>$" . number_format(((float)$meta_data_dropoff_service->value["price"] * (int)$meta_data_dropoff_service->value["quantity"]), 2, '.', ',') . "</div>";
          $return_string .= "</div>";
        }
        if (isset($meta_data_deposit)) {
          $return_string .= "<div style='padding: 0px;line-height: 20px; width:100%; position:relative;'>";
          $return_string .= "<div style='padding: 0px; color:#777777;'>Deposit Amount</div>";
          $return_string .= "<div style='padding: 0px; color:#777777; position: absolute; right: 0; top: 0'>($" . number_format(((float)$meta_data_deposit->value["price"] * (int)$values['quantity']), 2, '.', ',') . ")</div>";
          $return_string .= "</div>";
        }
        $return_string .= "</div>";
        return $return_string;
      } catch (Exception $e) {
        return null;
      }
    }
    return $product_name . " x " . $values['quantity'];
  }
}

// add_action('woocommerce_after_order_itemmeta', 'display_admin_order_item_custom_button', 10, 3);
function display_admin_order_item_custom_button($item_id, $item, $product)
{
  // Only "line" items and backend order pages
  if (!(is_admin() && $item->is_type('line_item')))
    return;

  $add_programs = $item->get_meta('add_program');
  $pickup_service = $item->get_meta('pickup_service');
  $dropoff_service = $item->get_meta('dropoff_service');
  $custom_price = $item->get_meta('custom_price');
  if (!empty($custom_price)) {
    echo '<p>' . get_the_title($product->get_id()) . ' - $' . $custom_price . '</p>';
  }


  if (!empty($add_programs)) {
    foreach ($add_programs as $key => $add_program) {
      echo '<p>' . $add_program["product_name"] . ' - $' . $add_program["price"] . '</p>';
    }
  }
  if (!empty($pickup_service)) {
    echo '<p>Transportation To Camp: ' . $pickup_service["product_name"] . ' - $' . $pickup_service["price"] . '</p>';
  }
  if (!empty($dropoff_service)) {
    echo '<p>Transportation From Camp: ' . $dropoff_service["product_name"] . ' - $' . $dropoff_service["price"] . '</p>';
  }
}

// function to validate only one program per session is added into cart.
function validate_one_program_per_session($products)
{
  $products_id = wp_list_pluck($products, 'product_id');
  $cart = WC()->cart->get_cart();
  $cart_products_id = wp_list_pluck($cart, 'product_id');
  $args = array(
    'post_type' => 'session',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
  );
  $seasons = new WP_Query($args);
  $sessions_products_count = [];
  while ($seasons->have_posts()) : $seasons->the_post();
    $camps = get_field('camps');
    $season_id = get_the_ID();
    // check if product_id is in camps
    foreach ($camps as $key => $camp) {
      $product_id = $camp->ID;
      $get_terms_camp = get_the_terms($camp->ID, 'product_cat');
      // if includes "general-camp-programs" category slug

      if (is_array($get_terms_camp) && in_array('general-camp-programs', wp_list_pluck($get_terms_camp, 'slug'))) {
        if (!$sessions_products_count[$season_id]) {
          $sessions_products_count[$season_id] = 0;
        }
        //check if $products has product_id
        if (in_array($product_id, $products_id)) {
          $sessions_products_count[$season_id] = $sessions_products_count[$season_id] + 1;
        }
        if (in_array($product_id, $cart_products_id)) {
          $sessions_products_count[$season_id] = $sessions_products_count[$season_id] + 1;
        }
      }
      // if includes "specialty-camp-programs" category slug
      if (is_array($get_terms_camp) && in_array('specialty-camp-programs', wp_list_pluck($get_terms_camp, 'slug'))) {
        if (!$sessions_products_count[$season_id]) {
          $sessions_products_count[$season_id] = 0;
        }
        //check if $products has product_id
        if (in_array($product_id, $products_id)) {
          $sessions_products_count[$season_id] = $sessions_products_count[$season_id] + 1;
        }
        if (in_array($product_id, $cart_products_id)) {
          $sessions_products_count[$season_id] = $sessions_products_count[$season_id] + 1;
        }
      }
    }
  endwhile;
  wp_reset_postdata();
  // validate if all sessions counts are < 1 or is empty
  foreach ($sessions_products_count as $session_id => $count) {
    if ($count > 1) {
      return false;
    }
  }
  return true;
}


// add_action('woocommerce_before_calculate_totals', 'add_custom_price', 100001, 1);
function add_custom_price($cart_object)
{
  try {
    foreach ($cart_object->cart_contents as $key => $value) {
      $event_id = array_key_exists('event_id', $value) ? $value['event_id'] : 0;
      if ($value['iscamp']) {
        $event_total_price = $value['custom_price'];
        /*if (array_key_exists('addons', $value)) {
          foreach ($value['addons'] as $addon) {
            if($addon['type'] != 'deposit') $event_total_price += $addon['price'];
          }
        }*/
        //print_r($event_total_price);
        //die();
        if (isset($event_total_price)) {
          $value['data']->set_price($event_total_price);
          $value['data']->set_regular_price($event_total_price);
          $value['data']->set_sale_price($event_total_price);
          $value['data']->set_sold_individually('yes');
          $value['data']->get_price();
        }
      }
    }
  } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
  }
}

function wk_get_item_data($item_data, $cart_item_data)
{
  if (isset($cart_item_data['addons'])) {
    $addons_data = [];
    foreach ($cart_item_data['addons'] as $addon) {
      $addon_type = $addon['type'];
      $type_text = '';
      if ($addon_type == 'add_program') {
        $type_text = '';
      } else if ($addon_type == 'pickup_service') {
        $type_text = 'Transportation To Camp: ';
      } else if ($addon_type == 'dropoff_service') {
        $type_text = 'Transportation From Camp: ';
      }
      $addons_data[] = $type_text . $addon['product_name'] . ' - <b>$' . $addon['price'] . '</b>';
    }
    if (!empty($addons_data)) {
      $item_data[] = array(
        'key'     => __('Addons', 'woocommerce'),
        'value'   => implode('<br/> ', $addons_data),
        'display' => '',
      );
    }
  }
  return $item_data;
}
// add_filter('woocommerce_get_item_data', 'wk_get_item_data', 10, 2);

function ccampanokijig_set_current_user_cookies()
{
  if (isset($_COOKIE['is_manager'])) {
    $cookie_value_manager = sanitize_text_field($_COOKIE['is_manager']);
    $cookie_parts = explode('|', $cookie_value_manager); // 0 => user_login, 1 => expiration, 2 => token, 3 => hmac
    if (count($cookie_parts) !== 4) {
      return '';
    }
    $user_session_token = $cookie_parts[2];
    $is_or_was_manager = get_transient('sfwc_is_or_was_manager_' . $user_session_token);
  }
  if (isset($_COOKIE['is_supervisor'])) {
    $cookie_value_supervisor = sanitize_text_field($_COOKIE['is_supervisor']);
    $cookie_parts = explode('|', $cookie_value_supervisor); // 0 => user_login, 1 => expiration, 2 => token, 3 => hmac
    if (count($cookie_parts) !== 4) {
      return '';
    }
    $user_session_token = $cookie_parts[2];
    $is_or_was_supervisor = get_transient('sfwc_is_or_was_supervisor_' . $user_session_token);
  }
  $sfwc_options = (array) get_option('sfwc_options');
  $sfwc_option_selected_roles = (isset($sfwc_options['sfwc_option_selected_roles'])) ? $sfwc_options['sfwc_option_selected_roles'] : array('customer', 'subscriber');
  $current_user_id = get_current_user_id();
  $account_level_type = get_user_meta($current_user_id, 'sfwc_account_level_type', true);
  $children_ids = get_user_meta($current_user_id, 'sfwc_children', true);
  $children_of_manager = array();
  if (!empty($children_ids)) {
    foreach ($children_ids as $children_id) {
      $single_array = get_user_meta($children_id, 'sfwc_children', true);
      if (!empty($single_array)) {
        foreach ($single_array as $single_value) {
          $children_of_manager[] = $single_value;
        }
      }
    }
  }
  $args_supervisor = array(
    //'role'    => 'customer',
    //'role__in' => ['customer', 'subscriber'],
    'role__in' => $sfwc_option_selected_roles,
    'orderby' => 'ID',
    'order' => 'ASC',
    'meta_key' => 'sfwc_account_level_type',
    'meta_value' => 'supervisor',
    'meta_query' => array(
      array(
        'key' => 'sfwc_children',
        'value' => '"' . $current_user_id . '"',
        'compare' => 'LIKE',
      ),
    ),
  );
  $user_query_supervisor = new WP_User_Query($args_supervisor);
  if (!empty($user_query_supervisor->get_results())) {
    foreach ($user_query_supervisor->get_results() as $user) {
      $supervisor_id = $user->ID;
    }
  }
  $args_manager = array(
    //'role'    => 'customer',
    //'role__in' => ['customer', 'subscriber'],
    'role__in' => $sfwc_option_selected_roles,
    'orderby' => 'ID',
    'order' => 'ASC',
    'meta_key' => 'sfwc_account_level_type',
    'meta_value' => 'manager',
    'meta_query' => array(
      array(
        'key' => 'sfwc_children',
        'value' => '"' . $current_user_id . '"',
        'compare' => 'LIKE',
      ),
    ),
  );
  $user_query_manager = new WP_User_Query($args_manager);
  if (!empty($user_query_manager->get_results())) {
    foreach ($user_query_manager->get_results() as $user) {
      $manager_id = $user->ID;
    }
  }
  if (isset($_POST['campanokijig_frontend_children'])) {
    $selected = sanitize_text_field($_POST['campanokijig_frontend_children']);
    if (is_numeric($selected) && $selected >= 1 && preg_match('/^[1-9][0-9]*$/', $selected)) {


      /**
       * Check if logged in user is Supervisor.
       *
       * In this case this should be enough:
       * if ( is_user_logged_in() && $account_level_type == 'supervisor' ) {...}
       *
       * Anyway...
       */
      if ((is_user_logged_in() && $account_level_type == 'supervisor') && (isset($cookie_value_supervisor) && ($cookie_value_supervisor === $is_or_was_supervisor))) {

        /**
         * Check if selected user is a subaccount of currently logged Supervisor.
         *
         * Or, in case Supervisor has switched to a Manager, check if selected user is a sub-acount of currently logged Mangaer (tied to the initially logged Supervisor).
         */
        if (in_array($selected, $children_ids, true) || in_array($selected, $children_of_manager, true)) {

          // Clears the cart session when called.
          wc_empty_cart(); // Necessary in order for the cart to be auto-populated with correct data after the switch.

          // Removes all of the cookies associated with authentication.
          wp_clear_auth_cookie();

          wp_set_current_user($selected);
          wp_set_auth_cookie($selected);

          // Fix cart not populating after switch from user with empty cart to user with data in cart.
          wc_setcookie('woocommerce_cart_hash', md5(json_encode(WC()->cart->get_cart())));

          //wc_setcookie( 'woocommerce_items_in_cart', 1 );
          //do_action( 'woocommerce_set_cart_cookies', true );

        } else {

          wc_add_notice(esc_html__('You are not authorized to access the chosen account.', 'subaccounts-for-woocommerce'), 'error');
        }
      }
      /**
       * Check if logged in user is Manager.
       */
      elseif (is_user_logged_in() && $account_level_type == 'manager') {

        /**
         * Check if currently logged in 'Manager' has come to its account after switching from 'Supervisor'.
         *
         * Checking this by verifying if 'is_supervisor' cookie is set on its browser.
         */
        if (isset($cookie_value_supervisor) && ($cookie_value_supervisor === $is_or_was_supervisor)) {

          if ((!empty($children_ids) && in_array($selected, $children_ids, true)) || $selected == $supervisor_id) {

            // Clears the cart session when called.
            wc_empty_cart(); // Necessary in order for the cart to be auto-populated with correct data after the switch.

            // Removes all of the cookies associated with authentication.
            wp_clear_auth_cookie();

            wp_set_current_user($selected);
            wp_set_auth_cookie($selected);

            // Fix cart not populating after switch from user with empty cart to user with data in cart.
            wc_setcookie('woocommerce_cart_hash', md5(json_encode(WC()->cart->get_cart())));

            //wc_setcookie( 'woocommerce_items_in_cart', 1 );
            //do_action( 'woocommerce_set_cart_cookies', true );

          } else {

            wc_add_notice(esc_html__('You are not authorized to access the chosen account.', 'subaccounts-for-woocommerce'), 'error');
          }
        }
        /**
         * Otherwise it means that 'Manager' has logged in from its own account (without switching from Supervisor).
         *
         * Therefore do not provide him access to its 'Supervisor' by removing: $selected == $supervisor_id.
         */
        elseif (!isset($cookie_value_supervisor) || ($cookie_value_supervisor !== $is_or_was_supervisor)) {

          // Make sure 'is_manager' cookie is set and its value is equal to transient stored in DB
          if (isset($cookie_value_manager) && ($cookie_value_manager === $is_or_was_manager)) {

            if (!empty($children_ids) && in_array($selected, $children_ids, true)) {

              // Clears the cart session when called.
              wc_empty_cart(); // Necessary in order for the cart to be auto-populated with correct data after the switch.

              // Removes all of the cookies associated with authentication.
              wp_clear_auth_cookie();

              wp_set_current_user($selected);
              wp_set_auth_cookie($selected);

              // Fix cart not populating after switch from user with empty cart to user with data in cart.
              wc_setcookie('woocommerce_cart_hash', md5(json_encode(WC()->cart->get_cart())));

              //wc_setcookie( 'woocommerce_items_in_cart', 1 );
              //do_action( 'woocommerce_set_cart_cookies', true );

            } else {

              wc_add_notice(esc_html__('You are not authorized to access the chosen account.', 'subaccounts-for-woocommerce'), 'error');
            }
          }
        }
      }
      /**
       * Check if currently logged in 'Default' user has come to its account after switching from 'Supervisor' or 'Manager'.
       *
       * Checking this by verifying if either 'is_supervisor' or 'is_manager' cookie is set on its browser.
       */
      elseif (
        (is_user_logged_in() && $account_level_type !== 'supervisor' || $account_level_type !== 'manager')
        && ((isset($cookie_value_supervisor) && ($cookie_value_supervisor === $is_or_was_supervisor)) || (isset($cookie_value_manager) && ($cookie_value_manager === $is_or_was_manager)))
      ) {


        // Get Supervisor's ID from Default user's Manager ID
        $args_supervisor = array(
          //'role'    => 'customer',
          //'role__in' => ['customer', 'subscriber'],
          'role__in' => $sfwc_option_selected_roles,
          //'exclude'  => $user_id,	// Exclude ID of customer who made currently displayed order
          'orderby' => 'ID',
          'order' => 'ASC',
          'meta_key' => 'sfwc_account_level_type',
          'meta_value' => 'supervisor',
          'meta_query' => array(
            array(
              'key' => 'sfwc_children',
              'value' => '"' . $manager_id . '"',
              'compare' => 'LIKE',
            ),
          ),
        );


        // The User Query
        $user_query_supervisor = new WP_User_Query($args_supervisor);



        // User Loop
        if (!empty($user_query_supervisor->get_results())) {
          foreach ($user_query_supervisor->get_results() as $user) {

            $supervisor_id = $user->ID;
          }
        }


        if ((!empty($children_ids) && in_array($selected, $children_ids, true)) || isset($supervisor_id) && $supervisor_id == $selected || isset($manager_id) && $manager_id == $selected) {

          // Clears the cart session when called.
          wc_empty_cart(); // Necessary in order for the cart to be auto-populated with correct data after the switch.

          // Removes all of the cookies associated with authentication.
          wp_clear_auth_cookie();

          wp_set_current_user($selected);
          wp_set_auth_cookie($selected);

          // Fix cart not populating after switch from user with empty cart to user with data in cart.
          wc_setcookie('woocommerce_cart_hash', md5(json_encode(WC()->cart->get_cart())));

          //wc_setcookie( 'woocommerce_items_in_cart', 1 );
          //do_action( 'woocommerce_set_cart_cookies', true );

        } else {

          wc_add_notice(esc_html__('You are not authorized to access the chosen account.', 'subaccounts-for-woocommerce'), 'error');
        }
      } else {

        wc_add_notice(esc_html__('You are not authorized to access the chosen account.', 'subaccounts-for-woocommerce'), 'error');
      }
    }

    // If $selected is not a positive integer.
    else {

      wc_add_notice(esc_html__('Incorrect data sent.', 'subaccounts-for-woocommerce'), 'error');
    }
  }
}
add_action('wp', 'ccampanokijig_set_current_user_cookies', 999);

// on auth change redirect if login is come from /registration/
add_action('wp_login', 'ccampanokijig_login_redirect', 10, 2);
function ccampanokijig_login_redirect($user_login, $user)
{
  $current_page = $_SERVER['REQUEST_URI'];
  if (strpos($current_page, 'registration') > 0) {
    wp_safe_redirect(esc_url('/registration\/?step=registration_form'));
    exit;
  }
}
function custom_sfwc_redirect_to_dashboard_after_account_switch()
{
  // get current page
  $current_page = $_SERVER['REQUEST_URI'];
  if (isset($_POST['campanokijig_frontend_children'])) {
    if (strpos($current_page, 'registration') > 0) {
      if (isset($_GET['step']) && $_GET['step'] == 'select_camper') {
        if (isset($_POST['stay_here']) && $_POST['stay_here'] == 'true') {
          wp_safe_redirect(esc_url('/registration\/?step=select_camper'));
          exit;
        }
        wp_safe_redirect(esc_url('/registration\/?step=registration_form'));
        exit;
      }
      wp_safe_redirect(esc_url('/registration\/?step=registration_form'));
      exit;
    } else {
      wp_safe_redirect(esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))));
      exit;
    }
  }
}
add_action('template_redirect', 'custom_sfwc_redirect_to_dashboard_after_account_switch', 999);

/**
 * Add "Subaccounts" menu item and content to My Account page (Pt.8).
 *
 * Create content for the shortcode (AKA Form for subaccounts creation on frontend).
 */
function sfwc_custom_add_subaccount_form_content()
{
  /**
   * Check number of subaccount already created.
   *
   *
   */
  // Get ID of currently logged-in user
  $user_parent = get_current_user_id();
  $user_parent_data = get_userdata($user_parent);
  $sfwc_options = (array) get_option('sfwc_options');
  // Get 'Customer Display Name' from Options settings.
  $sfwc_option_display_name = (isset($sfwc_options['sfwc_option_display_name'])) ? $sfwc_options['sfwc_option_display_name'] : 'username';
  // Get 'Subaccounts Number Limit' from Options settings.
  $sfwc_option_subaccounts_number_limit = (isset($sfwc_options['sfwc_option_subaccounts_number_limit'])) ? $sfwc_options['sfwc_option_subaccounts_number_limit'] : 10;
  //Check 'Customer Display Name' in Subaccounts > Settings > Options and display it accordingly.
  if (($sfwc_option_display_name == 'full_name') && ($user_parent_data->user_firstname || $user_parent_data->user_lastname)) {
    // Echo 'Full Name + Email' (if either First Name or Last Name has been set).
    $user_parent_name = '<strong>' . esc_html($user_parent_data->user_firstname) . ' ' . esc_html($user_parent_data->user_lastname) . '</strong>';
  } elseif (($sfwc_option_display_name == 'company_name') && ($user_parent_data->billing_company)) {
    // Echo 'Company + Email' (if Company name has been set).
    $user_parent_name = '<strong>' . esc_html($user_parent_data->billing_company) . '</strong>';
  } else {
    // Otherwise echo 'Username + Email'.
    $user_parent_name = '<strong>' . esc_html($user_parent_data->user_login) . '</strong>';
  }
  /**
   * Get subaccounts of the currently logged in user.
   *
   * This array might include empty values, aka users that are still set as subaccounts of the current user,
   * but no longer exist (have been deleted from admin).
   */
  $already_children = get_user_meta($user_parent, 'sfwc_children', true);
  // Exclude possible empty values (no longer existing users) from array.
  if (!empty($already_children)) {
    foreach ($already_children as $key => $value) {
      // Prevent empty option values within the frontend dropdown user switcher 
      // in case a user has been deleted (but still present within 'sfwc_children' meta of an ex parent account).
      $user_exists = get_userdata($value);
      if ($user_exists !== false) {
        $already_children_existing[] = $value;
      }
    }
    if (isset($already_children_existing) && is_array($already_children_existing)) {
      $qty_subaccount_already_set = count($already_children_existing);
      if (
        (($qty_subaccount_already_set >= $sfwc_option_subaccounts_number_limit || $qty_subaccount_already_set >= 10) && !sfwc_is_plugin_active('subaccounts-for-woocommerce-pro.php')) ||
        ($qty_subaccount_already_set >= $sfwc_option_subaccounts_number_limit && $sfwc_option_subaccounts_number_limit != 0)
      ) {
        wc_print_notice(
          sprintf(
            esc_html__('Maximum number of subaccounts already reached for %1$s. Please contact the site administrator and ask to increase this value.', 'subaccounts-for-woocommerce'),
            $user_parent_name
          ),
          'error'
        );
        return;
      }
    }
  }
  /**
   * In case form submit was unsuccessful, re-populate input fields with previously posted (wrong) data,
   * so that user can correct it.
   *
   * If successful, input fields cleared with $_POST = array(); in above validation function.
   */
  $user_login = isset($_POST['user_login']) && $_POST['user_login'] != "" ? sanitize_user($_POST['user_login']) : "";
  $email = isset($_POST['email']) && $_POST['email'] != "" ? sanitize_email($_POST['email']) : "";
  $first_name = isset($_POST['first_name']) && $_POST['first_name'] != "" ? sanitize_text_field($_POST['first_name']) : "";
  $last_name = isset($_POST['last_name']) && $_POST['last_name'] != "" ? sanitize_text_field($_POST['last_name']) : "";
  $company = isset($_POST['company']) && $_POST['company'] != "" ? sanitize_text_field($_POST['company']) : "";
?>
  <form id="sfwc_form_add_subaccount_frontend" method="post">
    <?php wp_nonce_field('sfwc_add_subaccount_frontend_action', 'sfwc_add_subaccount_frontend'); ?>
    <?php
    $username_required_css = ((isset($_POST['user_login']) && $_POST['user_login'] == "")
      || (isset($_POST['user_login']) &&  username_exists($_POST['user_login']))
      || (isset($_POST['user_login']) && !validate_username($_POST['user_login']))
    ) ? "color:red;" : "";
    $email_required_css = ((isset($_POST['email']) && $_POST['email'] == "")
      || (isset($_POST['email']) && email_exists($_POST['email']))
      || (isset($_POST['email']) && !is_email($_POST['email']))
    ) ? "color:red;" : "";
    ?>
    <div class="user_login" style="margin-bottom:20px; width:100%; float:left;">
      <label for="user_login" style="display:block; margin-bottom:0; <?php echo esc_attr($username_required_css); ?>"><?php echo get_field('username_label', 'option'); ?> <span style="font-weight:bold;">*</span></label>
      <input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($user_login); ?>" style="width:100%;">
    </div>
    <!--
			<div class="first_name" style="margin-bottom:20px; width:48%; float:left;">
				<label for="first_name" style="display:block; margin-bottom:0;"><?php esc_html_e('First Name', 'subaccounts-for-woocommerce'); ?></label>
				<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($first_name); ?>" style="width:100%;">
			</div>
			<div class="last_name" style="margin-bottom:20px; width:48%; float:right;">
				<label for="last_name" style="display:block; margin-bottom:0;"><?php esc_html_e('Last Name', 'subaccounts-for-woocommerce'); ?></label>
				<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($last_name); ?>" style="width:100%;">
			</div>
			<div class="company" style="margin-bottom:20px; width:100%;">
				<label for="company" style="display:block; margin-bottom:0;"><?php esc_html_e('Company', 'subaccounts-for-woocommerce'); ?></label>
				<input type="text" name="company" id="company" value="<?php echo esc_attr($company); ?>" style="width:100%;">
			</div>-->
    <p style="padding:15px; background:#f5f5f5; border-left:5px; border-left-color:#7eb330; border-left-style:solid; display:flex;">
      <span style="font-size:35px; color:#7eb330; align-self:center;">&#128712;</span>
      <span style="align-self:center; padding-left:10px;">
        <?php echo esc_html__('An email containing the username and a link to set the password will be sent to the new account after the subaccount is created.', 'subaccounts-for-woocommerce'); ?>
      </span>
    </p>
    <input type="submit" value="<?php echo get_field('add_subaccount_button_label', 'option'); ?>" style="padding:10px 40px;">
    <p style="margin-top:50px;">
      <span style="font-weight:bold;">*</span> <?php echo esc_html__('These fields are required.', 'subaccounts-for-woocommerce'); ?></span>
    </p>

  </form>


  <?php
}
add_shortcode('sfwc_add_subaccount_custom_shortcode', 'sfwc_custom_add_subaccount_form_content');

add_filter('woocommerce_add_cart_item_data', 'add_cart_item_data', 20, 3);
function add_cart_item_data($cart_item_meta, $product_id, $variation_id)
{

  //user restriction
  if (!apply_filters('wc_deposits_deposit_enabled_for_customer', true)) {
    return $cart_item_meta;
  }
  $default = get_option('wc_deposits_default_option');
  $product = wc_get_product($product_id);
  if (!$product) return $cart_item_meta;
  $override = apply_filters('wc_deposits_add_to_cart_deposit_override', array(), $product_id, $variation_id);
  if ($product->get_type() === 'variable') {

    $deposit_enabled = $override['enable'] ?? wc_deposits_is_product_deposit_enabled($variation_id);
    $force_deposit = $override['force'] ?? wc_deposits_is_product_deposit_forced($variation_id);
  } else {
    $deposit_enabled = $override['enable'] ?? wc_deposits_is_product_deposit_enabled($product_id);
    $force_deposit = $override['force'] ?? wc_deposits_is_product_deposit_forced($product_id);
  }

  if ($deposit_enabled) {
    if (!isset($_REQUEST[$product_id . '-deposit-radio'])) {
      $_REQUEST[$product_id . '-deposit-radio'] = $default ? $default : 'deposit';
    }

    if (isset($variation_id) && isset($_REQUEST[$variation_id . '-deposit-radio'])) {
      $_REQUEST[$product_id . '-deposit-radio'] = $_REQUEST[$variation_id . '-deposit-radio'];

      if (isset($_REQUEST[$variation_id . '-selected-plan'])) {
        $_REQUEST[$product_id . '-selected-plan'] = $_REQUEST[$variation_id . '-selected-plan'];
      }
    }

    $cart_item_meta['deposit'] = array(
      'enable' => $force_deposit ? 'yes' : ($_REQUEST[$product_id . '-deposit-radio'] === 'full' ? 'no' : 'yes')
    );

    if (isset($override['enable']) && $override['enable']) $cart_item_meta['deposit']['enable'] = 'yes';
    if ($cart_item_meta['deposit']['enable'] === 'yes') {
      if ((isset($_REQUEST[$product_id . '-selected-plan']))) {
        //payment plan selected
        $cart_item_meta['deposit']['payment_plan'] = $_REQUEST[$product_id . '-selected-plan'];
      } elseif (wc_deposits_get_product_deposit_amount_type($product_id) === 'payment_plan') {
        // default selection is deposit  and deposit type is payment plan, so pick the first payment plan

        $available_plans = $variation_id ? wc_deposits_get_product_available_plans($variation_id) : wc_deposits_get_product_available_plans($product_id);
        if (is_array($available_plans)) {
          $cart_item_meta['deposit']['payment_plan'] = $available_plans[0];
        }
      }
      if (isset($override['payment_plan'])) {
        $cart_item_meta['deposit']['payment_plan'] = $override['payment_plan'];
      }
    }
  }
  if (isset($cart_item_meta['iscamp']) && $cart_item_meta['iscamp'] == true) {
    if (isset($cart_item_meta['isPrincipal']) && $cart_item_meta['isPrincipal'] == true) {
      $cart_item_meta['deposit'] = $cart_item_meta['deposit-registration'];
    } else {
      $cart_item_meta['deposit'] = null;
    }
  }
  return $cart_item_meta;
}


add_action('woocommerce_cart_updated', 'woocommerce_cart_updated_callback', 99, 1);
function woocommerce_cart_updated_callback($cart)
{
  $cart = WC()->cart->get_cart();
  foreach ($cart as $key => $value) {
    if (isset($value['iscamp']) && $value['iscamp']) {
      if (isset($value['isPrincipal']) && $value['isPrincipal']) {
        $cart[$key]['deposit'] = $value['deposit-registration'];
      } else {
        $cart[$key]['deposit'] = null;
      }
    }
  }
}

// when cart item is removed check if isPrincipal and remove all items with same session.
add_action('woocommerce_cart_item_removed', 'woocommerce_cart_item_removed_callback', 1, 1);
function woocommerce_cart_item_removed_callback($cart_item_key)
{
  $cart = WC()->cart->get_cart();
  $cart_item = $cart[$cart_item_key];
  $sessionHavePrincipal = [];
  foreach ($cart as $key => $item) {
    if ($item['iscamp']) {
      if (!isset($sessionHavePrincipal[$item['session']])) $sessionHavePrincipal[$item['session']] = false;
      if ($item['isPrincipal']) {
        $sessionHavePrincipal[$item['session']] = true;
      }
    }
  }
  foreach ($cart as $key => $item) {
    if ($item['iscamp']) {
      if (!$sessionHavePrincipal[$item['session']]) {
        WC()->cart->remove_cart_item($key);
      }
    }
  }
}

add_action('woocommerce_after_calculate_totals', 'calculate_deposit_totals', 2999);
function calculate_deposit_totals($cart_object)
{
  $cart = WC()->cart->get_cart();
  $depositTotal = 0.0;
  foreach ($cart as $key => $value) {
    /*if(isset($value['deposit-registration']) && $value['deposit-registration']['enable'] == 'yes'){
        WC()->cart->cart_contents[$key]['deposit'] = apply_filters('wc_deposits_cart_item_deposit_data', $value['deposit-registration'], $value);
      }*/
    if (
      // isset($value['deposit']['enable']) 
      //&& $value['deposit']['enable'] == 'yes'
      ($value['iscamp'] && $value['isPrincipal'])
      || !isset($value['iscamp'])
    ) {
      $depositTotal += $value['deposit']['deposit'];
    }
  }
  $cartObj = WC()->cart;
  $cartObj->deposit_info['deposit_amount'] = $depositTotal;
}



add_action('woocommerce_thankyou', 'woocommerce_thankyou', 999, 1);
function woocommerce_thankyou($order_id)
{
  // account order id
  $account_order_id = get_post_meta($order_id, '_customer_user', true);
  $user = get_userdata($account_order_id);
  $display_name = $user->display_name;
  $html = '<div class="store-credit-cart">
      <div class="content-store-credit">
        <div class="woocommerce-customer-details">
          <h2 class="woocommerce-column__title">Camper Information</h2>
          <div style="padding:27px 17px;">
            <p>Camper User Name: <b>' . $display_name . '</b></p>
          </div>
        </div>
      </div>
    </div>';
  echo $html;
}

//add_action('woocommerce_email_after_order_table', 'woocommerce_email_customer_details', 999, 1);
add_action('woocommerce_email_order_meta', 'woocommerce_email_customer_details', 5, 1);
// add_action('woocommerce_email_customer_details', 'woocommerce_email_customer_details', 999, 1);
function woocommerce_email_customer_details($order)
{
  // account order id
  $order_id = $order->get_id();
  $account_order_id = get_post_meta($order_id, '_customer_user', true);
  $user = get_userdata($account_order_id);
  $display_name = $user->display_name;
  $html = '<div class="store-credit-cart">
      <div class="content-store-credit">
        <div class="woocommerce-customer-details">
          <h2 class="woocommerce-column__title">Camper Information</h2>
          <h3>Camper User Name: ' . $display_name . '</h3>
        </div>
      </div>
    </div>';
  echo $html;
}

add_filter('woocommerce_order_item_get_formatted_meta_data', 'unset_specific_order_item_meta_data', 10, 2);
function unset_specific_order_item_meta_data($formatted_meta, $item)
{
  // Only on emails notifications
  if (is_admin() || is_wc_endpoint_url())
    return $formatted_meta;

  foreach ($formatted_meta as $key => $meta) {
    if (in_array($meta->key, array('custom_price')))
      unset($formatted_meta[$key]);
  }
  return $formatted_meta;
}

function admin_enqueue_scripts_callback()
{
  //Add the Select2 CSS file
  wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
  //Add the Select2 JavaScript file
  wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0');
  //Add a JavaScript file to initialize the Select2 elements
  wp_enqueue_script('select2-init', '/wp-content/plugins/mage-eventpress-addon-extra-services/select2-init.js', 'jquery', '4.1.0-rc.0');
}
add_action('admin_enqueue_scripts', 'admin_enqueue_scripts_callback');

function mylisttable_elements($self)
{
  // check if page is wpda_wpdp_1_2
  if (isset($_GET['page']) && $_GET['page'] == 'wpda_wpdp_1_2') {
    ob_start(); ?>
    <div>
      <div class="wrap">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="cabin_management">
          <div>
            <p>Update conditions:</p>
            <label for="session">Session:</label>
            <select class="wpbusinet-select2" name="session">
              <option value="">Select a session</option>
              <?php
              // get all cpt from session get acf field camps is relation ship from product.
              $args = array(
                'post_type' => 'session',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'order' => 'ASC',
                'orderby' => 'title'
              );
              $query = new WP_Query($args);
              if ($query->have_posts()) {
                while ($query->have_posts()) {
                  $query->the_post();
                  $id = get_the_ID();
                  $title = get_the_title();
                  echo '<option value="' . $id . '">' . $title . '</option>';
                }
              }
              ?>
            </select>
            <label for="genero">Gender:</label>
            <select name="gender">
              <option selected disabled>Select gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <label for="grado">grade:</label>
            <select name="grade">
              <option selected disabled>Select grade</option>
              <option value="0">0</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
              <option value="6">6</option>
              <option value="7">7</option>
              <option value="8">8</option>
            </select>
          </div>
          <div style="margin-bottom:15px">
            <p>Set Cabin to update</p>
            <label for="cabin">Cabin:</label>
            <select class="wpbusinet-select2" name="cabin">
              <option value="">Select a cabin</option>
              <?php
              $args = array(
                'post_type' => 'cabin',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'order' => 'ASC',
                'orderby' => 'title'
              );
              $query = new WP_Query($args);
              if ($query->have_posts()) {
                while ($query->have_posts()) {
                  $query->the_post();
                  $title = get_the_title();
                  echo '<option value="' . $title . '">' . $title . '</option>';
                }
              }
              ?>
            </select>
          </div>

          <input type="submit" name="filtrar_cabin" class="button button-primary" value="Update cabins">
        </form>
      </div>

      <div class="wrap">
        <form method="get">
          <div>
            <p>Filters:</p>
            <input type="hidden" name="page" value="wpda_wpdp_1_2">
            <label for="order">Order:</label>
            <input type="text" name="wpda_search_column_OrderId" placeholder="Order">
            <label for="session">Session:</label>
            <select class="wpbusinet-select2" name="wpda_search_column_SessionId">
              <option value="">Select a session</option>
              <?php
              // get all cpt from session get acf field camps is relation ship from product.
              $args = array(
                'post_type' => 'session',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'order' => 'ASC',
                'orderby' => 'title'
              );
              $query = new WP_Query($args);
              if ($query->have_posts()) {
                while ($query->have_posts()) {
                  $query->the_post();
                  $id = get_the_ID();
                  $title = get_the_title();
                  echo '<option value="' . $id . '">' . $title . '</option>';
                }
              }
              ?>
            </select>
            <label for="genero">Gender:</label>
            <select name="wpda_search_column_Gender">
              <option selected disabled>Select gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <label for="grado">grade:</label>
            <select name="wpda_search_column_Grade">
              <option selected disabled>Select grade</option>
              <option value="0">0</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
              <option value="6">6</option>
              <option value="7">7</option>
              <option value="8">8</option>
            </select>
            <label for="cabin">Cabin:</label>
            <select class="wpbusinet-select2" name="wpda_search_column_Cabin">
              <option value="">Select a cabin</option>
              <?php
              $args = array(
                'post_type' => 'cabin',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'order' => 'ASC',
                'orderby' => 'title'
              );
              $query = new WP_Query($args);
              if ($query->have_posts()) {
                while ($query->have_posts()) {
                  $query->the_post();
                  $title = get_the_title();
                  echo '<option value="' . $title . '">' . $title . '</option>';
                }
              }
              ?>
            </select>
            <input type="submit" class="button button-primary" value="Filter">
          </div>
        </form>
      </div>
    </div>
    <?php
    $html = ob_get_clean();
    echo $html;
  }
}
add_action('wpda_before_list_table', 'mylisttable_elements', 7);

add_action('admin_post_nopriv_cabin_management', 'cabin_management_handler');
add_action('admin_post_cabin_management', 'cabin_management_handler');
function cabin_management_handler()
{
  if (isset($_POST['filtrar_cabin'])) {
    global $wpdb;
    $session = sanitize_text_field($_POST['session']);
    $genero = sanitize_text_field($_POST['gender']);
    $grado = sanitize_text_field($_POST['grade']);
    $cabaña = sanitize_text_field($_POST['cabin']);
    if (empty($session) || empty($genero) || empty($grado)) {
      // do_action('admin_notices', 'cabin_filter_required');
      wp_redirect( admin_url( 'admin.php?page=wpda_wpdp_1_2' ) || admin_url( 'admin.php?page=wpda_wpdp_2_2' ) );

    }
    if (empty($cabin)) {
      wp_redirect( admin_url( 'admin.php?page=wpda_wpdp_1_2' ) || admin_url( 'admin.php?page=wpda_wpdp_2_2' ) );

    }
    // Realizar el update en la tabla
    $args = array();
    if(!empty($session)){
      $args['SessionId'] = $session;
    }
    if(!empty($genero)){
      $args['Gender'] = $genero;
    }
    if(!empty($grado)){
      $args['Grade'] = $grado;
    }
    $result = $wpdb->update(
      'cabin_management',
      array('Cabin' => $cabaña),
      $args,
      array('%s'),
      array('%s', '%s', '%s')
    );
    //echo "post update";
    //var_dump($result);
    // do_action('admin_notices', 'cabin_filter_success');
    wp_redirect( admin_url( 'admin.php?page=wpda_wpdp_1_2' ) || admin_url( 'admin.php?page=wpda_wpdp_2_2' ) );

    /*wp_safe_redirect(
      // Sanitize.
      esc_url(
          // Retrieves the site url for the current site.
          site_url( '/wp-admin/admin-post.php?page=wpda_wpdp_1_2' )
      )
    );*/
  }
}

function cabin_filter_required($messages)
{
  echo // Customize the message below as needed
  '<div class="notice notice-warning is-dismissible">
  <p>One filter input is required!.</p>
  </div>';
}
add_action('admin_notices', 'cabin_filter_required');
function cabin_filter_success($messages)
{
  echo // Customize the message below as needed
  '<div class="notice notice-success is-dismissible">
  <p>Cabins updated!.</p>
  </div>';
}
add_action('admin_notices', 'cabin_filter_success');

// sql query to list order items that are camp.
function get_order_items_camp($order_id)
{
  global $wpdb;
  $query = "SELECT 
      wp_item_meta.order_item_id, 
      wp_items.order_item_name, 
      wp_items.order_id,
      wp_post.*
    FROM wp_woocommerce_order_itemmeta wp_item_meta
    JOIN wp_woocommerce_order_items wp_items ON wp_item_meta.order_item_id = wp_items.order_item_id
    JOIN wp_posts wp_post ON wp_items.order_id = wp_posts.ID
  
  ";
  $results = $wpdb->get_results($query);
  return $results;
}