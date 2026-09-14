<div class="updated" style="border: 1px solid #c3c4c7; padding: 15px">
	<h1 style="padding: 0;"><?php echo wp_kses_post($args['heading']); ?></h1>

	<p style="margin-bottom: 15px;">
		<?php echo wp_kses_post($args['description']); ?>
	</p>

	<div style="display: flex; justify-content: space-between;">
		<div>
			<a href="<?php echo esc_attr(esc_url($args['optInUrl'])); ?>" class="button-primary button-large">Allow & Continue</a> &nbsp;
			<a href="<?php echo esc_attr(esc_url($args['optOutUrl'])); ?>" class="button-secondary button-large" style="border-color: transparent">Skip</a>
		</div>
		<div>
			<?php if ($args['termsUrl'] !== '') { ?>
			<a href="<?php echo esc_attr($args['termsUrl']); ?>">Terms of service</a> &nbsp; &nbsp;
			<?php } ?>

			<?php if ($args['policyUrl'] !== '') { ?>
				<a href="<?php echo esc_attr($args['policyUrl']); ?>">Privacy Policy</a>
			<?php } ?>
		</div>
	</div>
</div>