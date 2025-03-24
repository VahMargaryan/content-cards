<?php

namespace Drupal\content_cards\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;


/**
 * Provides a block to display 3 recent cards.
 *
 * @Block(
 *   id = "recent_cards_block",
 *   admin_label = @Translation("Recent Cards Block")
 * )
 */
class RecentCardsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, FileUrlGeneratorInterface $fileUrlGenerator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery()
      ->condition('type', 'cards')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 3);

    $nids = $query->execute();
    $nodes = $nodeStorage->loadMultiple($nids);

    $cards = [];

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

      $cards[] = [
        'title' => $node->label(),
        'description' => $node->get('field_card_description')->value,
        'url' => $node->toUrl()->toString(),
        'image' => $imageUrl,
      ];
    }

    $build = [
      '#theme' => 'recent_cards_block',
      '#cards' => $cards,
      '#attached' => [
        'library' => [
          'content_cards/recent_cards_styles',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list'],
        'contexts' => ['user.roles'],
        'max-age' => 600,
      ],
    ];


    $cacheMetadata = new CacheableMetadata();
    foreach ($nodes as $node) {
      $cacheMetadata->addCacheableDependency($node);
    }
    $cacheMetadata->applyTo($build);

    return $build;
  }

}
