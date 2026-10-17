<?php /* WordPress 5.6+ doesn't add its own dismiss button to a notice that already has one. */ ?>
<div class="notice notice-info is-dismissible" style="border: 1px solid #c3c4c7; padding-top: 15px; padding-bottom: 15px; padding-left: 15px">
	<a href="<?php echo esc_url($args['optOutUrl']); ?>" class="notice-dismiss" style="text-decoration: none; box-sizing: border-box">
		<span class="screen-reader-text"><?php echo esc_html($args['strings']['dismiss']); ?></span>
	</a>

	<h1 style="padding: 0;"><?php echo wp_kses_post($args['heading']); ?></h1>

	<p style="margin-bottom: 15px;">
		<?php echo wp_kses_post($args['description']); ?>
	</p>

	<?php if (!empty($args['dataWeCollect'])) { ?>
		<p style="margin-bottom: 15px;">
			<strong><?php echo esc_html($args['strings']['data_shared']); ?></strong>
			<?php echo esc_html(implode(', ', $args['dataWeCollect'])); ?>.
		</p>
	<?php } ?>

	<div style="display: flex; justify-content: space-between;">
		<div>
			<a href="<?php echo esc_url($args['optInUrl']); ?>" class="button-primary button-large"><?php echo esc_html($args['strings']['allow']); ?></a> &nbsp;
			<a href="<?php echo esc_url($args['optOutUrl']); ?>" class="button-secondary button-large" style="border-color: transparent"><?php echo esc_html($args['strings']['skip']); ?></a>
		</div>
		<div>
			<?php if ($args['termsUrl'] !== '') { ?>
			<a href="<?php echo esc_url($args['termsUrl']); ?>"><?php echo esc_html($args['strings']['terms']); ?></a> &nbsp; &nbsp;
			<?php } ?>

			<?php if ($args['policyUrl'] !== '') { ?>
				<a href="<?php echo esc_url($args['policyUrl']); ?>"><?php echo esc_html($args['strings']['privacy']); ?></a>
			<?php } ?>
		</div>
	</div>
</div>
