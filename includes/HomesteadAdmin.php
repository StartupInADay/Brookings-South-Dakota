<?php
class HomesteadAdmin {

  /**
   * Create a new instance.
   */
  public function __construct()
  {
    add_action('admin_init', array($this, 'export'),          10);
    add_action('admin_init', array($this, 'initSettings'),    10);
    add_action('admin_menu', array($this, 'addSettingsPage'), 10);
  }

  // Public Methods
  // ---------------------------------------------------------------------------

  /**
   * Add the settings page.
   *
   * @action admin_menu
   *
   * @return void
   */
  public function addSettingsPage()
  {
    add_options_page(
      homestead()->name,                 // $page_title
      homestead()->name,                 // $menu_title
      'manage_options',                  // $capability
      homestead()->slug,                 // $menu_slug
      array($this, 'renderSettingsPage') // $function
    );
  }

  /**
   * Export members.
   *
   * @return void
   */
  public function export()
  {
    if (!$_POST) {
      return;
    }

    $input = $_POST;

    if (empty($input['_action'])
      || ($input['_action'] !== 'homestead_export')) {
      return;
    }

    if (empty($input['_wpnonce'])
      || !wp_verify_nonce($input['_wpnonce'], 'homestead_export')) {
      wp_die('Nope.');
    }

    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename=members.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $csv = fopen('php://output', 'w');

    fputcsv($csv, array('Email'));

    $members = HomesteadMember::get();

    foreach ($members as $member) {
      $email = get_post_meta($member->id, 'hc_email', true);

      if (empty($email)) {
        continue;
      }

      $email = trim($email);
      $email = strtolower($email);

      fputcsv($csv, array($email));
    }

    fclose($csv);

    exit;
  }

	/**
	 * Initialize the settings.
	 *
	 * @action admin_init
   *
   * @return void
	 */
  public function initSettings()
  {
    add_settings_section(
      homestead()->slug,       // $id
      '',                      // $title
      '__return_empty_string', // $callback
      homestead()->slug        // $page
    );

    $fields = array(
      array(
        'key'         => 'app_id',
        'label'       => 'Facebook App ID',
        'description' => 'Can be obtained from the <a href="https://developers.facebook.com/apps/" target="_blank">Facebook App Dashboard</a>. Visit the <a href="https://developers.facebook.com/docs/apps/register" target="_blank">Facebook Developer Documentation</a> to learn more.',
        'type'        => 'text',
      ),
      array(
        'key'         => 'email',
        'label'       => 'Email Address',
        'description' => 'The email address that will be notified when a new member approves the opt-in phrase.',
        'type'        => 'email',
      ),
      array(
        'key'         => 'members_page',
        'label'       => 'Members Page',
        'description' => 'The page that displays the members. The page should contain the <code>[homestead_members]</code> shortcode.',
        'type'        => 'page',
      ),
      array(
        'key'         => 'join_page',
        'label'       => 'Join Page',
        'description' => 'The page that display the join form. The page should contain the <code>[homestead_join]</code> shortcode.',
        'type'        => 'page',
      ),
      array(
        'key'         => 'update_page',
        'label'       => 'Update Page',
        'description' => 'The page that display the update form. The page should contain the <code>[homestead_update]</code> shortcode.',
        'type'        => 'page',
      ),
      array(
        'key'         => 'opt_in',
        'label'       => 'Opt-In Phrase',
        'description' => 'The phrase that comes after "I would like..." on the join and update pages.',
        'type'        => 'textarea',
      ),
      array(
        'key'         => 'debug_mode',
        'label'       => 'Debug Mode',
        'description' => 'Enable debug mode',
        'type'        => 'checkbox',
      )
    );

    foreach ($fields as $field) {
      add_settings_field(
        homestead()->slug.'_'.$field['key'], // $id
        $field['label'],                     // $title
        array($this, 'renderSettings'),      // $callback
        homestead()->slug,                   // $page
        homestead()->slug,                   // $section
        $field                               // $args
      );
    }

    register_setting(
      homestead()->slug,               // $option_group
      homestead()->slug,               // $option_name
      array($this, 'validateSettings') // $sanitize_callback
    );
  }

  /**
	 * Render the settings fields.
   *
   * @caller add_settings_field
   *
   * @param array $field
   *
   * @return void
	 */
  public function renderSettings($field)
  {
    $settings = homestead()->getSettings();

    $key  = $field['key'];
    $type = $field['type'];

    $value = (!empty($settings[$key])) ? $settings[$key] : '';

    switch ($type) {
      case 'page':
        wp_dropdown_pages(array(
          'name'              => sprintf('%s[%s]', homestead()->slug, $key),
          'option_none_value' => '0',
          'selected'          => $value,
          'show_option_none'  => '&mdash; Select &mdash;'
        ));
        break;
      case 'checkbox':
        $format1 = '<input name="%1$s" type="hidden" value="0" />';
        $format2 = '<label><input name="%1$s" type="%2$s" value="1"%3$s />%4$s</label>';

        $desc = (!empty($field['description'])) ? $field['description'] : '';
        $name = sprintf('%s[%s]', homestead()->slug, $key);

        printf($format1, $name);
        printf($format2, $name, $type, checked('1', $value, false), $desc);

        break;
      case 'textarea':
        $format = '<textarea class="code large-text" name="%1$s" rows="2">%3$s</textarea>';
        $value = (is_array($value))
          ? implode("\n", $value)
          : esc_textarea($value);
        printf($format, sprintf('%s[%s]', homestead()->slug, $key), $type, $value);
        break;
      default:
        $format = '<input class="regular-text" name="%1$s" type="%2$s" value="%3$s" />';
        $value  = esc_attr($value);
        printf($format, sprintf('%s[%s]', homestead()->slug, $key), $type, $value);
        break;
    }

    if (!empty($field['description']) && ($type !== 'checkbox')) {
      echo '<p class="description">' . $field['description'] . '</p>';
    }
  }

  /**
	 * Render the settings template.
   *
   * @caller add_options_page
   *
   * @return void
	 */
  public function renderSettingsPage()
  {
    homestead()->renderTemplate('settings');
  }

  /**
   * Validate the settings.
   *
   * @caller register_setting
   *
   * @param array $post
   *
   * @return array
   **/
  public function validateSettings($post)
  {
    $current = homestead()->getSettings();
    $updated = array();

    $updated['app_id']       = (!empty($post['app_id'])) ? $post['app_id'] : '';
    $updated['debug_mode']   = (!empty($post['debug_mode'])) ? (int)$post['debug_mode'] : 0;
    $updated['email']        = (!empty($post['email'])) ? $post['email'] : '';
    $updated['join_page']    = (!empty($post['join_page'])) ? (int)$post['join_page'] : 0;
    $updated['members_page'] = (!empty($post['members_page'])) ? (int)$post['members_page'] : 0;
    $updated['opt_in']       = (!empty($post['opt_in'])) ? $post['opt_in'] : '';
    $updated['update_page']  = (!empty($post['update_page'])) ? (int)$post['update_page'] : 0;

    return $updated;
  }
}
