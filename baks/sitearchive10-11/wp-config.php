<?php

# Database Configuration
define( 'DB_NAME', 'wp_boardgamesdev' );
define( 'DB_USER', 'boardgamesdev' );
define( 'DB_PASSWORD', 'jU0x8qJk35Ag7RXgAXmV' );
define( 'DB_HOST', '127.0.0.1' );
define( 'DB_HOST_SLAVE', '127.0.0.1' );
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', 'utf8_unicode_ci');
$table_prefix = 'quqql19koe_';

# Security Salts, Keys, Etc
define('AUTH_KEY',         'x*JlL$.gF9fcFj=)cDDc*Wp~|mLLD/Lp.r$K%+J25A}Zfjxo|k_N256A{</~`ebo');
define('SECURE_AUTH_KEY',  'pZVhlar*Gl8nP3CLd@JlKrw_S7-aQ}gy`O_p+NKHiP&3]#<LCe(&lANjvD>#gT7w');
define('LOGGED_IN_KEY',    'JZu@K_{r$;Rt#E%E%j$1AcwCw<}hur[cFUICZm?^fOaE2kC4NmXVf*anG6L:*g1E');
define('NONCE_KEY',        '5`h,-`ifctKu$p~UvObQ@SuNYLy6f6AA4gk)lpC5O/FWsx]_Tr?1_I//pCJQDXCK');
define('AUTH_SALT',        'RUj#zhEb!*M$?CeG%j{p(8-k,}wS|X6#FnI$+4kZJ$}U]3RNtBu]YY7z{-D%w1[R');
define('SECURE_AUTH_SALT', '(}I[:!G8/lWWio*E+,$@og)>CNg8>$#EC)X(P&FFme}H)mk|`yj$14+Qoe>;n}33');
define('LOGGED_IN_SALT',   'G0Kqre gxvG oxb>pV&iTI8]~Hn`N_(VwM>,JTAqPYL!%R0eEd/T-M~:#!r4OG3}');
define('NONCE_SALT',       'H&[9bDT(Q.4Q_u=gx @h}1|*B7+1p-%A1I:3ta %c?fd5<Uz]LU+R5-*7~N)8v~W');


# Localized Language Stuff

define( 'WP_CACHE', TRUE );

define( 'WP_AUTO_UPDATE_CORE', false );

define( 'PWP_NAME', 'boardgamesdev' );

define( 'FS_METHOD', 'direct' );

define( 'FS_CHMOD_DIR', 0775 );

define( 'FS_CHMOD_FILE', 0664 );

define( 'PWP_ROOT_DIR', '/nas/wp' );

define( 'WPE_APIKEY', '141d30c51ab044d1632636299c9cb2531ab84b42' );

define( 'WPE_FOOTER_HTML', "" );

define( 'WPE_CLUSTER_ID', '43663' );

define( 'WPE_CLUSTER_TYPE', 'pod' );

define( 'WPE_ISP', true );

define( 'WPE_BPOD', false );

define( 'WPE_RO_FILESYSTEM', false );

define( 'WPE_LARGEFS_BUCKET', 'largefs.wpengine' );

define( 'WPE_SFTP_PORT', 2222 );

define( 'WPE_LBMASTER_IP', '' );

define( 'WPE_CDN_DISABLE_ALLOWED', false );

define( 'DISALLOW_FILE_MODS', FALSE );

define( 'DISALLOW_FILE_EDIT', FALSE );

define( 'DISABLE_WP_CRON', false );

define( 'WPE_FORCE_SSL_LOGIN', true );

define( 'FORCE_SSL_LOGIN', true );

/*SSLSTART*/ if ( isset($_SERVER['HTTP_X_WPE_SSL']) && $_SERVER['HTTP_X_WPE_SSL'] ) $_SERVER['HTTPS'] = 'on'; /*SSLEND*/

define( 'WPE_EXTERNAL_URL', false );

define( 'WP_POST_REVISIONS', FALSE );

define( 'WPE_WHITELABEL', 'wpengine' );

define( 'WP_TURN_OFF_ADMIN_BAR', false );

define( 'WPE_BETA_TESTER', false );

umask(0002);

$wpe_cdn_uris=array ( );

$wpe_no_cdn_uris=array ( );

$wpe_content_regexs=array ( );

$wpe_all_domains=array ( 0 => 'boardgames.com', 1 => 'boardgamesdev.wpengine.com', 2 => 'www.boardgames.com', );

$wpe_varnish_servers=array ( 0 => 'pod-43663', );

$wpe_special_ips=array ( 0 => '66.228.38.142', );

$wpe_ec_servers=array ( );

$wpe_largefs=array ( );

$wpe_netdna_domains=array ( 2 =>  array ( 'match' => 'boardgames.com', 'secure' => false, 'dns_check' => '0', 'zone' => '33v80n27p6vtd1rnksp2a579', ), );

$wpe_netdna_domains_secure=array ( );

$wpe_netdna_push_domains=array ( );

$wpe_domain_mappings=array ( );

$memcached_servers=array ( 'default' =>  array ( 0 => 'unix:///tmp/memcached.sock', ), );
define('WPLANG','');

# WP Engine ID


# WP Engine Settings

define( 'WP_DEBUG', false );
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);




# That's It. Pencils down
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');
require_once(ABSPATH . 'wp-settings.php');

$_wpe_preamble_path = null; if(false){}
