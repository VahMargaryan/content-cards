<?php

namespace Drupal\Tests\content_cards\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Functional test for Card API Controller.
 *
 * @group content_cards
 */
class CardApiControllerTest extends BrowserTestBase {

  protected static $modules = [
    'node',
    'taxonomy',
    'user',
    'field',
    'text',
    'content_cards',
  ];

  protected $defaultTheme = 'stark';

  protected $testTerm;

  protected function setUp(): void {
    parent::setUp();

    // Create vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // Create content type.
    $this->drupalCreateContentType(['type' => 'cards']);

    // Create term.
    $this->testTerm = Term::create([
      'vid' => 'tags',
      'name' => 'FunctionalTestTag',
    ]);
    $this->testTerm->save();

    // Create node tagged with the term.
    $node = Node::create([
      'type' => 'cards',
      'title' => 'Functional Test Card',
      'status' => 1,
    ]);
    $node->save();

    // Create user with permission and login.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
  }

  public function testApiWithVariousFilters(): void {
    //Title filter matches card.
    $this->drupalGet('/api/cards?title=Functional');
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertEquals('success', $data['status']);
    $this->assertCount(1, $data['data']);
    $this->assertEquals('Functional Test Card', $data['data'][0]['title']);

    //Non-existent title.
    $this->drupalGet('/api/cards?title=NoMatchTitle');
    $this->assertSession()->statusCodeEquals(404);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertEquals('error', $data['status']);
    $this->assertEmpty($data['data']);

    //Valid tag filter by name.
    $tagName = $this->testTerm->label();
    $this->drupalGet("/api/cards?tag={$tagName}");
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertEquals('success', $data['status']);
    $this->assertNotEmpty($data['data']);

    //Non-existent tag.
    $this->drupalGet('/api/cards?tag=NoMatchTag');
    $this->assertSession()->statusCodeEquals(404);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertEquals('error', $data['status']);
    $this->assertEmpty($data['data']);
  }

}
