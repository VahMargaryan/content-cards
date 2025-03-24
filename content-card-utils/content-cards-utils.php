<?php

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

// Step 1: Create tags and map name => term ID.
$tag_names = ['Design', 'Technology', 'Education', 'Health', 'Travel'];
$tag_map = [];

$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

print "Creating tags...\n";

foreach ($tag_names as $name) {
  $existing_ids = $term_storage->getQuery()
    ->condition('vid', 'tags')
    ->condition('name', $name)
    ->accessCheck(FALSE)
    ->execute();

  if (!empty($existing_ids)) {
    $tid = reset($existing_ids);
    print "Tag exists: $name (tid: $tid)\n";
  }
  else {
    $term = Term::create([
      'vid' => 'tags',
      'name' => $name,
    ]);
    $term->save();
    $tid = $term->id();
    print "Created tag: $name (tid: $tid)\n";
  }

  $tag_map[$name] = $tid;
}

// Step 2: Sample content using tag names from the map.
$sample_data = [
  [
    'title' => 'Design Card',
    'description' => 'A card about design trends.',
    'tags' => ['Design'],
  ],
  [
    'title' => 'Tech Innovations',
    'description' => 'Latest in technology.',
    'tags' => ['Technology', 'Education'],
  ],
  [
    'title' => 'Health & Travel',
    'description' => 'Tips on health and travel.',
    'tags' => ['Health', 'Travel'],
  ],
];

print "\nCreating sample card content...\n";

foreach ($sample_data as $data) {
  $tag_ids = array_map(fn($name) => $tag_map[$name], $data['tags']);

  $node = Node::create([
    'type' => 'cards',
    'title' => $data['title'],
    'field_card_description' => $data['description'],
    'field_card_tags' => $tag_ids,
    'uid' => 1,
    'status' => 1,
  ]);
  $node->save();

  print "Created node: {$data['title']} (nid: {$node->id()})\n";
}

print "\n✅ Script complete: Tags and sample cards created.\n";
