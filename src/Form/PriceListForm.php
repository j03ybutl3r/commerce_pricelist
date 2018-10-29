<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Price list edit forms.
 *
 * @ingroup commerce_pricelist
 */
class PriceListForm extends ContentEntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, MessengerInterface $messenger, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_pricelist\Entity\PriceList */
    $store_query = $this->entityManager->getStorage('commerce_store')->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => t("Products can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_pricelist\Entity\PriceList $priceList */
    $priceList = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;

    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $priceList->getChangedTime(),
    ];

    $last_saved = t('Not saved yet');
    if (!$priceList->isNew()) {
      $last_saved = $this->dateFormatter->format($priceList->getChangedTime(), 'short');
    }

    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#weight' => 100,
      'published' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $priceList->isPublished() ? $this->t('Published') : $this->t('Not published'),
        '#access' => !$priceList->isNew(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
      ],
      'changed' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['entity-meta__last-saved', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Last saved') . '</h4> ' . $last_saved,
      ],
      'author' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['author', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Author') . '</h4> ' . $priceList->getOwner()->getDisplayName(),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $price_list = $this->entity;
    $insert = $price_list->isNew();
    $price_list->save();
    $price_list_link = $price_list->link($this->t('View'));
    $context = ['@type' => $price_list->getType(), '%title' => $price_list->label(), 'link' => $price_list_link];
    $price_list_type = PriceList::load($price_list->bundle());
    $price_list_type_label = $price_list_type ? $price_list_type->label() : FALSE;
    $t_args = ['@type' => $price_list_type_label, '%title' => $price_list->link($price_list->label())];

    if ($insert) {
      $this->logger('price_list')->notice('@type: added %title.', $context);
      $this->messenger()->addStatus($this->t('@type %title has been created.', $t_args));
    }
    else {
      $this->logger('price_list')->notice('@type: updated %title.', $context);
      $this->messenger()->addStatus($this->t('@type %title has been updated.', $t_args));
    }

    foreach ($price_list->field_price_list_item as $item) {
      $itemEntity = $item->get('entity')->getTarget()->getValue();
      $itemEntity->setWeight($item->getValue()['weight']);
      $itemEntity->save();
    }

    $form_state->setRedirect('entity.price_list.collection');
  }

}
