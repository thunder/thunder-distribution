<?php

namespace Drupal\thunder_article\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a node revision.
 *
 * @internal
 */
class NodeRevisionRevertDefaultForm extends ConfirmFormBase {
  /**
   * The node revision.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $revision;

  /**
   * Constructs a new NodeRevisionRevertForm.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(protected readonly DateFormatterInterface $dateFormatter, protected readonly TimeInterface $time) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'node_revision_revert_default_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to revert to the default revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.node.edit_form', ['node' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Revert to the default revision from %revision-date.', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL): array {
    $this->revision = $node;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->setRevisionLogMessage($this->t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]));
    $this->revision->setRevisionUserId($this->currentUser()->id());
    $this->revision->setRevisionCreationTime($this->time->getRequestTime());
    $this->revision->setChangedTime($this->time->getRequestTime());
    $this->revision->save();

    $this->logger('content')
      ->notice('@type: reverted %title revision %revision.', [
        '@type' => $this->revision->bundle(),
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]);
    $this->messenger()
      ->addStatus($this->t('@type %title has been reverted to the revision from %revision-date.', [
        '@type' => node_get_type_label($this->revision),
        '%title' => $this->revision->label(),
        '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
      ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\node\NodeInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\node\NodeInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(NodeInterface $revision, FormStateInterface $form_state): NodeInterface {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionTranslationAffected(TRUE);

    return $revision;
  }

}
