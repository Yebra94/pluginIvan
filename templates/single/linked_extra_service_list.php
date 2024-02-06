<h3 class='ex-sec-title'>Extra activities</h3>
<table id='mep_event_linked_extra_service_table'>
  <?php

  foreach ($mep_events_linked_extra_prices as $field) {

    $option_service       = array_key_exists('extra_service_option_service', $field) ? $field['extra_service_option_service'] : '';
    $service_qty_type   = array_key_exists('extra_service_option_qty_type', $field) ? $field['extra_service_option_qty_type'] : 'input';
    $stock = mep_count_total_available_seat( $option_service );
    $qty_type               = $service_qty_type;
    $ext_left               = mep_count_total_available_seat( $option_service );
    $mep_event_ticket_type      = get_post_meta($option_service, 'mep_event_ticket_type', true) ? get_post_meta($option_service, 'mep_event_ticket_type', true) : array();
    $service_name = $mep_event_ticket_type[0]["option_name_t"];
    $tic_price      = $mep_event_ticket_type[0]["option_price_t"]; // mep_get_price_including_tax($option_service, $service_price);
    $service_price = $tic_price;
    $actual_price   = mage_array_strip(wc_price($tic_price));
    $data_price     = str_replace(get_woocommerce_currency_symbol(), '', $actual_price);
    $data_price     = str_replace(wc_get_price_thousand_separator(), '', $data_price);
    $data_price     = str_replace(wc_get_price_decimal_separator(), '.', $data_price);
  ?>
    <tr>
      <td align="Left"><?php echo esc_html($service_name); ?>
        <?php if ($mep_available_seat == 'on') { ?>
          <div class="xtra-item-left"><?php echo esc_html($ext_left); ?>
            <?php echo mep_get_option('mep_left_text', 'label_setting_sec', __('Left:', 'mage-eventpress'));  ?>
          </div>
        <?php } ?>

        <input type="hidden" name='mep_event_start_date_es[]' value='<?php echo esc_attr($event_date); ?>'>
      </td>
      <td class="mage_text_center">
        <?php
        if ($ext_left > 0) {
          if ($qty_type == 'dropdown') { ?>
            <select name="linked_event_extra_service_qty[]" id="eventpxtp_" class='extra-qty-box'>
              <?php for ($i = 0; $i <= $ext_left; $i++) { ?>
                <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?> <?php echo esc_html($service_name); ?></option>
              <?php } ?>
            </select>
          <?php } else { ?>
            <div class="mage_input_group">
              <span class="fa fa-minus qty_dec"></span>
              <input id="eventpx" size="4" inputmode="numeric" type="text" class='extra-qty-box' name='linked_event_extra_service_qty[]' data-price='<?php echo esc_attr($data_price); ?>' value='0' min="0" max="<?php echo esc_attr($ext_left); ?>">
              <span class="fa fa-plus qty_inc"></span>
            </div>
        <?php }
        } else {
          echo mep_get_option('mep_not_available_text', 'label_setting_sec', __('Not Available', 'mage-eventpress'));
        } ?>
      </td>
      <td class="mage_text_center"><?php echo wc_price(esc_html(mep_get_price_including_tax($post_id, $service_price)));
                                    if ($ext_left > 0) { ?>
          <p style="display: none;" class="price_jq"><?php echo esc_html($tic_price) > 0 ? esc_html($tic_price) : 0;  ?></p>
          <input type="hidden" name='linked_event_extra_service_name[]' value='<?php echo esc_attr($service_name); ?>'>
          <input type="hidden" name='linked_event_extra_service_price[]' value='<?php echo esc_attr($service_price); ?>'>
        <?php } ?>
      </td>
    </tr>
  <?php
    $count++;
  }
  ?>
</table>