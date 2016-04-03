<?php

/**
 * @file
 * Rules integration for line items.
 *
 * @addtogroup rules
 * @{
 */


/**
 * Implements hook_rules_action_info().
 */
function commerce_pricelist_rules_action_info() {
$actions = array();

  $actions['commerce_pricelist_rate_apply'] = array(
    'label' => t('Apply a pricelist rate to a line item'),
    'parameter' => array(
      'commerce_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
        'getter callback' => 'entity_property_verbatim_get',
      ),
      'user' => array(
        'type' => 'user',
        'label' => t('User'),
      ),
    ),
    'provides' => array(
      'commerce_pricelist_price_array' => array(
        'type' => 'list<commerce_price>',
        'label' => t('Commerce Pricelist price')
      ),
    ),
    'group' => t('Commerce pricelist'),
    'callbacks' => array(
      'execute' => 'commerce_pricelist_rate_rules_apply',
    ),
  );

  $action_types['commerce_pricelist_unit_price_amount'] = array(
    'label' => t('Set the price to a specific amount and apply inclusive taxes'),
    'amount description' => t('Specify the numeric amount to set the price component to.'),
  );

  // Define the action using a common set of parameters.
  foreach ($action_types as $key => $value) {
    $actions[$key] = array(
      'label' => $value['label'],
      'parameter' => array(
        'commerce_line_item' => array(
          'type' => 'commerce_line_item',
          'label' => t('Line item'),
        ),
        'amount' => array(
          'type' => 'decimal',
          'label' => t('Amount'),
          'description' => $value['amount description'],
        ),
        'component_name' => array(
          'type' => 'text',
          'label' => t('Price component type'),
          'description' => t('Price components track changes to prices made during the price calculation process, and they are carried over from the unit price to the total price of a line item. When an order total is calculated, it combines all the components of every line item on the order. When the unit price is altered by this action, the selected type of price component will be added to its data array and reflected in the order total display when it is formatted with components showing. Defaults to base price, which displays as the order Subtotal.'),
          'options list' => 'commerce_price_component_titles',
          'default value' => 'list_price',
        ),
        'round_mode' => array(
          'type' => 'integer',
          'label' => t('Price rounding mode'),
          'description' => t('Round the resulting price amount after performing this operation.'),
          'options list' => 'commerce_round_mode_options_list',
          'default value' => COMMERCE_ROUND_HALF_UP,
        ),
      ),
      'group' => t('Commerce pricelist'),
    );
  }

  return $actions;
}

/**
 * Rules action: loads and applies a pricelist rate to the given line item.
 */
function commerce_pricelist_rate_rules_apply($line_item, $user = FALSE) {
  $commerce_pricelist_price_array = array();
  // @todo commerce_product is maybe just one of many possible product types?
  $product_id = $line_item->commerce_product['und'][0]['product_id'];
  if ($product_id) {
    // We have a regular commerce product.
    $quantity = $line_item->quantity;
    $product = commerce_product_load($product_id);
    $sku = $product->sku;

    // Default to current user
    if (!$user) {
      global $user;
    }

    $time = ($line_item->created == "") ? FALSE : $line_item->created;
    $pricelist_price = commerce_pricelist_get_user_price($user, $sku, $quantity, $time);

    // If pricelist was applied, return the price array as a new variable for use in
    // subsequent actions.
    if ($pricelist_price) {
      $commerce_pricelist_price_array[] = $pricelist_price;
    }
  }

  return array('commerce_pricelist_price_array' => $commerce_pricelist_price_array);
}


/**
 * Rules action: set the price to a specific amount and apply inclusive taxes.
 *
 * @see commerce_line_item_unit_price_amount
 * @see commerce_tax_field_attach_load
 */
function commerce_pricelist_unit_price_amount($line_item, $amount, $component_name, $round_mode) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $unit_price['amount'];
    $updated_amount = commerce_round($round_mode, $amount);

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $unit_price['currency_code'],
      'data' => array(),
    );

    // If it specifies a inclusive tax and we can load it...
    if (!empty($unit_price['data']['include_tax']) &&
      $tax_rate = commerce_tax_rate_load($unit_price['data']['include_tax'])) {
      // Reverse apply the tax.
      $tax_amount = $difference['amount'] - ($difference['amount'] / (1 + $tax_rate['rate']));
      $tax_amount = commerce_tax_rate_round_amount($tax_rate, $tax_amount);

      // Subtract taxes
      $difference['amount'] -= $tax_amount;

      // Add the tax to the data array.
      $tax_component = $difference;
      $tax_component['amount'] = $tax_amount;
      $tax_component['data']['tax_rate'] = $tax_rate;

      $wrapper->commerce_unit_price->data = commerce_price_component_add(
        $wrapper->commerce_unit_price->value(),
        $tax_rate['price_component'],
        $tax_component,
        TRUE);
    }

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      $component_name,
      $difference,
      TRUE
    );
  }
}

/**
 * @}
 */
