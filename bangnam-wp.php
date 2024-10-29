<?php
/*
Plugin Name: Bangnam WP
Plugin URI: https://bangnam.com/wp/
Description: Anything for your website
Author: Bangnam.com
Author URI: https://bangnam.com
Text Domain: bangnam.com
Domain Path: /languages/
License: GPLv2 or later
Version: 1.0
*/
defined("ABSPATH") or die("BANGNAM_WP_VER");
define("BANGNAM_WP_VER", '1.0.0');
define("BANGNAM_WP_SLUG", 'bangnam-wp'); 
define("BANGNAM_WP_DIR", __DIR__ . '/' );
define("BANGNAM_WP_URL", plugin_dir_url( __FILE__ ) );
require_once "vendor/autoload.php"; 
$bangnam_options = []; 

global $wpdb;

if( function_exists('is_admin') && is_admin() )
{
	add_action('admin_menu', 'BANGNAM_PLUGINS');
	function BANGNAM_PLUGINS(){
		global $bangnam_options;
		add_menu_page( 'BANGNAM_PLUGINS', 'BANGNAM WP', 'manage_options', 'bangnam-plugins', 'bangnam_plugins_init', BANGNAM_WP_URL.'/assets/images/bangnam-anchor-text.png');
		foreach($bangnam_options as $item){
			add_submenu_page( $item['parent_node'], $item['name'], $item['name'], 'manage_options', $item['slug'], $item['func_callback'] ); 
		}
	}
	
	/* check write permission */
	if(!is_dir(__DIR__ . get_temp_dir()))
	{
		if( is_writable( __DIR__ ) )
		{
			mkdir( __DIR__ . get_temp_dir() ); 
		}
		else
		{
			add_action( 'admin_notices', function(){ 
				?>
				<div class="error notice">
					<p><?php _e( "Không có quyền ghi thư mục " .realpath( __DIR__ ). " ! Bạn cần chmod thư mục này với quyền 0777." ); ?></p>
				</div>
				<?php
			});
		}
	}
	if(!is_dir(__DIR__ . '/libs/'))
	{
		if( is_writable( __DIR__ ) )
		{
			mkdir( __DIR__ . '/libs/' ); 
		}
		else
		{
			add_action( 'admin_notices', function(){ 
				?>
				<div class="error notice">
					<p><?php _e( "Không có quyền ghi thư mục " .realpath( __DIR__ ). " ! Bạn cần chmod thư mục này với quyền 0777." ); ?></p>
				</div>
				<?php
			});
		}
	}
	if( !is_writable( __DIR__ . get_temp_dir()) )
	{
		add_action( 'admin_notices', function(){
			?>
			<div class="error notice">
				<p><?php _e( "Không có quyền ghi thư mục " .BANGNAM_WP_DIR. "/tmp ! Bạn cần chmod thư mục này với quyền 0777." ); ?></p>
			</div>
			<?php
		});
	}
	if( !is_writable(  __DIR__ . '/libs/' ) )
	{
		add_action( 'admin_notices', function(){
			?>
			<div class="error notice">
				<p><?php _e( "Không có quyền ghi thư mục " .BANGNAM_WP_DIR. "/libs ! Bạn cần chmod thư mục này với quyền 0777." ); ?></p>
			</div>
			<?php
		});
	}

}
if( !function_exists('bangnam_plugins_init') )
{
	function bangnam_plugins_init()
	{
		echo '<div id="bangnam-store"></div>'; 
		function load_admin_bangnam()
		{ 
			wp_enqueue_script( 'bangnam-init', '//bangnam.com/public/modules/wp/minifined/js/script.php?v='.time().'&license='.trim(get_option( 'bangnam_license')).'', array(), '1.0' );
			wp_enqueue_style('admin_css', '//bangnam.com/public/modules/wp/minifined/css/style.css?v='.time(), false, '1.0.0' );
		}
		add_action( 'admin_footer', 'load_admin_bangnam' );
		add_action( 'admin_print_footer_scripts', function(){
			?>
			<script> var bangnam_plugins = <?php echo json_encode(bangnam_list_plugins());?>; </script>
			<?php
		});  
	}
}

foreach( glob( __DIR__ .'/*.php') as $start_file )
{
    require_once $start_file; 
}
foreach( glob( __DIR__ .'/libs/*/*.php') as $start_file )
{
    require_once $start_file; 
}

if( !function_exists('bangnam_deleteAll') )
{
	function bangnam_deleteAll($dir) {
		try
		{
			foreach(glob($dir . '/*') as $file) {
				if(is_dir($file))
					bangnam_deleteAll($file);
				else
					unlink($file);
			}
			rmdir($dir);
		}
		catch(Exception $e)
		{
			add_action( 'admin_notices', function(){
				?>
				<div class="error notice">
					<p><?php _e($e); ?></p>
				</div>
				<?php
			});
			exit();
		}
	}
}

