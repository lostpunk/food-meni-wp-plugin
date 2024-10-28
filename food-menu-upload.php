<?php
/*
Plugin Name: Food Menu Upload
Description: Плагин для управления файлами меню школы через административную панель.
Version: 1.1
Author: webcreate21.ru
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
define('FOOD_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FOOD_MENU_INCLUDES_DIR', FOOD_MENU_PLUGIN_DIR . 'includes/');

require_once FOOD_MENU_INCLUDES_DIR . 'MenuUploader.php';
require_once FOOD_MENU_INCLUDES_DIR . 'BaseMenu.php';
require_once FOOD_MENU_INCLUDES_DIR . 'CalendarMenu.php';
require_once FOOD_MENU_INCLUDES_DIR . 'ResourcesFile.php';
require_once FOOD_MENU_INCLUDES_DIR . 'TypicalMenu.php';
global $food_menu_db_version;
$food_menu_db_version = '1.0';


register_activation_hook(__FILE__, 'food_menu_upload_install');

function food_menu_upload_install()
{
    global $wpdb;
    global $food_menu_db_version;

    $table_name = $wpdb->prefix . 'food_menu_files';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date date DEFAULT NULL,
        year varchar(4) DEFAULT NULL,
        subtype varchar(2) DEFAULT NULL,
        type varchar(50) NOT NULL,
        filename varchar(255) NOT NULL,
        url varchar(255) NOT NULL,
        created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('food_menu_db_version', $food_menu_db_version);
}

register_deactivation_hook(__FILE__, 'food_menu_upload_uninstall');

function food_menu_upload_uninstall()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';

    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}

add_action('admin_menu', 'food_menu_upload_create_menu');

function food_menu_upload_create_menu()
{
    add_menu_page(
        'Food Menu Upload',
        'Школьное меню',
        'manage_options',
        'food-menu-upload',
        'food_menu_upload_admin_page',  // Основная страница плагина
        'dashicons-media-spreadsheet',
        20
    );

    add_submenu_page(
        'food-menu-upload',
        'Календарь питания',
        'Календарь питания',
        'manage_options',
        'food-menu-upload-calendar',
        'food_menu_upload_calendar_page'
    );

    add_submenu_page(
        'food-menu-upload',
        'Типовое меню',
        'Типовое меню',
        'manage_options',
        'food-menu-upload-sample',
        'food_menu_upload_sample_page'
    );

    add_submenu_page(
        'food-menu-upload',
        'Перечень ресурсов',
        'Перечень ресурсов',
        'manage_options',
        'food-menu-upload-resources',
        'food_menu_upload_resources_page'
    );

}

function food_menu_upload_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $baseMenu = new \includes\BaseMenu($table_name, $way, $root_directory);
    if (isset($_GET['success'])) {
        echo '<div class="updated notice"><p>' . $_GET['success'] . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 class="text-primary">Загрузка ежедневного меню</h1>
        <?= $baseMenu->getUploadForm(); ?>
        <h1 class="text-primary">Мониторинг меню на сегодня <?= wp_date("d-m-Y")?></h1>
        <?= $baseMenu->getTodayMenuTable() ?>
        <h1>Последние 30 меню</h1>
        <table class="shortcodes wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Начальная школа <span>[menu_list subtype="sm" limit="30"]</span></th>
                <th>Средняя школа <span> [menu_list subtype="ss" limit="30"]</span></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?= getLimitedMenuList('sm', 30,true) ?></td>
                <td><?= getLimitedMenuList('ss', 30,true) ?></td>
            </tr>
            </tbody>
        </table>
        <h1 class="text-primary">Мониторинг загруженных файлов</h1>
        <?= $baseMenu->getUploadedFiles() ?>
    </div>

    <?php
    if (isset($_POST['upload'])) {
        $baseMenu->upload($_POST, $_FILES);
    }
    if (isset($_POST['delete'])) {
        $baseMenu->deleteFile($_POST['name']);
    }

}

function food_menu_upload_calendar_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $calendarMenu = new \includes\CalendarMenu($table_name, $way, $root_directory);
    if (isset($_GET['success'])) {
        echo '<div class="updated notice"><p>' . $_GET['success'] . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 class="text-primary">Загрузка Календаря питания</h1>
        <?= $calendarMenu->getUploadForm(); ?>
        <h1 class="text-primary">Загруженные календари</h1>
        <?= $calendarMenu->getUploadedFiles() ?>
        <h1>Шорткоды</h1>
        <table class="shortcodes wp-list-table widefat fixed striped">
            <tbody>
            <tr>
                <td><span>Отображения актуального календаря питания</span></td>
                <td><span>[last_calendar_menu]</span></td>
            </tr>
            </tbody>
        </table>

    </div>

    <?php
    if (isset($_POST['upload'])) {
        $calendarMenu->upload($_POST, $_FILES);
    }
    if (isset($_POST['delete'])) {
        $calendarMenu->deleteFile($_POST['name']);
    }
}

function food_menu_upload_resources_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $resourcesFile = new \includes\ResourcesFile($table_name, $way, $root_directory);
    if (isset($_GET['success'])) {
        echo '<div class="updated notice"><p>' . $_GET['success'] . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 class="text-primary">Загрузка перечня ресурсов</h1>
        <?= $resourcesFile->getUploadForm(); ?>
        <h1 class="text-primary">Загруженные файлы</h1>
        <?= $resourcesFile->getUploadedFiles() ?>
        <h1>Шорткоды</h1>
        <table class="shortcodes wp-list-table widefat fixed striped">
            <tbody>
            <tr>
                <td><span> Получить ресурсный файл</span></td>
                <td><span>[findex_file]</span></td>
            </tr>
            </tbody>
        </table>
    </div>

    <?php
    if (isset($_POST['upload'])) {
        $resourcesFile->upload($_POST, $_FILES);
    }
    if (isset($_POST['delete'])) {
        $resourcesFile->deleteFile($_POST['name']);
    }
}

function food_menu_upload_sample_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $typicalMenu = new \includes\TypicalMenu($table_name, $way, $root_directory);
    if (isset($_GET['success'])) {
        echo '<div class="updated notice"><p>' . $_GET['success'] . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 class="text-primary">Загрузка типового меню по годам</h1>
        <?= $typicalMenu->getUploadForm(); ?>
        <h1 class="text-primary">Загруженные файлы</h1>
        <?= $typicalMenu->getTypicalMenuTable() ?>
        <h1>Шорткоды</h1>
        <table class="shortcodes wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Последнее загруженное типовое меню для начальной школы</th>
                <th>Последнее загруженное типовое меню для средней школы</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><span> [last_sample_menu_by_type subtype="sm"]</span></td>
                <td><span> [last_sample_menu_by_type subtype="ss"]</span></td>
            </tr>
            </tbody>
        </table>
    </div>

    <?php
    if (isset($_POST['upload'])) {
        $typicalMenu->upload($_POST, $_FILES);
    }
    if (isset($_POST['delete'])) {
        $typicalMenu->deleteFile($_POST['name']);
    }
}

add_action('admin_enqueue_scripts', 'food_menu_upload_admin_assets');
function food_menu_upload_admin_assets($hook_suffix)
{
    if (strpos($hook_suffix, 'food-menu-upload') !== false) {
        wp_enqueue_style('food-menu-custom', plugins_url('style.css', __FILE__));
        wp_enqueue_script('food-menu-jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', array(), null, true);
        wp_enqueue_script('food-menu-custom', plugins_url('script.js', __FILE__), array('jquery'), null, true);
    }
}

add_shortcode('today_menu_sm', 'render_today_menu_sm');


function render_today_menu_sm()
{
//    [today_menu_sm]
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $baseMenu = new \includes\BaseMenu($table_name, $way, $root_directory);
    $todayMenu = $baseMenu->getTodayMenu('sm');
    if ($todayMenu) {
        return '<a href="' . $todayMenu->url . '">Меню на сегодня(Начальная школа)' . $todayMenu->filename . '</a>';
    }
    return '<span>На сегодня меню не загружено(Начальная школа)</span>';
}

add_shortcode('today_menu_ss', 'render_today_menu_ss');
function render_today_menu_ss()
{
//    [today_menu_ss]
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $baseMenu = new \includes\BaseMenu($table_name, $way, $root_directory);
    $todayMenu = $baseMenu->getTodayMenu('ss');
    if ($todayMenu) {
        return '<p><a href="' . $todayMenu->url . '">Меню на сегодня(Средняя школа) ' . $todayMenu->filename . '</a></p>';
    }
    return '<span>На сегодня меню не загружено(Средняя школа)</span>';
}

add_shortcode('menu_list', 'render_menu_list');
function render_menu_list($atts)
{
//    [menu_list subtype="sm" limit="30"]
    $atts = shortcode_atts(
        array(
            'limit' => '30',  // Значение по умолчанию для type
            'subtype' => 'sm',     // Значение по умолчанию для subtype
        ),
        $atts,
        'menu_list'
    );
    $limit = sanitize_text_field($atts['limit']);
    $subtype = sanitize_text_field($atts['subtype']);
    return getLimitedMenuList($subtype, $limit);
//    global $wpdb;
//    $table_name = $wpdb->prefix . 'food_menu_files';
//    $way = "food";
//    $root_directory = ABSPATH . $way;
//    $baseMenu = new \includes\BaseMenu($table_name, $way, $root_directory);
//    $menuList = $baseMenu->getLimitedMenuList($subtype, $limit);
//    if ($menuList) {
//        $html = '<ul>';
//        $description = $subtype == 'sm' ? ' (Начальная школа)' : ' (Средняя школа)';
//        foreach ($menuList as $menu) {
//            $html .= '<li><a href="' . $menu->url . '">' . $menu->filename . $description . '</a></li>';
//        }
//        $html .= '</ul>';
//        return $html;
//    }
//    return '<div>Меню нет</div>';
}

add_shortcode('last_calendar_menu', 'render_last_calendar_menu');
function render_last_calendar_menu()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $calendarMenu = new \includes\CalendarMenu($table_name, $way, $root_directory);
    $lastMenu = $calendarMenu->getLastMenu();
    if ($lastMenu) {
        return '<p><a href="' . $lastMenu->url . '">' . $lastMenu->filename . ' (Календарь питания)</a></p>';
    }
    return '<span>Календарь питания не загружен</span>';
}

add_shortcode('last_sample_menu_by_type', 'render_sample_menu_by_type');
function render_sample_menu_by_type($atts)
{
//    [last_sample_menu_by_type subtype="sm"]
    $atts = shortcode_atts(
        array(
            'subtype' => 'sm',     // Значение по умолчанию для subtype
        ),
        $atts,
        'last_sample_menu_by_type'
    );
    $subtype = sanitize_text_field($atts['subtype']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $typicalMenu = new \includes\TypicalMenu($table_name, $way, $root_directory);
    $lastTypicalMenu = $typicalMenu->getLastTypicalMenu($subtype);
    $description = $subtype == 'sm' ? ' (Начальная школа)' : ' (Средняя школа)';
    if ($lastTypicalMenu) {
        return '<p><a href="' . $lastTypicalMenu->url . '">' . $lastTypicalMenu->filename . ' Типовое примерное меню приготавливаемых блюд на ' . $lastTypicalMenu->year . ' год' . $description . '</a></p>';
    }
    return '<div>Типовое примерное меню приготавливаемых блюд' . $description . ' не загружено</div>';
}

add_shortcode('findex_file', 'render_findex_file');
function render_findex_file()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $calendarMenu = new \includes\ResourcesFile($table_name, $way, $root_directory);
    $recurseFile = $calendarMenu->getRecurseFile();
    if ($recurseFile) {
        return '<p><a href="' . $recurseFile->url . '">' . $recurseFile->filename . ' (Перечень ресурсов)</a></p>';
    }
    return '<span>Перечень ресурсов не загружен</span>';
}

function getLimitedMenuList($subtype, $limit, $makeRemove = false)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'food_menu_files';
    $way = "food";
    $root_directory = ABSPATH . $way;
    $baseMenu = new \includes\BaseMenu($table_name, $way, $root_directory);
    $menuList = $baseMenu->getLimitedMenuList($subtype, $limit);
    if ($menuList) {
        $html = '<ul>';
        $description = $subtype == 'sm' ? ' (Начальная школа)' : ' (Средняя школа)';
        foreach ($menuList as $menu) {
            $html .= '<li><a href="' . $menu->url . '">' . $menu->filename . $description . '</a>';
            if ($makeRemove) {
                $html .= '<form method="POST" style="display:inline-block; margin-left:20px;">
                         <input type="hidden" name="name" value="' . $menu->filename . '">
                        <button type="submit" name="delete" class="button button-secondary">Удалить</button>
                        </form>';
            }

            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
    return '<div>Меню нет</div>';
}