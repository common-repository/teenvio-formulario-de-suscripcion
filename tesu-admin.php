<?php
require_once('tesu-widget.php');
/**
* Plugin Name: Teenvio - Formulario de suscripci贸n
* Description: Genera un Widget para conectar con el sistema Teenvio
* Text Domain: tesu_i18n
* Version: 1.2.3.1
* Author: Teenvio
* Author URI: http://www.teenvio.com
* License: GPL2
*/
function tesu_cache_ping(){
	$options = get_option('tesu_plugin_options');
	if(empty($options['tesu_login']) || $options['tesu_login']!=true){
	    return false;
	}
	
	$needTOping = true;
	$today = new DateTime();

	
	if(empty($options['ping']) ){
		$needTOping =true;
	}else{//contiene algo
		try{
			$today = new DateTime();
			$last_ping = new DateTime($options['ping']);
			$interval = date_diff($today, $last_ping,TRUE);
			$hours_of_last_ping = $interval->format('%h');
			if($hours_of_last_ping >= 6){
				$needTOping = true;
				$options['ping']='';
			}else{
				$needTOping = false;
			}
		}catch(exception $e){
		  $needTOping = true;
		}
	}
	
	if($needTOping){
		require_once 'class/APIClientPOST.php';
		try{
			$api=new Teenvio\APIClientPOST($options['user'],$options['plan'],$options['pass'],$options['urlCall'],$options['ping']);
			$options['ping'] = $today->format("Y-m-d H:i");
			$options['urlCall'] = $api->getCurrentURLCall();
			$dataplan=$api->getAccountData('JSON');
			if(strstr($dataplan,'gratuito')){
			    $options['tipoplan']='gratuito';
			}else{
			    $options['tipoplan']='pago';
			}
			$options['tesu_login']=true;
			update_option( 'tesu_plugin_options', $options); 
		}catch(Teenvio\TeenvioException $e){
		    $options['urlCall']='';
            $options['ping']='';
            $options['tipoplan']='';
            $options['tesu_login']=false;
            
	        update_option( 'tesu_plugin_options', $options);
			add_action( 'admin_notices', 'tesu_error_connection' );
			return false;
		}
	}
	
	return true;
}

