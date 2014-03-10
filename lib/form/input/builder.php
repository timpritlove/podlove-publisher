<?php
namespace Podlove\Form\Input;

class Builder {

	/**
	 * Model record.
	 * @var object
	 */
	public $object;

	/**
	 * Form field name prefix.
	 * @var string
	 */
	public $context;

	public $object_key;
	public $arguments;

	public function __construct( $object, $context ) {
		$this->object     = $object;
		$this->context    = $context;
	}

	public function get_field_name() {
		return ( $this->context ) ? "{$this->context}[{$this->object_key}]" : $this->object_key;
	}

	public function get_field_id() {
		if ( $this->context ) {
			$id = "{$this->context}_{$this->object_key}";
		} else {
			$id = $this->object_key;
		}
		
		$id = str_replace( array( '[', ']' ), '_', $id );
		$id = str_replace( '__', '_', $id );

		return $id;
	}

	public function get_extra_html_attributes() {
		if ( ! isset( $this->arguments['html'] ) || ! is_array( $this->arguments['html'] ) )
			return '';

		$compiled_html = '';

		foreach ( $this->arguments['html'] as $key => $value )
			$compiled_html .= "$key=\"$value\" ";

		return $compiled_html;
	}

	/**
	 * Generate values required to build input fields.
	 * 
	 * @param  string $object_key name of the model attribute
	 * @param  array  $arguments  input field options
	 * @return void
	 */
	private function build_input_values( $object_key, $arguments ) {
		$this->object_key = $object_key;
		$this->arguments  = $arguments;

		$this->field_name  = $this->get_field_name();

		// multiselect takes care of its values
		if ( ! isset( $arguments['ignore_values'] ) || $arguments['ignore_values'] === false ) {
			$this->field_value = $this->object->{$object_key};

			if ( $this->field_value === NULL && isset( $arguments['default'] ) && $arguments['default'] ) {
				$this->field_value = $arguments['default'];
			}
		}
		
		$this->field_id        = $this->get_field_id();
		$this->html_attributes = $this->get_extra_html_attributes();
	}

	public function string( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<div>
			<input type="text" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr( $this->field_value ); ?>" <?php echo $this->html_attributes; ?>>
		</div>
		<?php
	}

	public function hidden( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<div>
			<input type="hidden" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr( $this->field_value ); ?>" <?php echo $this->html_attributes; ?>>
		</div>
		<?php
	}

	public function text( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<div>
			<textarea name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html_attributes; ?>><?php echo $this->field_value; ?></textarea>
		</div>
		<?php
	}

	public function password( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		$random = rand();
		?>
		<div>
			<input type="password" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo $random; ?>" <?php echo $this->html_attributes; ?>>
			<input type="hidden" name="<?php echo "field_filler_".$this->field_name; ?>" id="<?php echo "field_filler_".$this->field_name; ?>" value="<?php echo $random; ?>" <?php echo $this->html_attributes; ?>>
			<input type="hidden" name="passwords[]" value="<?php echo esc_attr( $this->object_key ) ?>">
		</div>
		<?php
	}

	public function checkbox( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<input type="checkbox" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php if ( in_array( $this->field_value, array( true, 1, 'on' ) ) ): ?>checked="checked"<?php endif; ?> <?php echo $this->html_attributes; ?>>
		<input type="hidden" name="checkboxes[]" value="<?php echo esc_attr( $this->object_key ) ?>">
		<?php
	}

	public function select( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<select name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html_attributes; ?>>
			<?php if ( ! isset( $this->arguments['please_choose'] ) || $this->arguments['please_choose'] ): ?>
				<option value=""><?php
					if (isset($this->arguments['please_choose_text'])) {
						echo $this->arguments['please_choose_text'];
					} else {
						echo __( 'Please choose ...', 'podlove' );
					}
				?></option>
			<?php endif; ?>
			<?php foreach ( $this->arguments['options'] as $key => $value ): ?>
				<?php 
				if ( is_array( $value ) ) {
					$attributes = $value['attributes'];
					$value = $value['value'];
				} else {
					$attributes = '';
				}
				?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php echo $attributes ?> <?php if ( $key == $this->field_value ): ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function multiselect( $object_key, $arguments ) {
		$arguments['ignore_values'] = true;
		$this->build_input_values( $object_key, $arguments );

		foreach ( $this->arguments['options'] as $key => $value ) {
			if ( isset( $this->arguments['multi_values'][ $key ] ) ) {
				$checked = $this->arguments['multi_values'][ $key ];
			} else {
				$checked = $this->arguments['default'];
			}
			
			$name = $this->field_name . '[' . $key . ']';
			
			// generate an id without braces by turning braces into underscores
			$id = $this->field_id . '_' . $key;
			$id = str_replace( array( '[', ']' ), '_', $id );
			$id = str_replace( '__', '_', $id );
			
			if ( isset( $this->arguments['multiselect_callback'] ) ) {
				$callback = call_user_func( $this->arguments['multiselect_callback'], $key );
			} else {
				$callback = '';
			}
			
			$html = function() use ( $id, $name, $checked, $callback, $value ) {
				?>
				<div>
					<label for="<?php echo $id; ?>">
						<input type="checkbox" name="<?php echo $name; ?>" id="<?php echo $id; ?>" <?php if ( $checked ): ?>checked="checked"<?php endif; ?> <?php echo $callback; ?>> <?php echo $value; ?>
					</label>
				</div>
				<?php
			};

			if ( isset( $this->arguments['around_each'] ) && is_callable( $this->arguments['around_each'] ) ) {
				$this->arguments['around_each']( $html );
			} else {
				call_user_func( $html );
			}

		}
	}

	public function radio( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		?>
		<?php foreach ( $this->arguments['options'] as $key => $value ): ?>
			<input type="radio" id="<?php echo $this->field_id . '_' . $key; ?>" name="<?php echo $this->field_name; ?>" value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $this->field_value ): ?> checked="checked"<?php endif; ?>>
			<label for="<?php echo $this->field_id . '_' . $key; ?>"><?php echo $value; ?></label>
		<?php endforeach; ?>
		<?php
	}

