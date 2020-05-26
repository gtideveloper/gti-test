<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gti-test' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ')[~VHNj/A6Ag1;mwtKlCJO-sSv#tb`WzG1O6#QRmkX07~g,k>53gC^8BLF**Fh6m' );
define( 'SECURE_AUTH_KEY',  'i0kaBfDmk;0S7@cvjO~3m)MWn89=,X5YCh87_e0h{Xl]~JlotB1H}l$|Tle^7Z/J' );
define( 'LOGGED_IN_KEY',    'tm;[3Mr!(A=,K4.{g91@xZRx^D]uAOi0<,E90}~H`*)K:#-{3A@^lftOLU&iz~<R' );
define( 'NONCE_KEY',        '|AnC7WCv$g|EdzPe`X%9*1qU5iV .&gv(7dtFU(ltQMb>qnxBp$qmao#q3+n*f~9' );
define( 'AUTH_SALT',        '9Y~B(_sbH{*!0@UCP/ae;,E([$r^p%GDqSd$OHWvHA((>u1Y25zwc@t@X?nY/1Fu' );
define( 'SECURE_AUTH_SALT', '~$W,cC0j#9%QA%FrZ7#O&n7<;$CH>f[[iQnH{I^#3?Rkbc/V77#m Cen_ C]ohWa' );
define( 'LOGGED_IN_SALT',   '~5~9@gb_YmcWTWv;;U;QC0rkyb-&>+V9JDwDMcaeE14-0L<_|_6:%WX`U%WUW##_' );
define( 'NONCE_SALT',       ':oDw/B!M*CQq:y@?k<zMqQM?Bo3UUn/NbRl{lo0og7%.]Nq$NNA F+EIZNye[hS,' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
