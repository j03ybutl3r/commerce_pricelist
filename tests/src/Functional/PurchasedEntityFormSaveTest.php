<?php

namespace Drupal\Tests\commerce_pricelist\Functional;

use Drupal\commerce_pricelist\Entity\PriceListItem;

/**
 * Class PurchasedEntityFormSaveTest.
 *
 * Test for https://www.drupal.org/project/commerce_pricelist/issues/2974109
 *
 * @group commerce_pricelist
 */
class PurchasedEntityFormSaveTest extends PriceListBrowserTestBase {

  /**
   * Test the action of saving a purchased entity in form level.
   */
  public function testProductFormSave() {
    $variation_a = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductVariation $variation_b */
    $variation_b = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'variations' => [$variation_a, $variation_b],
      'stores' => $this->stores,
    ]);

    $priceListItem = $this->createEntity('price_list_item', [
      'type' => 'default',
      'purchased_entity' => $variation_b,
    ]);
    $variation_b->set('field_price_list_item', [$priceListItem]);
    $variation_b->save();

    $edit = [
      'title[0][value]' => 'lll',
    ];
    $this->drupalPostForm('/product/' . $this->product->id() . '/edit', $edit, t('Save'));
    \Drupal::service('entity_type.manager')->getStorage('price_list_item')->resetCache([$priceListItem->id()]);
    $priceListItem = PriceListItem::load($priceListItem->id());

    $this->assertEquals($variation_b->id(), $priceListItem->getPurchasedEntity()->id());

  }

}
