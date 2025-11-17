<?php

/**
 * Template Name: Get badge page template
 */

get_header(); ?>
<style>
    #page {
		max-width:unset;
		width:100vw;
		margin:0;
	}
	.site-content {
		display:flex;
		min-height:90vh;
		align-items:center;
		justify-content:center;
		background: rgb(0,132,203);
		background: linear-gradient(149deg, rgba(0,132,203,1) 19%, rgba(127,196,28,1) 57%);
	}
    .message-area {
        background:white;
        padding:40px;
        min-width:60%;
        max-width:90%;
        border-radius:3px;
    }
        .btn {
        background:var(--accent);
        color:white;
        text-decoration:none;
        padding:10px 22px;
        cursor:pointer;
    }
</style>

<?php

$output = array();
$token = htmlspecialchars($_GET['token']);
$email = htmlspecialchars($_GET['email']);

function attempt_to_generate_badge_for_user($id) {
   
    require_once plugin_dir_path( __DIR__ ) . 'BadgeBuilder.php';

    $badge = new BadgeBuilder($id, true);

    if(!empty($badge->errors)) {
        return array("Error", "Please try again later. If the problem persists, contact us using the button below.</p>", "<a href='mailto:info@dagora.ch'>info@dagora.ch</a>");
    }

    $badge_link = " <a class='btn' href='$badge->badge_output' target='_blank' download>Print your badge</a>"; // Single url of file

    return array("Success!", "Your badge was successfully generated. Download it using the button below.", $badge_link);

}

if(!isset($_GET['email']) || empty($_GET['email'])) {
    $output[0] = "<h3>Sorry, we couldn't find your email address in our records.</h3>";
    $output[1] = "<p>Please try again or contact us using the button below.</p>";
    $output[2] = "<a class='btn' href='mailto:info@dagora.ch'>info@dagora.ch</a>";
}

if(!isset($_GET['token']) || empty($_GET['token'])) {
    $output[0] = "<h3>Sorry, we couldn't find your email address in our records.</h3>";
    $output[1] = "<p>Please try again or contact us using the button below.</p>";
    $output[2] = "<a class='btn' href='mailto:info@dagora.ch'>info@dagora.ch</a>";
}


global $wpdb;
$row = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}registrations WHERE email = '%s'", $email) );
$db_token = $row[0]->hubspot_id;

if(empty($db_token) || $token !== $db_token) {
    $output[0] = "<h3>Sorry, we couldn't find your email address in our records.</h3>";
    $output[1] = "<p>Please try again or contact us using the button below.</p>";
    $output[2] = "<a class='btn' class='btn' href='mailto:info@dagora.ch'>info@dagora.ch</a>";
}


if($token === $db_token) {
    $output = attempt_to_generate_badge_for_user($row[0]->id);

} else {
    $output[0] = "<h3>Sorry. Something went wrong.</h3>";
    $output[1] = "<p>Please try again or contact us using the button below.</p>";
    $output[2] = "<a class='btn' class='btn' href='mailto:info@dagora.ch'>info@dagora.ch</a>";

}

echo "<div class='message-area'>";
echo "<div class='message'>";

echo "<h2>" . $output[0] . "</h2>";
echo "<p>" . $output[1] . "</p>";
echo "<p>" . $output[2] . "</p>";

echo "</div>";
echo "</div>";


get_footer();