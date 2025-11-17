<template>
  <div class="sub-section" style="flex-direction:column;">
    <message-announce v-if="announce" :message="announce" @closeMessage="killMessage()" />
    <vue-good-table
            :columns="columns"
            :rows="couponsList"
            theme="black-rhino"
            :search-options="{
              enabled: true
            }"
            :pagination-options="{
                enabled: true,
                perPage: 20,
                position: 'bottom',
            }">
            <div slot="table-actions">
              <button class="create-new-coupon" @click="startNewCoupon">
                <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm80 224h-64v64a16 16 0 0 1-32 0v-64h-64a16 16 0 0 1 0-32h64v-64a16 16 0 0 1 32 0v64h64a16 16 0 0 1 0 32z"/></svg>
              </button>
            </div>
            <template slot="table-row" slot-scope="props">
                <span v-if="props.column.field == 'edit'">
                    <button @click="editCoupon(props.row.originalIndex)">Edit</button>
                </span>
                <span v-else-if="props.column.field == 'titleLink'">
                    <a :href="props.row.permalink" target="_blank" class="coupon-link">
                      {{props.row.coupon_title}}
                    </a> &#10140;
                </span>
            </template>
        </vue-good-table>
        <div class="export-section" style="margin-top:100px !important;">
          <h3>
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M336 176h40a40 40 0 0 1 40 40v208a40 40 0 0 1-40 40H136a40 40 0 0 1-40-40V216a40 40 0 0 1 40-40h40"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m176 272 80 80 80-80M256 48v288"/></svg>
            Export coupons</h3>
          <a
            class='exporter-butt'
            href='/wp-content/plugins/eventer/export_coupons.php'
            target='_blank'
            >
            Export coupons
          </a>
        </div>
        <div class="edit-shelf" v-if="(couponToEdit !== null)" v-scroll-lock="couponToEdit">
            <div class="content-wrap">
                <span class="options-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M459.94 53.25a16.06 16.06 0 0 0-23.22-.56L424.35 65a8 8 0 0 0 0 11.31l11.34 11.32a8 8 0 0 0 11.34 0l12.06-12c6.1-6.09 6.67-16.01.85-22.38zM399.34 90 218.82 270.2a9 9 0 0 0-2.31 3.93L208.16 299a3.91 3.91 0 0 0 4.86 4.86l24.85-8.35a9 9 0 0 0 3.93-2.31L422 112.66a9 9 0 0 0 0-12.66l-9.95-10a9 9 0 0 0-12.71 0z"/><path d="M386.34 193.66 264.45 315.79A41.08 41.08 0 0 1 247.58 326l-25.9 8.67a35.92 35.92 0 0 1-44.33-44.33l8.67-25.9a41.08 41.08 0 0 1 10.19-16.87l122.13-121.91a8 8 0 0 0-5.65-13.66H104a56 56 0 0 0-56 56v240a56 56 0 0 0 56 56h240a56 56 0 0 0 56-56V199.31a8 8 0 0 0-13.66-5.65z"/></svg>
                    <h2>Edit coupon</h2>
                </span>

                <button class="close-button" @click="(couponToEdit = null)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M320 320 192 192m0 128 128-128"/></svg>
                </button>
                <form id="edit-registrant">
                    <h2>Coupon details</h2>
                    <input type="hidden" v-model="couponToEdit.invitation_post_id">
                    <div class="double">
                        <label for="code">Coupon code
                        <input type="text" id="code" v-model="couponToEdit.coupon_title"/></label>

                        <label for="lname">Discount
                        <input type="text" id="lname" v-model="couponToEdit.discount"
                          placeholder="e.g. 75, 50, 100"/></label>
                    </div>
                    <div class="double">
                        <label for="max-uses">Max uses allowed
                            <input type="number" id="max-uses"
                            v-model="couponToEdit.max_uses"/>
                        </label>
                        <label for="actual-uses">Actual uses
                            <input type="number" id="actual-uses"
                            v-model="couponToEdit.actual_uses" disabled/>
                        </label>
                    </div>

                    <h2>Invitation details</h2>
                    <div class="checkbox-wrap">
                      <input type="checkbox" @change="changeGuestStatus($event)"
                      v-model="couponToEdit.guest_status" />
                      <p>Is this for guests?</p>
                    </div>

                    <label for="related-post">Recipient
                        <select name="related-post" v-model="couponToEdit.recipient_id">
                            <option value="other">Other</option>
                            <option v-for="(item, index) in usersList"
                            :value="item.id"
                            :key="index">
                            {{item.name}}
                            </option>
                        </select>
                    </label>
                    <label for="other-recipient" v-show="couponToEdit.recipient_id === 'other'">
                            Add new recipient if not in dropdown list
                            <input type="text"
                                id="other-recipient"
                                v-model="couponToEdit.recipient_name"/>
                    </label>

                    <label for="coupon-type">Coupon type
                        <select id="coupon-type" v-model="couponToEdit.invitation_type">
                            <option value="generic">Generic coupon</option>
                            <option value="Netcomm member">Netcomm member</option>
                            <option value="Dagorà member">Dagorà member</option>
                            <option value="LTCC member">LTCC member</option>
                            <option value="Brand-retailer-manufacturer">
                                Brand/Retailer/Manufacturer</option>
                            <option value="Staff">Staff</option>
                            <option value="Speaker">Speaker</option>
                            <option value="Sponsor">Sponsor</option>
                            <option value="Prospect">Prospect</option>
                            <option value="Institution">Institution</option>
                            <option value="School-university">School/University</option>
                        </select>
                        </label>

                        <label for="related-post">Invitation layout
                        <select id="coupon-type" v-model="couponToEdit.with_headliners">
                            <option value="standard">Standard</option>
                            <option value="custom">Custom</option>
                        </select></label>

                        <div style="padding-bottom:40px;">
                            <span v-show="couponToEdit.with_headliners == 'custom'">
                                Choose who appears on the invitation
                            </span>
                            <v-select multiple
                                id="custom-headliners"
                                name="custom-headliners"
                                v-show="couponToEdit.with_headliners == 'custom'"
                                v-model="couponToEdit.headliners"
                                :options="speakersList"
                                :reduce="speaker => speaker.id"
                                label="name"
                                :closeOnSelect="false">
                                </v-select>
                        </div>

                    <div class="double">
                        <button v-if="couponToEdit.invitation_post_id"
                        class="save-edits form-button"
                        type="button" @click="saveCoupon('edit')">
                        Save edits
                        </button>
                        <button v-else
                        class="save-edits form-button"
                        type="button" @click="saveCoupon('create')">
                        Save edits
                        </button>
                        <button
                        v-if="couponToEdit.invitation_post_id"
                        class="delete-registration form-button"
                        type="button"
                        @click="saveCoupon('delete')">
                        Delete coupon
                        </button>
                    </div>
                </form>
            </div>
        </div>
  </div>
