<?php

namespace Drupal\paragraphs_paste\Form;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\paragraphs_paste\ParagraphsPastePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter the entity form to add access unpublished elements.
 */
class ParagraphsPasteForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The ParagraphsPaste plugin manager.
   *
   * @var \Drupal\paragraphs_paste\ParagraphsPastePluginManager
   */
  protected $pluginManager;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\paragraphs_paste\ParagraphsPastePluginManager $pluginManager
   *   The ParagraphsPaste plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ParagraphsPastePluginManager $pluginManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->pluginManager = $pluginManager;
    $this->config = $configFactory->get('paragraphs_paste.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.paragraphs_paste.plugin'),
      $container->get('config.factory')
    );
  }

  /**
   * Alter the entity form to add access unpublished elements.
   */
  public function formAlter(&$elements, FormStateInterface $form_state, array $context) {

    if ($elements['#cardinality'] !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $form_state->isProgrammed()) {
      return;
    }
    // Construct wrapper id.
    $fieldWrapperId = Html::getId(implode('-', array_merge($context['form']['#parents'], [$elements['#field_name']])) . '-add-more-wrapper');

    $elements['paragraphs_paste']['#attributes']['data-paragraphs-paste'] = 'enabled';
    $elements['paragraphs_paste']['#attached']['library'][] = 'paragraphs_paste/init';

    // Move children to table header and remove $elements['paragraphs_paste'],
    // see paragraphs_preprocess_field_multiple_value_form().
    $elements['paragraphs_paste']['#paragraphs_paste'] = TRUE;

    $elements['paragraphs_paste']['paste_content'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    $elements['paragraphs_paste']['paste_action'] = [
      '#type' => 'submit',
      '#value' => t('Paste'),
      '#submit' => [[get_class($this), 'pasteSubmit']],
      '#attributes' => [
        'class' => ['visually-hidden'],
        'data-paragraphs-paste' => 'enabled',
      ],
      '#ajax' => [
        'callback' => [ParagraphsWidget::class, 'addMoreAjax'],
        'wrapper' => $fieldWrapperId,
      ],
      '#limit_validation_errors' => [['paragraphs_paste']],
    ];
  }

  /**
   * Submit callback.
   */
  public static function pasteSubmit(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state);
    $host = $form_state->getFormObject()->getEntity();

    $values = json_decode(
      NestedArray::getValue(
        $form_state->getUserInput(),
        array_merge(array_slice($submit['button']['#parents'], 0, -1), ['paste_content'])
      )
    );
    /* @var ParagraphsPastePluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.paragraphs_paste.plugin');

    foreach ($values as $value) {
      $plugin = $plugin_manager->getPluginFromInput($value);

      $paragraph_entity = $plugin->build($value);
      /* @var \Drupal\paragraphs\Entity\Paragraph $paragraph_entity */
      $paragraph_entity->setParentEntity($host, $submit['field_name']);
      $submit['widget_state']['paragraphs'][] = [
        'entity' => $paragraph_entity,
        'display' => 'default',
        'mode' => 'edit',
      ];
      $submit['widget_state']['real_items_count']++;
      $submit['widget_state']['items_count']++;
    }

    ParagraphsWidget::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);
    $form_state->setRebuild();
  }

}
