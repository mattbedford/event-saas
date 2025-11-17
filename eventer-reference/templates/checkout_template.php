<?php
/**
 * Template Name: Checkout template
 */
$errors = [];
$orig = [];

if (isset($_GET['formref'])) {
    $key = sanitize_text_field($_GET['formref']);
    $errors = get_transient($key . '_errors') ?: [];
    $orig = get_transient($key . '_old') ?: [];
    delete_transient($key . '_errors');
    delete_transient($key . '_old');
}

get_header();

if (get_option('ticket_price') === false || get_option('event_name') === false) {
    echo "Sorry! Checkout is not yet available. Please check back soon.";
    exit;
}

echo '<script>var fullTicketPrice = "' . get_option('ticket_price') . '";</script>';
$stripe_init_location = admin_url('admin-post.php');
?>

<h2>Register for <?php echo get_option('event_name'); ?></h2>

<form id="checkout-form" class="checkout-form" action="<?php echo $stripe_init_location; ?>" method="POST">
    <?php if (!empty($errors)): ?>
        <div class="error-console">
            <strong>Please correct the highlighted fields below:</strong>
            <ul>
                <?php foreach ($errors as $msg): ?>
                    <li><?php echo esc_html($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>


    <div class="section-1">
        <input type="hidden" name="source_event" value="<?php echo get_option('event_tag'); ?>">
        <?php wp_nonce_field('eventer_checkout_action', 'eventer_checkout_nonce'); ?>
        <input type="hidden" name="action" value="eventer_checkout">

        <input type="hidden" name="required" value="">
        <input type="text" name="first_name" id="first_name" style="display:block;position:absolute;right:-5000px;height:0;width:0;opacity:0.1;">

        <div class="form-section personal-details">
            <h3>Personal details</h3>
            <span class="notes">* Required</span>
            <div class="form-flexer">
                <div class="input">
                    <label for="title">Title (Optional)</label>
                    <input type="text" name="title" id="title" placeholder="Mr/Ms/Other" value="<?php echo $orig['title'] ?? ''; ?>">
                </div>
                <div class="input">
                    <label for="fname">First name <span class="req">*</span></label>
                    <input type="text" name="fname" id="fname" required="" value="<?php echo $orig['fname'] ?? ''; ?>">
                </div>

                <div class="input">
                    <label for="lname">Last name <span class="req">*</span></label>
                    <input type="text" name="lname" id="lname" required="" value="<?php echo $orig['lname'] ?? ''; ?>">
                </div>
            </div>

            <div class="input">
                <label for="country">Country <span class="req">*</span></label>
                <select name="country" id="country" class="<?php if(isset($errors['country'])) echo 'error';?>" required>
                    <option value="" selected="selected">Please select</option>
                    <option value="Switzerland">Switzerland</option>
                    <option value="Afghanistan">Afghanistan</option>
                    <option value="Albania">Albania</option>
                    <option value="Algeria">Algeria</option>
                    <option value="Andorra">Andorra</option>
                    <option value="Angola">Angola</option>
                    <option value="Anguilla">Anguilla</option>
                    <option value="Antarctica">Antarctica</option>
                    <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                    <option value="Argentina">Argentina</option>
                    <option value="Armenia">Armenia</option>
                    <option value="Aruba">Aruba</option>
                    <option value="Australia">Australia</option>
                    <option value="Austria">Austria</option>
                    <option value="Azerbaijan">Azerbaijan</option>
                    <option value="Bahamas">Bahamas</option>
                    <option value="Bahrain">Bahrain</option>
                    <option value="Bangladesh">Bangladesh</option>
                    <option value="Barbados">Barbados</option>
                    <option value="Belarus">Belarus</option>
                    <option value="Belgium">Belgium</option>
                    <option value="Belize">Belize</option>
                    <option value="Benin">Benin</option>
                    <option value="Bermuda">Bermuda</option>
                    <option value="Bhutan">Bhutan</option>
                    <option value="Bolivia">Bolivia</option>
                    <option value="Bonaire">Bonaire</option>
                    <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                    <option value="Botswana">Botswana</option>
                    <option value="Brazil">Brazil</option>
                    <option value="Brunei Darussalam">Brunei Darussalam</option>
                    <option value="Bulgaria">Bulgaria</option>
                    <option value="Burkina Faso">Faso</option>
                    <option value="Burundi">Burundi</option>
                    <option value="Côte d'Ivoire">Ivoire</option>
                    <option value="Cambodia">Cambodia</option>
                    <option value="Cameroon">Cameroon</option>
                    <option value="Canada">Canada</option>
                    <option value="Cape Verde">Cape Verde</option>
                    <option value="Cayman Islands">Cayman Islands</option>
                    <option value="Central African Republic">Central African Republic</option>
                    <option value="Chad">Chad</option>
                    <option value="Chile">Chile</option>
                    <option value="China">China</option>
                    <option value="Colombia">Colombia</option>
                    <option value="Comoros">Comoros</option>
                    <option value="Congo">Congo</option>
                    <option value="Congo">Congo</option>
                    <option value="Costa Rica">Costa Rica</option>
                    <option value="Croatia">Croatia</option>
                    <option value="Cuba">Cuba</option>
                    <option value="Cyprus">Cyprus</option>
                    <option value="Czech Republic">Czech Republic</option>
                    <option value="Denmark">Denmark</option>
                    <option value="Djibouti">Djibouti</option>
                    <option value="Dominica">Dominica</option>
                    <option value="Dominican Republic">Dominican Republic</option>
                    <option value="Ecuador">Ecuador</option>
                    <option value="Egypt">Egypt</option>
                    <option value="El Salvador">Salvador</option>
                    <option value="Equatorial Guinea">Guinea</option>
                    <option value="Eritrea">Eritrea</option>
                    <option value="Estonia">Estonia</option>
                    <option value="Ethiopia">Ethiopia</option>
                    <option value="Falkland Islands">Falkland Islands</option>
                    <option value="Faroe Islands">Islands</option>
                    <option value="Fiji">Fiji</option>
                    <option value="Finland">Finland</option>
                    <option value="France">France</option>
                    <option value="French Guiana">Guiana</option>
                    <option value="Gabon">Gabon</option>
                    <option value="Gambia">Gambia</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Germany">Germany</option>
                    <option value="Ghana">Ghana</option>
                    <option value="Gibraltar">Gibraltar</option>
                    <option value="Greece">Greece</option>
                    <option value="Greenland">Greenland</option>
                    <option value="Grenada">Grenada</option>
                    <option value="Guadeloupe">Guadeloupe</option>
                    <option value="Guam">Guam</option>
                    <option value="Guatemala">Guatemala</option>
                    <option value="Guernsey">Guernsey</option>
                    <option value="Guinea">Guinea</option>
                    <option value="Bissau">Bissau</option>
                    <option value="Guyana">Guyana</option>
                    <option value="Haiti">Haiti</option>
                    <option value="Honduras">Honduras</option>
                    <option value="Hong Kong">Kong</option>
                    <option value="Hungary">Hungary</option>
                    <option value="Iceland">Iceland</option>
                    <option value="India">India</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="Iran">Iran</option>
                    <option value="Iraq">Iraq</option>
                    <option value="Ireland">Ireland</option>
                    <option value="Israel">Israel</option>
                    <option value="Italy">Italy</option>
                    <option value="Jamaica">Jamaica</option>
                    <option value="Japan">Japan</option>
                    <option value="Jersey">Jersey</option>
                    <option value="Jordan">Jordan</option>
                    <option value="Kazakhstan">Kazakhstan</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Kiribati">Kiribati</option>
                    <option value="Korea">Korea</option>
                    <option value="Kuwait">Kuwait</option>
                    <option value="Kyrgyzstan">Kyrgyzstan</option>
                    <option value="Laos">Laos</option>
                    <option value="Latvia">Latvia</option>
                    <option value="Lebanon">Lebanon</option>
                    <option value="Lesotho">Lesotho</option>
                    <option value="Liberia">Liberia</option>
                    <option value="Libya">Libya</option>
                    <option value="Liechtenstein">Liechtenstein</option>
                    <option value="Lithuania">Lithuania</option>
                    <option value="Luxembourg">Luxembourg</option>
                    <option value="Macao">Macao</option>
                    <option value="Macedonia">Macedonia</option>
                    <option value="Madagascar">Madagascar</option>
                    <option value="Malawi">Malawi</option>
                    <option value="Malaysia">Malaysia</option>
                    <option value="Maldives">Maldives</option>
                    <option value="Mali">Mali</option>
                    <option value="Malta">Malta</option>
                    <option value="Martinique">Martinique</option>
                    <option value="Mauritania">Mauritania</option>
                    <option value="Mauritius">Mauritius</option>
                    <option value="Mayotte">Mayotte</option>
                    <option value="Mexico">Mexico</option>
                    <option value="Micronesia">Micronesia</option>
                    <option value="Moldova">Moldova</option>
                    <option value="Monaco">Monaco</option>
                    <option value="Mongolia">Mongolia</option>
                    <option value="Montenegro">Montenegro</option>
                    <option value="Montserrat">Montserrat</option>
                    <option value="Morocco">Morocco</option>
                    <option value="Mozambique">Mozambique</option>
                    <option value="Myanmar">Myanmar</option>
                    <option value="Namibia">Namibia</option>
                    <option value="Nauru">Nauru</option>
                    <option value="Nepal">Nepal</option>
                    <option value="Netherlands">Netherlands</option>
                    <option value="New Caledonia">New Caledonia</option>
                    <option value="New Zealand">New Zealand</option>
                    <option value="Nicaragua">Nicaragua</option>
                    <option value="Niger">Niger</option>
                    <option value="Nigeria">Nigeria</option>
                    <option value="Niue">Niue</option>
                    <option value="Norway">Norway</option>
                    <option value="Oman">Oman</option>
                    <option value="Pakistan">Pakistan</option>
                    <option value="Palau">Palau</option>
                    <option value="Palestinian Territory">Palestinian Territory</option>
                    <option value="Panama">Panama</option>
                    <option value="Papua New Guinea">Guinea</option>
                    <option value="Paraguay">Paraguay</option>
                    <option value="Peru">Peru</option>
                    <option value="Philippines">Philippines</option>
                    <option value="Pitcairn Islands">Islands</option>
                    <option value="Poland">Poland</option>
                    <option value="Portugal">Portugal</option>
                    <option value="Puerto Rico">Puerto Rico</option>
                    <option value="Qatar">Qatar</option>
                    <option value="Reunion">Reunion</option>
                    <option value="Romania">Romania</option>
                    <option value="Russian Federation">Russian Federation</option>
                    <option value="Rwanda">Rwanda</option>
                    <option value="Samoa">Samoa</option>
                    <option value="San Marino">San Marino</option>
                    <option value="Sao Tome and Principe">Principe</option>
                    <option value="Saudi Arabia">Arabia</option>
                    <option value="Senegal">Senegal</option>
                    <option value="Serbia">Serbia</option>
                    <option value="Seychelles">Seychelles</option>
                    <option value="Sierra Leone">Sierra Leone</option>
                    <option value="Singapore">Singapore</option>
                    <option value="Slovakia">Slovakia</option>
                    <option value="Slovenia">Slovenia</option>
                    <option value="Somalia">Somalia</option>
                    <option value="South Africa">South Africa</option>
                    <option value="South Sudan">Sudan</option>
                    <option value="Spain">Spain</option>
                    <option value="Sri Lanka">Sri Lanka</option>
                    <option value="Sudan">Sudan</option>
                    <option value="Suriname">Suriname</option>
                    <option value="Swaziland">Swaziland</option>
                    <option value="Sweden">Sweden</option>
                    <option value="Taiwan">Taiwan</option>
                    <option value="Tajikistan">Tajikistan</option>
                    <option value="Tanzania">Tanzania</option>
                    <option value="Thailand">Thailand</option>
                    <option value="Togo">Togo</option>
                    <option value="Tokelau">Tokelau</option>
                    <option value="Tonga">Tonga</option>
                    <option value="Trinidad and Tobago">Tobago</option>
                    <option value="Tunisia">Tunisia</option>
                    <option value="Turkey">Turkey</option>
                    <option value="Turkmenistan">Turkmenistan</option>
                    <option value="Tuvalu">Tuvalu</option>
                    <option value="Uganda">Uganda</option>
                    <option value="Ukraine">Ukraine</option>
                    <option value="UAE">UAE</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="United States">United States</option>
                    <option value="Uruguay">Uruguay</option>
                    <option value="Uzbekistan">Uzbekistan</option>
                    <option value="Vanuatu">Vanuatu</option>
                    <option value="Venezuela">Venezuela</option>
                    <option value="Viet Nam">Vietnam</option>
                    <option value="Yemen">Yemen</option>
                    <option value="Zambia">Zambia</option>
                    <option value="Zimbabwe">Zimbabwe</option>
                </select>
            </div>

            <div class="form-flexer">
                <div class="input">
                    <label for="role">Job title <span class="req">*</span></label>
                    <input type="text" name="role" id="role" required="" value="<?php echo $orig['role'] ?? ''; ?>">
                </div>
                <div class="input">
                    <label for="email">Email<span class="req">*</span></label>
                    <input type="email" name="email" id="email" required="" value="<?php echo $orig['email'] ?? ''; ?>">
                </div>

            </div>

            <div class="form-flexer">
                <div class="input">
                    <label for="Mobile">Mobile phone (optional)</label>
                    <input type="tel" name="mobile" id="mobile" placeholder="+41" value="<?php echo $orig['mobile'] ?? ''; ?>">
                </div>
                <div class="input">
                    <label for="office">Office phone <span class="req">*</span></label>
                    <input type="tel" name="office" id="office" required="" value="<?php echo $orig['office'] ?? ''; ?>">
                </div>
            </div>
        </div>

        <div class="form-section interests" style="display:none;">
            <h3>Interests</h3>
            <label for="tags" style="padding-bottom:5px;">(Optional) What are your or of your company 3 priorities in terms of innovation for the next 12 months? Use the <span class="plus">+</span> icon to add your own</label>
            <!---<textarea name="tags" id="tags" required=""></textarea>--->
            <select name="tags[]" id="tags" multiple="multiple">
                <option data-placeholder="true"></option>
                <option value="AI">AI</option>
                <option value="sustainability">sustainability</option>
                <option value="e-commerce platform">e-commerce platforms</option>
                <option value="3D prototyping">3D prototyping</option>
                <option value="investments">Investments</option>
            </select>
        </div>

        <div class="form-section company-details">
            <h3>Company details</h3>
            <span class="notes">* required</span>
            <div class="form-flexer">
                <div class="input">
                    <label for="company">Company name <span class="req">*</span></label>
                    <input type="text" name="company" id="company" required="" value="<?php echo $orig['company'] ?? ''; ?>">
                </div>
                <div class="input">
                    <label for="website">Website url (including 'https://') <span class="req">*</span></label>
                    <input type="url" name="website" id="website" placeholder="https://yourwebsite.com" required="" value="<?php echo $orig['website'] ?? ''; ?>">
                </div>
            </div>

            <div class="input" style="display:none;">
                <label for="my_company_is">Tell us what kind of company it is <span class="req">*</span></label>
                <select name="my_company_is" id="my_company_is">
                    <option disabled="" value="">What kind of company is yours?</option>
                    <option value="Brand, Retailer, Manufacturer or Online Shop">Brand, Retailer, Manufacturer or Online Shop</option>
                    <option value="Investor, Family Office,">Investor, Family Office, Business Angel</option>
                    <option value="Media / Press">Media / Press / Journalism</option>
                    <option value="Public Administration / Institution">Public Administration / Institution</option>
                    <option value="Research Institute, University, School">Research Institute, University, School</option>
                    <option value="Vendor / Supplier of Services">Vendor / Supplier of Services for Innovation and e-Commerce</option>
                    <option value="Other" selected="selected">Other</option>
                </select>
            </div>

            <div class="input">
                <label for="address">Street address <span class="req">*</span></label>
                <input type="text" name="address" id="address" required="" value="<?php echo $orig['address'] ?? ''; ?>">
            </div>

            <div class="form-flexer">
                <div class="input">
                    <label for="postcode">Postcode <span class="req">*</span></label>
                    <input type="text" name="postcode" id="postcode" required="" value="<?php echo $orig['postcode'] ?? ''; ?>">
                </div>

                <div class="input">
                    <label for="city">City <span class="req">*</span></label>
                    <input type="text" name="city" id="city" required="" value="<?php echo $orig['city'] ?? ''; ?>">
                </div>
            </div>
        </div>

        <div class="form-section t-and-c">
            <h3>Terms & conditions</h3>
            <span class="notes">* Required</span>
            <p>Dagorà Lifestyle Innovation Hub along with its strategic partners Netcomm Suisse , Loomish and the LifeStyle-Tech Competence Center are committed to protecting and respecting your privacy, and we’ll only use your personal information to administer your account and to provide the products and services you requested from us. From time to time, we would like to contact you about our products and services, as well as other content that may be of interest to you. </p>
            <p>By submitting this form, you also agree to have your contact information, including email, passed on to the sponsors of this event for the purpose of following up on your interests.</p>
            <div class="mktbox">
                <input type="checkbox" name="mkt" id=mkt required>
                <label for="mkt"><span class="req">*</span> I consent to my data being used for marketing communication and updates on initiatives</label>
            </div>
            <p>You can unsubscribe from these communications at any time. For more information on how to unsubscribe, our privacy practices, and how we are committed to protecting and respecting your privacy, please review our <a target="_blank" href="https://dagora.ch/data-protection-policy/">Privacy Policy</a>. By clicking submit below, you consent to allow Dagorà and its strategic partners Netcomm Suisse and Loomish SA to store and process the personal information submitted above to provide you the content requested.
            </p>
        </div>
    </div>
    <div class="section-2">
        <div class="float-box">
            <h4>1 x admittance to <?php echo get_option('event_name') ?> <span>
            <?php echo get_option('venue_name') . " - " .get_option('venue_city') . ", " . get_option('venue_country') . "on " .
                fix_the_date(get_option('event_date')) . " @ " . get_option('event_start') . "AM"; ?></span></h4>
            <h3 id="price-to-pay"><?php echo get_option('ticket_price') . " CHF"; ?></h3>
            <img class="payment-methods" src="<?php echo site_url(); ?>/wp-content/plugins/eventer/assets/payment-methods.png"><br>

            <label for="coupon">Do you have a coupon code?</label>
            <div class="coupon-holder">
                <input class="coupon-box" type="text" name="coupon" placeholder="Coupon code"><span id="apply-me">Apply coupon</span>
            </div>
            <span id="coupon-message"></span>

            <input type="submit" id="form-submit" value="Register now">
        </div>
    </div>
</form>

<script>
    window.addEventListener("load", function () {
        const errConsole = document.querySelector(".error-console");
        if (errConsole) {
            errConsole.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    });
</script>

<?php get_footer(); ?>

