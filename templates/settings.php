<div class="wrap">

  <h2><?php echo homestead()->name; ?> Settings</h2>

  <form
    action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>"
    method="post"
    target="_blank">
    <input name="_action" type="hidden" value="homestead_export" />
    <?php wp_nonce_field('homestead_export'); ?>
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row"><label>Export</label></th>
          <td><button class="button" type="submit">Export Email Addresses</button></td>
        </tr>
      </tbody>
    </table>
  </form>

  <form method="post" action="options.php">
    <?php
    settings_fields(homestead()->slug);
    do_settings_sections(homestead()->slug);
    submit_button();
    ?>
  </form>

</div><!-- /.wrap -->
