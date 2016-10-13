<?php
class HomesteadMember {

  /**
   * @var int
   */
  public $id;

  /**
   * @var string
   */
  public $plural = 'Homestead Members';

  /**
   * @var string
   */
  public $singular = 'Homestead Member';

  /**
   * @var string
   */
  public $slug = 'homestead-member';

  /**
   * @var bool
   */
  protected $exists = false;

  /**
   * @var WP_Post
   */
  protected $post;

  /**
   * @var array
   */
  protected $supports = [
    'title'
  ];

  /**
   * @var array
   */
  protected $tax = [
    'category' => 'homestead_member_category'
  ];

  /**
   * @var string
   */
  public $type = 'homestead_member';

  /**
   * Create a new instance.
   *
   * @param WP_Post $post
   */
  public function __construct($post = null)
  {
    if ($post) {
      $this->hydrate($post);
    }
  }

  // Public Methods
  // ---------------------------------------------------------------------------

  /**
   * Get the avatar URL.
   *
   * @return string
   */
  public function getAvatarUrl()
  {
    $fbId = get_post_meta($this->id, 'hc_fb_id', true);

    if ($fbId) {
      return 'https://graph.facebook.com/'.$fbId.'/picture?height=600&width=600';
    }

    $avatarUrl = get_post_meta($this->id, 'hc_avatar_url', true);

    if ($avatarUrl) {
      return $avatarUrl;
    }

    return '';
  }

  /**
   * Get the category slugs for JS.
   *
   * @return string
   */
  public function getCategorySlugsForJs()
  {
    $slugs = wp_get_object_terms($this->id, $this->tax['category'], array('fields' => 'slugs'));
    return implode(',', $slugs);
  }

  /**
   * Get the email.
   *
   * @return string
   */
  public function getEmail()
  {
    $email = get_post_meta($this->id, 'hc_email', true);
    $email = antispambot($email);

    return $email;
  }

  /**
   * Get the Facebook URL.
   *
   * @return string
   */
  public function getFbUrl()
  {
    $fbUrl = get_post_meta($this->id, 'hc_fb_url', true);
    return ($fbUrl) ? $fbUrl : '';
  }

  /**
   * Get the name.
   *
   * @return string
   */
  public function getName()
  {
    $name = get_the_title($this->id);
    $name = apply_filters('the_title', $name, $this->id);
    $name = preg_replace('/\s+/i', ' ', $name);

    return $name;
  }

  /**
   * Hydrate the model.
   *
   * @param WP_Post $post
   *
   * @return $this
   */
  public function hydrate($post)
  {
    $this->exists = true;
    $this->id     = $post->ID;
    $this->post   = $post;

    return $this;
  }

  /**
   * Send the "update" email.
   *
   * @param array $input
   *
   * @return bool
   */
  public function sendUpdateEmail($input)
  {
    $key = wp_generate_password(20, false);

    update_post_meta($this->id, 'hc_update_key',       $key);
    update_post_meta($this->id, 'hc_update_timestamp', time());

    ob_start();
    homestead()->renderTemplate('email-update', array(
      'email' => $input['email'],
      'key'   => $key
    ));
    $body = ob_get_clean();

    $to      = $input['email'];
    $subject = 'Update Your Homestead Connect Profile';

    $domain  = homestead()->getDomain();
    $headers = "From: Homestead Connect <no-reply@{$domain}>" . "\r\n";

    return wp_mail($to, $subject, $body, $headers);
  }

  /**
   * Check if this member has approved their profile to be shared.
   *
   * @return boolean
   */
  public function share()
  {
    $share = get_post_meta($this->id, 'hc_share', true);
    return ($share === '1');
  }

