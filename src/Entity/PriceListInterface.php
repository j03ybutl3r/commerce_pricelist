<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining price list entities.
 *
 * @ingroup commerce_pricelist
 */
interface PriceListInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the price list type.
   *
   * @return string
   *   The price list type.
   */
  public function getType();

  /**
   * Gets the price list name.
   *
   * @return string
   *   Name of the price list.
   */
  public function getName();

  /**
   * Sets the price list name.
   *
   * @param string $name
   *   The price list name.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListInterface
   *   The called price list entity.
   */
  public function setName($name);

  /**
   * Gets the price list creation timestamp.
   *
   * @return int
   *   Creation timestamp of the price list.
   */
  public function getCreatedTime();

  /**
   * Sets the price list creation timestamp.
   *
   * @param int $timestamp
   *   The price list creation timestamp.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListInterface
   *   The called price list entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the price list's item list.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListItemInterface[]
   *   The price list items.
   */
  public function getItems();

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

}
