<?php

$args = array(
  'post_type' => 'transportation',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'orderby' => "title",
  'order' => 'ASC',
);
$areas = [];
$loop = new WP_Query($args);
while ($loop->have_posts()) : $loop->the_post();
  $areas[] = array(
    "title" => get_the_title(),
    "pickup_price"  => get_field('pickup_price', get_the_ID()),
    "dropoff_price" => get_field('dropoff_price', get_the_ID()),
  );
endwhile;
wp_reset_query();
?>
<h3 class='ex-sec-title'>Transportation</h3>
<div class="mep-user-info-sec">
  <table id='mep_event_linked_extra_service_table'>
    <tr>
      <td align="Left">
        To Camp
      </td>
      <td class="mage_text_center">
        <label>
          <select name="transportation_pickup_service[]" id="eventtransportationpickup" required>
            <option value="0">None</option>
            <?php for ($i = 0; $i <= count($areas); $i++) { ?>
              <?php if ($areas[$i]) { ?>
                <option value="<?php echo $areas[$i]["pickup_price"]; ?>"><?php echo $areas[$i]["title"]; ?></option>
            <?php }
            } ?>
          </select>
          <input type="hidden" name="transportation_pickup_name">
        </label>
      </td>
      <td class="mage_text_center">
        <span class="woocommerce-Price-amount amount" id="pickup-price"><bdi><span class="woocommerce-Price-currencySymbol">$</span>0.00</bdi></span>
      </td>
    </tr>
    <tr>
      <td align="Left">
        To Home
      </td>
      <td class="mage_text_center">
        <label>
          <select name="transportation_dropoff_service[]" id="eventtransportationdropoff" required>
            <option value="0">None</option>
            <?php for ($i = 0; $i <= count($areas); $i++) { ?>
              <?php if ($areas[$i]) { ?>
                <option value="<?php echo $areas[$i]["dropoff_price"]; ?>"><?php echo $areas[$i]["title"]; ?></option>
            <?php }
            } ?>
          </select>
          <input type="hidden" name="transportation_dropoff_name">
        </label>
      </td>
      <td class="mage_text_center">
        <span class="woocommerce-Price-amount amount" id="dropoff-price"><bdi><span class="woocommerce-Price-currencySymbol">$</span>0.00</bdi></span>
      </td>
    </tr>
  </table>
  <script>
    var $j = jQuery.noConflict();
    // $j is now an alias to the jQuery function; creating the new alias is optional.
    $j(document).ready(function() {
      const pickupField = $j('#pickup-price')
      const selectPickup = $j('#eventtransportationpickup')
      const inputPickupHidden = $j('input[name="transportation_pickup_name"]')
      const dropoffField = $j('#dropoff-price')
      const selectDropoff = $j('#eventtransportationdropoff')
      const inputDropoffHidden = $j('input[name="transportation_dropoff_name"]')
      const totalElement = $j('#usertotal')
      var total = <?php if ($event_meta['_price'][0]) {
                    echo esc_attr($event_meta['_price'][0]);
                  } else {
                    echo 0;
                  } ?>;
      if (pickupField && selectPickup) {
        selectPickup.on('change', function(e) {
          let value = selectPickup?.val()
          let text = selectPickup?.find('option:selected').text()
          inputPickupHidden.val(text)
          if (value) value = parseFloat(value)
          var sum = 0;
          var total = 0;
          if (dropoffField && selectDropoff) {
            let dropoffValue = selectDropoff?.val()
            if (dropoffValue) dropoffValue = parseFloat(dropoffValue)
            total = total + dropoffValue
          }
          jQuery('.price_jq').each(function() {
            var price = jQuery(this);
            var count = price.closest('tr').find('.extra-qty-box');
            sum = (parseFloat(price.html().match(/-?(?:\d+(?:\.\d*)?|\.\d+)/)) * count.val());
            total = total + sum;
          });
          if (value === '' || value === null || value === undefined) {
            pickupField.html('<bdi><span class="woocommerce-Price-currencySymbol">$</span>' + parseFloat(0).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            }) + '</bdi>')
          } else {
            total = total + value
            pickupField.html('<bdi><span class="woocommerce-Price-currencySymbol">$</span>' + value.toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            }) + '</bdi>')
          }
          jQuery('#rowtotal').val(total);
          jQuery('#usertotal').html(mp_event_wo_commerce_price_format(total));
        })
      }
      if (dropoffField && selectDropoff) {
        selectDropoff.on('change', function(e) {
          let value = selectDropoff?.val()
          if (value) value = parseFloat(value)
          let text = selectDropoff?.find('option:selected').text()
          inputDropoffHidden.val(text)
          var sum = 0;
          var total = 0;
          if(pickupField && selectPickup) {
            let pickupValue = selectPickup?.val()
            if (pickupValue) pickupValue = parseFloat(pickupValue)
            total = total + pickupValue
          }
          jQuery('.price_jq').each(function() {
            var price = jQuery(this);
            var count = price.closest('tr').find('.extra-qty-box');
            sum = (parseFloat(price.html().match(/-?(?:\d+(?:\.\d*)?|\.\d+)/)) * count.val());
            total = total + sum;
          });
          if (value === '' || value === null || value === undefined) {
            dropoffField.html('<bdi><span class="woocommerce-Price-currencySymbol">$</span>' + parseFloat(0).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            }) + '</bdi>')
          } else {
            total = total + value
            dropoffField.html('<bdi><span class="woocommerce-Price-currencySymbol">$</span>' + value.toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            }) + '</bdi>')
          }
          jQuery('#rowtotal').val(total);
          jQuery('#usertotal').html(mp_event_wo_commerce_price_format(total));
        })
      }
    });
  </script>
</div>