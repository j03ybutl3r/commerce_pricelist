<?php

namespace Drupal\Tests\commerce_pricelist\Functional;

use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\CsvFileObject;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the price list item UI.
 *
 * @group commerce_pricelist
 */
class PriceListItemTest extends CommerceBrowserTestBase {

  /**
   * A test price list.
   *
   * @var \Drupal\commerce_pricelist\Entity\PriceListInterface
   */
  protected $priceList;

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $firstVariation;

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $secondVariation;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_pricelist',
    'commerce_pricelist_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_pricelist',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $this->priceList = $this->createEntity('commerce_pricelist', [
      'type' => 'commerce_product_variation',
      'name' => $this->randomMachineName(8),
      'start_date' => '2018-07-07',
    ]);
    $this->firstVariation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'RED-SHIRT',
      'title' => 'Red shirt',
      'price' => new Price('12.00', 'USD'),
    ]);
    $this->secondVariation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'BLUE-SHIRT',
      'title' => 'Blue shirt',
      'price' => new Price('11.00', 'USD'),
    ]);
  }

  /**
   * Tests adding a price list item.
   */
  public function testAdd() {
    $collection_url = Url::fromRoute('entity.commerce_pricelist_item.collection', [
      'commerce_pricelist' => $this->priceList->id(),
    ]);
    $this->drupalGet($collection_url->toString());
    $this->clickLink('Add price');

    $this->submitForm([
      'purchasable_entity[0][target_id]' => 'Red shirt (1)',
      'quantity[0][value]' => '10',
      'price[0][number]' => 50,
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved the Red shirt: $50.00 price.');

    $price_list_item = PriceListItem::load(1);
    $this->assertEquals($this->priceList->id(), $price_list_item->getPriceListId());
    $this->assertEquals($this->firstVariation->id(), $price_list_item->getPurchasableEntityId());
    $this->assertEquals('10', $price_list_item->getQuantity());
    $this->assertEquals(new Price('50', 'USD'), $price_list_item->getPrice());
  }

  /**
   * Tests editing a price list item.
   */
  public function testEdit() {
    $price_list_item = $this->createEntity('commerce_pricelist_item', [
      'type' => 'commerce_product_variation',
      'price_list_id' => $this->priceList->id(),
      'purchasable_entity' => $this->firstVariation->id(),
      'quantity' => '10',
      'price' => new Price('50', 'USD'),
    ]);
    $this->drupalGet($price_list_item->toUrl('edit-form'));
    $this->submitForm([
      'purchasable_entity[0][target_id]' => 'Blue shirt (2)',
      'quantity[0][value]' => '9',
      'price[0][number]' => 40,
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved the Blue shirt: $40.00 price.');

    \Drupal::service('entity_type.manager')->getStorage('commerce_pricelist_item')->resetCache([$price_list_item->id()]);
    $price_list_item = PriceListItem::load(1);
    $this->assertEquals($this->secondVariation->id(), $price_list_item->getPurchasableEntityId());
    $this->assertEquals('9', $price_list_item->getQuantity());
    $this->assertEquals(new Price('40', 'USD'), $price_list_item->getPrice());
  }

  /**
   * Tests duplicating a price list item.
   */
  public function testDuplicate() {
    $price_list_item = $this->createEntity('commerce_pricelist_item', [
      'type' => 'commerce_product_variation',
      'price_list_id' => $this->priceList->id(),
      'purchasable_entity' => $this->firstVariation->id(),
      'quantity' => '10',
      'price' => new Price('50', 'USD'),
    ]);
    $this->drupalGet($price_list_item->toUrl('duplicate-form'));
    $this->assertSession()->pageTextContains('Duplicate Red shirt: $50.00');
    $this->submitForm([
      'quantity[0][value]' => '20',
      'price[0][number]' => 25,
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved the Red shirt: $25.00 price.');

    \Drupal::service('entity_type.manager')->getStorage('commerce_pricelist_item')->resetCache([$price_list_item->id()]);
    // Confirm that the original price list item is unchanged.
    $price_list_item = PriceListItem::load(1);
    $this->assertEquals($this->priceList->id(), $price_list_item->getPriceListId());
    $this->assertEquals($this->firstVariation->id(), $price_list_item->getPurchasableEntityId());
    $this->assertEquals('10', $price_list_item->getQuantity());
    $this->assertEquals(new Price('50', 'USD'), $price_list_item->getPrice());

    // Confirm that the new price list item has the expected data.
    $price_list_item = PriceListItem::load(2);
    $this->assertEquals($this->priceList->id(), $price_list_item->getPriceListId());
    $this->assertEquals($this->firstVariation->id(), $price_list_item->getPurchasableEntityId());
    $this->assertEquals('20', $price_list_item->getQuantity());
    $this->assertEquals(new Price('25', 'USD'), $price_list_item->getPrice());
  }

  /**
   * Tests deleting a price list item.
   */
  public function testDelete() {
    $price_list_item = $this->createEntity('commerce_pricelist_item', [
      'type' => 'commerce_product_variation',
      'price_list_id' => $this->priceList->id(),
      'purchasable_entity' => $this->firstVariation->id(),
      'quantity' => '10',
      'price' => new Price('50', 'USD'),
    ]);
    $this->drupalGet($price_list_item->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_pricelist_item')->resetCache([$price_list_item->id()]);
    $price_list_item_exists = (bool) PriceListItem::load($price_list_item->id());
    $this->assertFalse($price_list_item_exists);
  }

  /**
   * Tests importing price list items and deleting existing price list items.
   */
  public function testImportPriceListItemsWithDelete() {
    // A price list item to be deleted.
    $price_list_item = $this->createEntity('commerce_pricelist_item', [
      'type' => 'commerce_product_variation',
      'price_list_id' => $this->priceList->id(),
      'purchasable_entity' => $this->firstVariation->id(),
      'quantity' => '10',
      'price' => new Price('666', 'USD'),
    ]);

    $collection_url = Url::fromRoute('entity.commerce_pricelist_item.collection', [
      'commerce_pricelist' => $this->priceList->id(),
    ]);
    $this->drupalGet($collection_url->toString());
    $this->clickLink('Import prices');

    $filepath = drupal_get_path('module', 'commerce_pricelist_test') . '/files/prices.csv';
    $this->getSession()->getPage()->attachFileToField('files[csv]', $filepath);
    $this->submitForm([
      'mapping[quantity_column]' => 'qty',
      'mapping[list_price_column]' => 'msrp',
      'mapping[currency_column]' => 'currency',
      'delete_existing' => TRUE,
    ], 'Import prices');
    $this->assertSession()->pageTextContains('Imported 2 prices.');
    $this->assertSession()->pageTextContains('Skipped 2 prices during import.');
    $this->assertSession()->pageTextContains('Red shirt');
    $this->assertSession()->pageTextContains('Blue shirt');

    /** @var \Drupal\Core\Entity\EntityStorageInterface $price_list_item_storage */
    $price_list_item_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_pricelist_item');
    // Confirm that the existing price list item was deleted.
    $price_list_item_storage->resetCache([$price_list_item->id()]);
    $price_list_item_exists = (bool) PriceListItem::load($price_list_item->id());
    $this->assertFalse($price_list_item_exists);

    // Confirm that two new price list items have been created.
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface[] $price_list_items */
    $price_list_items = $price_list_item_storage->loadMultiple();
    $this->assertCount(2, $price_list_items);
    $first_price_list_item = reset($price_list_items);
    $this->assertEquals($this->priceList->id(), $first_price_list_item->getPriceListId());
    $this->assertEquals($this->firstVariation->id(), $first_price_list_item->getPurchasableEntityId());
    $this->assertEquals('1', $first_price_list_item->getQuantity());
    $this->assertEquals(new Price('50', 'USD'), $first_price_list_item->getListPrice());
    $this->assertEquals(new Price('40', 'USD'), $first_price_list_item->getPrice());
    $this->assertTrue($first_price_list_item->isEnabled());

    $second_price_list_item = end($price_list_items);
    $this->assertEquals($this->priceList->id(), $second_price_list_item->getPriceListId());
    $this->assertEquals($this->secondVariation->id(), $second_price_list_item->getPurchasableEntityId());
    $this->assertEquals('3', $second_price_list_item->getQuantity());
    $this->assertEquals(new Price('99.99', 'USD'), $second_price_list_item->getListPrice());
    $this->assertEquals(new Price('89.99', 'USD'), $second_price_list_item->getPrice());
    $this->assertTrue($second_price_list_item->isEnabled());
  }

  /**
   * Tests importing price list items and updating existing price list items.
   */
  public function testImportPriceListItemsWithUpdate() {
    $collection_url = Url::fromRoute('entity.commerce_pricelist_item.collection', [
      'commerce_pricelist' => $this->priceList->id(),
    ]);
    $this->drupalGet($collection_url);
    $this->clickLink('Import prices');

    $filepath = drupal_get_path('module', 'commerce_pricelist_test') . '/files/prices.csv';
    $this->getSession()->getPage()->attachFileToField('files[csv]', $filepath);
    $this->submitForm([
      'mapping[quantity_column]' => 'qty',
      'mapping[list_price_column]' => 'msrp',
      'mapping[currency_column]' => 'currency',
      'delete_existing' => FALSE,
    ], 'Import prices');
    $this->assertSession()->pageTextContains('Imported 2 prices.');
    $this->assertSession()->pageTextContains('Skipped 2 prices during import.');

    $price_list_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_pricelist_item');
    // Confirm that two new price list items have been created.
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface[] $price_list_items */
    $price_list_items = $price_list_item_storage->loadMultiple();
    $this->assertCount(2, $price_list_items);

    $collection_url = Url::fromRoute('entity.commerce_pricelist_item.collection', [
      'commerce_pricelist' => $this->priceList->id(),
    ]);
    $this->drupalGet($collection_url);
    $this->clickLink('Import prices');

    $filepath = drupal_get_path('module', 'commerce_pricelist_test') . '/files/prices_update.csv';
    $this->getSession()->getPage()->attachFileToField('files[csv]', $filepath);
    $this->submitForm([
      'mapping[quantity_column]' => 'qty',
      'mapping[list_price_column]' => 'msrp',
      'mapping[currency_column]' => 'currency',
      'delete_existing' => FALSE,
    ], 'Import prices');
    $this->assertSession()->pageTextContains('Imported 1 price.');
    $this->assertSession()->pageTextContains('Updated 2 prices.');
    $this->assertSession()->pageTextContains('Skipped 1 price during import.');

    $price_list_item_storage->resetCache();
    // Confirm that quantities 1 and 3 were updated, and quantity 5 was created.
    $price_list_item_ids = $price_list_item_storage->getQuery()
      ->condition('purchasable_entity', $this->firstVariation->id())
      ->condition('quantity', 1)
      ->execute();
    $this->assertCount(1, $price_list_item_ids);
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface $price_list_item */
    $price_list_item = $price_list_item_storage->load(reset($price_list_item_ids));
    $this->assertEquals($this->priceList->id(), $price_list_item->getPriceListId());
    $this->assertEquals($this->firstVariation->id(), $price_list_item->getPurchasableEntityId());
    $this->assertEquals('1', $price_list_item->getQuantity());
    $this->assertEquals(new Price('60.00', 'USD'), $price_list_item->getListPrice());
    $this->assertEquals(new Price('50.00', 'USD'), $price_list_item->getPrice());
    $this->assertTrue($price_list_item->isEnabled());

    $price_list_item_ids = $price_list_item_storage->getQuery()
      ->condition('purchasable_entity', $this->secondVariation->id())
      ->condition('quantity', 3)
      ->execute();
    $this->assertCount(1, $price_list_item_ids);
    $price_list_item = $price_list_item_storage->load(reset($price_list_item_ids));
    $this->assertEquals($this->priceList->id(), $price_list_item->getPriceListId());
    $this->assertEquals($this->secondVariation->id(), $price_list_item->getPurchasableEntityId());
    $this->assertEquals('3', $price_list_item->getQuantity());
    $this->assertEquals(new Price('89.99', 'USD'), $price_list_item->getListPrice());
    $this->assertEquals(new Price('79.99', 'USD'), $price_list_item->getPrice());
    $this->assertTrue($price_list_item->isEnabled());

    $price_list_item_ids = $price_list_item_storage->getQuery()
      ->condition('purchasable_entity', $this->secondVariation->id())
      ->condition('quantity', 5)
      ->execute();
    $this->assertCount(1, $price_list_item_ids);
    $price_list_item = $price_list_item_storage->load(reset($price_list_item_ids));
    $this->assertEquals($this->priceList->id(), $price_list_item->getPriceListId());
    $this->assertEquals($this->secondVariation->id(), $price_list_item->getPurchasableEntityId());
    $this->assertEquals('5', $price_list_item->getQuantity());
    $this->assertEquals(new Price('89.99', 'USD'), $price_list_item->getListPrice());
    $this->assertEquals(new Price('79.99', 'USD'), $price_list_item->getPrice());
    $this->assertTrue($price_list_item->isEnabled());
  }

  /**
   * Tests exporting price list items.
   */
  public function testExportPriceListItems() {
    // Create 20 price list items for each of our 2 test variations.
    $expected_rows = [];
    foreach ([$this->firstVariation, $this->secondVariation] as $variation) {
      for ($i = 1; $i <= 20; $i++) {
        $price_list_item = $this->createEntity('commerce_pricelist_item', [
          'type' => 'commerce_product_variation',
          'price_list_id' => $this->priceList->id(),
          'purchasable_entity' => $variation->id(),
          'quantity' => $i,
          'price' => new Price('666', 'USD'),
        ]);
        $expected_rows[] = [
          'purchasable_entity' => $variation->getSku(),
          'quantity' => $price_list_item->getQuantity(),
          'list_price' => '',
          'price' => $price_list_item->getPrice()->getNumber(),
          'currency_code' => 'USD',
        ];
      }
    }
    $collection_url = Url::fromRoute('entity.commerce_pricelist_item.collection', [
      'commerce_pricelist' => $this->priceList->id(),
    ]);
    $this->drupalGet($collection_url->toString());
    $this->clickLink('Export prices');
    $this->submitForm([
      'mapping[quantity_column]' => 'qty',
      'mapping[list_price_column]' => 'msrp',
      'mapping[currency_column]' => 'currency',
    ], 'Export prices');
    $this->assertSession()->pageTextContains('Exported 40 prices.');
    $csv = new CsvFileObject('temporary://pricelist-1-prices.csv', TRUE, [
      'product_variation' => 'purchasable_entity',
      'qty' => 'quantity',
      'msrp' => 'list_price',
      'price' => 'price',
      'currency' => 'currency_code',
    ]);
    foreach ($expected_rows as $expected_row) {
      $row = $csv->current();
      $this->assertEquals($expected_row, $row);
      $csv->next();
    }
    $this->assertEquals($csv->count(), 40);
  }

}
