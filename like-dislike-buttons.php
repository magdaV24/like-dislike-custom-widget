<?php

/**
 * Plugin Name: Like/Dislike System
 * Description: A custom plugin that allows the user to like or dislike a comment;
 * Author: Magda Vasilache
 * Author URI: -
 * Version: 1.0.0
 * Text Domain: like-dislike-custom-widget
 */

if (!defined('ABSPATH')) {
    exit;
}

function like_dislike_buttons_scripts()
{
    wp_enqueue_script('like-dislike-jquery', "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js", array(), '3.7.1', true);
    wp_enqueue_script('like-dislike-buttons-script', plugin_dir_url(__FILE__) . '/js/like-dislike-buttons.js', array('jquery'), '1.0', true);
    wp_enqueue_style('like-dislike-buttons-style', plugin_dir_url(__FILE__) . '/css/like-dislike-buttons.css');
}
add_action('wp_enqueue_scripts', 'like_dislike_buttons_scripts');

function register_like_dislike_buttons_widget()
{
    register_widget('Like_Dislike_Custom_Widget');
}
add_action('widgets_init', 'register_like_dislike_buttons_widget');


class Like_Dislike_Custom_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'recent_comments',
            'Like_Dislike_Custom_Widget',
            array('description' => 'Allows the logged in user to like or dislike comments.')
        );
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        $comment_id = get_comment_ID();
        $likes_count = get_comment_likes_count($comment_id);
        $dislikes_count = get_comment_dislikes_count($comment_id);
        $user_id = get_current_user_id();
        $status = get_comment_status($comment_id, $user_id);
?>
        <div class="like-dislike-widget-wrapper">
            <div class="likes-wrapper">
                <div class="like-dislike-button" id="like-button" data-comment-id="<?php echo $comment_id; ?>">
                <?php
                    if ($status[0] === false) {
                    ?>
                        <div>
                            <i class="fa-regular fa-thumbs-up"></i>
                        </div>
                    <?php }
                    if ($status[0] === true) { ?>
                        <div>
                            <i class="fa-solid fa-thumbs-up"></i>
                        </div>
                    <?php } ?>
                </div>
                <div class="counts"><?php echo $likes_count; ?></div>
            </div>
            <div class="dislikes-wrapper">
                <div class="like-dislike-button" id="dislike-button" data-comment-id="<?php echo $comment_id; ?>">
                    <?php
                    if ($status[1] === false) {
                    ?>
                        <div>
                            <i class="fa-regular fa-thumbs-down"></i>
                        </div>
                    <?php }
                    if ($status[1] === true) { ?>
                        <div>
                            <i class="fa-solid fa-thumbs-down"></i>
                        </div>
                    <?php } ?>
                </div>
                <div class="counts"><?php echo $dislikes_count; ?></div>

            </div>
        </div>
<?php

        echo $args['after_widget'];
    }
}

// Handles the liking of a comment 
function handle_like()
{
    if (isset($_POST['comment_id'])) {
        $comment_id = $_POST['comment_id'];
        $user_id = get_current_user_id();

        $result = update_comment_interaction($user_id, $comment_id, 'like');

        echo json_encode(array('success' => true, 'result' => $result));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Invalid request'));
    }

    wp_die();
}

// Handles the disliking of a comment 

function handle_dislike()
{
    if (isset($_POST['comment_id'])) {
        $comment_id = $_POST['comment_id'];
        $user_id = get_current_user_id();

        $result = update_comment_interaction($user_id, $comment_id, 'dislike');

        echo json_encode(array('success' => true, 'result' => $result));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Invalid request'));
    }

    wp_die();
}

add_action('wp_ajax_handle_like', 'handle_like');

add_action('wp_ajax_handle_dislike', 'handle_dislike');


// Function that handles the way the interaction with a comment will be saved in the database
function update_comment_interaction($user_id, $comment_id, $interaction_type)
{
    $status = get_comment_status($comment_id, $user_id);

    switch ($interaction_type) {
        case 'like':
            if (!$status[0]) {
                update_comment_meta($comment_id, 'user_liked_' . $user_id, $user_id);
            }
            if ($status[1]) {
                delete_comment_meta($comment_id, 'user_disliked_' . $user_id);
            }
            if ($status[0]) {
                delete_comment_meta($comment_id, 'user_liked_' . $user_id, $user_id);
            }
            break;
        case 'dislike':
            if (!$status[1]) {
                update_comment_meta($comment_id, 'user_disliked_' . $user_id, $user_id);
            }
            if ($status[0]) {
                delete_comment_meta($comment_id, 'user_liked_' . $user_id);
            }
            if ($status[1]) {
                delete_comment_meta($comment_id, 'user_disliked_' . $user_id, $user_id);
            }
            break;
    }

    return true;
}

// Two function that count the number of likes and dislikes a comment has accumulated
function get_comment_likes_count($comment_id)
{
    $comment_meta = get_comment_meta($comment_id);

    $likes_count = 0;

    foreach ($comment_meta as $key => $value) {
        if (strpos($key, 'user_liked_') === 0) {
            $likes_count++;
        }
    }

    return $likes_count;
}

function get_comment_dislikes_count($comment_id)
{
    $comment_meta = get_comment_meta($comment_id);

    $dislikes_count = 0;

    foreach ($comment_meta as $key => $value) {
        if (strpos($key, 'user_disliked_') === 0) {
            $dislikes_count++;
        }
    }

    return $dislikes_count;
}

// Custom  function that check whether or not the comment has been liked and disliked

function get_comment_status($comment_id, $user_id)
{

    $comment_meta = get_comment_meta($comment_id);
    $user_liked = 'user_liked_' . $user_id;
    $user_disliked = 'user_disliked_' . $user_id;

    $status_liked = isset($comment_meta[$user_liked]);
    $status_disliked = isset($comment_meta[$user_disliked]);

    return array(
        $status_liked, $status_disliked
    );
}
