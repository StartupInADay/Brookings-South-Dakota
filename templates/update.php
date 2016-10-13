<?php
$_email = isset($_GET['email']) ? $_GET['email'] : '';
$_key   = isset($_GET['key']) ? $_GET['key'] : '';

$showForm = false;

$id        = '';
$avatarUrl = '';
$fbId      = '';
$fbUrl     = '';
$help      = '';
$share     = '';
$email     = '';
$name      = '';
$catSlugs  = array();

if ($_email && $_key) {
  $member = HomesteadMember::find($_email);

  if (!$member) {
    wp_die('Member not found.');
  }

  $updateKey = get_post_meta($member->id, 'hc_update_key', true);

  if ($_key !== $updateKey) {
    wp_die('Invalid update key.');
  }

  $id        = $member->id;
  $avatarUrl = get_post_meta($member->id, 'hc_avatar_url', true);
  $fbId      = get_post_meta($member->id, 'hc_fb_id', true);
  $fbUrl     = get_post_meta($member->id, 'hc_fb_url', true);
  $help      = get_post_meta($member->id, 'hc_help', true);
  $share     = get_post_meta($member->id, 'hc_share', true);

  $email = get_post_meta($member->id, 'hc_email', true);
  $email = antispambot($email);

  $name = get_the_title($member->id);
  $name = apply_filters('the_title', $name, $member->id);
  $name = preg_replace('/\s+/i', ' ', $name);

  $catSlugs  = wp_get_object_terms($member->id, 'homestead_member_category', array('fields' => 'slugs'));

  $showForm = true;
}
?>

<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId: '<?php echo homestead()->getSetting('app_id'); ?>',
      version: 'v2.5',
      xfbml: true
    })
  };
  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>

<div class="hc-update">

  <div id="hc-step-1" class="hc-actions<?php echo ($showForm) ? ' hc-hide' : ''; ?>">

    <h4>Updating is easy. Enter your email address and we'll send you a link to update your profile.</h4>

    <form
      action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>"
      method="post">
      <input name="_action" type="hidden" value="homestead_update" />
      <?php wp_nonce_field('homestead_update'); ?>
      <div class="hc-form-group">
        <label>Email <span class="hc-required">*</span></label>
        <input class="form-control" name="email" required type="email" />
      </div>
      <button class="hc-btn" type="submit">Send Update Email</button>
    </form>

    <hr />

    <h4>Sign up with Facebook? Click here to update your profile.</h4>

    <button
      id="hc-update-facebook-btn"
      class="hc-btn"
      type="button">Update Via Facebook</button>

  </div><!-- .hc-actions -->

  <div id="hc-step-2" class="hc-register<?php echo ($showForm) ? '' : ' hc-hide'; ?>">
    <form
      action=""
      enctype="multipart/form-data"
      method="post">

      <input name="_action" type="hidden" value="homestead_save" />
      <input name="id" type="hidden" value="<?php echo $id; ?>" />
      <input id="hc-fb-id" name="fb_id" type="hidden" value="<?php echo $fbId; ?>" />
      <input id="hc-fb-url" name="fb_url" type="hidden" value="<?php echo $fbUrl; ?>" />
      <input id="hc-img" name="avatar_url" type="hidden" value="<?php echo $avatarUrl; ?>" />

      <?php wp_nonce_field('homestead_save'); ?>

      <div class="hc-row">

        <?php if ($showForm) : ?>

          <div class="hc-col hc-col-xs--12 hc-col-sm--3 hc-col-md--4">
            <?php if ($avatarUrl) : ?>
              <div class="hc-avatar"><img src="<?php echo $avatarUrl; ?>" alt="thumb" /></div>
            <?php else : ?>
              <div class="hc-thumb">
                <div class="hc-thumb__initials"></div>
                <img
                  src="<?php echo homestead()->getAssetUrl('/assets/img/thumb-001.png'); ?>"
                  alt="thumb" />
              </div><!-- .hc-thumb -->
            <?php endif; ?>
            <input name="avatar" type="file" />
          </div><!-- .hc-col -->

        <?php else : ?>

          <div class="hc-col hc-col-xs--12 hc-col-sm--3 hc-col-md--4">
            <div class="hc-avatar hc-hide"></div>
            <div class="hc-thumb hc-hide">
              <div class="hc-thumb__initials"></div>
              <img
                src="<?php echo homestead()->getAssetUrl('/assets/img/thumb-001.png'); ?>"
                alt="thumb" />
            </div><!-- .hc-thumb -->
            <input name="avatar" type="file" />
          </div><!-- .hc-col -->

        <?php endif; ?>

        <div class="hc-col hc-col-xs--12 hc-col-sm--9 hc-col-md--8">

            <div class="hc-form-group">
              <label>Name <span class="hc-required">*</span></label>
              <input
                id="hc-name"
                class="form-control"
                name="name"
                required
                type="text"
                value="<?php echo $name; ?>" />
            </div>

            <div class="hc-form-group">
              <label>Email <span class="hc-required">*</span></label>
              <input
                id="hc-email"
                class="form-control"
                name="email"
                required
                type="email"
                value="<?php echo $email; ?>" />
            </div>

            <div class="hc-form-group">
              <label>I Am A... <span class="hc-required">*</span></label>
              <?php foreach ($categories as $category) : ?> 
                <div class="hc-checkbox">
                  <label>
                    <input
                      name="categories[]"
                      type="checkbox"
                      value="<?php echo esc_attr($category->term_id); ?>"
                      <?php echo in_array($category->slug, $catSlugs) ? 'checked="checked"' : '' ?> />
                    <?php echo esc_html($category->name); ?>
                  </label>
                </div><!-- hc-checkbox -->
              <?php endforeach; ?> 
            </div><!-- .hc-form-group -->

            <div class="hc-form-group">
              <label>I would like...</label>
              <div class="hc-checkbox">
                <label>
                  <input name="help" type="hidden" value="0" />
                  <input name="help" type="checkbox" value="1"<?php checked('1', $help); ?> />
                  <?php echo homestead()->getSetting('opt_in'); ?>
                </label>
              </div><!-- hc-checkbox -->
              <div class="hc-checkbox">
                <label>
                  <input name="share" type="hidden" value="0" />
                  <input name="share" type="checkbox" value="1"<?php checked('1', $share); ?> />
                  To share my email address to connect with other creatives
                </label>
              </div><!-- hc-checkbox -->
            </div><!-- .hc-form-group -->

            <button class="hc-btn" type="submit">Save Changes</button>

        </div><!-- .hc-col -->

      </div><!-- .hc-row -->

    </form>
  </div><!-- #hc-step-2 -->

</div><!-- .hc-update -->
