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
define( 'DB_NAME', 'if0_38346062_wp50' );

/** Database username */
define( 'DB_USER', '38346062_1' );

/** Database password */
define( 'DB_PASSWORD', '6!.C39Sp4D' );

/** Database hostname */
define( 'DB_HOST', 'sql105.byetcluster.com' );

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
define( 'AUTH_KEY',         'bsph7za6tbxggjyvtbgees1xhc0ydhokabg76aqdxqkkmlgq16klsgkrvcuvztox' );
define( 'SECURE_AUTH_KEY',  'reyt6m3wlsj0mre4kywtpix5oftpewrcmboixbko6gmouurlq3lwnczmgzyv2gbx' );
define( 'LOGGED_IN_KEY',    '5kyetsepufvjfiug5uekg7ytrjodvvtxnrzm10qqwxu37x3lavfla8ji7c6hdegd' );
define( 'NONCE_KEY',        'j5f4lmzy0errfhykvcplfqmazo7vpuewm8khv8v5idh4z8hhflzbkyccdjfupc31' );
define( 'AUTH_SALT',        'y8jiqc2xis58sspsxe6hktngqgz9q5gngqvi3l1xew3rchprhygbm1a1tpmuztu6' );
define( 'SECURE_AUTH_SALT', '46utyihtjmdtbszoqtlettgoicnzu2acmir7i07o2wme54inp5vzsrrnqu51kp4m' );
define( 'LOGGED_IN_SALT',   'wo8xypxa4cao6iwvzlkhryy6920x4l3g6uliy27drneaytrtyahpmkeolibiqdha' );
define( 'NONCE_SALT',       'yeudnubqonw8doaqaveplsl8rm99ediojz3ehxyxacydalv12tmrp69sv2wvplyv' );

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
$table_prefix = 'wpji_';

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
