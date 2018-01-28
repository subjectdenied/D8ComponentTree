<?php

namespace Drupal\taxonomy_components;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;


/**
 * Loads taxonomy terms in a tree
 */
class TaxonomyComponents {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * TaxonomyTermTree constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Loads the tree of a vocabulary.
   *
   * @param string $vocabulary
   *   Machine name
   *
   * @return array
   */
  public function load($vocabulary) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary);
    $tree = [];

    foreach ($terms as $tree_object) {
      $this->buildComponentTree($tree, $tree_object, $vocabulary);
    }

    return $tree;
  }

  protected function buildComponentTree(&$tree, $object, $vocabulary) {
    if ($object->depth != 0) {
      return;
    }

    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $tree[$object->tid]->Content = NULL;

    // get related component
    $targetId = Term::load($object->tid)->field_content->target_id;
    $tree[$object->tid]->Content = Node::load($targetId);

    /*
    foreach ($targetField as $item) {
      if ($targetField->entity) {
        $targetId = $item->entity->id();
        $tree[$object->tid]->Content = Node::load($targetId);
      }
    }
    */

    // get children
    $object_children = &$tree[$object->tid]->children;

    $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($object->tid);

    if (!$children) {
      return;
    }

    $child_tree_objects = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid == $child->id()) {
         $this->buildComponentTree($object_children, $child_tree_object, $vocabulary);
        }
      }
    }
  }

}