<?php
/**
 * Plugin Name: Homestead Connect
 * Plugin URI: https://wordpress.org/plugins/homestead-connect/
 * Author: NewOver
 * Author URI: http://newover.com/
 * Description: Homestead provides tools to help you inspire your community.
 * Version: 1.2.3
 */

if (!defined('ABSPATH')) {
  return;
}

class Homestead {

  /**
   * @var string
   */
  public $name = 'Homestead Connect';

  /**
   * @var string
   */
  public $slug = 'homestead';

  /**
   * @var Homestead
   */
  protected static $_instance = null;

  /**
   * @var HomesteadAdmin
   */
  protected $admin;

  /**
   * @var strin
   */
  protected $baseFile;

  /**
   * @var string
   */
  protected $basePath;

  /**
   * @var string
   */
  protected $baseUrl;

  /**
   * @var boolean
   */
  protected $minify;

  /**
   * @var array
   */
  protected $settings;

  /**
   * @var string
   */
  protected $version = '1.2.3';

  /**
   * Create a new instance.
   */
  public function __construct()
  {
    $this->baseFile = __FILE__;

    $this->basePath = dirname(__FILE__);

    $this->baseUrl = untrailingslashit(plugins_url('', __FILE__));

    $this->minify = (defined('WP_DEBUG') && !WP_DEBUG);

    $this->settings = get_option($this->slug, array());

    // Autoload
    // ---

    $this->autoload();

    // Activate
    // ---

    register_activation_hook(__FILE__, array($this, 'activate'));

    // Actions
    // ---

    add_action('homestead_filter_icon', array($this, 'renderFilterIcon'), 10);
    add_action('init',                  array($this, 'bootModels'),       10);
    add_action('init',                  array($this, 'handleSave'),       999);
    add_action('init',                  array($this, 'handleUpdate'),     999);
    add_action('wp_enqueue_scripts',    array($this, 'enqueueAssets'),    999);

    // Remove default emoji scripts and styles. This confuses our icons since
    // we're using the unicode character option.
    remove_action('wp_head',         'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles',           10);

    // Shortcodes
    // ---

    add_shortcode('homestead_members', array($this, 'shortcodeHomesteadMembers'));
    add_shortcode('homestead_join',    array($this, 'shortcodeHomesteadJoin')   );
    add_shortcode('homestead_update',  array($this, 'shortcodeHomesteadUpdate') );
  }

  // Public Methods
  // ---------------------------------------------------------------------------

  /**
   * Do things during plugin activation.
   *
   * @hook register_activation_hook
   *
   * @return void
   */
  public function activate()
  {
    $settings = $this->getSettings();

    if (empty($settings['opt_in'])) {
      $settings['opt_in'] = 'Help starting a business/project';
    }

    $this->bootModels();

    flush_rewrite_rules();
  }

  /**
   * Boot models.
   *
   * @return void
   */
  public function bootModels()
  {
    HomesteadMember::boot();
  }