  /**
   * Update the model.
   *
   * @param object $member
   * @param array  $input
   *
   * @return null
   */
  public function update($input)
  {
    // Avatar...
    if (!empty($_FILES['avatar']['size'])) {
      require_once ABSPATH.'wp-admin/includes/image.php';
      require_once ABSPATH.'wp-admin/includes/file.php';
      require_once ABSPATH.'wp-admin/includes/media.php';

      $attachmentId = media_handle_upload('avatar', 0, array(), array(
        'test_form' => false
      ));

      if ($attachmentId) {
        $input['avatar_url'] = wp_get_attachment_image_src($attachmentId, 'medium')[0];
      }
    }

    wp_update_post(array(
      'ID'         => $this->id,
      'post_title' => $input['name'],
    ));

    // Meta...
    update_post_meta($this->id, 'hc_avatar_url', isset($input['avatar_url']) ? $input['avatar_url'] : '');
    update_post_meta($this->id, 'hc_email', $input['email']);
    update_post_meta($this->id, 'hc_fb_id', isset($input['fb_id']) ? $input['fb_id'] : '');
    update_post_meta($this->id, 'hc_fb_url', isset($input['fb_url']) ? $input['fb_url'] : '');
    update_post_meta($this->id, 'hc_share', isset($input['share']) ? $input['share'] : '');

    // Categories...
    $catIds = array_map('intval', $input['categories']);
    $catIds = array_unique($catIds);

    wp_set_object_terms($this->id, null,    $this->tax['category']);
    wp_set_object_terms($this->id, $catIds, $this->tax['category']);

    delete_post_meta($this->id, 'hc_update_key');
    delete_post_meta($this->id, 'hc_update_timestamp');
  }

  // Protected Methods
  // ---------------------------------------------------------------------------

  /**
   * Register the post type.
   *
   * @return $this
   */
  protected function registerPostType()
  {
    register_post_type(
      $this->type,
      array(
        'label' => $this->plural,
        'labels' => array(
          'name'                  => $this->plural,
          'singular_name'         => $this->singular,
          'add_new'               => 'Add New',
          'add_new_item'          => "Add New {$this->singular}",
          'edit_item'             => "Edit {$this->singular}",
          'new_item'              => "New {$this->singular}",
          'view_item'             => "View {$this->singular}",
          'search_items'          => "Search {$this->plural}",
          'not_found'             => "No {$this->plural}",
          'not_found_in_trash'    => "No {$this->plural} found in Trash",
          'parent_item_colon'     => "Parent {$this->singular}:",
          'all_items'             => "All {$this->plural}",
          'archives'              => "{$this->singular} Archives",
          'insert_into_item'      => "Insert into {$this->singular}",
          'uploaded_to_this_item' => "Uploaded to this {$this->singular}",
          'featured_image'        => 'Featured Image',
          'set_featured_image'    => 'Set featured image',
          'remove_featured_image' => 'Remove featured image',
          'use_featured_image'    => 'Use as featured image',
          'filter_items_list'     => "Filter {$this->plural} list",
          'items_list_navigation' => "{$this->plural} list navigation",
          'items_list'            => "{$this->plural} list",
          'menu_name'             => $this->plural,
        ),
        'capability_type'     => 'post',
        'can_export'          => true,
        'description'         => '',
        'exclude_from_search' => false,
        'has_archive'         => false,
        'hierarchical'        => false,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_in_admin_bar'   => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_rest'        => false,
        'show_ui'             => true,
        'supports'            => $this->supports,
        'taxonomies'          => array_values($this->tax),
        'rewrite' => array(
          'slug'       => $this->slug,
          'with_front' => false
        )
      )
    );

    return $this;
  }

