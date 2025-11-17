jQuery(document).ready(function () {
	const form = document.querySelector(".checkout-form");

	form.addEventListener("submit", function (event) {
		// Let browser handle native validation first
		if (!form.checkValidity()) {
			return; // browser will show validation messages
		}

		// Run custom email validation (must match certain criteria)
		const emailOk = runOwnEmailCheck();
		if (!emailOk) {
			const mailField = document.querySelector("#email");
			mailField.setCustomValidity(
				"Please provide your own business email."
			);
			mailField.reportValidity();
			setTimeout(() => mailField.setCustomValidity(""), 6000);
			event.preventDefault(); // stop form if email fails
			return;
		}
	});

	// Custom business email check
	function runOwnEmailCheck() {
		const email = jQuery("#email").val().toLowerCase().trim();


		const bannedUsernames = [
			"info", "admin", "hello", "contact", "support", "sales", "mail", "team", "noreply", "office"
		];

		const [localPart, domain] = email.split("@");

		if (!localPart || !domain) return false;

		// Block if the *entire* local part is a banned role
		if (bannedUsernames.includes(localPart)) return false;

		return true;
	}

	// Coupon input listener
	jQuery(document).on("input", ".coupon-box", function () {
		const val = jQuery(this).val();
		jQuery("#apply-me").toggleClass("active", val.length > 4);
	});

	// Coupon apply button logic
	jQuery("#apply-me").click(function () {
		const code = jQuery(".coupon-box").val();
		if (code.length >= 5) checkThisCoupon(code);
	});

	function checkThisCoupon(code) {
		const data = [
			{ name: "action", value: "check_submitted_coupon" },
			{ name: "sureandsecret", value: user_ajax_nonce },
			{ name: "submitted_coupon", value: code }
		];

		jQuery.ajax({
			url: user_admin_url,
			type: "post",
			data: data,
			success: function (response) {
				const result = JSON.parse(response);
				let priceText = "CHF " + fullTicketPrice;
				let message = "Sorry. This coupon does not seem to be valid. Please check.";

				switch (result) {
					case "zerotopay":
						priceText = "CHF 0.00";
						message = "Discount code applied.";
						break;
					case "couponlimit":
						message = "Sorry. The maximum uses for this coupon has been reached.";
						break;
					case "badcoupon":
					case "couponnotexist":
						break;
					default:
						priceText = "CHF " + result;
						message = "Discount code applied.";
				}

				jQuery("#price-to-pay").html(priceText);
				jQuery("#coupon-message").html(message).slideDown();
			}
		});
	}

	// Optional: dismiss error UI
	const errorCloseBtn = document.getElementById("error-close");
	if (errorCloseBtn) {
		errorCloseBtn.addEventListener("click", () => {
			document.querySelector(".error-console").style.display = "none";
		});
	}

	// Clean invalid field styles on input
	jQuery("input, select, textarea").on("input change", function () {
		jQuery(this).removeClass("invalid");
	});
});
