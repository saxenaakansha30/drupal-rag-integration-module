<?php

namespace Drupal\drupal_rag_integration\EntityOperation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\drupal_rag_integration\ApiClient;
use Drupal\drupal_rag_integration\Repository\EntityDocRepository;
use Psr\Log\LoggerInterface;

/**
 * Performs operations on entities with document mappings.
 */
class RagEntityOperations {

  /**
   * The entity document repository service.
   *
   * @var \Drupal\drupal_rag_integration\Repository\EntityDocRepository
   */
  protected $entityDocRepository;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The RAG application Api Client.
   *
   * @var \Drupal\drupal_rag_integration\ApiClient
   */
  protected $apiClient;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\drupal_rag_integration\Repository\EntityDocRepository $entity_doc_repository
   *   The entity document repository service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\drupal_rag_integration\ApiClient $api_client
   *   The api client.
   */
  public function __construct(
    EntityDocRepository $entity_doc_repository,
    LoggerInterface $logger,
    ApiClient $api_client
  ) {
    $this->entityDocRepository = $entity_doc_repository;
    $this->logger = $logger;
    $this->apiClient = $api_client;
  }

  /**
   * Handles insert operations for entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   */
  public function handleInsert(EntityInterface $entity): void {
    $nid = $entity->id();
    try {
      $payload = $this->getInsertPayloadFromEntity($entity);
      $doc_ids = $this->apiClient->callApi('/feed/add', $payload);

      if ($doc_ids && $doc_ids['doc_ids'] ?? NULL) {
        // Insert the new document IDs.
        foreach ($doc_ids['doc_ids'] as $doc_id) {
          $this->entityDocRepository->add($nid, $doc_id);
          $this->logger->info('Document ID @{doc_id} added for Node ID @{nid}.', [
            '@doc_id' => $doc_id,
            '@nid' => $nid,
          ]);
        }
      }
    } catch (\Exception $e) {
      $this->logger->error('An error occurred while handling insert operation for node {nid}: {message}', [
        'nid' => $nid,
        'message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Handles entity update operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   */
  public function handleUpdate(EntityInterface $entity) {
    $nid = $entity->id();
    try {
      $payload = $this->getUpdatePayloadFromEntity($entity);
      $doc_ids = $this->apiClient->callApi('/feed/update', $payload);

      if ($doc_ids && $doc_ids['doc_ids'] ?? NULL) {
        // Update the new document IDs.
        $this->entityDocRepository->update($nid, $doc_ids['doc_ids']);
        $this->logger->info('Document IDs updated for Node ID @{nid}.', [
          '@nid' => $nid,
        ]);
      }
    } catch (\Exception $e) {
      $this->logger->error('An error occurred while handling updating operation for node {nid}: {message}', [
        'nid' => $nid,
        'message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Handles entity delete operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   */
  public function handleDelete(EntityInterface $entity) {
    $nid = $entity->id();
    try {
      $payload = $this->getDeletePayloadFromEntity($entity);
      $doc_ids = $this->apiClient->callApi('/feed/delete', $payload);

      $this->entityDocRepository->delete($nid);
      $this->logger->info('Document IDs deleted for Node ID @{nid}.', [
        '@nid' => $nid,
      ]);
    } catch (\Exception $e) {
      $this->logger->error('An error occurred while handling updating operation for node {nid}: {message}', [
        'nid' => $nid,
        'message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Retrieve the payload from the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   *
   * @return mixed
   *   The payload to send to the API.
   */
  protected function getInsertPayloadFromEntity(EntityInterface $entity) {
    $payload = [
      'nid' => (string) $entity->id(),
      'data' => $entity->body->value,
    ];

    return json_encode($payload);
  }

  /**
   * Retrieve the payload for update API from the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   *
   * @return mixed
   *   The payload to send to the API.
   */
  protected function getUpdatePayloadFromEntity(EntityInterface $entity) {
    $payload = [
      'nid' => (string) $entity->id(),
      'ids' => $this->entityDocRepository->getDocIds($entity->id()),
      'data' => $entity->body->value,
    ];

    return json_encode($payload);
  }

  /**
   * Retrieve the payload for delete API from the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   *
   * @return mixed
   *   The payload to send to the API.
   */
  protected function getDeletePayloadFromEntity(EntityInterface $entity) {
    $payload = [
      'ids' => $this->entityDocRepository->getDocIds($entity->id()),
    ];

    return json_encode($payload);
  }

}
