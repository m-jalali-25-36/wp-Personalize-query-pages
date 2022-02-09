<?php

/**
 * Plugin Name: Personalize query pages
 * Description: A WordPress plugin for customizing page query
 * Version: 1.0
 * Author: m-jalali
 * Author URI: http://www.m-jalali.ir
 */


function pqp_set_template($template)
{
    $pqp_list_page = unserialize(get_option("pqp_list_page", ''));
    if (empty($pqp_list_page))
        $pqp_list_page = array();
    $pageID = -1;
    foreach ($pqp_list_page as $idPage) {
        if (is_page($idPage)) {
            $pageID = $idPage;
            break;
        }
    }
    if ($pageID == -1)
        return $template;

    // $template_path = TEMPLATEPATH . '/' . "templatename.php";
    // if (file_exists($template_path)) {
    return locate_template('index.php');
    // }
}
add_action('template_include', 'pqp_set_template');


function pqp_alter_query($query)
{
    //gets the global query var object
    global $wp_query;
    $pqp_list_page = unserialize(get_option("pqp_list_page", ''));
    if (empty($pqp_list_page))
        $pqp_list_page = array();
    $pageID = -1;
    foreach ($pqp_list_page as $idPage) {
        if (is_page($idPage)) {
            $pageID = $idPage;
            break;
        }
    }
    if ($pageID == -1)
        return;

    if (!$query->is_main_query())
        return;

    $pqp_list_args = unserialize(get_option("pqp_list_args", ''));
    if (empty($pqp_list_args))
        $pqp_list_args = array();

    if (array_key_exists($pageID, $pqp_list_args) && !empty($pqp_list_args[$pageID])) {

        if (!array_key_exists('posts_per_page', $pqp_list_args[$pageID]['args']))
            $pqp_list_args[$pageID]['args']['posts_per_page'] = get_option('posts_per_page');
        if (!array_key_exists('paged', $pqp_list_args[$pageID]['args']))
            $pqp_list_args[$pageID]['args']['paged'] = get_query_var('paged');

        $wp_query = new WP_Query($pqp_list_args[$pageID]['args']);

        if (!empty($pqp_list_args[$pageID]['other'])) {
            foreach ($pqp_list_args[$pageID]['other'] as $pqp_key => $wp_key) {
                switch ($pqp_key) {
                    case 'pqp_is_single':
                        $wp_query->is_home = $wp_key;
                        break;
                    case 'pqp_is_single':
                        $wp_query->is_single = $wp_key;
                        break;
                    case 'pqp_is_page':
                        $wp_query->is_page = $wp_key;
                        break;
                    case 'pqp_is_archive':
                        $wp_query->is_archive = $wp_key;
                        break;
                }
            }
        }
    }

    //we remove the actions hooked on the '__after_loop' (post navigation)
    // remove_all_actions('__after_loop');
}
add_action('pre_get_posts', 'pqp_alter_query');


function pqp_add_menu()
{
    $tt_page = add_menu_page("Personalize query pages", "Personalize query pages", "manage_options", "pqp-panel", "pqp_admin_panel_display", null, 99);
}
add_action("admin_menu", "pqp_add_menu");