function tesu_plugin_admin_add_page() {
	if ( empty ( $GLOBALS['admin_page_hooks']['teenvio_menu'] ) ){
		add_menu_page('Teenvio', 'Teenvio', 'manage_options', 'teenvio_menu', 'teenvio_menu',plugins_url( 'images/teenvio_20x20.png', __FILE__ ));
	}
	add_submenu_page('teenvio_menu','', 'TeSu Plugin', 'manage_options', 'tesu_plugin', 'tesu_plugin_options_page');
	remove_submenu_page('teenvio_menu','teenvio_menu');
	
	if( (ini_get('allow_url_fopen') != true) && !(function_exists('curl_version')) ){
		add_action( 'admin_notices', 'tesu_error_limit_php' );
	}else{
			$options = get_option('tesu_plugin_options');
			if(empty($options['tesu_login']) || $options['tesu_login']!=true){
				add_action( 'admin_notices', 'tesu_error_connection' );
			}else{
				$vacio = false;
				if(empty($options)){
				$vacio=true;
				}else{
					$mandatory=array('user','plan','pass','url_polpriv');
					foreach($options as $key=>$value){
						$options_name = trim($key);
						if(empty($value) && in_array($options_name,$mandatory)) {
							$vacio=true;
						}
					}		
				}	
				if($vacio==true){
					add_action( 'admin_notices', 'tesu_error_connection' );
				}else{
				//	tesu_cache_ping();
				}
			}
	}
}
function tesu_plugin_options_page(){
	echo "\n<!-- Teenvio Submit Admin -->\n";
	$tpl = file_get_contents(plugin_dir_path(__FILE__). 'tpl/tesu-admin.tpl');
	$tpl = str_replace('__#configuracion#__', __('configuracion', 'tesu_i18n' ),$tpl);
	$tpl = str_replace('__#logo_head#__', plugins_url('images/teenvio_head.png',__FILE__ ),$tpl);
	echo $tpl; 

?>	
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
<script> 
if (jQuery){
	jQuery(document).ready(function(){
		jQuery.validate({
			lang: '<?php echo substr(get_locale(), 0, 2); ?>'
		});
	});
}
	
	
</script>

<div class="tesu-body">
	<form id="tesudata" action="options.php" method="post">
		<?php settings_fields('tesu_plugin_options'); ?>
		<?php do_settings_sections('tesu_plugin'); ?>
		<h4>* <?php _e('obligatorio','tesu_i18n'); ?> </h4>
		<br>
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /><?php echo "<span>".__('si no dispone de cuenta puede crear una', 'tesu_i18n' )." <a href='".__('enlace alta','tesu_i18n')."' target='_blank'> ".__('aqui','tesu_i18n')."</a></span>"; ?>
	</form>
</div>
<?php
	echo "\n<!-- Teenvio Submit Admin -->\n";
}
function tesu_plugin_admin_init(){
	register_setting( 'tesu_plugin_options', 'tesu_plugin_options', 'tesu_plugin_options_validate' );
	
	add_settings_section('tesu_plugin_main', ' ', 'tesu_plugin_section_text', 'tesu_plugin');
	add_settings_field('tesu_string_user',  __('name', 'tesu_i18n' )." *", 'tesu_plugin_setting_user', 'tesu_plugin', 'tesu_plugin_main');
	add_settings_field('tesu_string_plan', __('plan', 'tesu_i18n' )." *", 'tesu_plugin_setting_plan', 'tesu_plugin', 'tesu_plugin_main');
	add_settings_field('tesu_string_pass', __('pass', 'tesu_i18n' )." *", 'tesu_plugin_setting_password', 'tesu_plugin', 'tesu_plugin_main');
	
	add_settings_field('tesu_string_url_polpriv', __('urlpoliticas', 'tesu_i18n' )." *", 'tesu_plugin_setting_url_polpriv', 'tesu_plugin', 'tesu_plugin_main');
	add_settings_field('tesu_string_url_conuso',  __('urlcondiciones', 'tesu_i18n' ), 'tesu_plugin_setting_url_conuso', 'tesu_plugin', 'tesu_plugin_main');
	
	add_settings_field('tesu_string_gname',  __('gname', 'tesu_i18n' ), 'tesu_plugin_setting_gname', 'tesu_plugin', 'tesu_plugin_main');
	add_settings_field('tesu_string_gid',  '', 'tesu_plugin_setting_gid', 'tesu_plugin', 'tesu_plugin_main');

}
function tesu_plugin_section_text() {
	//echo null;
}
function tesu_plugin_setting_user() {
	$options = get_option('tesu_plugin_options');
	echo "<input id='tesu_string_user' data-validation='required'' name='tesu_plugin_options[user]' size='40' type='text' placeholder='". __('name', 'tesu_i18n' ) ."' value='{$options['user']}' />";
}
function tesu_plugin_setting_plan() {
	$options = get_option('tesu_plugin_options');
	echo "<input id='tesu_string_plan' data-validation='required' name='tesu_plugin_options[plan]' size='40' type='text' placeholder='".__('plan', 'tesu_i18n' )."' value='{$options['plan']}' />";
}
function tesu_plugin_setting_password() {
	$options = get_option('tesu_plugin_options');
	echo "<input id='tesu_string_pass' data-validation='required' name='tesu_plugin_options[pass]' size='40' type='password' placeholder='".__('pass', 'tesu_i18n' )."' value='{$options['pass']}' />";
}
function tesu_plugin_setting_url_polpriv() {
	$options = get_option('tesu_plugin_options');
	echo "<input id='tesu_string_url_polpriv' data-validation='url'  name='tesu_plugin_options[url_polpriv]' size='40' type='text' placeholder='http://mysite.com'  value='{$options['url_polpriv']}' /> ";
}
function tesu_plugin_setting_url_conuso() {
	$options = get_option('tesu_plugin_options');
	echo "<input id='tesu_string_url_conuso' data-validation='url' data-validation-optional='true' name='tesu_plugin_options[url_conuso]' size='40' type='text' placeholder='http://mysite.com'  value='{$options['url_conuso']}' />";
}
function tesu_plugin_setting_gname() {
	$options = get_option('tesu_plugin_options');
	echo "<input id='tesu_string_gname' name='tesu_plugin_options[gname]' size='40' type='text' value='{$options['gname']}' />";
}
function tesu_plugin_setting_gid() {
	$options = get_option('tesu_plugin_options');
	echo "<input id='tesu_string_gid' name='tesu_plugin_options[gid]' size='40' type='hidden' value='{$options['gid']}' />";
}
function tesu_plugin_options_validate($input){	
	$error=false;
	
	$mandatory=array('user','plan','pass','url_polpriv');
	
	foreach($input as $key=>$value){
		$input_name = trim($key);
		if(empty($value) && in_array($input_name,$mandatory)) {
			$error=true;
			$type = 'error';
		        $message = __( 'Complete los datos obligatorios', 'my-text-domain' );
		}
	}		
	
	if($error==true){
		add_action( 'admin_notices', 'tesu_error_datos_vacios' );
	}else{
		try{
			require_once 'class/APIClientPOST.php';
			$options = get_option('tesu_plugin_options');
			$api=new Teenvio\APIClientPOST($input['user'],$input['plan'],$input['pass'],$options['urlCall'],$options['ping']);
	
			if(empty($input['gname']))
				$input['gname']="Formulario de Wordpress";
		
			$input['gid'] = $api->saveGroup($input['gname'],__('tesugroupdescription','tesu_i18n'),$input['gid']);
			$input['tesu_login']=true;
			$input['ping'] = null;
			$input['urlCall'] = null;
		}catch(Teenvio\TeenvioException $ex){
			add_action( 'admin_notices', 'tesu_error_connection' );
		}
	}
	
	return $input;	
}
function tesu_error_connection() {
    ?>
    <div class="error notice">
        <p><strong>Teenvio Widget</strong> - <?php _e('datosincorrectos','tesu_i18n'); ?></p>
    </div>
    <?php
}
function tesu_error_limit_php() {
    ?>
    <div class="error notice">
        <p><strong>Teenvio Widget</strong> - <?php _e('error limit php','tesu_i18n'); ?></p>
    </div>
    <?php
}
function tesu_plugin_activate(){
    register_uninstall_hook( __FILE__, 'tesu_plugin_uninstall' );
}
function tesu_plugin_uninstall(){
	unregister_widget('tesu_widget');
	delete_option('tesu_plugin_options');

	$widgets = get_option('sidebars_widgets');
	foreach($widgets as $widget_zone=>&$widget_data){
		$rowtodelete=array();

		foreach($widget_data as $id_row=>$value){
			if(strpos($value, "tesu_widget")!==false){
				$rowtodelete[]=$id_row;
			}
		}
		if(count($rowtodelete)>0){
			foreach($rowtodelete as $key=>$idtodelete){
				unset($widget_data[$idtodelete]);
			}
		}
	}
	update_option('sidebars_widgets', $widgets);	
}

add_action('admin_menu', 'tesu_plugin_admin_add_page');
add_action('admin_init', 'tesu_plugin_admin_init');
register_activation_hook( __FILE__, 'tesu_plugin_activate' );
