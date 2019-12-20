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
use Drupal\paragraphs_paste\ParagraphsPastePluginBase;
use Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin\OEmbedUrl;
use Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin\Text;
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
      '#value' => $this->t('Paste'),
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

    $pasted_data = json_decode(
      NestedArray::getValue(
        $form_state->getUserInput(),
        array_merge(array_slice($submit['button']['#parents'], 0, -1), ['paste_content'])
      )
    );

    // Split on urls and double newlines.
    $data = preg_split('~(https?://[^\s/$.?#].[^\s]*|[\r\n]+\s?[\r\n]+)~', $pasted_data, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $items = self::traverseData($data);

    foreach ($items as $item) {
      if ($item->plugin instanceof ParagraphsPastePluginBase) {
        $paragraph_entity = $item->plugin->build($item->value);
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
    }

    ParagraphsWidget::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);
    $form_state->setRebuild();
  }

  /**
   * Traverse pasted data.
   *
   * @param array $data
   *   Pasted data.
   *
   * @return array
   *   Enriched data.
   */
  public static function traverseData(array $data) {
    $plugin_manager = \Drupal::service('plugin.manager.paragraphs_paste.plugin');
    $results = [];

    // Enrich pasted data with plugins.
    foreach ($data as $value) {
      $results[] = (object) ['plugin' => $plugin_manager->getPluginFromInput($value), 'value' => $value];
    }

    foreach ($results as $key => $result) {
      // Merge text items following a split on an oembed url.
      if ($result->plugin instanceof OEmbedUrl) {

        $iterator = $key + 1;
        while ($iterator < count($results)) {
          $current = $iterator;
          $next = $iterator + 1;

          if ($results[$current]->plugin instanceof Text && $results[$next]->plugin instanceof Text) {
            $results[$next]->value = $results[$current]->value . $results[$next]->value;
            $results[$current]->plugin = FALSE;
          }
          $iterator++;
        }
      }
    }

    return $results;
  }

}