function pqp_combination_args($args)
{
    $args_result = array();
    $args_result['args'] = array();
    $args_result['other'] = array();

    $pqp_args_name = array(
        'pqp_post_type' => 'post_type',
        'pqp_posts_per_page' => 'posts_per_page',
        'pqp_order' => 'order',
        'pqp_orderby' => 'orderby',
        'pqp_author_in' => 'author__in',
        'pqp_author_not' => 'author__not_in',
        'pqp_category_in' => 'category__in',
        'pqp_category_not' => 'category__not_in',
        'pqp_tag_in' => 'tag__in',
        'pqp_tag_not' => 'tag__not_in',
        'pqp_is_home' => '',
        'pqp_is_single' => '',
        'pqp_is_page' => '',
        'pqp_is_archive' => '',
        'pqp_args' => 'args'
    );
    $defult_args = array(
        'post_type' =>  'post',
        'posts_per_page' =>  get_option('posts_per_page'),
        'paged' =>  get_query_var('paged'),
        'post_status' =>  'publish',
        'orderby' =>  'date',
        'order' =>  'DESC',
        'author__in' => '',
        'author__not_in' => '',
        'category__in' => '',
        'category__not_in' => '',
        'tag__in' => '',
        'tag__not_in' => '',
        'category_name' =>  '',
        'tag' =>  '',
        'cat' =>  '',
        'tag_id' =>  '',
        'author' =>  '',
        'author_name' =>  ''
    );

    // Combination
    foreach ($args as $pqp_key => $pqp_val) {
        if (array_key_exists($pqp_key, $pqp_args_name) && array_key_exists($pqp_args_name[$pqp_key], $defult_args)) {
            $args_result['args'][$pqp_args_name[$pqp_key]] = $pqp_val;
        } else if ($pqp_key == 'pqp_args' && !empty($pqp_val)) {
            // foreach ($pqp_val as $key => $value) {
            //     if (array_key_exists($key, $defult_args)) {
            //         $args_result['args'][$key] = $value;
            //     }
            // }
        } else if (array_key_exists($pqp_key, $pqp_args_name) && $pqp_args_name[$pqp_key] == '') {
            $args_result['other'][$pqp_key] = $pqp_val;
        }
    }
    return $args_result;
}

function pqp_admin_panel_display()
{
    $action = !empty($_GET) && !empty($_GET['action']) ? $_GET['action'] : "first";
    $page_id = !empty($_GET) && !empty($_GET['page_id']) ? $_GET['page_id'] : -1;

    // pqp_page pqp_post_type pqp_posts_per_page pqp_order pqp_orderby pqp_author_in pqp_author_not
    // pqp_category_in  pqp_category_not pqp_tag_in pqp_tag_not pqp_args
    if (!empty($_POST)) {
        $successful = true;
        $pqp_list_page = unserialize(get_option("pqp_list_page", ''));
        $pqp_list_args = unserialize(get_option("pqp_list_args", ''));
        if (empty($pqp_list_page))
            $pqp_list_page = array();
        if (empty($pqp_list_args))
            $pqp_list_args = array();

        // action Add
        if (!empty($_POST['action']) && $_POST['action'] == "add") {
            if (array_search($_POST['pqp_page'], $pqp_list_page) === false) {
                $pqp_list_page[] = $_POST['pqp_page'];
                $pqp_list_args[$_POST['pqp_page']] = pqp_combination_args($_POST);
                $successful = $successful && update_option("pqp_list_page", serialize($pqp_list_page));
                if ($successful)
                    $successful = $successful && update_option("pqp_list_args", serialize($pqp_list_args));
            } else
                $successful = false;
        }
        // action Edit
        else if (!empty($_POST['action']) && $_POST['action'] == "edit") {
            if (array_search($_POST['pqp_page'], $pqp_list_page) !== false) {
                $pqp_list_args[$_POST['pqp_page']] = pqp_combination_args($_POST);
                $successful = $successful && update_option("pqp_list_args", serialize($pqp_list_args));
            } else
                $successful = false;
        }
        // action remove
        else if (!empty($_POST['action']) && $_POST['action'] == "remove" && !empty($_POST['page_id'])) {
            $index = array_search($_POST['page_id'], $pqp_list_page);
            if ($index !== false) {
                unset($pqp_list_page[$index]);
                unset($pqp_list_args[$_POST['page_id']]);
                $successful = $successful && update_option("pqp_list_page", serialize($pqp_list_page));
                if ($successful)
                    $successful = $successful && update_option("pqp_list_args", serialize($pqp_list_args));
            } else
                $successful = false;
        } else
            $successful = false;

        if ($successful) {
            echo "<div class=\"\">successful</div>";
        } else {
            echo "<div class=\"\">un successful</div>";
        }
    }
?>
    <div class="wrap">
        <?php
        if ($action == 'add')
            pqp_add_page_display();
        else if ($action == 'edit' && $page_id != -1)
            pqp_add_page_display($page_id);
        else if ($action == 'remove' && $page_id != -1)
            pqp_remove_page_display($page_id);
        else
            pqp_first_page_display();
        ?>
    </div>
<?php
}

