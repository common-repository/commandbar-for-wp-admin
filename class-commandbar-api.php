<?php

class CommandBar_API
{
    public function __construct()
    {
        add_action('rest_api_init', function () {
            register_rest_route('commandbar/v1', '/posts/(?P<query>.*)', array(
                'methods' => 'GET',
                'callback' => [$this, 'query_posts'],
                'permission_callback' => [$this, 'enable_query_posts']
            ));
        });
    }

    function filter_posts($where)
    {
        global $wpdb;
        return $where . " AND " . $wpdb->posts . '.post_type NOT IN ("page") ';
    }

    function serialize_post($post)
    {
        global $wp_post_types;
        $is_admin = current_user_can('editor') && COMMANDBAR_ENABLE_FOR_ADMIN_USERS;

        return commandbar_serialize_post($post, '<span>' . $wp_post_types[$post->post_type]->labels->singular_name . '</span>', $is_admin);
    }

    function enable_query_posts() {
        $user = wp_get_current_user();
        $allowed_admin_roles = ['administrator', 'editor', 'author', 'contributor'];

        if(COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS) {
            return true;
        } else if(COMMANDBAR_ENABLE_FOR_ADMIN_USERS) {
            return !!array_intersect($allowed_admin_roles, $user->roles );
        }

        return false;
    }

    function query_posts($data)
    {
        $s = urldecode($data['query']);

        $post_types = NULL;
        $post_status = 'any';
        $editable_post_types = [];

        $is_admin = current_user_can('edit_posts') && COMMANDBAR_ENABLE_FOR_ADMIN_USERS;

        if ($is_admin) {
            global $wp_post_types;

            foreach ($wp_post_types as $post_type => $descriptor) {
                if (current_user_can($descriptor->cap->edit_posts)) {
                    array_push($editable_post_types, $post_type);
                }
            }

            $post_types = $editable_post_types;
        } else {
            $post_types = 'any';
            $post_status = NULL;
        }

        if (!$is_admin) {
            // if regular user, individual commands are added for the pages, so we want to filter them out
            add_filter('posts_where',  [$this, 'filter_posts']);
        }
        
        $query1 = new WP_Query([
            'post_type' => $post_types,
            'perm' => 'readable',
            'posts_per_page' => 20,
            'post_status'    => $post_status,
            'meta_query' => array(
                array(
                    'value' => $s,
                    'compare' => 'LIKE'
                )
            )
        ]);

        $query2 = new WP_Query([
            'post_type' => $post_types,
            'perm' => 'readable',
            'posts_per_page' => 20,
            'post_status'    => $post_status,
            'orderby' => 'relevance',
            's' => $s
        ]);
        
        if (!$is_admin) {
            remove_filter('posts_where',  [$this, 'filter_posts']);
        }

        $posts_by_id = [];

        $posts = [];

        foreach ($query2->posts as $post) {
            if (array_key_exists($post->ID, $posts_by_id)) continue;

            $posts_by_id[$post->ID] = TRUE;
            array_push($posts, $post);
        }

        foreach ($query1->posts as $post) {
            if (array_key_exists($post->ID, $posts_by_id)) continue;

            $posts_by_id[$post->ID] = TRUE;
            array_push($posts, $post);
        }


        $filtered_posts = array_map([$this, "serialize_post"], $posts);
        return $filtered_posts;
    }
}

new CommandBar_API();
