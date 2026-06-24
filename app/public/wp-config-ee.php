<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'miniwebgames' );

/** Database username */
define( 'DB_USER', 'miniwebgames' );

/** Database password */
define( 'DB_PASSWORD', 'miniwebgames@888Harutheme' );

/** Database hostname */
define( 'DB_HOST', 'db' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'R>r?E,>WI){8:*gxvpE@F{PEu{J:pM#4aLGFlDF;exh6vqKQxw<>xUqbbDG6M~z|' );
define( 'SECURE_AUTH_KEY',   '8<wlwyb(F!|>pa8y-=}@H<E<E5D:AYdFH|/BORQ rmu|`Jl{}3Kftrc>4@IEIF@:' );
define( 'LOGGED_IN_KEY',     'N/3En%w5?,gq$B2:gd*lI2HH<3tXujfIapY}PJKb4IG8$j9}Is#/bE&t5e/bXSc3' );
define( 'NONCE_KEY',         'X1G@#vmJP5c|t4g*, F|.J_A*vXR.u{ ]O>XMt&)9vFo-0_bQn/{Zo|#Mx77$ *B' );
define( 'AUTH_SALT',         '$Sp7R.WLqv+h#[5Kr%#`B<E9hYD$*ghEZsqUJnkvBTY$#Lc`=Rj<Av/A968H>;}f' );
define( 'SECURE_AUTH_SALT',  'zkiDx>.vjPz@<NNJmf:)Z+2g9A1W:=]yVeqdex0pK9eP;P{=O66klz}@u+jAR1z&' );
define( 'LOGGED_IN_SALT',    'rMia#+{KHn Eq.o7i<RG($C&Zeml8yY=e9Yq$Y.7K;>qW&xRqJ|T@n4Zz(YLXN^@' );
define( 'NONCE_SALT',        '$~}s CG=S10NUWZqxt6BqjBIpYv#uQTv}.Os^S8yVi[)7n+E-`q29A04/X}QgzB=' );
define( 'WP_CACHE_KEY_SALT', '2*}WbPz-{*g,nLR-`K]0jwPVG:cUX(bz38<zoKdTmT,f 0#Q>!;khV-pkhe({RZ]' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'production' );


if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) {
	$_SERVER['HTTPS'] = 'on';						
}	

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