// function pgp_admin_init()
// {
// }
// add_action("admin_init", "pgp_admin_init");


function pqp_first_page_display()
{
    $pqp_list_page = unserialize(get_option("pqp_list_page", ''));
    $pqp_list_args = unserialize(get_option("pqp_list_args", ''));
    if (empty($pqp_list_args))
        $pqp_list_args = array();
?>
    <style>
        .pqp_ul {
            display: block;
        }

        .pqp_ul li {}

        .pqp_ul li {
            display: inline-block;
            float: left;
        }

        .pqp_ul li:first-child::after {
            content: '';
        }

        .pqp_ul li::after {
            content: ',';
            margin-right: 5px;
            color: #ff0000;
        }
    </style>
    <h1 class="wp-heading-inline">Personalize query pages</h1>
    <div class="row"><a href="admin.php?page=pqp-panel&action=add" class="page-title-action">add</a></div>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column">page</th>
                <th scope="col" class="manage-column">post type</th>
                <th scope="col" class="manage-column">Properties</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pqp_list_page)) {
                foreach ($pqp_list_page as $page) { ?>
                    <tr class="">
                        <td class="">
                            <strong><a class="" href="<?php echo get_permalink($page); ?>" target="_blank" aria-label=""><?php echo get_the_title($page); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=pqp-panel&action=edit&page_id=<?php echo $page; ?>" aria-label="ویرایش">ویرایش</a> | </span>
                                <span class="trash"><a href="admin.php?page=pqp-panel&action=remove&page_id=<?php echo $page; ?>" class="submitdelete" aria-label="حذف">حذف</a> | </span>
                                <span class="view"><a href="<?php echo get_permalink($page); ?>" target="_blank" rel="bookmark" aria-label="نمایش">نمایش</a></span>
                            </div>
                        </td>
                        <td class="">
                            <ul class="pqp_ul">
                                <?php if (!empty($pqp_list_args[$page]['args']['post_type'])) foreach ($pqp_list_args[$page]['args']['post_type'] as $type) { ?>
                                    <li><?php echo $type; ?></li>
                                <?php } ?>
                            </ul>
                        </td>
                        <td class="">
                            <ul class="pqp_ul">
                                <?php if (!empty($pqp_list_args[$page]))
                                    foreach ($pqp_list_args[$page]['other'] as $key => $value) {
                                        if ($key == "pqp_post_type" || $key == "pqp_order" || $key == "pqp_orderby" || $key == "pqp_posts_per_page") continue;
                                ?>
                                    <li><?php echo str_replace('pqp_', '', $key); ?></li>
                                <?php } ?>
                            </ul>
                        </td>
                    </tr>
            <?php }
            } else echo '<tr class=""><td>null</td></tr>'; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column">page</th>
                <th scope="col" class="manage-column">post type</th>
                <th scope="col" class="manage-column">Properties</th>
            </tr>
        </tfoot>

    </table>
<?php
}

function pqp_remove_page_display($id)
{
?>
    <form action="admin.php?page=pqp-panel" method="POST">
        <p>Are you sure you want to delete the <a href="<?php echo get_permalink($id); ?>" target="_blank"><?php echo get_the_title($id); ?></a> page query?</p>
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="page_id" value="<?php echo $id; ?>">
        <input type="submit" value="Remove" class="button button-primary">
        <a class="button button-cancel" href="admin.php?page=pqp-panel&action=first" class="page-title-action">Cancel</a>
    </form>
<?php
}

