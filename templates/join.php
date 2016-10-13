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

<div class="hc-join">

  <div id="hc-step-1" class="hc-actions">
    <h4>Joining is easy. Sign up with Facebook (recommended) or email.</h4>
    <button
      id="hc-signup-facebook-btn"
      class="hc-btn"
      type="button">Sign Up with Facebook</button>
    <span>/</span>
    <button
      id="hc-signup-email-btn"
      class="hc-btn"
      type="button">Sign Up with Email</button>
  </div><!-- #hc-step-1 -->

  <div id="hc-step-2" class="hc-register hc-hide">
    <form
      action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>"
      enctype="multipart/form-data"
      method="post">

      <input name="_action" type="hidden" value="homestead_save" />
      <input id="hc-fb-id" name="fb_id" type="hidden" value="" />
      <input id="hc-fb-url" name="fb_url" type="hidden" value="" />
      <input id="hc-img" name="avatar_url" type="hidden" value="" />

      <?php wp_nonce_field('homestead_save'); ?>

      <div class="hc-row">

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

        <div class="hc-col hc-col-xs--12 hc-col-sm--9 hc-col-md--8">

            <div class="hc-form-group">
              <label>Name <span class="hc-required">*</span></label>
              <input
                id="hc-name"
                class="form-control"
                name="name"
                required
                type="text" />
            </div>

            <div class="hc-form-group">
              <label>Email <span class="hc-required">*</span></label>
              <input
                id="hc-email"
                class="form-control"
                name="email"
                required
                type="email" />
            </div>

            <div class="hc-form-group">
              <label>I Am A... <span class="hc-required">*</span></label>
              <?php foreach ($categories as $category) : ?> 
                <div class="hc-checkbox">
                  <label>
                    <input
                      name="categories[]"
                      type="checkbox"
                      value="<?php echo esc_attr($category->term_id); ?>" />
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
                  <input name="help" type="checkbox" value="1" />
                  <?php echo homestead()->getSetting('opt_in'); ?>
                </label>
              </div><!-- hc-checkbox -->
              <div class="hc-checkbox">
                <label>
                  <input name="share" type="hidden" value="0" />
                  <input name="share" type="checkbox" value="1" />
                  To share my email address to connect with other creatives
                </label>
              </div><!-- hc-checkbox -->
            </div><!-- .hc-form-group -->

            <button class="hc-btn" type="submit">Join</button>

        </div><!-- .hc-col -->

      </div><!-- .hc-row -->

    </form>
  </div><!-- #hc-step-2 -->

</div><!-- .hc-join -->
