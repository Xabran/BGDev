<?php 

/**
 * Package Included for DynaTheme
 * 
 * Do not Modify this under any circumtances
 *
 * @package     DynaTheme Global Configuration
 * @subpackage  Core Config
 * @author      SilverKenn
 * @version     1.1
 */
 
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

// Duplication could take you to hell
if( !class_exists( 'DynaTheme' ) ) {

	//Lets Rock!
	class DynaTheme {
			
		public static $cmk_version = '1.1';
		private $cmk_core_version;
		protected $rdf_version;
		public $active_template;
		public static $wp_upload_dir;
		public static $cmk_upload_dir;
		public static $cmk_upload_url;
		public static $cmk_framework_dir;
		public static $cmk_framework_url;
		public static $cmk_dir;
		public static $cmk_url;

		/**
         * Class Constructor. Defines the args for the theme set-up class
         * @since   1.1
         * @author  Silverkenn
         */
		public function __construct() {
			$this->vars();
			$this->cmk_initialize_settings();
		}

		/*
		 * Load theme settings object
		 * @return array() for settings options
		 * @since version 1.0
		 */
		public static function settings( $key, $message = NULL, $opt = array() ) {
			global $cmkdynatheme;
	        $options = ( $opt ) ? $opt: $cmkdynatheme ;
	        return ( array_key_exists( $key, $options) ) ? $options[$key] : $message;
	    }

		public static function v( $key, $message = NULL, $opt = array() ) {
			global $cmkdynatheme;
	        $options = ( $opt ) ? $opt: $cmkdynatheme ;
	        return ( array_key_exists( $key, $options) ) ? $options[$key] : $message;
	    }

	    public static function a( $key, $key2, $message = NULL, $opt = array() ) {
	        global $cmkdynatheme;
	        $options = ( $opt ) ? $opt: $cmkdynatheme ;
	        if ( array_key_exists($key, $options) ) {
	            $key = $options[$key];
	            if( is_array( $key ) && array_key_exists($key2, $key) ) {
	                return $key[$key2];
	            } else {
	                return $message;
	            }
	        } else {
	            return $message;
	        }
	    }

		/**
         * Assign value to variable. 
         * @since   1.1
         * @return  returns class static variables with their values
         */
		public function vars(){
			
			$cmk		 = wp_get_theme();
			$upload_dir  = wp_upload_dir();
			
			$this->cmk_core_version = $cmk->get('Version');
			$this->rdf_version = substr($cmk->get('Version'), 4, 12);
			$this->active_template = $cmk->get('Template');
	
			self::$wp_upload_dir = self::cmk_cleandir( $upload_dir['basedir']);
			self::$cmk_upload_dir = self::cmk_cleandir( $upload_dir['basedir'] . '/dynatheme' );
			self::$cmk_upload_url = self::cmk_cleandir( $upload_dir['baseurl'] . '/dynatheme' );
			self::$cmk_framework_dir = self::cmk_cleandir( dirname( dirname(__FILE__) ) );
			self::$cmk_framework_url = get_stylesheet_directory_uri().'/framework';
			self::$cmk_dir = self::cmk_cleandir( dirname( dirname( dirname(__FILE__) ) ) );
			self::$cmk_url = get_stylesheet_directory_uri();
		}

		/**
         * Initialize theme settings, load all configuration in a single functon
         * @since   1.1
         * @return  settings
         */
		public function cmk_initialize_settings() {
			
			# Check Framework File
			$redux_framework = self::$cmk_framework_dir . '/config/admin/ReduxCore-'.$this->rdf_version.'/framework.php';
			if ( !file_exists( $redux_framework ) ) {
				$redux_framework = self::$cmk_framework_dir . '/config/admin/ReduxCore/framework.php';
			}
			#Load Theme Option Main Configuration File
			if ( !class_exists( 'ReduxFramework' ) && file_exists( $redux_framework ) ) {
				require_once( $redux_framework );	
				if ( file_exists( self::$cmk_framework_dir. '/config/admin/dynatheme-admin.php' ) ) {
					require_once( self::$cmk_framework_dir. '/config/admin/dynatheme-admin.php' );
				}
				
			}
						
			## Load Admin Script & CSSS
			add_action( 'admin_head-appearance_page_cmkoptions', array( $this, 'cmk_enqueue_admin_css_script' ));
						
			# Create CMKDEV uploads directory	
			$this->set_cmk_uploads_folder();

			# Generate CSS & PHP files
			add_action('after_switch_theme', array( $this, 'generate_custom_files' ), 10, 3);
			add_filter('redux/options/cmkdynatheme/compiler', array( $this, 'generate_custom_files' ), 10, 3);

			# Load nescessary php files
			$this->load_deault_php_files_and_config();

			#Load Theme CSS and Script Configuration
			add_action( 'wp_enqueue_scripts',  array( $this, 'cmk_styles_script_config') );

			#Enqueue script that are enabled from theme options
			add_action('wp_enqueue_scripts', array($this, 'cmk_additional_scripts_config' ) );

			#Hook/Configure Metaboxes Values
			add_action( 'wp_head', array( $this, 'cmk_addition_wp_head_hook' ), 69 ); //Hook additional Header Script
			add_action( 'wp_footer', array( $this, 'cmk_addition_wp_footer_hook' ), 70 ); // Show Enqued Script
			add_action( 'wp_footer', array( $this, 'show_enqueued_styles_scripts' ), 69 ); // Hook Additional Footer script
			add_action( 'wp_print_scripts', array( $this, 'cmk_dequeue_scripts_style' ), 69 ); // Dequeue Scripts
			add_action('wp_print_styles',  array( $this, 'cmk_dequeue_scripts_style' ), 69); // Dequeue Styles 

			#load additional functionality 
			$this->load_custom_php_files();
		}

		/*
		 * Return URL or Direcoty with all forward slash
		 * @ param  string $path
		 * @ return $path
		 */
		public static function cmk_cleandir( $path ) {
            $path = str_replace('','', str_replace( array( "\\", "\\\\" ), '/', $path ) );
            if ($path[ strlen($path)-1 ] === '/') {
                $path = rtrim($path, '/');
            }
            return $path;
        }

        /*
		 * Create Dynatheme directory in wp-content/uploads folder. 
		 * @return display admin error in admin section if uploads folder is not writable.
		 * @since version 1.0
		 */
        private function set_cmk_uploads_folder() {
			if ( is_admin() ) {
				if ( !is_dir( self::$cmk_upload_dir ) ) { 
					if ( is_writable( self::$wp_upload_dir ) ) {
						wp_mkdir_p( self::$cmk_upload_dir , 0755, true);
					} else {
						add_action('admin_notices', function(){
							_e( '<div class="notice is-dismissible error"><p>Unable to create dynatheme directory, please make sure you have correct wordpress files and folder permission.</p></div>', 'cmk' );	
						});
					}
				} 
			}
		}

		/*
		 * Load custom css and Font-awesome on admin panel view. 
		 * @return null.
		 * @since version 1.0
		 */
		public function cmk_enqueue_admin_css_script(){
			wp_enqueue_style( 'cmk-font-awesome', self::$cmk_url.'/css/font-awesome/css/font-awesome.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'cmk_admin_css', self::$cmk_framework_url.'/config/admin/admin-enque.css' );
		}

		/*
		 * Include nesscessary php files specially those that are stored in /framework/ext/
		 * @return null
		 * @since version 1.0
		 */
		private function load_deault_php_files_and_config() {
			foreach (glob( self::cmk_cleandir( dirname( __FILE__ ).'/*-autoload.php' ) ) as $filename ) {
				include_once $filename;
			}

			if ( class_exists( 'WooCommerce' ) ) {
				include_once( self::cmk_cleandir( dirname( __FILE__ ).'/woocommerce-config.php' ) );
			} 

			if ( $this->active_template == 'genesis' ) {
				require_once( get_template_directory().'/lib/init.php');
				require_once('genesis-config.php');	
			}
			include_once( self::cmk_cleandir( dirname( __FILE__ ). '/admin/JSMin.php') );
		}

		/*
		 * Minifiy CSS file, 
		 * @return string $css
		 * @since version 1.0
		 */
		public static function cmk_css_minify( $css ) {
			// Normalize whitespace
			$css = preg_replace( '/\s+/', ' ', $css);
			// Remove comment blocks, everything between /* and */, unless
			// preserved with /*! ... */
			$css = preg_replace( '/\/\*[^\!](.*?)\*\//', '', $css);
			// Remove space after , : ; { }
			$css = preg_replace( '/(,|:|;|\{|}) /', '$1', $css);
			// Remove space before , ; { }
			$css = preg_replace( '/ (,|;|\{|})/', '$1', $css);
			// Strips leading 0 on decimal values (converts 0.5px into .5px)
			$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css);
			// Strips units if value is 0 (converts 0px to 0)
			$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css);
			// Converts all zeros value into short-hand
			$css = preg_replace( '/0 0 0 0/', '0', $css);
			// Ensures image path is correct, if we're serving .css file from subfolder
			//$css = preg_replace( '/url\(([\'"]?)images\//', 'url(${1}' . CHILD_URL . '/images/', $css);
			return apply_filters( 'cmk_minify_css', $css );
		}

		/*
		 * Generate custom CSS, PHP and Javascript files from Theme Options and store these files in uploads folder
		 * @return null
		 * @since version 1.0
		 */
		public function generate_custom_files() {
			
			if ( is_admin() ) {
				
				global $wp_filesystem;

				if ( empty( $wp_filesystem ) ) {
					require_once( ABSPATH .'/wp-admin/includes/file.php' );
					WP_Filesystem();
				}
				$cmkminifiedcss = '';
				$css = apply_filters( 'cmk/minified/css', array(
					'main'		=> self::$cmk_dir.'/css/main.css',
					'animate'	=> self::$cmk_dir.'/css/animate.min.css',
				), $css );
				foreach ( $css as $key => $source) {
					$cmkminifiedcss .= self::cmk_css_minify( $wp_filesystem->get_contents( $source ) );
				}
				$cmkminifiedcss .= self::cmk_css_minify( self::settings('cmk-custom-css') );

				$cssfilename = self::$cmk_upload_dir . '/custom' . '.css';
				$cssminfilename = self::$cmk_upload_dir . '/cmk.min' . '.css';
				$phpfilename = self::$cmk_upload_dir . '/custom' . '.php';
				$adminphpfilename = self::$cmk_upload_dir . '/admin-custom' . '.php';
				$jscriptfilename = self::$cmk_upload_dir . '/script' . '.js';
				
				$customCSS  = "/** This CSS File is auto-generated from theme option. Please do not edit this file directly**/\n";
				$customCSS .=  trim( self::settings('cmk-custom-css') );

				$customPHP  = "<?php /** This PHP File is auto-generated from theme option. Please do not edit this file directly**/ ?>\n";
				$customPHP .= trim( self::settings('cmk-custom-php') );

				$admincustomPHP  = "<?php /** This PHP File is auto-generated from theme option. Please do not edit this file directly**/ ?>\n";
				$admincustomPHP .= trim( self::settings('admin-cmk-custom-php') );
				
				$customJS = "/** This Javascript File is auto-generated from theme option. Please do not edit this file directly**/\n'use strict';\n";
				$customJS .= self::settings('cmk-minify-script') === '1' ? JSMin::minify( trim( self::settings('cmk-custom-script') ) ) : trim( self::settings('cmk-custom-script')) ; 

				$wp_filesystem->put_contents($cssfilename, $customCSS, 0644 );
				$wp_filesystem->put_contents($phpfilename, $customPHP, 0644 );
				$wp_filesystem->put_contents($adminphpfilename, $admincustomPHP, 0644 );
				$wp_filesystem->put_contents($cssminfilename, $cmkminifiedcss, 0644 );
				$wp_filesystem->put_contents($jscriptfilename, $customJS, 0644 );
		
				add_action('admin_notices', function(){
					_e( '<div class="notice is-dismissible updated"><p>Custom PHP, CSS and Javascript successfully generated.</p></div>', 'cmk' );	
				});	
				#$this->debug_to_console( 'yow->'. $customCSS );
			} else {
				add_action('admin_notices', function(){
					_e( '<div class="notice error"><p>You don\'t have rights to generate these files </p></div>', 'cmk' );	
				});	
			}
		}

		/*
		 * Load generated php files and include on theme functions
		 * @return null
		 * @since version 1.0
		 */

		private function load_custom_php_files() {

			$customphpfile      = self::$cmk_upload_dir.'/custom.php';
			$admincustomphpfile = self::$cmk_upload_dir.'/admin-custom.php';
			if ( !defined('INCLUDEPHPFILE') ) {
				if ( !is_admin() && file_exists( $customphpfile ) ) {
					include $customphpfile;
				}
				if ( file_exists( $admincustomphpfile ) ) {
					include $admincustomphpfile;
				}
				define('INCLUDEPHPFILE',1);
			}
		}

		/*
		 * Configure Theme CSS and JS files to be loaded
		 * @return null
		 * @since version 1.0
		 */
		public function cmk_styles_script_config() {

			$devmode = self::settings('cmk-site-build-mode');
			$fontawesome = self::settings('cmk-activate-font-awesome');
			$fontawesomesource = self::settings('cmk-font-awesome-source');
				
			$elusiveicon = self::settings('cmk-activate-elusive-icon');
			$elusiveiconsource = self::settings('cmk-elusive-icon-source');
				
			wp_dequeue_style( 'child-theme' );
				
			if ( $fontawesome === '1' && $fontawesomesource === '1'  ) {
				wp_enqueue_style('cmk-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
			}
			if ( $fontawesome === '1' && $fontawesomesource === '0' ) {
				wp_enqueue_style('cmk-font-awesome', self::$cmk_url. '/css/font-awesome/css/font-awesome.min.css');
			}
			if ( $elusiveicon === '1' && $elusiveiconsource === '1'  ) {
				wp_enqueue_style('cmk-elusive-icon', '//maxcdn.bootstrapcdn.com/elusive-icons/2.0.0/css/elusive-icons.min.css');
			}
			if ( $elusiveicon === '1' && $elusiveiconsource === '0' ) {
				wp_enqueue_style('cmk-elusive-icon', self::$cmk_url. '/css/elusive-icons/css/elusive-icons.min.css');
			}
				
			if ( empty( $devmode ) && $devmode == '0' ) {
				wp_enqueue_style('cmk', self::$cmk_url. '/css/main.css');
				wp_enqueue_style('animate', self::$cmk_url. '/css/animate.min.css');
				if ( file_exists( self::$cmk_upload_dir . '/custom.css' ) ) {
					wp_enqueue_style('cmk-custom', self::$cmk_upload_url . '/custom.css');
				}
			} else {
				wp_enqueue_style('cmk-min', self::$cmk_upload_url . '/cmk.min.css');
				
			}
		}

		/*
		 * Cheat on PHP, dump object/string on console log
		 * @return null
		 * @since version 1.0
		 */
		public function debug_to_console( $data ) {
		    if ( is_array( $data ) )
		        $output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
		    else
		        $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";
		    echo $output;
		}

		/*
		 * Register All script to be enqueue later, enable apply_filters function to be able to modify the 
		 * script parameters.
		 * @return scripts
		 * @since version 1.0
		 */
		public function cmk_additional_scripts_config() {

			global $wp_scripts;
			//Get scripts enabled in Theme Options
			$active_scripts = self::settings('cmk-enque-scripts');

			//List all default script in an array format with default values including each filter name
			$scripts = array(
				'script'	=> array(
					'cmk-angular-js' => array(
						'src'	 => self::$cmk_url. '/js/angular/angular.min.js',
						'filter' => 'cmk/register/angularjs',
					),
					'cmk-elevatezoom-js' => array(
						'src'	 => self::$cmk_url. '/js/elevatezoom.min.js',
						'filter' => 'cmk/register/elevatezoom',
					),
					'cmk-fancybox-js' => array(
						'src'	 => self::$cmk_url. '/js/fancybox/source/jquery.fancybox.pack.js',
						'filter' => 'cmk/register/fancyboxjs',
					),
					'cmk-grid-js' => array(
						'src'	 => self::$cmk_url. '/js/grid/cmk-grid.js',
						'filter' => 'cmk/register/gridjs',
					),
					'cmk-nav-js' => array(
						'src'	 => self::$cmk_url . '/js/nav.js',
						'filter' => 'cmk/register/navjs',
					),
					'cmk-sidr-js' => array(
						'src'	 => self::$cmk_url. '/js/sidr/sidr.min.js',
						'filter' => 'cmk/register/sidrjs',
					),
					'cmk-skrollr-js' => array(
						'src'	 => self::$cmk_url. '/js/skrollr/skrollr.min.js',
						'filter' => 'cmk/register/skrollrjs',
					),
					'cmk-vid-js' => array(
						'src'	 => self::$cmk_url. '/js/vidjs/jquery.vide.min.js',
						'filter' => 'cmk/register/vidjs',
					
					),
					'cmk-waypoints-js' => array(
						'src'	 => self::$cmk_url. '/js/waypoints/waypoints.min.js',
						'filter' => 'cmk/register/waypointsjs',
					),
					'cmk-cufonyui-js' => array(
						'src'	 => self::$cmk_url. '/js/cufon-yui.js',
						'filter' => 'cmk/register/cufonyuijs',
					),
					'cmk-prefixfree-js' => array(
						'src'	 => self::$cmk_url. '/js/prefixfree.min.js',
						'filter' => 'cmk/register/prefixfreejs',
					),
					'cmk-retina-js' => array(
						'src'	 => self::$cmk_url. '/js/retina.min.js',
						'filter' => 'cmk/register/retinajs',
					),
					'cmk-carousel-js' => array(
						'src'	 => self::$cmk_url. '/js/carousel/carousel.min.js',
						'filter' => 'cmk/register/carousel',
					),
					'cmk-lettering-js' => array(
						'src'	 => self::$cmk_url. '/js/lettering.min.js',
						'filter' => 'cmk/register/lettering',
					),
					'cmk-texttile-js' => array(
						'src'	 => self::$cmk_url. '/js/textillate.min.js',
						'filter' => 'cmk/register/lettering',
					),
					'cmk-date-js' => array(
						'src'	 => self::$cmk_url. '/js/date.js',
						'filter' => 'cmk/register/datejs',
					),
				),
				'deps'		=> array(),
				'ver' 		=> self::$cmk_version, 
				'infot'		=> true,
			);

			// Only register Custom Script from theme options if file_exists
			$cjs = apply_filters('cmk/register/dynathemejs', array(
					'src'	=> self::$cmk_upload_url . '/script.js',
					'deps'	=> array('jquery' ,'cmk-nav-js'), 
					'ver'	=> $scripts['ver'],
					'infot' => $scripts['infot'], 
			));
			if ( file_exists( self::$cmk_upload_dir . '/script.js' ) ) {
				wp_register_script('cmk-dynatheme-js', $cjs['src'], $cjs['deps'], $cjs['ver'], $cjs['infot']);
			}
			// Loop through each default script
			foreach ( $scripts['script'] as $script => $val) {

				// Reformat script, apply filters to their values and assign default values
				$sc = apply_filters($val['filter'], array(
					'src'	=> $val['src'],
					'deps'	=> $scripts['deps'], 
					'ver'	=> $scripts['ver'],
					'infot' => $scripts['infot'], 
				));

				//Register each script
				wp_register_script($script, $sc['src'], $sc['deps'], $sc['ver'], $sc['infot'] );
			}
			// Loop through each active scripts
			if ( $active_scripts ) {
				if ( is_array( $active_scripts )) {
					foreach( $active_scripts as $active ) {
				        // Enqueue all the script set as active
				        wp_enqueue_script( $active );
				    }
			    } else {
			    	wp_enqueue_script( $active_scripts );
			    }
			}

		    //Register Style Dependency for Javascript
			wp_register_style('cmk-fancybox-css', 
				self::$cmk_url. '/js/fancybox/source/jquery.fancybox.css', 
				'cmk', 
				self::$cmk_version,
				'all' 
			);
			wp_register_style('cmk-carousel-css', 
				self::$cmk_url. '/js/carousel/carousel.css', 
				'cmk', 
				self::$cmk_version,
				'all' 
			);
			$sidrcss = apply_filters('cmk/register/sidrcss', array(
				'src' => self::$cmk_url. '/js/sidr/css/sidr.light.css',
			));
			wp_register_style('cmk-sidr-css', $sidrcss['src'] );

			// load script from page meta
			$meta_scripts = self::_meta('enque_scripts');

			if( $meta_scripts ) {
				foreach( $meta_scripts as $scripts ) {
					if ( !empty( $scripts ))
			        wp_enqueue_script( $scripts );
			    }
		    }

			//Enque Style if Script is active
			if ( strpos( implode(' ', $wp_scripts->queue), 'cmk-fancybox-js') !== false ) {
				wp_enqueue_style('cmk-fancybox-css');
			}
			if ( strpos( implode(' ', $wp_scripts->queue), 'cmk-sidr-js') !== false ) {
				wp_enqueue_style('cmk-sidr-css');
			}
			if ( strpos( implode(' ', $wp_scripts->queue), 'cmk-carousel-js') !== false ) {
				wp_enqueue_style('cmk-carousel-css');
			}

		}

		/*
		 * Pull Value from DynaTheme Options in post types, take not that $prefix is already pre-defined
		 * and you only need to add the sting in its parameter
		 * @return string/array()
		 * @since version 1.0
		 */
		public static function _meta( $meta_id ) {
			global $post;
			$meta_key = '_cmk_box_'.$meta_id;
			if ( $meta_key && ( is_single() || is_page() ) ) {
				$meta_val = get_post_meta( $post->ID, $meta_key, true );
				if (!empty($meta_val))
					return $meta_val;
			}
        }

        /*
		 * Assing pre-defined value of lists of script to be use for Dynatheme Options in post types
		 * 
		 * @return $cmk_scripts array()
		 * @since version 1.0
		 */
		public static function get_cmk_unenqueue_lists() {
			
			/**Not working, Unfortunately, we cannot pull enqueued script from the front-end to display on the back-end it only returns the $cmk_scripts initial value**/
			#global $wp_scripts;
			#$enqueued = $wp_scripts->queue;

			$cmk_scripts = array(
				'cmk-angular-js'		=> 'AngularJS',
				'cmk-elevatezoom-js'	=> 'ElevateZoom',
				'cmk-carousel-js'		=> 'Carousel',
				'cmk-cufonyui-js'		=> 'Cufon Yui',
				'cmk-date-js'			=> 'Date JS',
				'cmk-fancybox-js'		=> 'FancyBox',
				'cmk-lettering-js'		=> 'Lettering',
				'cmk-grid-js'			=> 'Masonry',
				'cmk-prefixfree-js'		=> 'Prefixfree',
				'cmk-retina-js'			=> 'Retina',
				'cmk-sidr-js'			=> 'Sidr',
				'cmk-skrollr-js'		=> 'Skrollr',
				'cmk-texttile-js'		=> 'Textillate',
				'cmk-dynatheme-js'		=> 'Theme Options Script',
				'cmk-vid-js'			=> 'VideJS',
				'cmk-waypoints-js'		=> 'Waypoints',
			);
			/*if ( $enqueued ) {
				$cmk_scripts = array_flip( array_diff( array_flip( $cmk_scripts ), $enqueued ) );
			}*/

			return $cmk_scripts;	
		}

		# Hook Additional Header Script
		public function cmk_addition_wp_head_hook() {
			$headerScript = self::_meta('wp_head');
			echo $headerScript."\n";
		}
		
		# Hook Additional Footer Script
		public function cmk_addition_wp_footer_hook() {
			$footerScript = self::_meta('wp_footer');
			echo $footerScript;
		}
		
		#List All Unqueque Script and Styles
		public function show_enqueued_styles_scripts() {
			global $wp_scripts, $wp_styles;
			$showenqueueScripts = self::_meta('showenqueued_script');
			$showenqueueStyles = self::_meta('showenqueued_styles');
			$devmode = Dynatheme::settings('cmk-site-build-mode');
			if ( $showenqueueScripts === 'on' /*|| $devmode == '0'*/ ) {
				$allScipts = implode(' | ', $wp_scripts->queue);
				echo '<div class="ckensct"><b style="color:#78FF79">Script Handle Name(s) </b> '.$allScipts.'</div>';
			}
			if ( $showenqueueStyles === 'on' /*|| $devmode == '0'*/ ) {
				$allStyles = implode(' | ', $wp_styles->queue);
				echo '<div class="ckensct"><b style="color:#78FF79">Style Handle Name(s) </b> '.$allStyles.'</div>';
			}
		}
		
		# Optional Dequeue script and styles
		public function cmk_dequeue_scripts_style() {
			if ( !is_admin() ) {
				$removeenqueueScriptsval = preg_replace('/\s+/', '', self::_meta('remove_scripts') );
				$removeenqueueStylesval = preg_replace('/\s+/', '', self::_meta('remove_styles') );
				
				$removeenqueueScripts = explode('|', $removeenqueueScriptsval);
				$removeenqueueStyles = explode('|', $removeenqueueStylesval);
				
				wp_dequeue_script( $removeenqueueScripts );
				wp_dequeue_style(  $removeenqueueStyles );
				wp_deregister_style(  $removeenqueueStyles );
			}
		}


	//end Dynatheme class
	}
	$cmk = new DynaTheme;
}

if(!class_exists("C")){ class_alias('DynaTheme', 'C'); };
if(!class_exists("CMK")){ class_alias('DynaTheme', 'CMK'); };