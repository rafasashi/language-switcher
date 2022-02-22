<?php

if ( ! defined( 'ABSPATH' ) ) exit;

	class Language_Switcher_Widget extends WP_Widget {

		public $parent;
	
		public function __construct( $parent ) {
			
			$this->parent = $parent;
			
			// Instantiate the parent object
			
			parent::__construct( false, __('Language Switcher', 'language-switcher') );
		}

		public function widget( $args, $instance ) {
			
			// Widget output

			$html = $args['before_widget'];
			
				if( !empty($instance['title']) ){
					
					$title = apply_filters( 'widget_title', $instance['title'] );

					$html .= $args['before_title'] . $title . $args['after_title'];				
				}

				if( empty($instance['display']) ){
					
					$instance['display'] = 'button';
				}
				
				$html .= $this->parent->get_language_switcher($instance['display']);
				
			$html .= $args['after_widget'];
			
			echo wp_kses_normalize_entities($html);
		}

		public function update( $new_instance, $old_instance ) {
			
			$instance = $old_instance;
			
			$instance['title'] = strip_tags( $new_instance['title'] );
			
			$instance['display'] = strip_tags( $new_instance['display'] );
			
			return $instance;
		}

		public function form( $instance ) {
			
			$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
			
			$display = ! empty( $instance['display'] ) ? $instance['display'] : 'button';
			
			// Output admin widget options form
			
			echo '<p>';
				
				echo __( 'Title' , 'language-switcher' ) . ': ';
				
				echo '<br>';
				
				$this->parent->admin->display_field(
					
					array(
						'id' 			=> $this->get_field_id( 'title' ),
						'name' 			=> $this->get_field_name( 'title' ),
						'label'			=> '',
						'description'	=> '',
						'type'			=> 'text',
						'data'			=> $title,
						'placeholder'	=> '',
					), false, true
				);
				
			echo '</p>';
			
			echo '<p>';
			
				echo __( 'Display' , 'language-switcher' ) . ': ';
				
				$this->parent->admin->display_field(
					
					array(
						'id' 			=> $this->get_field_id( 'display' ),
						'name' 			=> $this->get_field_name( 'display' ),
						'label'			=> __( 'Display' , 'language-switcher' ),
						'description'	=> '',
						'type'			=> 'radio',
						'data'			=> $display,
						'options'		=> array('button'=>__( 'Button' , 'language-switcher' ),'list'=>__( 'List' , 'language-switcher' )),
					), false, true
				);
				
			echo '</p>';
		}
	}	