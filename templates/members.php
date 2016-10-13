<div class="hc-filter">
  <button class="hc-filter__btn is-active" type="button" data-slug="all">
    <?php do_action('homestead_filter_icon', 'all'); ?>
    Everyone
  </button>
  <?php foreach ($categories as $category) : ?>
    <button
      class="hc-filter__btn"
      type="button"
      data-slug="<?php echo esc_attr($category->slug); ?>">
      <?php do_action('homestead_filter_icon', $category->slug); ?>
      <?php echo esc_html($category->name); ?>
    </button>
  <?php endforeach; ?>
</div><!-- .hc-filter -->

<div class="hc-members">
  <div class="hc-row">

    <?php
    foreach ($members as $member) :
      $email = $member->getEmail();
      $name  = $member->getName();
    ?>

      <div class="hc-col hc-col--kick hc-col-xs--6 hc-col-sm--4 hc-col-md--3 hc-match-height">
        <div class="hc-member" data-slugs="<?php echo $member->getCategorySlugsForJs() ?>">

          <?php if ($avatarUrl = $member->getAvatarUrl()) : ?>
            <div class="hc-avatar">
              <img
                src="<?php echo $avatarUrl; ?>"
                alt="<?php the_title_attribute(); ?>" />
            </div><!-- .hc-avatar -->
          <?php else : ?>
            <div class="hc-thumb">
              <div class="hc-thumb__initials">
                 <?php echo homestead()->getInitials($name); ?>
              </div>
              <img
                src="<?php echo homestead()->getAssetUrl('/assets/img/thumb-001.png'); ?>"
                alt="bg" />
            </div><!-- .hc-thumb -->
          <?php endif; ?>

          <div class="hc-member__details">
            <p><strong><?php echo $name; ?></strong></p>
            <?php if ($fbUrl = $member->getFbUrl()) : ?>
              <p><a href="<?php echo esc_url($fbUrl); ?>" target="_blank">Facebook</a></p>
            <?php endif; ?>
            <?php if ($member->share()) : ?>
              <p><a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></p>
            <?php endif; ?>
          </div><!-- .hc-member__details -->

        </div><!-- .hc-member -->
      </div><!-- .hc-col -->

    <?php endforeach; ?>

  </div><!-- .hc-row -->
</div><!-- .hc-members -->