	public function image( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );
		
		// determine image dimensions
		$img_html_attributes = '';

		if ( isset( $arguments['image_width'] ) )
			$img_html_attributes .= ' width="' . $arguments['image_width'] . '"';

		if ( isset( $arguments['image_height'] ) )
			$img_html_attributes .= ' height="' . $arguments['image_height'] . '"';

		?>
		<div>
			<input type="text" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr( $this->field_value ); ?>" <?php echo $this->html_attributes; ?>>
			<br>
			<div class="podlove_cover_preview_container">
				<img src="<?php echo $this->field_value; ?>" <?php echo $img_html_attributes ?> class="podlove_cover_image podlove_background_color_black" />
			</div>	
		</div>
		<script type="text/javascript">
		(function($) {
			$("#<?php echo $this->field_id ?>").on( 'change', function() {
				url = $(this).val();
				template_source = $("#preview_template").html().replace( '{url}', url );

				$(this).parent().find("img").attr("src", url);
				$("#preview_template").html(template_source);
			} );

			$(document).ready(function () {

				url = $("#<?php echo $this->field_id ?>").val();
				template_source = $("#preview_template").html().replace( '{url}', url );

				$("#<?php echo $this->field_id ?>").parent().find("img").attr("src", url);
				$("#preview_template").html(template_source);

				$("#podlove_cover_preview").on( 'click', function() {
					var image_template = $("#preview_template").html();
					var container = $(".podlove_cover_preview_container");

					if ( $(".podlove_background_color_black").css('background-color') == 'rgb(0, 0, 0)' ) {
						container.animate({width: '300px', height: '300px'});
						container.html( image_template.replace( '{color}', 'black' ) );
						$(".podlove_cover_image").css( 'padding', '0px' );
						$(".podlove_background_color_black").css( 'background-color', 'transparent' );
						$("#podlove_cover_preview").html('Advanced Preview &raquo;');
					} else {
						container.append( image_template.replace( '{color}', 'gray' ) );
						container.append( image_template.replace( '{color}', 'white' ) );
						container.animate({width: '1100px', height: '350px'});
						$(".podlove_cover_image").css( 'padding', '25px' );
						$(".podlove_background_color_black").css( 'background-color', 'black' );
						$("#podlove_cover_preview").html('Advanced Preview &laquo;');
					}
				});
			});			
		})(jQuery);
		</script>
		<script type="text/template" id="preview_template">
			<img src="{url}" <?php echo $img_html_attributes ?> class="podlove_cover_image podlove_background_color_{color}" />
		</script>
		<?php
	}

	public function avatar( $object_key, $arguments ) {
		$this->build_input_values( $object_key, $arguments );

		?>
		<div>
			<input type="text" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr( $this->field_value ); ?>" <?php echo $this->html_attributes; ?>>
			<br>
			<img src="<?php echo $this->field_value; ?>" class="podlove-avatar" />
		</div>
		<script type="text/javascript">
		(function($) {
			function get_gravatar(field) {
				if( $(field).val().indexOf("@") == -1 ) {
					url = $(field).val();
				} else {
					url = 'http://www.gravatar.com/avatar/' + CryptoJS.MD5( $(field).val() ) + '&amp;s=50';

				}	
				$(field).parent().find("img").attr("src", url);
			}

			$("#<?php echo $this->field_id ?>").on( 'change', function() {
				get_gravatar(this);
			} );

			$( document ).ready(function() {
				get_gravatar( $("#<?php echo $this->field_id ?>") );
			});
		})(jQuery);
		</script>
		<?php
	}	


	public function callback( $object_key, $arguments ) {
		call_user_func( $arguments['callback'] );
	}

	/**
	 * Build nested form.
	 * 
	 * @param  object   $object   object that shall be modified via the form
	 * @param  array    $args     list of options, all optional
	 * 		- hidden dictionary with hidden values
	 * @param  function $callback inner form
	 * @return void
	 */
	function fields_for( $object, $args, $callback ) {
		// determine context
		$context = isset( $args['context'] ) ? $this->context . '[' . $args['context'] . ']' . "[{$object->id}]" : $this->context; 
		// build input elements
		call_user_func( $callback, new \Podlove\Form\Input\Builder( $object, $context ) );
	}

}