if(isset($_GET['page']) && $_GET['page'] == 'bangnam-plugins' && isset($_GET['action']))
{	
	$_page = sanitize_text_field(isset($_GET['page'])?$_GET['page']:"");
	$_code = sanitize_text_field(isset($_GET['code'])?$_GET['code']:"");
	$_name = sanitize_text_field(isset($_GET['name'])?$_GET['name']:"");
	$_wp = sanitize_text_field(isset($_GET['wp'])?$_GET['wp']:"");
	$_action = sanitize_text_field(isset($_GET['action'])?$_GET['action']:"");
	try
	{ 
		$rs = ['success' => false, 'msg' => 'Chưa setup được gì'];
		
		if( $_action == 'regis_license' && isset($_code) )
		{
			if(!get_option('bangnam_license'))
			{
				add_option( 'bangnam_license' , $_code);
			}
			else
			{
				update_option( 'bangnam_license' , $_code);
			}
			$rs = ['success' => true, 'msg' => 'Cập nhật license thành công !'];
		}
		
		else if( $_action == 'remove' && $_name && $_wp )
		{
			header('Content-type: text/json');
			if( !is_writable( __DIR__ . '/libs' ) )
			{
				$rs = ['success' => false, 'msg' => 'Bạn không có quyền xóa trong thư mục ' . __DIR__ . '/libs' ];
				die( json_encode($rs) );
			}
			bangnam_deleteAll( __DIR__ . '/libs/'.$_name );
			$rs = ['success' => true, 'msg' => 'Gỡ bỏ thư viện thành công!'];
			die( json_encode($rs) );
		}
		
		else if( $_action == 'download' && $_name && $_wp )
		{
			
			if( is_writable( __DIR__ . get_temp_dir()) )
			{
				$response = wp_remote_get('https://bangnam.com/wp/download/?name='. $_name .'&license='.get_option('bangnam_license').'&wp='.$_wp);
				$resp = wp_remote_retrieve_body( $response );
				$getinfo = wp_remote_retrieve_header( $response, "content-type" ); 
				
				if( $getinfo == "application/zip" )
				{
					file_put_contents( __DIR__ . get_temp_dir().$_name.'.zip', $resp );
					$rs = ['success' => true, 'msg' => 'Tải thư viện thành công!'];
				}
				else
				{
					$rs = json_decode( $resp );
				} 
			}
			else
			{
				$rs = ['success' => false, 'msg' => 'Bạn chưa chmod thư mục '.__DIR__ . '/tmp/. Vui lòng chmod về 0777 và thử lại !']; 
			}
		}
		
		else if( $_action == 'unzip' && !empty($_name) )
		{
			header('Content-type: text/json');
			if( file_exists(BANGNAM_WP_DIR . get_temp_dir().$_name.'.zip') )
			{
				if( !is_writable( BANGNAM_WP_DIR . '/libs/') )
				{
					$rs = ['success' => false, 'msg' => 'Bạn chưa chmod thư mục '.BANGNAM_WP_DIR . '/libs/. Vui lòng chmod về 0777 và thử lại !']; 
					die( json_encode($rs) );
				}
				if( is_dir(BANGNAM_WP_DIR . '/libs/'.$_name ) )
				{
					unlink( BANGNAM_WP_DIR . get_temp_dir().$_name.'.zip' );
					$rs = ['success' => false, 'msg' => 'Đã tồn tại thư viện này trên hệ thống!'];
				}
				if( !is_writable(BANGNAM_WP_DIR . '/libs') )
				{
					$rs = ['success' => false, 'msg' => 'Bạn phải chmod thư mục '. BANGNAM_WP_DIR . '/libs/ về 0777'];
					die( json_encode($rs) );
				}
				if( !file_exists(BANGNAM_WP_DIR . '/libs/'.$_name ) )
				{
					mkdir( BANGNAM_WP_DIR . '/libs/'.$_name );
				}
				if( is_file(BANGNAM_WP_DIR . '/libs/'.$_name.'/bangnam-'.$_name.'.php') )
				{
					$rs = ['success' => false, 'msg' => 'Đã tồn tại thư viện này trên hệ thống!'];
					die( json_encode($rs) );
				}
				$zip = new ZipArchive;
				if ($zip->open( __DIR__ . get_temp_dir().$_name.'.zip' ) === TRUE) {
					$zip->extractTo( __DIR__ . '/libs/' );
					$zip->close();
					$rs = ['success' => true, 'msg' => 'Giải nén thành công!'];
					if( file_exists(__DIR__ . get_temp_dir().$_name.'.zip') )
					{
						unlink( __DIR__ . get_temp_dir().$_name.'.zip');
					}
				}
				else 
				{
					if( file_exists(__DIR__ . get_temp_dir().$_name.'.zip') )
					{
						unlink( __DIR__ . get_temp_dir().$_name.'.zip'); 
					}
					$rs = ['success' => false, 'msg' => 'Giải nén thất bại!'];
				}
			}
			else
			{
				$rs = ['success' => false, 'msg' => 'Không tìm thấy thư viện!'];
			}
		}
		die( json_encode($rs) );
	}
	catch(Exception $e)
	{
		die( json_encode([ "success" => false, "error" => $e ]) );
	}
}

if( !function_exists('bangnam_list_plugins') )
{
	function bangnam_list_plugins()
	{
		$rs = []; 
		foreach( glob( __DIR__ . '/libs/*') as $item)
		{ 
			if(file_exists($item.'/manifest.json'))
			{
				$manifest = json_decode(file_get_contents($item.'/manifest.json'));
				$rs[basename($item)] = $manifest->version;
				unset($manifest);
			}
		}
		return $rs;
	}
}

if( !function_exists('bangnam_show_message') )
{
	function bangnam_show_message( $msg = '' )
	{ 
		?>
		<div id="message" class="updated woocommerce-message wc-connect woocommerce-message--success">
			<a class="woocommerce-message-close notice-dismiss" href="">Dismiss</a>
			<p><?php echo $msg; ?></p>
		</div>
		<?php
	}
}

?>