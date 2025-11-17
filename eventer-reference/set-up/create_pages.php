<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function run_page_creation() {
   $required_pages = [
        ["Checkout", "checkout"], // Title, slug
        ["Success", "success"],
        ["Get Badge", "get-badge"],
        ["Speaker registration", "speaker-registration"],
        ["Partner registration", "partner-registration"]
   ];

   foreach ($required_pages as $page) {
        if(post_exists($page[0]) !== 0) continue;
        create_new_page($page[0], $page[1]);
   }
   
}


function create_new_page($title, $slug) {
    // Setup custom vars
    $post_id = -1;
    $author_id = get_current_user_id();

    $args = ['post_title' => $title];

    $args = array(
            'comment_status'        => 'closed',
            'ping_status'           => 'closed',
            'post_author'           => $author_id,
            'post_name'             => $slug,
            'post_title'            => $title,
            'post_status'           => 'publish',
            'post_type'             => 'page'
    );

    $post_id = wp_insert_post( $args );
}

