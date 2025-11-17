<!DOCTYPE html>
<!--[if IE 6]>
<html id="ie6" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
    <script src="https://cdn-eu.pagesense.io/js/classroomsecrets/94f7c59710f8473d8f95ae94c6f7de11.js"></script>
	<meta name="p:domain_verify" content="a67cc0a8a3d34c2ea226d18fc820f223"/>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<title><?php elegant_titles(); ?></title>
	<?php elegant_description(); ?>
	<?php elegant_keywords(); ?>
	<?php elegant_canonical(); ?>

	<?php do_action( 'et_head_meta' ); ?>
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

	<?php $template_directory_uri = get_template_directory_uri(); ?>
	<!--[if lt IE 9]>
		<script src="<?php echo esc_url( $template_directory_uri . '/js/html5.js"' ); ?>" type="text/javascript"></script>
	<![endif]-->
    

	<link rel="stylesheet" href="https://use.typekit.net/abw0uyt.css">
	<script type="text/javascript">
		document.documentElement.className = 'js';
	</script>
    <script type="text/javascript" src="//code.jquery.com/jquery-1.8.3.js"></script>
    <script type="text/javascript">
                    $(window).load(function(){

                    $('.openabout').click(function() { 
                    $('#aboutwrap').slideDown();
                    $('.closeabout').show();
                    $('.openabout').hide();

                });


                $('.closeabout').click(function() {
                    $('#aboutwrap').slideUp();
                    $('.openabout').show();
                    $('.closeabout').hide();
                });
                    });
                </script>
	<!-- Google Tag Manager 
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-T95SGXC');</script>-->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-5HXPZ42');</script>
	<!-- End Google Tag Manager -->
	<link rel="stylesheet" href="//cdn.jsdelivr.net/gh/dmhendricks/bootstrap-grid-css@4.1.3/dist/css/bootstrap-grid.min.css" />
	<script src="https://kit.fontawesome.com/b6332aff87.js" crossorigin="anonymous"></script>

	<?php wp_head(); ?>
    <link rel="stylesheet" type="text/css" href="https://classroomsecrets.co.uk/wp-content/themes/Classroomsecrets/Foxy%20child/style.css?ver=<?php echo rand(1111,9999)?>">

    <style>
        /*#mega-menu-item-38900::before, #mega-menu-item-33498::before, #mega-menu-item-80612::before {*/
        /*    display:block !important;*/
        /*    position:relative;*/
        /*    content:'New!';*/
        /*    top:5px;*/
        /*    left:50%;*/
        /*    width:50px;*/
        /*    text-align:center;*/
        /*    transform:translateX(-50%);*/
        /*    background:#6fc36c;*/
        /*    color:white;*/
        /*    padding:0 5px;*/
        /*    z-index:5*/
        /*}*/

        @media screen and (max-width:767px) {
            #mega-menu-item-38900::before, #mega-menu-item-33498::before, #mega-menu-item-80612::before {
                margin-bottom: -20.85px;
            }
        }

        #mega-menu-item-80612 > a{
            background-color: #00b2ce !important;
            color: #fff!important;
        }

        #mega-menu-wrap-primary-menu #mega-menu-primary-menu > li.mega-menu-item {
            vertical-align: bottom !important;
        }
    </style>
</head>
<body <?php body_class(); ?>>
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1679326078971730'); // Insert your pixel ID here.
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1679326078971730&ev=PageView&noscript=1"
/></noscript>
<!-- DO NOT MODIFY -->
<!-- End Facebook Pixel Code -->
<!-- Google Tag Manager (noscript) 
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T95SGXC"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
 End Google Tag Manager (noscript) -->
<?php if(is_page('membership') || is_page(get_option('alt_success_page')) || is_page(get_option('alt_fail_page')) || is_page(get_option('renewals_info_page'))) {
 	echo '<div class="page-fix">';
 }
