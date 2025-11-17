<?php
if ( ! defined( 'ABSPATH' )) exit;


//Get all event options - site_url()/wp-json/core-vue/options-all
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/options-all',array(
        'methods'  => 'GET',
        'callback' => 'all_event_options',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });


//Post all event options CRUD - site_url()/wp-json/core-vue/options
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/options',array(
        'methods'  => 'POST',
        'callback' => 'set_the_options',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });


//Get all event registrations - site_url()/wp-json/core-vue/registrations-all
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/registrations-all',array(
        'methods'  => 'GET',
        'callback' => 'all_event_registrations',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Registrations CRUD - site_url()/wp-json/core-vue/edit-registration
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/edit-registration',array(
        'methods'  => 'POST',
        'callback' => 'edit_registration',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Get all event coupons - site_url()/wp-json/core-vue/coupons-all
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/coupons-all',array(
        'methods'  => 'GET',
        'callback' => 'all_event_coupons',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Get all event coupons - site_url()/wp-json/core-vue/people-and-orgs
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/people-and-orgs',array(
        'methods'  => 'GET',
        'callback' => 'all_people_and_orgs',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Get all event coupons - site_url()/wp-json/core-vue/just-people
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/just-people',array(
        'methods'  => 'GET',
        'callback' => 'just_the_people',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Coupons/Invitations CRUD - site_url()/wp-json/core-vue/edit-coupon-invitation
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/edit-coupon-invitation',array(
        'methods'  => 'POST',
        'callback' => 'edit_coupon_or_invitation',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });


//Save data to Hubspot - site_url()/wp-json/core-vue/hubspot-sync
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/hubspot-sync',array(
        'methods'  => 'POST',
        'callback' => 'hubspot_sync',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Save all data to Hubspot and sync HS with DB - site_url()/wp-json/core-vue/all-sync
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/all-sync',array(
        'methods'  => 'GET',
        'callback' => 'all_sync',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Push speaker coupon codes to HS event_coupon_code field - site_url()/wp-json/core-vue/speaker-codes
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/speaker-codes',array(
        'methods'  => 'GET',
        'callback' => 'do_speaker_codes',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

//Resend welcome email - site_url()/wp-json/core-vue/resend-welcome
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/resend-welcome',array(
        'methods'  => 'POST',
        'callback' => 'resend_welcome_mail',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });


// NEW VERSION 6.
//Print badges - site_url()/wp-json/core-vue/print-badge
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/print-badge',array(
        'methods'  => 'POST',
        'callback' => 'print_array_of_badges',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });


// Add a single registrant to db from the front-desk screen in Mission control: site_url()/wp-json/core-vue/ad-hoc-registration
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/ad-hoc-registration',array(
        'methods'  => 'POST',
        'callback' => 'add_ad_hoc_registration',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });


// Check in a single visitor from front-desk screen in Mission control: site_url()/wp-json/core-vue/do_my_check_in
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/do_my_check_in',array(
        'methods'  => 'POST',
        'callback' => 'do_single_check_in',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });

// Reset all "badge printed" flags to zero: site_url()/wp-json/core-vue/wipe_printed_list
add_action('rest_api_init', function () {
    register_rest_route( 'core-vue', '/wipe_printed_list',array(
        'methods'  => 'GET',
        'callback' => 'wipe_badge_printed_list',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
  });