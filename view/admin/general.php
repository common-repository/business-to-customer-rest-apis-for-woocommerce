<?php
defined( 'ABSPATH' ) || exit;
$pages = get_pages();
$settings = get_option('lwra_general_settings');
// print_r($settings);
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e('Letscms WooCommerce Rest API Settings', 'lwra'); ?></h1>
	<form id="lwra-settings-form" class="let-mt-4" autocomplete="off">
		<input type="hidden" name="action" value="save_lwra_general_settings">
		<div class="table-responsive">
			<table class="let-table let-table-bordered">
				<tr>
					<th class="let-align-middle let-bg-dark let-text-light let-text-left" width="200"><?php _e('License Key', 'lwra') ?> : </th>
					<td><input type="text" class="let-form-control" name="license_key" value="<?php echo isset($settings['license_key']) ? $settings['license_key'] : ''  ?>"></td>
				</tr>
				<tr>
					<th class="let-align-middle let-bg-dark let-text-light let-text-left"><?php _e('Terms & Conditions Page', 'lwra') ?> : </th>
					<td>
						<select class="let-form-control" name="tnc_page" style="max-width: 100%;">
							<option value="0" selected>None</option>
							<?php
								foreach($pages as $page) {
									if($settings['tnc_page'] == $page->ID) {
										echo '<option value="'.$page->ID.'" selected>'.$page->post_title.'</option>';
									}else {
										echo '<option value="'.$page->ID.'">'.$page->post_title.'</option>';
									}
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th class="let-align-middle let-bg-dark let-text-light let-text-left"><?php _e('About US Page', 'lwra') ?> : </th>
					<td>
						<select class="let-form-control" name="about_page" style="max-width: 100%;">
							<option value="0" selected>None</option>
							<?php
								foreach($pages as $page) {
									if($settings['about_page'] == $page->ID) {
										echo '<option value="'.$page->ID.'" selected>'.$page->post_title.'</option>';
									}else {
										echo '<option value="'.$page->ID.'">'.$page->post_title.'</option>';
									}
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th class="let-align-middle let-bg-dark let-text-light let-text-left"><?php _e('Privacy Policy Page', 'lwra') ?> : </th>
					<td>
						<select class="let-form-control" name="privacy_policy_page" style="max-width: 100%;">
							<option value="0" selected>None</option>
							<?php
								foreach($pages as $page) {
									if($settings['privacy_policy_page'] == $page->ID) {
										echo '<option value="'.$page->ID.'" selected>'.$page->post_title.'</option>';
									}else {
										echo '<option value="'.$page->ID.'">'.$page->post_title.'</option>';
									}
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th class="let-align-middle let-bg-dark let-text-light let-text-left"><?php _e('Return Policy Page', 'lwra') ?> : </th>
					<td>
						<select class="let-form-control" name="return_policy_page" style="max-width: 100%;">
							<option value="0" selected>None</option>
							<?php
								foreach($pages as $page) {
									if($settings['return_policy_page'] == $page->ID) {
										echo '<option value="'.$page->ID.'" selected>'.$page->post_title.'</option>';
									}else {
										echo '<option value="'.$page->ID.'">'.$page->post_title.'</option>';
									}
								}
							?>
						</select>
					</td>
				</tr>
			</table>
		</div>
		<div class="let-mt-4 let-text-center">
			<button type="submit" class="let-btn let-btn-info"><?php _e('Save', 'lwra'); ?></button>
		</div>
	</form>
</div>
<script type="text/javascript">
	jQuery(function($) {
		$('#lwra-settings-form').submit(function(e){
			e.preventDefault();
			var form = $(this);
			var form_data = form.serialize();
			// console.log(form);
			form.find('.let-text-danger, .let-alert').remove();
			var submit = form.find('[type="submit"]')
			submit.prop('disabled', true).html('<?php _e('Saving...', 'lwra') ?>');
			form.find('.let-border-danger').removeClass('let-border-danger');
			$.ajax({
				url : "<?php echo admin_url('admin-ajax.php'); ?>",
				type : 'post',
				dataType : 'json',
				data : form_data,
				success : function(result) {
					submit.prop('disabled', false).html('<?php _e('Save', 'lwra'); ?>');
					// console.log(result);
					if(!result.status) {
						$.each(result.errors, function(key, value){
							$('[name="'+key+'"]').addClass('let-border-danger').after('<div class="let-text-danger let-text-left">'+value+'</div>');
						});
					}else{
						submit.after('<div class="let-mt-4 let-alert let-alert-success let-text-left">'+result.message+'</div>');
					}
				}
			});
		});
	});
</script>