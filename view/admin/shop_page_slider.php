<?php
defined( 'ABSPATH' ) || exit;

$products = get_posts( [
		'numberposts'      => -1,
        'post_type'        => 'product',
    ]);

$product_cats = get_terms([
		'taxonomy' => 'product_cat',
    	'hide_empty' => true,
	]);
$slider_data = get_option('lwra_slider_settings');
$slider_data = is_array($slider_data) ? $slider_data : [];
$upalod_dir = wp_get_upload_dir();
// print_r($upalod_dir);
// print_r($slider_data);
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e('Letscms WooCommerce Rest API Settings', 'lwra'); ?></h1>
	<form id="lwra-shop-slider-form" class="let-mt-4" autocomplete="off">
		<input type="hidden" name="action" value="save_shop_page_slider_settings">
		<div class="table-responsive">
			<table class="let-table let-table-bordered">
				<thead class="let-bg-dark let-text-light">
					<tr>
						<th><?php _e('Image (600x300 & upto 1 MB)', 'lwra'); ?></th>
						<th><?php _e('Title', 'lwra'); ?></th>
						<th><?php _e('Subtitle', 'lwra'); ?></th>
						<th><?php _e('Link', 'lwra'); ?></th>
						<th><?php _e('Action', 'lwra'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(count($slider_data) < 10) : ?>
					<tr>
						<td class="let-align-middle let-text-left" width="40%">
							<input type="file" name="image" class="slider_image let-form-control" accept=".jpg,.png,.gif">
						</td>
						<td class="let-align-middle"><input type="text" name="title" class="let-form-control"></td>
						<td class="let-align-middle"><input type="text" name="subtitle" class="let-form-control"></td>
						<td class="let-align-middle">
							<select class="let-form-control link_type" name="link_type">
								<option value="0"><?php _e('None', 'lwra'); ?></option>
								<option value="product"><?php _e('Product', 'lwra'); ?></option>
								<option value="product_category"><?php _e('Product Categories', 'lwra'); ?></option>
							</select>
							<div style="display: none;">
								<select class="let-form-control category_link let-mt-2" name="link">
									<option value="0"><?php _e('Select Category', 'lwra'); ?></option>
									<?php
										foreach ($product_cats as $product_cat) {
											echo '<option value="'.$product_cat->term_id.'">'.$product_cat->name.'</option>';
										}
									?>
								</select>
							</div>
							<div style="display: none;">
								<select class="let-form-control product_link let-mt-2" name="link" >
									<option value="0"><?php _e('Select Product', 'lwra'); ?></option>
									<?php
										foreach ($products as $product) {
											echo '<option value="'.$product->ID.'">'.$product->post_title.'</option>';
										}
									?>
								</select>
							</div>
						</td>
						<td class="let-align-middle let-text-center"><button type="submit" class="let-btn let-btn-info"><?php _e('Save', 'lwra'); ?></button></td>
					</tr>
					<?php else: ?>
					<tr>
						<td class="let-text-center let-text-danger" colspan="5">Only 10 slide can be added </td>
					</tr>
					<?php endif;
						krsort($slider_data);
						foreach ($slider_data as $key => $slide) { 
						?>
							<tr>
								<td class="let-align-middle let-text-left" width="40%">
									<img src="<?php echo $upalod_dir['baseurl'].$slide['image']; ?>" width="100%">
								</td>
								<td class="let-align-middle"><?php echo $slide['title']; ?></td>
								<td class="let-align-middle"><?php echo $slide['subtitle']; ?></td>
								<td class="let-align-middle">
									<?php
										if($slide['link_type'] == 'product') {
											echo 'Product : '.get_the_title( $slide['link'] );
										}elseif($slide['link_type'] == 'product_category') {
											$term = get_term_by('ID', $slide['link'], 'product_cat');
											// print_r($term);
											echo 'Category : '.$term->name;
										}else{
											echo 'NONE';
										}
									?>
									<?php //echo $slide['link_type']; ?>
									<?php //echo get_the_title( $slide['link'] ); ?>
								</td>
								<td class="let-align-middle let-text-center"><button type="button" class="let-btn let-btn-danger remove_slide" data-key="<?php echo $key; ?>"><?php _e('Remove', 'lwra'); ?></button></td>
							</tr>
						<?php }
					?>
				</tbody>
			</table>
		</div>
		<!-- <div class="let-mt-4 let-text-center">
			<button type="submit" class="let-btn let-btn-info">Save</button>
		</div> -->
	</form>
</div>
<script type="text/javascript">

jQuery(function($){
	$(".slider_image").change(function() {
	  previewImage(this, $);
	});

	$('.link_type').change(function(){
		var link_type = $(this).val();
		if(link_type == 'product') {
			$('.product_link').prop('disabled', false).parent().show();
			$('.category_link').prop('disabled', true).parent().hide();
		}else if(link_type == 'product_category') {
			$('.product_link').prop('disabled', true).parent().hide();
			$('.category_link').prop('disabled', false).parent().show();
		}else{
			$('.product_link').prop('disabled', true).parent().hide();
			$('.category_link').prop('disabled', true).parent().hide();
		}
	});

	$('#lwra-shop-slider-form').submit(function(e){
		e.preventDefault();
		var form = $(this);
		var form_data =  new FormData(this);
		form.find('.let-text-danger, .let-alert').remove();
		form.next('.let-alert').remove();
		var submit = form.find('[type="submit"]')
		submit.prop('disabled', true).html('saving...');
		form.find('.let-border-danger').removeClass('let-border-danger');
		$.ajax({
			url : "<?php echo admin_url('admin-ajax.php'); ?>",
			type : 'post',
			dataType : 'json',
			data : form_data,
			contentType: false,
          	processData: false,
			success : function(result) {
				submit.prop('disabled', false).html('<?php _e('Save', 'lwra'); ?>');
				// console.log(result);
				if(!result.status) {
					$.each(result.errors, function(key, value){
						$('[name="'+key+'"]').addClass('let-border-danger').after('<div class="let-text-danger let-text-left">'+value+'</div>');
					});
				}else{
					form.before('<div class="let-mt-4 let-alert let-alert-success let-text-left">'+result.message+'</div>');
					location.reload();
				}
			}
		});
	});

	$('.remove_slide').click(function(){
		var btn = $(this);
		var key = btn.data('key');
		btn.prop('disabled', true).html('<?php _e('removing', 'lwra'); ?>');
		$.ajax({
			url : "<?php echo admin_url('admin-ajax.php'); ?>",
			type : 'post',
			dataType : 'json',
			data : {
				action : 'remove_shop_page_slider_slide',
				key : key
			},
			success : function(result) {
				console.log(result);
				btn.prop('disabled', false).html('<?php _e('Remove', 'lwra'); ?>');
				// form.after('<div class="let-mt-4 let-alert let-alert-success let-text-left">'+result.message+'</div>');
				alert(result.message);
				location.reload();
			}
		});
	});

});



function previewImage(input, $) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    $(input).prev('img').remove();
    reader.onload = function(e) {
      var image = $('<img>').attr('src', e.target.result).attr('width', '100%').attr('class', 'let-mb-4');
      // console.log(image[0].outerHTML);
      // console.log(image);
      $(input).before(image[0].outerHTML);
    }
    
    reader.readAsDataURL(input.files[0]); // convert to base64 string
  }
}


</script>