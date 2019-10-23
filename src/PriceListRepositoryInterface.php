<?php

namespace Drupal\commerce_pricelist;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

interface PriceListRepositoryInterface {

  /**
   * Loads the price list item for the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListItemInterface|null
   *   The price list item, NULL if no matching price list item could be found.
   */
  public function loadItem(PurchasableEntityInterface $entity, $quantity, Context $context);

}
