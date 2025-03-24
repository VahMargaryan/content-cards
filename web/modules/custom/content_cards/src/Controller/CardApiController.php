<?php

namespace Drupal\content_cards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for filtering Cards by title or tags.
 */
class CardApiController extends ControllerBase {

  protected FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('file_url_generator')
    );
  }

  /**
   * Returns filtered cards as cacheable JSON response.
   */
  public function getFilteredCards(Request $request): JsonResponse {
    $title = $request->query->get('title');
    $tag = $request->query->get('tag');

    $results = [];
    $statusCode = 200;

    $cacheContexts = ['url.query_args:title', 'url.query_args:tag'];
    $cacheTags = ['node_list', 'taxonomy_term_list'];

    try {
      $nodeStorage = $this->entityTypeManager()->getStorage('node');
      $termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');

      $query = $nodeStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'cards')
        ->condition('status', 1);

      // Optional title filter
      if (!empty($title)) {
        $query->condition('title', '%' . $title . '%', 'LIKE');
      }

      // Optional tag filter (ID or name)
      if (!empty($tag)) {
        $tagIds = [];

        if (is_numeric($tag)) {
          $tagIds[] = (int) $tag;
        }
        else {
          $terms = $termStorage->loadByProperties([
            'vid' => 'tags',
            'name' => $tag,
          ]);
          foreach ($terms as $term) {
            $tagIds[] = $term->id();
          }
        }

        if (!empty($tagIds)) {
          $query->condition('field_card_tags.target_id', $tagIds, 'IN');
          foreach ($tagIds as $tid) {
            $cacheTags[] = "taxonomy_term:$tid";
          }
        }
        else {
          $response = new CacheableJsonResponse([
            'status' => 'error',
            'message' => 'No matching tags found.',
            'data' => [],
          ], 404);
          $response->addCacheableDependency([
            '#cache' => [
              'contexts' => $cacheContexts,
              'tags' => $cacheTags,
              'max-age' => 3600,
            ],
          ]);
          return $response;
        }
      }

      $nids = $query->range(0, 50)->execute();

      if (!empty($nids)) {
        $nodes = $nodeStorage->loadMultiple($nids);

        foreach ($nodes as $node) {
          $imageUrl = '';
          if (!$node->get('field_card_image')->isEmpty()) {
            $media = $node->get('field_card_image')->entity;
            if ($media && $media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
              $file = $media->get('field_media_image')->entity;
              if ($file) {
                $imageUrl = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
              }
            }
          }

          $tagNames = [];
          if (!$node->get('field_card_tags')->isEmpty()) {
            foreach ($node->get('field_card_tags')->referencedEntities() as $term) {
              $tagNames[] = $term->label();
              $cacheTags[] = "taxonomy_term:{$term->id()}";
            }
          }

          $results[] = [
            'nid' => $node->id(),
            'title' => $node->label(),
            'description' => $node->get('field_card_description')->value,
            'image' => $imageUrl,
            'tags' => $tagNames,
            'url' => $node->toUrl()->setAbsolute()->toString(),
          ];

          $cacheTags[] = "node:{$node->id()}";
        }
      }
      else {
        $statusCode = 404;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('content_cards')->error($e->getMessage());
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Internal Server Error.',
        'data' => [],
      ], 500);
    }

    $response = new CacheableJsonResponse([
      'status' => $statusCode === 200 ? 'success' : 'error',
      'message' => $statusCode === 200 ? '' : 'No matching content found.',
      'data' => $results,
    ], $statusCode);

    $response->addCacheableDependency([
      '#cache' => [
        'contexts' => $cacheContexts,
        'tags' => array_unique($cacheTags),
        'max-age' => 3600,
      ],
    ]);

    return $response;
  }
}
