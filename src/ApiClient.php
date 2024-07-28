<?php

namespace Drupal\drupal_rag_integration;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with the Rag Application API.
 */
final class ApiClient {

  /**
   * The HTTP client used to make requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private ClientInterface $httpClient;

  /**
   * The logger service for logging messages.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * Constructs a FastAPIClient service object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(ClientInterface $httpClient, LoggerInterface $logger) {
    $this->httpClient = $httpClient;
    $this->logger = $logger;
  }

  /**
   * Call the API with a specific endpoint and payload.
   *
   * @param string $endpoint
   *   The API endpoint to call.
   * @param mixed $payload
   *   The data to send to the endpoint.
   *
   * @return mixed
   *   The API response.
   */
  public function callApi(string $endpoint, $payload) {
    try {
      $options = [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'body' => $payload,
      ];

      $response = $this->httpClient->request('POST', 'http://host.docker.internal:8086' . $endpoint, $options);

      $body = $response->getBody();
      $data = json_decode($body, TRUE);

      return $data;
    } catch (\Exception $e) {
      $this->logger->error('An error occurred while calling the FastAPI: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

}