  /**
   * Enqueue scripts and styles.
   *
   * @action wp_enqueue_scripts
   *
   * @return void
   */
  public function enqueueAssets()
  {
    $css = array(
      'app' => array(
        'deps' => array(),
        'path' => '/assets/css/app.css'
      ),
    );

    $js = array(
      'app' => array(
        'deps'      => array('backbone', 'jquery', 'underscore'),
        'in_footer' => true,
        'path'      => '/assets/js/app.js'
      )
    );

    foreach ($css as $key => $asset) {
      $handle = "homestead-{$key}";
      $url    = $this->getAssetUrl($asset['path'], true);

      wp_enqueue_style($handle, $url, $asset['deps'], $this->version);
    }

    foreach ($js as $key => $asset) {
      $handle = "homestead-{$key}";
      $url    = $this->getAssetUrl($asset['path'], true);

      wp_enqueue_script($handle, $url, $asset['deps'], $this->version, $asset['in_footer']);
    }

    wp_localize_script('homestead-app', 'homestead_app', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'app_id'   => $this->getSetting('app_id'),
    ));
  }

  /**
   * Get the base file.
   *
   * @return string
   */
  public function getBaseFile()
  {
    return $this->baseFile;
  }

  /**
   * Get the base path.
   *
   * @return string
   */
  public function getBasePath()
  {
    return $this->basePath;
  }

  /**
   * Get the base URL.
   *
   * @return string
   */
  public function getBaseUrl()
  {
    return $this->baseUrl;
  }

  /**
   * Get the domain.
   *
   * @return string
   */
  public function getDomain() {
    $url        = home_url();
    $parsed_url = parse_url($url);

    return $parsed_url['host'];
  }

  /**
   * Get the initials for a name.
   *
   * @param string $name
   *
   * @return string
   */
  public function getInitials($name)
  {
    $name_parts = explode(' ', $name);

    $initials = [];

    foreach ($name_parts as $name_part) {
      $initial = substr($name_part, 0, 1);
      $initial = strtoupper($initial);

      $initials[] = "<span>{$initial}</span>";
    }

    $html = implode('', $initials);

    return apply_filters('homestead_initials', $html, $name);
  }

  /**
   * Get the the URL for the join page.
   *
   * @return string
   */
  public function getJoinUrl()
  {
    $url = home_url();

    if ($pageId = $this->getSetting('join_page')) {
      $url = get_permalink($pageId);
    }

    return $url;
  }

  /**
   * Get the the URL for the members page.
   *
   * @return string
   */
  public function getMembersUrl()
  {
    $url = home_url();

    if ($pageId = $this->getSetting('members_page')) {
      $url = get_permalink($pageId);
    }

    return $url;
  }

  /**
   * Get the the URL for the update page.
   *
   * @return string
   */
  public function getUpdateUrl()
  {
    $url = home_url();

    if ($pageId = $this->getSetting('update_page')) {
      $url = get_permalink($pageId);
    }

    return $url;
  }

  /**
   * Get a setting value.
   *
   * @param string $key
   * @param mixed  $default
   *
   * @return mixed
   */
  public function getSetting($key, $default = '')
  {
    return isset($this->settings[$key]) ? $this->settings[$key] : $default;
  }

  /**
   * Get the settings.
   *
   * @return array
   */
  public function getSettings()
  {
    return $this->settings;
  }

  /**
   * Get the template path.
   *
   * @return string
   */
  public function getTemplatePath()
  {
    return $this->slug;
  }

  /**
   * Handle a save.
   *
   * NOTE: This needs to take place after we've registered our custom post
   * types and taxonomies.
   *
   * @action init
   *
   * @return void
   **/
  public function handleSave() {
    if (!$_POST) {
      return;
    }

    $input = $_POST;

    if (empty($input['_action'])
      || ($input['_action'] !== 'homestead_save')) {
      return;
    }

    if (empty($input['_wpnonce'])
      || !wp_verify_nonce($input['_wpnonce'], 'homestead_save')) {
      wp_die('Nope.');
    }

    $format = '%s <a href="'.$this->getJoinUrl().'">Please, try again.</a>';

    if (empty($input['email'])) {
      $message = sprintf($format, 'Email address is required.');
      wp_die($message);
    }

    if (empty($input['name'])) {
      $message = sprintf($format, 'Name is required.');
      wp_die($message);
    }

    if (!is_email($input['email'])) {
      $message = sprintf($format, 'Invalid email address.');
      wp_die($message);
    }

    if (empty($input['categories'])) {
      $message = sprintf($format, 'You must enter at least 1 category.');
      wp_die($message);
    }

    if (!empty($input['id'])) {
      $member = HomesteadMember::findById($input['id']);
    } else {
      $member = HomesteadMember::find($input['email']);
    }

    $redirect = $this->getMembersUrl();

    if ($member) {
      $member->update($input);

      wp_safe_redirect($redirect);
      exit;
    }

    HomesteadMember::insert($input);

    wp_safe_redirect($redirect);
    exit;
  }

  /**
   * Handle an update.
   *
   * NOTE: This needs to take place after we've registered our custom post
   * types and taxonomies.
   *
   * @action init
   *
   * @return void
   **/
  public function handleUpdate() {
    if (!$_POST) {
      return;
    }

    $input = $_POST;

    if (empty($input['_action'])
      || ($input['_action'] !== 'homestead_update')) {
      return;
    }

    if (empty($input['_wpnonce'])
      || !wp_verify_nonce($input['_wpnonce'], 'homestead_update')) {
      wp_die('Nope.');
    }

    $format = '%s <a href="'.$this->getUpdateUrl().'">Please, try again.</a>';

    if (empty($input['email'])) {
      $message = sprintf($format, 'Email address is required.');
      wp_die($message);
    }

    if (!is_email($input['email'])) {
      $message = sprintf($format, 'Invalid email address.');
      wp_die($message);
    }

    $member = HomesteadMember::find($input['email']);

    if (!$member) {
      $message = sprintf($format, 'We couldn\'t find a member with that email address.');
      wp_die($message);
    }

    $member->sendUpdateEmail($input);

    $redirect = $this->getMembersUrl();

    wp_safe_redirect($redirect);
    exit;
  }

  /**
   * Log data to the defined error handling routines.
   *
   * @return string
   */
  public function log($data)
  {
    if (!WP_DEBUG || !WP_DEBUG_LOG) {
      return;
    }

    error_log(print_r($data, true));
  }

  /**
   * Render the filter icon.
   *
   * @action homestead_filter_icon
   *
   * @param string slug
   *
   * @return void
   */
  public function renderFilterIcon($slug)
  {
    $fa = array(
      'all'               => 'user',
      '1-million-cups'    => 'coffee',
      'artist'            => 'paint-brush',
      'community-builder' => 'comments',
      'designer'          => 'laptop',
      'developer'         => 'code',
      'entrepreneur'      => 'lightbulb-o',
      'investor'          => 'line-chart',
      'maker'             => 'lightbulb-o',
      'musician'          => 'music',
      'photographer'      => 'camera',
      'writer'            => 'pencil',
    );

    if (array_key_exists($slug, $fa)) {
      printf('<span class="fa fa-%s"></span>', $fa[$slug]);
      return;
    }

    if ($slug === 'homesteader') {
      $svg = file_get_contents($this->getAssetUrl('/assets/svg/homestead.svg'));
      $svg = trim($svg);

      printf('<span class="hc-icon">%s</span>', $svg);
      return;
    }

    echo '<span class="fa fa-user"></span>';
  }

  /**
   * Render a template.
   *
   * @param string $name
   * @param array  $args
   *
   * @return void
   */
  public function renderTemplate($name, $args = array())
  {
    $find = sprintf('%s/%s.php', $this->getTemplatePath(), $name);

    $template = locate_template($find);

    if (!$template) {
      $template = sprintf('%s/templates/%s.php', $this->getBasePath(), $name);
    }

    if (!$template || !file_exists($template)) {
      return;
    }

    extract($args);

    include $template;
  }

  /**
   * Get a shortcode.
   *
   * @shortcode homestead_members
   *
   * @return string
   */
  public function shortcodeHomesteadMembers($atts, $content)
  {
    ob_start();
    $this->renderTemplate('members', array(
      'categories' => HomesteadMember::getCategories(),
      'members'    => HomesteadMember::get()
    ));
    return ob_get_clean();
  }

  /**
   * Get a shortcode.
   *
   * @shortcode homestead_join
   *
   * @return string
   */
  public function shortcodeHomesteadJoin($atts, $content)
  {
    ob_start();
    $this->renderTemplate('join', array(
      'categories' => HomesteadMember::getCategories()
    ));
    return ob_get_clean();
  }

  /**
   * Get a shortcode.
   *
   * @shortcode homestead_update
   *
   * @return string
   */
  public function shortcodeHomesteadUpdate($atts, $content)
  {
    ob_start();
    $this->renderTemplate('update', array(
      'categories' => HomesteadMember::getCategories()
    ));
    return ob_get_clean();
  }

  // Protected Methods
  // ---------------------------------------------------------------------------

  /**
   * Autoload... But not really.
   *
   * @return void
   */
  protected function autoload()
  {
    $basePath = $this->getBasePath();

    require_once $basePath.'/includes/helpers.php';
    require_once $basePath.'/includes/HomesteadAdmin.php';
    require_once $basePath.'/includes/HomesteadMember.php';

    if (is_admin()) {
      $this->admin = new HomesteadAdmin();
    }
  }

  /**
   * Get the URL for an asset.
   *
   * @param string  $path
   * @param boolean $minify
   *
   * @return string
   */
  protected function getAssetUrl($path, $minify = false)
  {
    $debugMode = $this->getSetting('debug_mode');

    if ($minify && !$debugMode) {
      $parts = pathinfo($path);

      $path = sprintf('/assets/min/%s.%s.%s',
        $parts['filename'],
        $this->version,
        $parts['extension']
      );
    }

    return $this->getBaseUrl().$path;
  }

  // Static Methods
  // ---------------------------------------------------------------------------

  /**
   * Get a singleton Homestead instance.
   *
   * @return Homestead
   */
  public static function instance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }
}

/**
 * Get a singleton Homestead instance.
 *
 * @return Homestead
 */
function homestead()
{
  return Homestead::instance();
}

return homestead();
