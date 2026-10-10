<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'habitquest' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'Qwera_13#06' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',         'k+1{vEFi4JYk|[F[R:OioGvq>h}G_su8Fe.O7Qs{?fJNRNtx6q~[fzz.b7que.hs' );
define( 'SECURE_AUTH_KEY',  'YWIJ@EQYn&eg`JB]tM+]0H@CYE+f4g%ejtOvb85d5-.%8xB=&T8pOB.sKyaRWy=:' );
define( 'LOGGED_IN_KEY',    'O2g`/21<%!yYWmtQ:)(ZOn`DHl)imCNrQ*z;QC9ey{l!l2H{6n}Ft:CF99N~6Y;0' );
define( 'NONCE_KEY',        'b6^NCF_l`_&UGokHUA:2K(z@nOIJ1+);_7nt=GG8gc]I7<2=GqV}(rm;9[K-XFD3' );
define( 'AUTH_SALT',        'Ti*ZtkNrxr*8c)T{Br<@Fvk[TpyA1=d}/H1Xo$&#PQpg(w0Df(53$K_d8gRZ-rXQ' );
define( 'SECURE_AUTH_SALT', '*m0k&#=nt5kH)G?n`t0+!JF4XsDl,.q_b*f.v,{+H[|}1(hsxsstd)5%O)XGCDa9' );
define( 'LOGGED_IN_SALT',   'v3vK|c.-d h?EiJ)wfx|Z^BgRZjg4bH53GEe!FHq5k;IVj/5PnKtHy-Sf+}GcF:P' );
define( 'NONCE_SALT',       'i7z.-o&x=m7G0:?YY=r*|9f&?Mfmr u:guGdib^i%&/S96@zsxXGVa7/ [cmq$ {' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
