const rootUrl = document.location.origin;
const config = {
  rootUrl,
  optionsRoute: `${rootUrl}/wp-json/core-vue/options`,
  AllOptionsRoute: `${rootUrl}/wp-json/core-vue/options-all`,
  allRegistrations: `${rootUrl}/wp-json/core-vue/registrations-all`,
  editRegistrations: `${rootUrl}/wp-json/core-vue/edit-registration`,
  allCoupons: `${rootUrl}/wp-json/core-vue/coupons-all`,
  peopleAndOrgs: `${rootUrl}/wp-json/core-vue/people-and-orgs`,
  onlyPeople: `${rootUrl}/wp-json/core-vue/just-people`,
  editCouponInvitation: `${rootUrl}/wp-json/core-vue/edit-coupon-invitation`,
  hubspotSync: `${rootUrl}/wp-json/core-vue/hubspot-sync`,
  allSync: `${rootUrl}/wp-json/core-vue/all-sync`,
  speakerCodes: `${rootUrl}/wp-json/core-vue/speaker-codes`,
  resendWelcome: `${rootUrl}/wp-json/core-vue/resend-welcome`,
  editBadges: `${rootUrl}/wp-json/core-vue/edit-badges`,
  printBadge: `${rootUrl}/wp-json/core-vue/print-badge`,
  adHocRegistration: `${rootUrl}/wp-json/core-vue/ad-hoc-registration`,
  resetBadges: `${rootUrl}/wp-json/core-vue/wipe_printed_list`,
};
export default config;