?>

	<div id="body-area">
		<div class="container">
			<header id="main-header" class="clearfix">
			<div class="topBar section">
				<div class="row">
				<div class="headerSection">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/CS-Logo.png"/></a>
				</div>
				<div class="headerMobileNav">
					<?php wp_nav_menu( array( 'theme_location' => 'mobile-menu' ) ); ?>
				</div>
				<div class="headerSection">
					<div class="headerSecondNav">
						<ul>
							<li><a href="/about/">About</a></li>
							<li><a href="/membership/">Membership</a></li>
							<li><a href="/category/news-and-blog/">Blog</a></li>
							<li><a href="/contact-us/">Contact</a></li>
							<li><a class="podcastHeader" href="/the-teachers-podcast/">Podcast</a></li>
							<?php //<li><a class="foundationHeader" href="/foundation/">Foundation</a></li>?>
							<li><a class="kidsHeader" href="https://kids.classroomsecrets.co.uk/">KIDS</a></li>
							<?php //<li><a class="eatHeader" href="/home-learning-centre/">Home Learning</a></li>?>
						</ul>
					</div>
				</div>
<?php
				    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
				        $link = "https";
				    else
				        $link = "http";
				    
				    // Here append the common URL characters. 
				    $link .= "://";
				    
				    // Append the host(domain name, ip) to the URL. 
				    $link .= $_SERVER['HTTP_HOST'];
				    
				    // Append the requested resource location to the URL 
				    $link .= $_SERVER['REQUEST_URI'];
				    
				    ?>
				    <div class="topNavArea">
				        <ul class="topNavTop">
						   <?php 
						   

						   global $current_user;
						   wp_get_current_user();
													
						   if (is_user_logged_in()) {
								if(current_user_can('s2member_level2')){
                                   echo '<li><a>School admin</a></li>';
                                } elseif(current_user_can('s2member_level1')){
									echo '<li><a>Premium member</a></li>';
								} elseif(current_user_can('edit_posts')){
									echo '<li><a>Staff user</a></li>';
								} else {
									echo '<li><a href="/membership/">Upgrade today</a></li>';
								}
						   }

						   if(!is_user_logged_in()) {
								echo '<li><a href="/membership/">Sign up</a></li>';
						   }
						   						   
				           if (is_user_logged_in()) {
								echo '<div class="profileDropdown">';
									echo '<li class=""><i style="font-size:32px; color:#00B2CE;" class="fa fa-user-circle"></i></li>';
										echo '<div class="profileDropdown-content"><ul>';
											echo '<li class="username"><a href="/my-profile/">'.$current_user->display_name.'</a></li>';
											echo '<li class="managePortfolio"><a href="/my-profile/">Manage Account</a></li>';
											echo '<li class="logout"><a href=' . wp_logout_url($link) . '>Log Out</a></li>';
										echo '</ul></div>';
								echo '</div>';
				            } else {
                                echo '<li class="loginBtn"><a href="/login/" title="Login">Log In</a></li>';
				            }
 					
				           ?>
				        </ul>
				    </div>
			</div>
			</div>
			<div class="section navigationSection">
                <div class="row" style="padding-top: 10px">
                    <ul class="topNavBottom">
                        <li><a href="/?s=&fwp_subscription_level=d08afb2965eb4489c73b980f38af50b1">Free resources</a></li>
                        <li><a href="<?php echo get_site_url() ?>/?s=">New resources</a></li>
                        <li class="searchTop">
                            <form role="search" method="get" class="search-form" action="<?php echo home_url( '/' ); ?>">
                                <label>
                                    <input type="search" class="search-field"
                                           placeholder="<?php echo esc_attr_x( 'Search 8000+ Resources …', 'placeholder' ) ?>"
                                           value="<?php echo get_search_query() ?>" name="s"
                                           title="<?php echo esc_attr_x( 'Search for:', 'label' ) ?>" />
                                    <input type="submit" class="search-submit"
                                           value="<?php echo esc_attr_x( '', 'submit button' ) ?>" />
                                </label>
                            </form>
                        </li>
                <li class="nav-filter-button"><a href="<?php echo get_site_url() ?>/?s="><i class="fa fa-filter"></i> Filter resources</a></li>
                        <!---<li class="nav-filter-button xmas-button" style="width:auto;position:relative;">
						<svg class="santa-hat" id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 297.17 205.47"><defs><style>.cls-1{fill:#fff}.cls-2{fill:#e6e6e6}</style></defs><circle class="cls-1" cx="269.1" cy="168.61" r="28.06"/><path class="cls-2" d="M291.85 152.18a37.17 37.17 0 0 1-42.74 36.11 28.06 28.06 0 0 0 42.74-36.11Z"/><path d="M10.19 160.57C10.19 128.77 14.34 0 101.34 0 166.16 0 219.7 31.92 247.2 82.64c22.66 41.81 21.9 71.68 21.9 71.68l-12.1 6.25s-30.35-49.39-68.84-63.33c0 0 8.49 45.24 4.34 63.33s-182.31 0-182.31 0Z" style="fill:#f76756"/><rect class="cls-1" y="160.57" width="202.67" height="44.9" rx="10.19"/><path class="cls-2" d="M192.48 190.79H10.19A10.19 10.19 0 0 1 0 180.6v14.6a10.19 10.19 0 0 0 10.19 10.19h182.29a10.19 10.19 0 0 0 10.19-10.19v-14.6a10.19 10.19 0 0 1-10.19 10.19Z"/><path d="M188.14 97.24c-23.35-8.68-60.91-13.39-60.91-13.39s57.17 52 65.25 76.72c0 0 4.52-16.27-4.34-63.33Z" style="fill:#e75143"/></svg>
						<a href="/category/christmas-resources/">Christmas resources</a></li>--->
                    </ul>
                </div>

				<div class="row">
				<nav id="top-navigation">
				<?php
					$menuClass = 'nav';
					if ( 'on' == et_get_option( 'foxy_disable_toptier' ) ) $menuClass .= ' et_disable_top_tier';
					$primaryNav = '';
					if ( function_exists( 'wp_nav_menu' ) ) {
						$primaryNav = wp_nav_menu( array( 'theme_location' => 'primary-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'echo' => false ) );
					}
					if ( '' == $primaryNav ) { ?>
					<ul class="<?php echo esc_attr( $menuClass ); ?>">
						<?php if ( 'on' == et_get_option( 'foxy_home_link' ) ) { ?>
							<li <?php if ( is_home() ) echo( 'class="current_page_item"' ); ?>><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home','Foxy' ); ?></a></li>
						<?php }; ?>

						<?php show_page_menu( $menuClass, false, false ); ?>
						<?php show_categories_menu( $menuClass, false ); ?>
					</ul>
					<?php }
					else echo( $primaryNav );
				?>
				</nav>
			</div>

	    </div>
		
		<!---COVID SUPPORT BLOCK
		<div class="covid-banner-full-width"><span class="covid-icon"><svg class="svg-icon" style="width:1em;height:1em;vertical-align:middle;fill:currentColor;overflow:hidden" viewBox="0 0 1024 1024" xmlns="https://www.w3.org/2000/svg"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"/><path d="M464 688a48 48 0 1 0 96 0 48 48 0 1 0-96 0ZM488 576h48c4.4 0 8-3.6 8-8V296c0-4.4-3.6-8-8-8h-48c-4.4 0-8 3.6-8 8v272c0 4.4 3.6 8 8 8z"/></svg></span>School affected by COVID? We have all the tools you need to continue your kids' <a class="covid-banner-link" href="https://classroomsecrets.co.uk/home-learning-centre/">learning from home</a>.<span class="close-covid-banner"><svg class="svg-icon" style="width:1em;height:1em;vertical-align:middle;fill:currentColor;overflow:hidden" viewBox="0 0 1024 1024" xmlns="https://www.w3.org/2000/svg"><path d="M685.4 354.8c0-4.4-3.6-8-8-8l-66 .3L512 465.6l-99.3-118.4-66.1-.3c-4.4 0-8 3.5-8 8 0 1.9.7 3.7 1.9 5.2l130.1 155L340.5 670c-1.2 1.5-1.9 3.3-1.9 5.2 0 4.4 3.6 8 8 8l66.1-.3L512 564.4l99.3 118.4 66 .3c4.4 0 8-3.5 8-8 0-1.9-.7-3.7-1.9-5.2L553.5 515l130.1-155c1.2-1.4 1.8-3.3 1.8-5.2z"/><path d="M512 65C264.6 65 64 265.6 64 513s200.6 448 448 448 448-200.6 448-448S759.4 65 512 65zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"/></svg></span></div>
		<style>
			.covid-banner-full-width {
				display:none;
				padding:5px;
				text-align:center;
				color:white;
				background:#ffbb00;
				letter-spacing:0.3px;
				position:relative;
    			z-index:5;
			}
			.covid-banner-full-width a {
				color:#454545;
			}	
			
			.covid-icon  {
				font-size:25px;
				margin-right:10px;
			}
			.close-covid-banner {
				font-size:25px;
				cursor:pointer;
				margin-left:50px;
				color:#454545;
			}
			
			@media screen and (max-width:767px) {
			   .covid-banner-full-width {
				   position:relative;
			   padding:10px 50px !important;
			   text-align:left !important;
			   line-height:20px;
			   }
			   .covid-icon {
				   position:absolute;
				   left:10px;
			   }
			   .close-covid-banner {
				   position:absolute;
				   right:10px;
				   bottom:10px;
				   margin:0 !important;
			   }
			}
		</style>--->
		<script>
		/*
		window.onload = function(){ 
			//Check to see if covidSupport cookie exists or not at page load. If exists, no need to show banner
			if(!document.cookie.match(/^(.*;)?\s*covidSupport\s*=\s*[^;]+(.*)?$/)){
				let covidBanner = document.querySelector(".covid-banner-full-width");
				covidBanner.style.display = "block";
			} 
		
		
		//Add a new cookie if sm clicks close on cookie banner.
			let closeCovidBanner = document.querySelector(".close-covid-banner");
			closeCovidBanner.addEventListener("click", function(){
				const d = new Date();
				d.setTime(d.getTime() + (2 * 24 * 60 * 60 * 1000));
				let expires = "expires="+d.toUTCString();
				document.cookie = "covidSupport=true;" + expires + "path=/";
				let XcovidBanner = document.querySelector(".covid-banner-full-width");
				XcovidBanner.style.display = "none";
			});
			let clickCovidBanner = document.querySelector(".covid-banner-link");
			clickCovidBanner.addEventListener("click", function(){
				const d = new Date();
				d.setTime(d.getTime() + (2 * 24 * 60 * 60 * 1000));
				let expires = "expires="+d.toUTCString();
				document.cookie = "covidSupport=true;" + expires + "path=/";
			});
		};
		
		*/
		</script>
		<!---END COVID SUPPORT BLOCK--->
			</header> <!-- #main-header -->
			
			
			<!--- Internet Explorer warning --->
			<div id="internet-explorer-warning-closed" style="display:none;"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i><p>Internet Explorer is out of date!</p><span id="ie-warn-open">&rsaquo;</span></div>
			
			<div id="internet-explorer-warning" style="display:none;">
			<div class="ie-warning-inner">
			<i class="fa fa-exclamation-triangle" aria-hidden="true"></i><h3>Internet Explorer is out of date!</h3>
			<p>For greater security and performance, please consider updating to one of the following free browsers</p>
			
			<a href="https://www.google.com/chrome/" target="_blank"><div class="browser-choice chrome-icon"><i class="fa fa-chrome" aria-hidden="true"></i>Get Chrome &rsaquo;</div></a>
			<a href="https://www.microsoft.com/en-us/edge" target="_blank"><div class="browser-choice edge-icon"><i class="fa fa-edge" aria-hidden="true"></i>Get Edge &rsaquo;</div></a>
			<a href="https://www.mozilla.org/en-US/firefox/new/" target="_blank"><div class="browser-choice firefox-icon"><i class="fa fa-firefox" aria-hidden="true"></i>Get Firefox &rsaquo;</div></a>
			<span id="ie-warn-close">x</span>
			</div>
			</div>
			
			<script>
			//Script to add warning for internet explorer users
			$( document ).ready(function() {
			//Testing for IE
				var ua = window.navigator.userAgent;
				var isIE = /MSIE|Trident/.test(ua);
				if ( isIE ) {
						$(".home #internet-explorer-warning").slideDown();
						$("#internet-explorer-warning-closed").slideDown("slow");
						$("#wpfront-notification-bar").css('margin-top', '72px');

            			window.onclick = function(event) {
               				let ieWarning = document.getElementById("internet-explorer-warning");
                			let ieCloseButton = document.getElementById("ie-warn-close");

                			if (event.target == ieWarning || event.target == ieCloseButton) {
                    		ieWarning.style.display = "none";
                		}
  			  		};
					$("#ie-warn-open").click(function() {
					$("#internet-explorer-warning").slideDown();
					});
				}	
 			});		
			</script>
			
			
			<?php
			//<div style="background-color:#f76756;color:#fff;text-align:center;padding:10px 20px;">
			//<p style="margin-bottom:0px;padding-bottom:0px;">Our website is super busy at the moment - you may have noticed it running a bit slow. We will be making some changes this evening to speed things up.<br>Thanks for your patience.</p>
			//</div>
			?>