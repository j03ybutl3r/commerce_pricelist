<?php

/**
 * @file
 * Post update functions for commerce_pricelsit.
 */

/**
 * Grants the "manage price_list_item" permission.
 */
function commerce_pricelist_post_update_6() {
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\user\RoleInterface[] $roles */
  $roles = $entity_type_manager->getStorage('user_role')->loadMultiple();

  foreach ($roles as $role) {
    if (
      $role->hasPermission("update any price_list") ||
      $role->hasPermission("update own price_list")
    ) {
      /** @var \Drupal\commerce_pricelist\Entity\PriceListItemTypeInterface[] $price_list_item_types */
      $price_list_item_types = $entity_type_manager->getStorage('price_list_item_type')
        ->loadMultiple();
      foreach ($price_list_item_types as $price_list_item_type) {
        $role->grantPermission("manage {$price_list_item_type->id()} price_list_item");
      }
    }
    $role->save();
  }
}