function pqp_add_page_display($pid = false)
{
    $args = array();
    $other = array();
    if ($pid !== false) {
        $pqp_list_args = unserialize(get_option("pqp_list_args", ''));
        $args = $pqp_list_args[$pid]['args'];
        $other = $pqp_list_args[$pid]['other'];
    }
?>
    <style>
        .pqp_row {
            padding: 20px;
        }

        .pqp_row label {
            display: inline-block;
            width: 20%;
        }

        .pqp_row .pqp_sec {
            display: inline-block;
            width: 70%;
        }

        .pqp_row .pqp_sec input[type=text],
        .pqp_row .pqp_sec input[type=number],
        .pqp_row .pqp_sec select,
        .pqp_row .pqp_sec textarea {
            width: 30%;
        }
    </style>
    <h1 class="wp-heading-inline">Add Personalize query pages</h1>
    <div class="row"><a href="admin.php?page=pqp-panel&action=first" class="page-title-action">back</a></div>
    <form action="admin.php?page=pqp-panel" method="post">
        <div class="pqp_row">
            <label for="pqp_page">Select page</label>
            <div class="pqp_sec">
                <select name="pqp_page" id="">
                    <?php $pages = get_pages();
                    foreach ($pages as $page) { ?>
                        <option value="<?php echo $page->ID ?>" <?php echo ($pid && $pid == $page->ID) ? 'selected' : ''; ?>><?php echo $page->post_title ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_post_type">Select post type</label>
            <div class="pqp_sec">
                <select name="pqp_post_type[]" id="" multiple>
                    <?php $post_types = get_post_types(array(
                        'public'   => true
                        //    '_builtin' => false
                    ));
                    foreach ($post_types as $type) { ?>
                        <option value="<?php echo $type ?>" <?php echo ($pid &&  !empty($args['post_type']) && array_search($type, $args['post_type']) !== false) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                    <?php } ?>
                    <option value="any">any</option>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_posts_per_page">posts per page</label>
            <div class="pqp_sec">
                <input type="number" name="pqp_posts_per_page" min="1" value="<?php echo $pid ? $args['posts_per_page'] : ''; ?>">
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_order">order</label>
            <div class="pqp_sec">
                <select name="pqp_order">
                    <option value="ASC" <?php echo ($pid && $args['order'] == 'ASC') ? 'selected' : ''; ?>>ASC</option>
                    <option value="DESC" <?php echo ($pid && $args['order'] == 'DESC') ? 'selected' : ''; ?>>DESC</option>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_orderby">orderby</label>
            <div class="pqp_sec">
                <select name="pqp_orderby">
                    <option value="none" <?php echo ($pid && $args['orderby'] == 'none') ? 'selected' : ''; ?>>none</option>
                    <option value="ID" <?php echo ($pid && $args['orderby'] == 'ID') ? 'selected' : ''; ?>>ID</option>
                    <option value="author" <?php echo ($pid && $args['orderby'] == 'author') ? 'selected' : ''; ?>>author</option>
                    <option value="title" <?php echo ($pid && $args['orderby'] == 'title') ? 'selected' : ''; ?>>title</option>
                    <option value="name" <?php echo ($pid && $args['orderby'] == 'name') ? 'selected' : ''; ?>>name</option>
                    <option value="type" <?php echo ($pid && $args['orderby'] == 'type') ? 'selected' : ''; ?>>type</option>
                    <option value="date" <?php echo ($pid && $args['orderby'] == 'date') ? 'selected' : ''; ?>>date</option>
                    <option value="modified" <?php echo ($pid && $args['orderby'] == 'modified') ? 'selected' : ''; ?>>modified</option>
                    <option value="parent" <?php echo ($pid && $args['orderby'] == 'parent') ? 'selected' : ''; ?>>parent</option>
                    <option value="rand" <?php echo ($pid && $args['orderby'] == 'rand') ? 'selected' : ''; ?>>rand</option>
                    <option value="comment_count" <?php echo ($pid && $args['orderby'] == 'comment_count') ? 'selected' : ''; ?>>comment_count</option>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_author_in">author</label>
            <div class="pqp_sec">
                <select name="pqp_author_in[]" multiple>
                    <?php $authors = get_users();
                    foreach ($authors as $author) {
                        if ($pid && !empty($args['author__in']) && array_search($author->ID, $args['author__in']) !== false)
                            echo "<option value=\"{$author->ID}\" selected>{$author->display_name}</option>";
                        else
                            echo "<option value=\"{$author->ID}\">{$author->display_name}</option>";
                    } ?>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_author_not">author not in</label>
            <div class="pqp_sec">
                <select name="pqp_author_not[]" multiple>
                    <?php
                    foreach ($authors as $author) {
                        if ($pid && !empty($args['author__not'])  && array_search($author->ID, $args['author__not']) !== false)
                            echo "<option value=\"{$author->ID}\" selected>{$author->display_name}</option>";
                        else
                            echo "<option value=\"{$author->ID}\">{$author->display_name}</option>";
                    } ?>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_category_in">category in</label>
            <div class="pqp_sec">
                <select name="pqp_category_in[]" multiple>
                    <?php $categories = get_categories();
                    foreach ($categories as $category) {
                        if ($pid && !empty($args['category__in'])  && array_search($category->cat_ID, $args['category__in']) !== false)
                            echo "<option value=\"{$category->cat_ID}\" selected>{$category->name}</option>";
                        else
                            echo "<option value=\"{$category->cat_ID}\">{$category->name}</option>";
                    } ?>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_category_not">category not in</label>
            <div class="pqp_sec">
                <select name="pqp_category_not[]" multiple>
                    <?php
                    foreach ($categories as $category) {
                        if ($pid && !empty($args['category__not']) && array_search($category->cat_ID, $args['category__not']) !== false)
                            echo "<option value=\"{$category->cat_ID}\" selected>{$category->name}</option>";
                        else
                            echo "<option value=\"{$category->cat_ID}\">{$category->name}</option>";
                    } ?>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_tag_in">tag in</label>
            <div class="pqp_sec">
                <select name="pqp_tag_in[]" multiple>
                    <?php $tags = get_tags();
                    foreach ($tags as $tag) {
                        if ($pid && !empty($args['tag__in']) && array_search($tag->tag_ID, $args['tag__in']) !== false)
                            echo "<option value=\"{$tag->tag_ID}\" selected>{$tag->name}</option>";
                        else
                            echo "<option value=\"{$tag->tag_ID}\">{$tag->name}</option>";
                    } ?>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_tag_not">tag not in</label>
            <div class="pqp_sec">
                <select name="pqp_tag_not[]" multiple>
                    <?php
                    foreach ($tags as $tag) {
                        if ($pid && !empty($args['tag__not']) && array_search($tag->tag_ID, $args['tag__not']) !== false)
                            echo "<option value=\"{$tag->tag_ID}\" selected>{$tag->name}</option>";
                        else
                            echo "<option value=\"{$tag->tag_ID}\">{$tag->name}</option>";
                    } ?>
                </select>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_is_home">is home</label>
            <div class="pqp_sec">
                <input type="checkbox" name="pqp_is_home" id="pqp_is_home" <?php echo ($pid && $args['is__home'] === 'on') ? 'checked' : ''; ?>>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_is_single">is single</label>
            <div class="pqp_sec">
                <input type="checkbox" name="pqp_is_single" id="pqp_is_single" <?php echo ($pid && $other['pqp_is_single'] === 'on') ? 'checked' : ''; ?>>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_is_page">is page</label>
            <div class="pqp_sec">
                <input type="checkbox" name="pqp_is_page" id="pqp_is_page" <?php echo ($pid && $other['pqp_is_page'] === 'on') ? 'checked' : ''; ?>>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_is_archive">is archive</label>
            <div class="pqp_sec">
                <input type="checkbox" name="pqp_is_archive" id="pqp_is_archive" <?php echo ($pid && $other['pqp_is_archive'] === 'on') ? 'checked' : ''; ?>>
            </div>
        </div>
        <div class="pqp_row">
            <label for="pqp_args">args</label>
            <div class="pqp_sec">
                <textarea name="pqp_args" id="pqp_args" cols="30" rows="10"><?php echo $pid ? $other['pqp_args'] : ''; ?></textarea>
            </div>
        </div>
        <div class="pqp_row">
            <input type="hidden" name="action" value="<?php echo $pid ? 'edit' : 'add'; ?>">
            <input class="button button-primary" type="submit" name="submit" value="<?php echo $pid ? 'Save' : 'Add'; ?>">
            <a class="button button-cancel" href="admin.php?page=pqp-panel&action=first" class="page-title-action">back</a>
        </div>
    </form>
<?php
}
