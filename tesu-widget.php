<?php

class tesu_widget extends WP_Widget {

	function __construct() {
		parent::__construct('tesu_widget',__('Teenvio Widget', 'tesu_widget_domain'),array( 'description' => __( 'descriptionwidget', 'tesu_i18n' ), ) 
		);
	}

	public function widget( $args, $instance ) {		
		$options = get_option('tesu_plugin_options');

		    if(!tesu_cache_ping()){return;}

			$title = apply_filters( 'widget_title', $instance['title'] );
			echo $args['before_widget'];
	
			if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
			
			echo "\n<!-- Teenvio Submit Form -->\n";
			tesu_add_scripts();
			
			$tpl = file_get_contents(plugin_dir_path(__FILE__).'tpl/tesu-form.tpl');
			$tpl = str_replace('__#urlloading#__',plugins_url('/images/loading.gif', __FILE__ ),$tpl);
			$tpl = str_replace('__#loading#__', __('loading', 'tesu_i18n' ),$tpl);	
			$tpl = str_replace('__#email#__', __('email', 'tesu_i18n' ),$tpl);
			$tpl = str_replace('__#acepto#__', __('acepto', 'tesu_i18n' ),$tpl);
			$tpl = str_replace('__#politicas#__', __('politicas', 'tesu_i18n' ),$tpl);
			$tpl = str_replace('__#ylas#__', __('ylas', 'tesu_i18n' ),$tpl);
			$tpl = str_replace('__#condiciones#__', __('condiciones', 'tesu_i18n' ),$tpl);
			$tpl = str_replace('__#enviar#__', __('enviar', 'tesu_i18n' ),$tpl);
			$tpl = str_replace('__#gracias#__', __('gracias', 'tesu_i18n' ),$tpl);
					
			$tpl = str_replace('__#url_polpriv#__', $options['url_polpriv'],$tpl);
			if(!empty($options['url_conuso'])){
				$tpl = str_replace('__#url_conuso#__', $options['url_conuso'],$tpl);	
			}else{
				$tpl = str_replace('__#url_conuso#__', " ",$tpl);	
				$tpl = str_replace('__#hide_conuso#__', "display:none;",$tpl);	
			}
			$setWidget='';
			
			if(empty($options['tipoplan']) || $options['tipoplan']!= 'pago'){$setWidget= "Powered by <a href='http://teenvio.com' target='_blank'> teenvio </a>";}
			$tpl = str_replace('__#tesu#__', $setWidget,$tpl);
	//		
			echo $tpl;
			echo "\n<!-- Teenvio Submit Form -->\n";
			echo $args['after_widget'];
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}else {
			$title = __( 'New title', 'tesu_widget_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}
	
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
	
}

function tesu_load_widget() {
	register_widget( 'tesu_widget' );
}

function tesu_add_scripts(){
	wp_register_script( 'tesu_script',plugins_url( '/tesu.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'tesu_script' );
	wp_localize_script( 'tesu_script', 'tesuAjax', array( 'url' => admin_url( 'admin-ajax.php')));
}

function tesu_ajax_save(){
	$data['email']=  tesu_get_option('tesu-email');	
	
	if (isset($data['email'])){
	    try{
    		require_once 'class/APIClientPOST.php';
    		$options = get_option('tesu_plugin_options');
    		
    		$api=new Teenvio\APIClientPOST($options['user'],$options['plan'],$options['pass'],$options['urlCall'],$options['ping']);
    		$id= $api->saveContact($data);
    		$jsondata = $api->getGroupData($options['gid']);
    		$gdata = json_decode($jsondata);
    		$gid = $api->saveGroup($gdata->name,$gdata->description,$gdata->id);
    		$retorno = $api->groupContact($data['email'],$gid);
	    }catch(Teenvio\TeenvioException $e){
            $options['urlCall']='';
            $options['ping']='';
            $options['tipoplan']='';
            update_option( 'tesu_plugin_options', $options);
            
            $retorno="Error";
	    }
	}else{
		$retorno="Error";
	}

	wp_die($retorno);
	
}

function tesu_get_option($key){
	$data = $_POST;
	if (is_array($data) && isset($data[$key])){
		return stripslashes($data[$key]);
	}else{
		return null;
	}
}

function tesu_load_translation_files() {
	$plugin_dir = dirname( plugin_basename( __FILE__ ) ) . '/i18n/' ;
	load_plugin_textdomain( 'tesu_i18n', false, $plugin_dir );
}

add_action('widgets_init', 'tesu_load_widget' );
add_action('after_setup_theme','tesu_load_translation_files');
add_action('wp_ajax_tesu_ajax_save', 'tesu_ajax_save' );
add_action('wp_ajax_nopriv_tesu_ajax_save', 'tesu_ajax_save' );
