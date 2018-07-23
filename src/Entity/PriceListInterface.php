<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\commerce_store\Entity\EntityStoresInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for price lists.
 */
interface PriceListInterface extends ContentEntityInterface, EntityChangedInterface, EntityStoresInterface {

  /**
   * Gets the price list name.
   *
   * @return string
   *   The price list name.
   */
  public function getName();

  /**
   * Sets the price list name.
   *
   * @param string $name
   *   The price list name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the price list start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The price list start date.
   */
  public function getStartDate();

  /**
   * Sets the price list start date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The price list start date.
   *
   * @return $this
   */
  public function setStartDate(DrupalDateTime $start_date);

  /**
   * Gets the price list end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The price list end date, or NULL
   */
  public function getEndDate();

  /**
   * Sets the price list end date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The price list end date.
   *
   * @return $this
   */
  public function setEndDate(DrupalDateTime $end_date);

  /**
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Get whether the price list is enabled.
   *
   * @return bool
   *   TRUE if the price list is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets whether the price list is enabled.
   *
   * @param bool $enabled
   *   Whether the price list is enabled.
   *
   * @return $this
   */
  public function setEnabled($enabled);

  /**
   * Gets the price list item IDs.
   *
   * No matching getItems() method is provided because there can potentially
   * be thousands of items in a single list, making it too costly to load them
   * all at once.
   *
   * @return int[]
   *   The price list item IDs.
   */
  public function getItemIds();

}
