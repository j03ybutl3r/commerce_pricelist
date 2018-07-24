<?php

namespace Drupal\commerce_pricelist;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class PriceListPriceResolver implements PriceResolverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A static cache of loaded price list IDs.
   *
   * @var array
   */
  protected $priceListIds;

  /**
   * Constructs a new PriceListPriceResolver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $price_list_ids = $this->loadPriceListIds($entity->getEntityTypeId(), $context);
    if (empty($price_list_ids)) {
      return NULL;
    }

    $price_list_item_storage = $this->entityTypeManager->getStorage('commerce_pricelist_item');
    $query = $price_list_item_storage->getQuery();
    $query
      ->condition('type', $entity->getEntityTypeId())
      ->condition('price_list_id', $price_list_ids, 'IN')
      ->condition('quantity', $quantity, '<=')
      ->condition('purchasable_entity', $entity->id())
      ->sort('quantity', 'ASC');
    $result = $query->execute();
    if (empty($result)) {
      return NULL;
    }

    /** @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface[] $price_list_items */
    $price_list_items = $price_list_item_storage->loadMultiple($result);
    if (count($price_list_items) > 1) {
      // Multiple matching price list items found.
      // First, reduce to one per price list, by selecting the quantity tier.
      $grouped_price_list_items = [];
      foreach ($price_list_items as $price_list_item) {
        $price_list_id = $price_list_item->getPriceListId();
        $grouped_price_list_items[$price_list_id] = $price_list_item;
      }
      // Then, select the one whose price list has the smallest weight.
      $price_list_weights = [];
      foreach ($grouped_price_list_items as $price_list_id => $price_list_item) {
        $price_list_weight = array_search($price_list_id, $price_list_ids);
        $price_list_weights[$price_list_id] = $price_list_weight;
      }
      asort($price_list_weights);
      $sorted_price_list_ids = array_keys($price_list_weights);
      $price_list_id = reset($sorted_price_list_ids);
      $price_list_item = $grouped_price_list_items[$price_list_id];
    }
    else {
      $price_list_item = reset($price_list_items);
    }

    return $price_list_item ? $price_list_item->getPrice() : NULL;
  }

  /**
   * Loads the available price list IDs for the given bundle and context.
   *
   * @param string $bundle
   *   The price list bundle.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return int[]
   *   The price list IDs.
   */
  protected function loadPriceListIds($bundle, Context $context) {
    $customer_id = $context->getCustomer()->id();
    $store_id = $context->getStore()->id();
    $today = gmdate('Y-m-d', $context->getTime());
    $cache_key = sprintf('%s:%s:%s:%s', $bundle, $customer_id, $store_id, $today);
    if (!isset($this->priceListIds[$cache_key])) {
      $price_list_storage = $this->entityTypeManager->getStorage('commerce_pricelist');
      $query = $price_list_storage->getQuery();
      $query
        ->condition('type', $bundle)
        ->condition('stores', [$store_id], 'IN')
        ->condition('start_date', $today, '<=')
        ->condition($query->orConditionGroup()
          ->condition('end_date', $today, '>=')
          ->notExists('end_date')
        )
        ->condition('status', TRUE)
        ->sort('weight', 'ASC')
        ->sort('id', 'DESC');
      $result = $query->execute();
      $this->priceListIds[$cache_key] = array_values($result);
    }

    return $this->priceListIds[$cache_key];
  }

}
