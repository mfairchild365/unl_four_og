<?php

/**
 * Implements template_preprocess_page().
 */
function unl_og_preprocess_page(&$vars, $hook) {
  if (module_exists('og_context')) {
    // Set site_name to Group's display name.
    if (!empty($vars['node'])) {
      $group_context = og_context();

      // Make sure that the current page has a group associated with it.
      if ($group = node_load($group_context['gid'])) {
        $vars['site_name'] = $group->title;
      }
    }
//     //if not dealing with a node, Are we still in group context - views?
//     if(!$vars['og_id'] && $group = og_get_group_context()){
//         $vars['site_name'] = print_r($vars['site_name'],true);
//         $vars['og'] =$group->title;
//         $vars['og_id'] = $group->nid;
//     }
//     if ($vars['og_id']) {
//       $vars['site_name'] = $vars['og'] . ' <span>&nbsp;' . $vars['site_name'] . '</span>';
//     }
  }
}

/**
 * Implements hook_html_head_alter().
 */
function unl_og_html_head_alter(&$head_elements) {
  // Return if <link rel="home"> has already been set elsewhere (in a Context for example).
  foreach ($head_elements as $key => $element) {
    if ($element["#tag"] == 'link' && isset($element['#attributes']['rel']) && $element['#attributes']['rel'] == 'home') {
      return;
    }
  }

  // Otherwise add a <link rel="home"> tag with the current group as the href attribute.
  $group = unl_og_get_current_group();
  if (!$group) {
    return;
  }
  $front_nid = unl_og_get_front_group_id();

  if (isset($group) && $group && isset($front_nid) && (int)$group->nid !== (int)$front_nid) {
    $href = 'node/' . $group->nid;
  }
  else {
    $href = '';
  }

  $head_elements['drupal_add_html_head_link:home'] = array(
    '#tag' => 'link',
    '#attributes' => array(
      'rel' => 'home',
      'href' => url($href, array('absolute' => TRUE)),
    ),
    '#type' => 'html_tag',
  );
}

/**
 * Implements hook_menu_breadcrumb_alter().
 */
function unl_og_menu_breadcrumb_alter(&$active_trail, $item) {
  $active_trail[0]['title'] = 'UNL';

  $group = unl_og_get_current_group();
  if (!$group) {
    return false;
  }
  $front_nid = unl_og_get_front_group_id();

  // Only splice in the current group if the current group is not the main/front group.
  if ($group->nid !== $front_nid) {
    $group_breadcrumb = array(
      'title' => $group->title,
      'href' => 'node/' . $group->nid,
      'link_path' => '',
      'localized_options' => array(),
      'type' => 0,
    );
    array_splice($active_trail, 1, 0, array($group_breadcrumb));
  }
}

/**
 * Implements theme_breadcrumb().
 */
function unl_og_breadcrumb($variables) {
  $group = unl_og_get_current_group();
  if (!$group) {
    return false;
  }
  $front_nid = unl_og_get_front_group_id();

  // Append title of current page -- http://drupal.org/node/133242
  if (!drupal_is_front_page() && $group->nid !== $front_nid) {
    $variables['breadcrumb'][] = drupal_get_title();
    // If on a group page pop the previous line off the array.
    if ($node = menu_get_object()) {
      if ($node->type == 'group') {
        array_pop($variables['breadcrumb']);
      }
    }
  }
  // Add breadcrumb on front page. unl_og_menu_breadcrumb_alter is not called on front page.
  else if (drupal_is_front_page()) {
    $variables['breadcrumb'][] = '<a href="' . url('<front>') . '">UNL</a>';
  }

  $html = '<ul>' . PHP_EOL;
  foreach ($variables['breadcrumb'] as $breadcrumb) {
    $html .= '<li>' .  $breadcrumb . '</li>' . PHP_EOL;
  }
  $html .= '</ul>';

  return $html;
}

/**
 * Custom function that returns the group node of the current group context.
 */
function unl_og_get_current_group() {
  if (module_exists('og_context')) {
    $group_context = og_context();
    return node_load($group_context['gid']);
  }
  return false;
}

/**
 * Custom function that returns the nid of the group being used for <front>.
 */
function unl_og_get_front_group_id() {
  $front_nid = 0;
  $front_url = drupal_get_normal_path(variable_get('site_frontpage', 'node'));
  $front_url = trim($front_url, '/');
  $front = explode('/', $front_url);
  if ($front[0]=='node' && ctype_digit($front[1])) {
    $front_nid = $front[1];
  }
  return $front_nid;
}
