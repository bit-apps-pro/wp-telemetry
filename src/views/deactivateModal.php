<div class="bitapps-dm-wrapper" id="<?php echo esc_attr($args['slug']); ?>-bitapps-dm-wrapper" data-slug="<?php echo esc_attr($args['slug']); ?>">
  <div class="bitapps-dm-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($args['slug']); ?>-bitapps-dm-title" tabindex="-1">
    <div class="bitapps-dm-header">
      <span class="bitapps-dm-header-title" id="<?php echo esc_attr($args['slug']); ?>-bitapps-dm-title">
        <?php echo esc_html($args['strings']['survey_title']); ?>
      </span>
      <svg class="bitapps-dm-close-svg" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
        <path fill="currentcolor" stroke="currentcolor" stroke-width="2" d="M14.5,1.5l-13,13m0-13,13,13" transform="translate(-1 -1)"></path>
      </svg>
    </div>
    <form class="bitapps-dm-form" method="post">
      <?php wp_nonce_field(esc_attr($args['prefix']) . 'nonce', '_ajax_nonce'); ?>
      <input type="hidden" name="action" value="<?php echo esc_attr($args['prefix']) . 'deactivate_feedback'; ?>" />

      <div class="bitapps-dm-form-caption">
        <?php echo esc_html($args['strings']['survey_caption']); ?>
      </div>
      <div class="bitapps-dm-form-body">
        <?php foreach ($args['reasons'] as $reasonKey => $reason) { ?>
          <div class="bitapps-dm-input-wrapper">
            <input id="<?php echo esc_attr($args['slug']) . '-deactivate-feedback-' . esc_attr($reasonKey); ?>" class="bitapps-dm-input" type="radio" name="reason_key" value="<?php echo esc_attr($reasonKey); ?>" required />
            <label for="<?php echo esc_attr($args['slug']) . '-deactivate-feedback-' . esc_attr($reasonKey); ?>" class="bitapps-dm-label"><?php echo esc_html($reason['title']); ?></label>
            <?php if (!empty($reason['placeholder'])) { ?>
              <input class="bitapps-dm-feedback-text" type="text" name="reason_text[<?php echo esc_attr($reasonKey); ?>]" placeholder="<?php echo esc_attr($reason['placeholder']); ?>" />
            <?php } ?>
            <?php if (!empty($reason['alert'])) { ?>
              <div class="bitapps-dm-feedback-text">
                <?php echo esc_html($reason['alert']); ?>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
      <div class="bitapps-dm-form-disclosure">
        <?php echo esc_html($args['strings']['survey_disclosure']); ?>
        <?php if (!empty($args['policyUrl'])) { ?>
          <a href="<?php echo esc_url($args['policyUrl']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($args['strings']['privacy']); ?></a>
        <?php } ?>
      </div>
      <div class="bitapps-dm-form-footer">
        <button type="submit" class="bitapps-dm-form-submit">
          <?php echo esc_html($args['strings']['survey_submit']); ?>
        </button>
        <button type="button" class="bitapps-dm-form-skip">
          <?php echo esc_html($args['strings']['survey_skip']); ?>
        </button>
      </div>
    </form>
  </div>
</div>
