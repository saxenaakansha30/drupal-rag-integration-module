<?php

namespace Drupal\drupal_rag_integration\Repository;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Repository service for entity document mappings.
 *
 * This service provides methods to add, update, retrieve, and delete
 * mappings between Drupal entity IDs and document IDs returned from an
 * external API.
 */
class EntityDocRepository {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a new EntityDocRepository object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(Connection $database, LoggerInterface $logger) {
    $this->database = $database;
    $this->logger = $logger;
  }

  /**
   * Adds a mapping for a document ID and a node ID.
   *
   * @param int $nid
   *   The node ID.
   * @param string $doc_id
   *   The document ID for the node stored.
   * @param string $type
   *   The type of the document.
   *
   * @return int|false
   *   The new record's ID in the drupal_rag_integration_node_doc table
   *   or FALSE in case of an error.
   */
  public function add(int $nid, string $doc_id, string $type = 'default'): mixed {
    try {
      // Insert the new record and return its ID.
      return $this->database->insert('drupal_rag_integration_node_doc')
        ->fields([
          'nid' => $nid,
          'doc_id' => $doc_id,
          'type' => $type,
        ])
        ->execute();
    } catch (\Exception $e) {
      // Log the exception.
      $this->logger->error('An error occurred while adding document ID: {message}', [
        'message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Updates the mappings associated with a given node ID.
   *
   * @param int $nid
   *   The node ID.
   * @param array $doc_ids
   *   An array of new document IDs from the external API.
   * @param string $type
   *   The type of the documents.
   *
   * @return bool
   *   TRUE if the operation is successful, FALSE otherwise.
   */
  public function update(int $nid, array $doc_ids, string $type = 'default'): bool {
    $transaction = $this->database->startTransaction();
    try {
      // First, delete any existing records for the node.
      $this->delete($nid);

      // Insert the new document IDs.
      foreach ($doc_ids as $doc_id) {
        $this->add($nid, $doc_id, $type);
      }
      return TRUE;
    } catch (\Exception $e) {
      // Roll back the transaction and log the exception.
      $transaction->rollBack();
      $this->logger->error('An error occurred while updating document IDs: {message}', [
        'message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Deletes all document mappings for the specified node ID.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return int|false
   *   The number of deleted records or FALSE in case of an error.
   */
  public function delete(int $nid): mixed {
    try {
      // Perform the deletion and return the number of affected rows.
      return $this->database->delete('drupal_rag_integration_node_doc')
        ->condition('nid', $nid)
        ->execute();
    } catch (\Exception $e) {
      // Log the exception.
      $this->logger->error('An error occurred while deleting document IDs for node {nid}: {message}', [
        'nid' => $nid,
        'message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Retrieves all document IDs associated with the specified node ID.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return array
   *   An array of associated document IDs; an empty array if no records are found
   *   or an error occurs.
   */
  public function getDocIds(int $nid): array {
    try {
      // Query the table and fetch the results.
      $query = $this->database->select('drupal_rag_integration_node_doc', 'd')
        ->fields('d', ['doc_id'])
        ->condition('nid', $nid);

      return $query->execute()?->fetchCol() ?: [];
    } catch (\Exception $e) {
      // Log the exception.
      $this->logger->error('An error occurred while retrieving document IDs: {message}', [
        'message' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