</template>

<script>
import auth from '@/assets/auth';
import 'vue-good-table/dist/vue-good-table.css';
import { VueGoodTable } from 'vue-good-table';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import MessageAnnounce from './MessageAnnounce.vue';

export default {
  name: 'CouponsManagement',
  components: {
    VueGoodTable,
    vSelect,
    MessageAnnounce,
  },
  data() {
    return {
      announce: null,
      couponToEdit: null,
      couponsList: [],
      usersList: [],
      speakersList: [],
      columns: [
        {
          label: 'Coupon',
          field: 'titleLink',
        },
        {
          label: 'Discount %',
          field: 'discount',
        },
        {
          label: 'Recipient',
          field: 'recipient_name',
        },
        {
          label: 'For guests?',
          field: this.guestCheck,
        },
        {
          label: 'Invitation type',
          field: 'invitation_type',
        },
        {
          label: 'Invitation display',
          field: this.headlinersCheck,
        },
        {
          label: 'Max uses',
          field: 'max_uses',
        },
        {
          label: 'Actual uses',
          field: 'actual_uses',
        },
        {
          label: 'View/Edit',
          field: 'edit',
        },
      ],
    };
  },
  methods: {
    killMessage() {
      this.grabCoupons();
      this.announce = null;
    },
    async grabCoupons() {
      this.couponsList = [];
      const url = auth.allCoupons;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => { this.couponsList = result; });
    },
    async grabUsers() {
      this.usersList = [];
      const url = auth.peopleAndOrgs;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => { this.usersList = result; });
    },
    async grabSpeakers() {
      this.usersList = [];
      const url = auth.onlyPeople;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => { this.speakersList = result; });
    },
    async saveCoupon(cmd) {
      if (!this.couponToEdit.coupon_title
      || !this.couponToEdit.discount
      || !this.couponToEdit.recipient_id) {
        this.announce = ['Hold it right there...', 'You need to supply all required fields (coupon code, discount % and recipient)'];
        return;
      }
      this.couponToEdit.command = cmd;
      const data = JSON.stringify(this.couponToEdit);
      const url = auth.editCouponInvitation;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'POST', headers, body: data })
        .then(this.couponToEdit = null)
        .then((result) => result.json())
        .then((result) => { this.announce = result; });
    },
    guestCheck(rowObj) {
      if (rowObj.guest_status === '1') {
        return 'Yes';
      }
      return 'No';
    },
    headlinersCheck(rowObj) {
      if (rowObj.headliners.length >= 1) {
        return 'Custom';
      }
      return 'Standard';
    },
    editCoupon(rowId) {
      this.couponToEdit = this.couponsList[rowId];
      if (this.couponsList[rowId].guest_status === '1') {
        this.couponToEdit.guest_status = true;
      } else {
        this.couponToEdit.guest_status = false;
      }
    },
    startNewCoupon() {
      this.couponToEdit = {};
      this.couponToEdit.with_headliners = 'standard';
      this.couponToEdit.invitation_type = 'generic';
    },
    changeGuestStatus(event) {
      // eslint-disable-next-line
      if (event.target.checked) {
        this.couponToEdit.guest_status = true;
      } else {
        this.couponToEdit.guest_status = false;
      }
    },
  },
  mounted() {
    this.grabCoupons();
    this.grabUsers();
    this.grabSpeakers();
  },

};
</script>

<style>
.vs__selected-options span.vs__selected {
    border:none;
    height:25px;
    background:#37bafd;
    color:white;
    min-width:100px;
}
</style>
