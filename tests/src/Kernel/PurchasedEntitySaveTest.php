<?php

namespace Drupal\Tests\commerce_pricelist\Kernel;

use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Tests the the action of saving purchased entity.
 *
 * @group commerce_pricelist
 */
class PurchasedEntitySaveTest extends PriceListKernelTestBase {

  /**
   * Test the action of saving a purchased entity.
   */
  public function testPurchasedEntitySave() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variationa = ProductVariation::create([
      'type' => 'default',
    ]);
    $variationa->save();
    $variationb = ProductVariation::create([
      'type' => 'default',
    ]);
    $variationb->save();

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
      'variations' => [$variationa, $variationb],
    ]);
    $product->save();

    /** @var \Drupal\commerce_pricelist\Entity\PriceListItem $priceListItem */
    $priceListItem = PriceListItem::create([
      'type' => 'default',
      'purchased_entity' => $variationb,
    ]);
    $priceListItem->save();
    $this->assertEquals(NULL, $priceListItem->getName());
    $variationb->set('field_price_list_item', [$priceListItem]);
    $variationb->save();
    $this->assertEquals($variationb->getTitle(), $priceListItem->getName());

    /** @var \Drupal\commerce_pricelist\Entity\PriceList $priceList */
    $priceList = PriceList::create([
      'type' => 'default',
      'title' => 'My Price list Title',
      'field_price_list_item' => [$priceListItem],
    ]);
    $priceList->save();

    // Test saving purchased entity from issue.
    $product->setTitle('Llama');
    $variationb->save();

    $this->assertEquals('My Product Title', $priceListItem->getName());

    $product->save();
    $this->assertEquals($variationb->id(), $priceListItem->getPurchasedEntity()->id());

  }

}
