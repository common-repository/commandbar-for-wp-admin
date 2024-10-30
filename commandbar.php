<?php
/** 
 * CommandBar integration for WordPress 
 * 
 * @package CommandBar 
 * @author Jared Luxenberg
 * @copyright 2022 Foobar, Inc.
 * @license BSD-3-Clause
 * 
 * @wordpress-plugin 
 * Plugin Name: CommandBar for WP Admin
 * Plugin URI: https://www.commandbar.com/wordpress
 * Description: CommandBar gives your users onboarding nudges, quick actions, relevant support content, and powerful search, in one ‍personalized, blazingly fast widget.
 * Version: 1.0.7
 * Author: Jared Luxenberg 
 * Author URI: https://www.commandbar.com/
 * License: BSD-3-Clause 
 * License URI: https://www.gnu.org/licenses/license-list.html#ModifiedBSD 
 **/

define('COMMANDBAR_PLUGIN_VERSION', '1.0.6');

require_once dirname(__FILE__) . '/commandbar-config.php';
require_once dirname(__FILE__) . '/commandbar-util.php';
require_once dirname(__FILE__) . '/class-commandbar-boot.php';
require_once dirname(__FILE__) . '/class-commandbar-settings.php';
require_once dirname(__FILE__) . '/class-commandbar-api.php';
require_once dirname(__FILE__) . '/class-commandbar-scripts.php';
require_once dirname(__FILE__) . '/class-commandbar-activation.php';
require_once dirname(__FILE__) . '/class-commandbar-override-search.php';
require_once dirname(__FILE__) . '/class-commandbar-context.php';
require_once dirname(__FILE__) . '/class-commandbar-post-customization.php';
require_once dirname(__FILE__) . '/gutenberg-block/class-commandbar-custom-gutenberg-block.php';