  /**
   * Register the taxonomies.
   *
   * @return $this
   */
  protected function registerTaxonomies()
  {
    register_taxonomy(
      $this->tax['category'],
      $this->type,
      array(
        'labels' => array(
          'name'          => "{$this->singular} Categories",
          'singular_name' => "{$this->singular} Category",
          'menu_name'     => 'Categories',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud'     => false,
        'rewrite' => array(
          'slug'       => "{$this->slug}-category",
          'with_front' => false,
        )
      )
    );

    return $this;
  }

  /**
   * Send the "help" email.
   *
   * @param array $input
   *
   * @return bool
   */
  protected function sendHelpEmail($input)
  {
    ob_start();
    homestead()->renderTemplate('email-help');
    $body = ob_get_clean();

    $body = str_replace('%name%', $input['name'], $body);
    $body = str_replace('%email%', $input['email'], $body);

    if (!$to = homestead()->getSetting('email')) {
      $to = get_option('admin_email');
    }

    $subject = 'Looking for Help';

    $domain  = homestead()->getDomain();
    $headers = "From: Homestead Connect <no-reply@{$domain}>" . "\r\n";

    return wp_mail($to, $subject, $body, $headers);
  }

  /**
   * Send the "welcome" email.
   *
   * @param array $input
   *
   * @return bool
   */
  protected function sendWelcomeEmail($input) {
    ob_start();
    homestead()->renderTemplate('email-welcome');
    $body = ob_get_clean();

    $to      = $input['email'];
    $subject = 'Welcome to Homestead Connect!';

    $domain  = homestead()->getDomain();
    $headers = "From: Homestead Connect <no-reply@{$domain}>" . "\r\n";

    return wp_mail($to, $subject, $body, $headers);
  }

  // Static Methods
  // ---------------------------------------------------------------------------

  /**
   * Boot.
   *
   * @return $this
   */
  public static function boot()
  {
    $instance = new static();

    $instance
      ->registerTaxonomies()
      ->registerPostType();

    return $instance;
  }

  /**
   * Find a model.
   *
   * @param string $email
   *
   * @return boolean
   **/
  public static function find($email)
  {
    $instance = new static();

    $args = array(
      'post_type'      => $instance->type,
      'posts_per_page' => 1,
      'meta_query' => array(
        array(
          'key'     => 'hc_email',
          'value'   => $email,
          'compare' => '=',
        )
      )
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
      return $instance->hydrate($query->posts[0]);
    }

    return null;
  }

  /**
   * Find a model by ID.
   *
   * @param mixed $id
   *
   * @return boolean
   **/
  public static function findById($id)
  {
    $instance = new static();

    $post = get_post($id);

    if ($post) {
      return $instance->hydrate($post);
    }

    return null;
  }

  /**
   * Get all.
   *
   * @return array
   **/
  public static function get()
  {
    $instance = new static();

    $posts = get_posts(array(
      'post_type'      => $instance->type,
      'posts_per_page' => -1,
    ));

    $members = array();

    foreach ($posts as $post) {
      $members[] = new static($post);
    }

    return $members;
  }

  /**
   * Get the categories.
   *
   * @return array
   */
  public static function getCategories()
  {
    $instance = new static();

    return get_terms($instance->tax['category'], array(
      'hide_empty' => false,
      'order'      => 'asc',
      'orderby'    => 'name',
    ));
  }

  /**
   * Insert a member.
   *
   * @param array $input
   *
   * @return $this
   */
  public static function insert($input)
  {
    $instance = new static();

    // Insert...
    $id = wp_insert_post(array(
      'post_status' => 'publish',
      'post_title'  => $input['name'],
      'post_type'   => $instance->type,
    ));

    if (!$id) {
      wp_die('Something went wrong.');
    }

    // Avatar...
    if (!empty($_FILES['avatar']['size'])) {
      require_once ABSPATH.'wp-admin/includes/image.php';
      require_once ABSPATH.'wp-admin/includes/file.php';
      require_once ABSPATH.'wp-admin/includes/media.php';

      $attachmentId = media_handle_upload('avatar', 0, array(), array(
        'test_form' => false
      ));

      if ($attachmentId) {
        $input['avatar_url'] = wp_get_attachment_image_src($attachmentId, 'medium')[0];
      }
    }

    // Meta...
    update_post_meta($id, 'hc_avatar_url', isset($input['avatar_url']) ? $input['avatar_url'] : '');
    update_post_meta($id, 'hc_email', $input['email']);
    update_post_meta($id, 'hc_fb_id', isset($input['fb_id']) ? $input['fb_id'] : '');
    update_post_meta($id, 'hc_fb_url', isset($input['fb_url']) ? $input['fb_url'] : '');
    update_post_meta($id, 'hc_help', isset($input['help']) ? $input['help'] : '');
    update_post_meta($id, 'hc_share', isset($input['share']) ? $input['share'] : '');

    // Categories...
    $catIds = array_map('intval', $input['categories']);
    $catIds = array_unique($catIds);

    wp_set_object_terms($id, $catIds, $instance->tax['category']);

    // Help...
    if ($input['help'] === '1') {
      $instance->sendHelpEmail($input);
    }

    // Welcome...
    $instance->sendWelcomeEmail($input);
  }
}
