<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access control handler for price list items.
 *
 * Price list items are always managed in the scope of their parent
 * (the price list), so they have a simplified permission set, and rely on
 * parent access when possible:
 * - A price list item can be viewed if the parent price list can be viewed.
 * - A price list item can be created, updated or deleted if the user has the
 *   "manage $bundle price_list" permission.
 *
 * The "administer commerce_pricelist" permission is also respected.
 */
class PriceListItemAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface $entity */
    $priceList = $entity->getPriceList();
    if (!$priceList) {
      // The price list item is malformed.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if ($operation == 'view') {
      $result = $priceList->access('view', $account, TRUE);
    }
    else {
      $bundle = $entity->bundle();
      $result = AccessResult::allowedIfHasPermission($account, "manage $bundle price_list_item");
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Create access depends on the "manage" permission because the full entity
    // is not passed, making it impossible to determine the parent price list.
    $result = AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      "manage $entity_bundle price_list_item",
    ], 'OR');

    return $result;
  }

}
