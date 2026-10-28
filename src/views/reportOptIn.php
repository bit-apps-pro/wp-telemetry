<?php
if (!defined('ABSPATH')) {
    exit;
}

// WordPress 5.6+ doesn't add its own dismiss button to a notice that already has one.
?>
<div class="notice notice-info is-dismissible" style="border: 1px solid #c3c4c7; padding-top: 15px; padding-bottom: 15px; padding-left: 15px">
	<a href="<?php echo esc_url($args['optOutUrl']); ?>" class="notice-dismiss" style="text-decoration: none; box-sizing: border-box">
		<span class="screen-reader-text"><?php echo esc_html($args['strings']['dismiss']); ?></span>
	</a>

	<h1 style="padding: 0;"><?php echo wp_kses_post($args['heading']); ?></h1>

	<p style="margin-bottom: 0;">
		<?php echo wp_kses_post($args['description']); ?>
	</p>

	<?php if (!empty($args['dataWeCollect'])) { ?>
		<details style="margin-top: 8px;">
			<summary style="cursor: pointer;"><?php echo esc_html($args['strings']['data_shared']); ?></summary>
			<ul style="list-style: disc; margin: 8px 0 0 20px;">
				<?php foreach ($args['dataWeCollect'] as $item) { ?>
					<li style="margin: 0;"><?php echo esc_html($item); ?></li>
				<?php } ?>
			</ul>
		</details>
	<?php } ?>

	<form method="post" style="margin-top: 15px;">
		<input type="hidden" name="_wpnonce" value="<?php echo esc_attr($args['nonce']); ?>" />
		<input type="hidden" name="<?php echo esc_attr($args['optInField']); ?>" value="true" />

		<?php if ($args['marketingField'] !== '') { ?>
			<p style="margin: 0 0 12px;">
				<label>
					<input type="checkbox" name="<?php echo esc_attr($args['marketingField']); ?>" value="yes" />
					<?php echo esc_html($args['strings']['marketing']); ?>
				</label>
			</p>
		<?php } ?>

		<div style="display: flex; justify-content: space-between;">
			<div>
				<button type="submit" class="button-primary button-large"><?php echo esc_html($args['strings']['allow']); ?></button> &nbsp;
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
	</form>
</div>
