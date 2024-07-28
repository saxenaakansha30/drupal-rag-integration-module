<?php

namespace Drupal\drupal_rag_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\drupal_rag_integration\ApiClient;

/**
 * Provides a form for submitting questions to the FastAPI.
 */
class AskForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The FastAPI client service.
   *
   * @var \Drupal\drupal_rag_integration\ApiClient
   */
  protected ApiClient $apiClient;

  /**
   * Constructs a new AskForm.
   *
   * @param \Drupal\drupal_rag_integration\ApiClient $apiClient
   *   The FastAPI client service.
   */
  public function __construct(ApiClient $api_client) {
    $this->apiClient = $api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AskForm {
    return new static(
      $container->get('drupal_rag_integration.api_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupal_rag_integration_ask_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Define the text field for the question.
    $form['question'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ask a question'),
      '#required' => TRUE,
      '#description' => $this->t('Enter your question for the RAG system.'),
    ];

    // Define the submit button.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ask'),
    ];

    // Display the response if available.
    if ($form_state->get('response') !== NULL) {
      $form['response'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Response: @response', ['@response' => $form_state->get('response')]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $question = $form_state->getValue('question');

    $endpoint = '/ask';
    $payload = json_encode(['question' => $question]);

    $response = $this->apiClient->callApi($endpoint, $payload);

    if (isset($response['response'])) {
      $form_state->set('response', $response['response']);
    } else {
      $form_state->set('response', $this->t('Error occurred: @error', ['@error' => $response['error'] ?? $this->t('Unknown error')]));
    }

    $form_state->setRebuild(TRUE);
  }
}
