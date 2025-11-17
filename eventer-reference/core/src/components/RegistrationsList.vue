<template>
  <div class="sub-section">
    <message-announce v-if="announce" :message="announce" @closeMessage="killMessage()" />
    <div class="registrations-table">
        <vue-good-table
            :columns="columns"
            :rows="registrationsList"
            :row-style-class="rowStyleClassFn"
            theme="black-rhino"
            :search-options="{
              enabled: true
            }"
            :pagination-options="{
                enabled: true,
                perPage: 50,
                position: 'bottom',
            }">
            <div class="buttons-bit" slot="table-actions">
              <button class="create-new-registrant" @click="createRegistrant()">
                <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm80 224h-64v64a16 16 0 0 1-32 0v-64h-64a16 16 0 0 1 0-32h64v-64a16 16 0 0 1 32 0v64h64a16 16 0 0 1 0 32z"/></svg>
              </button>
            </div>
            <template slot="table-row" slot-scope="props">
              <div v-if="props.column.field == 'edit'">
                <button @click="editRegistrant(props.row.originalIndex)">
                  <svg xmlns="http://www.w3.org/2000/svg" class="ionicon edit-me" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M384 224v184a40 40 0 0 1-40 40H104a40 40 0 0 1-40-40V168a40 40 0 0 1 40-40h167.48"/><path d="M459.94 53.25a16.06 16.06 0 0 0-23.22-.56L424.35 65a8 8 0 0 0 0 11.31l11.34 11.32a8 8 0 0 0 11.34 0l12.06-12c6.1-6.09 6.67-16.01.85-22.38zM399.34 90 218.82 270.2a9 9 0 0 0-2.31 3.93L208.16 299a3.91 3.91 0 0 0 4.86 4.86l24.85-8.35a9 9 0 0 0 3.93-2.31L422 112.66a9 9 0 0 0 0-12.66l-9.95-10a9 9 0 0 0-12.71 0z"/></svg>
                </button>
                <button class='sync-state'
                  @click="syncWithHubspot(props.row.originalIndex)"
                  v-html="editButton(props.row)">
                </button>
              </div>
           </template>
        </vue-good-table>
        <div class="sync-all-section">
          <h3>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M434.67 285.59v-29.8c0-98.73-80.24-178.79-179.2-178.79a179 179 0 0 0-140.14 67.36m-38.53 82v29.8C76.8 355 157 435 256 435a180.45 180.45 0 0 0 140-66.92"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m32 256 44-44 46 44m358 0-44 44-46-44"/></svg>
            Sync all data with Hubspot</h3>
            <p v-if="registrationsList.filter(item => item.hs_synched === '0').length < 75">
              You have
              <strong>
               {{registrationsList.filter(item => item.hs_synched === '0').length}}
              </strong>
               unsynced contacts
            </p>
            <p class="sync-warn" v-else>Watch out. You have
               {{registrationsList.filter(item => item.hs_synched === '0').length}}
               unsynced contacts.
              Trying to sync a large number (> 100) <em>will</em> lead to problems.
               Sync now for a happier life.
            </p>
          <button @click="syncAllWithHubspot()">Sync from Hubspot</button>
          <br><br>
          <p>
            Hit this button to call Hubspot and import any registered users
            who appear there but are not yet visibile in the table above.
          </p>
        </div>
        <div class="export-section">
          <h3>
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M336 176h40a40 40 0 0 1 40 40v208a40 40 0 0 1-40 40H136a40 40 0 0 1-40-40V216a40 40 0 0 1 40-40h40"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m176 272 80 80 80-80M256 48v288"/></svg>
            Export registration data</h3>
          <a
            class='exporter-butt'
            href='/wp-content/plugins/eventer/export_registrations.php'
            target='_blank'
            >
            Export registrations
          </a>
        </div>
    </div>
    <div class="edit-shelf" v-if="(oneToEdit !== null)" v-scroll-lock="oneToEdit">
      <div class="content-wrap">
        <span class="options-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M459.94 53.25a16.06 16.06 0 0 0-23.22-.56L424.35 65a8 8 0 0 0 0 11.31l11.34 11.32a8 8 0 0 0 11.34 0l12.06-12c6.1-6.09 6.67-16.01.85-22.38zM399.34 90 218.82 270.2a9 9 0 0 0-2.31 3.93L208.16 299a3.91 3.91 0 0 0 4.86 4.86l24.85-8.35a9 9 0 0 0 3.93-2.31L422 112.66a9 9 0 0 0 0-12.66l-9.95-10a9 9 0 0 0-12.71 0z"/><path d="M386.34 193.66 264.45 315.79A41.08 41.08 0 0 1 247.58 326l-25.9 8.67a35.92 35.92 0 0 1-44.33-44.33l8.67-25.9a41.08 41.08 0 0 1 10.19-16.87l122.13-121.91a8 8 0 0 0-5.65-13.66H104a56 56 0 0 0-56 56v240a56 56 0 0 0 56 56h240a56 56 0 0 0 56-56V199.31a8 8 0 0 0-13.66-5.65z"/></svg>
          <h2 v-if="oneToEdit.payment_status != 'Pending'">Edit registration</h2>
          <h2 v-else>View non-registered contact</h2>
        </span>
        <span class="sync-status-notice suspicious"
          v-if="oneToEdit.id && checkSuspiciousFields(oneToEdit)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M85.57 446.25h340.86a32 32 0 0 0 28.17-47.17L284.18 82.58c-12.09-22.44-44.27-22.44-56.36 0L57.4 399.08a32 32 0 0 0 28.17 47.17z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m250.26 195.39 5.74 122 5.73-121.95a5.74 5.74 0 0 0-5.79-6h0a5.74 5.74 0 0 0-5.68 5.95z"/><path d="M256 397.25a20 20 0 1 1 20-20 20 20 0 0 1-20 20z"/></svg>
          <span>
            This data does not look right. please check company, email, website and name fields.
          </span>
        </span>
        <span class="sync-status-notice to-sync"
          v-else-if="oneToEdit.id && oneToEdit.hs_synched !== '1'"
        >
          <svg class="sync-me" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M434.67 285.59v-29.8c0-98.73-80.24-178.79-179.2-178.79a179 179 0 0 0-140.14 67.36m-38.53 82v29.8C76.8 355 157 435 256 435a180.45 180.45 0 0 0 140-66.92"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m32 256 44-44 46 44m358 0-44 44-46-44"/></svg>
          <span>
            Not yet synced with Hubspot.
          </span>
        </span>

        <button class="close-button" @click="(oneToEdit = null)">
          <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M320 320 192 192m0 128 128-128"/></svg>
        </button>
        <form id="edit-registrant">

          <h2>User contacts</h2>
          <input type="hidden" v-model="oneToEdit.id">
          <div class="double">
            <label for="fname">First name (Required)
              <input type="text" id="fname" v-model="oneToEdit.name"/></label>

            <label for="lname">Last name (Required)
              <input type="text" id="lname" v-model="oneToEdit.surname"/></label>
          </div>

          <div class="double">
            <label for="company">Company (Required)
              <input type="text" id="company" v-model="oneToEdit.company"/></label>

            <label for="website">Website
              <input type="url" id="website" v-model="oneToEdit.website"/></label>
          </div>

          <div class="company_type_wrapper">
                <p>Company type</p>
                <select name="company_type" id="company_type" v-model="oneToEdit.my_company_is">
                    <option value="Brand, Retailer, Manufacturer or Online Shop">
                      Brand, Retailer, Manufacturer or Online Shop</option>
                    <option value="Investor, Family Office,">
                      Investor, Family Office, Business Angel</option>
                    <option value="Media &amp; Press">
                      Media / Press / Journalism</option>
                    <option value="Public Administration / Institution">
                      Public Administration / Institution</option>
                    <option value="Research Institute, University, School">
                      Research Institute, University, School</option>
                    <option value="Vendor / Supplier of Services">
                      Vendor / Supplier of Services for Innovation and e-Commerce</option>
                    <option value="Other">Other</option>
                </select>
            </div>

          <label for="role">Role
              <input type="text" id="role" v-model="oneToEdit.role"/></label>

          <label for="email">Email address (Required)
              <input type="email" id="email" v-model="oneToEdit.email"/></label>

          <div>
            <label for="mobile">Mobile phone
              <input type="text" id="company" v-model="oneToEdit.mobile_phone"/></label>

            <label for="office">Office phone (Required)
              <input type="text" id="office" v-model="oneToEdit.office_phone"/></label>
          </div>

          <h2>Address</h2>
          <label for="address">Address
            <input type="text" id="address" v-model="oneToEdit.street_address"/></label>
          <label for="city">City
            <input type="text" id="city" v-model="oneToEdit.city"/></label>
          <label for="country">Country
            <input type="text" id="country" v-model="oneToEdit.country"/></label>

          <h2>Sign-up details</h2>
          <ul v-if="oneToEdit.id">
            <li>Coupon: <strong>{{oneToEdit.coupon_code}}</strong></li>
            <li>Billed: <strong>CHF {{oneToEdit.paid}}</strong>
              <span v-if="oneToEdit.payment_status == 'Pending'">
                - contact will not be registered to event until payment</span>
            </li>
            <li :class="{pending : oneToEdit.payment_status == 'Pending'}">
              Payment status: <strong>{{oneToEdit.payment_status}}</strong>
            </li>
            <li>Sign-up date: <strong><span v-html="registrationDate(oneToEdit)">
            </span></strong></li>
            <li>User interests: <strong><span v-html="sortLikes(oneToEdit)">
            </span></strong></li>
            <!--<li>Welcome email sent: <strong><span v-html="welcomedOrNot(oneToEdit)">
            </span></strong>
              <a style="margin-left:10px;text-decoration:none;"
              href="#" >
                Send now
                <span style="font-size:20px;position:relative;bottom:-1px;">
                  &#8594;
                </span>
              </a>
            </li>-->
          </ul>

          <div class="double">
            <button v-if="oneToEdit.id"
              class="save-edits form-button"
              type="button" @click="editRegistration('edit')">
              Save edits
            </button>
            <button v-else
              class="save-edits form-button"
              type="button" @click="editRegistration('create')">
              Add registration
            </button>
            <button
              v-if="oneToEdit.id && oneToEdit.hs_synched !== '1'"
              type="button"
              class="form-button hubspot-sync"
              @click="syncWithHubspot(oneToEdit.id)"
            >
              Sync with Hubspot
            </button>
            <button
              v-if="oneToEdit.id"
              class="delete-registration form-button"
              type="button"
              @click="editRegistration('delete')">
              Delete registration
            </button>
            <div class="delete-warning"
            style="background:#efefef;padding:10px;width:95%;
            font-size:12px;color:#3a3a3a;margin:10px 0;width:100%;">
              Caution: When you delete a registration it will remove EVERYONE
              with that email from our event list (and on Hubspot).
              If you need to remove duplicates, delete <em>all</em>
              users from the site with that email address and then add them again.
            </div>
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
import MessageAnnounce from './MessageAnnounce.vue';

export default {

  name: 'RegistrationsList',
  components: {
    VueGoodTable, MessageAnnounce,
  },
  data() {
    return {
      oneToEdit: null,
      announce: null,
      registrationsList: [],
      columns: [
        {
          label: 'Name',
          field: this.concatName,
        },
        {
          label: 'Email',
          field: 'email',
        },
        {
          label: 'Company',
          field: 'company',
        },
        {
          label: 'Job title',
          field: 'role',
        },
        {
          label: 'Company type',
          field: 'my_company_is',
        },
        {
          label: 'Coupon',
          field: 'coupon_code',
        },
        {
          label: 'Status',
          field: this.paidAmount,
        },
        {
          label: 'Registered',
          field: this.registrationDate,
        },
        {
          label: 'View/Edit',
          field: 'edit',
        },
      ],
    };
  },
  methods: {
    async doSpeakerCodes() {
      this.announce = ['Ok. Synching speakers', 'This may take a minute. Please do not close this tab.'];
      const url = auth.speakerCodes;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => { this.announce = result; });
    },
    async grabRegistrations() {
      this.registrationsList = [];
      const url = auth.allRegistrations;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => { this.registrationsList = result; });
    },
    async syncAllWithHubspot() {
      this.announce = ['Ok. Let\'s do this', 'Please wait while we sync Hubspot data. This may take a few minutes. Please do not close this tab.'];
      const url = auth.allSync;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => {
          if (result[0] === 'Success') {
            this.announce = result;
          } else {
            this.announce = ['Uh oh', 'Something went wrong with the sync. Sorry, we do not know any more than that.'];
          }
        });
    },
    async resendWelcomeEmail() {
      const data = JSON.stringify(this.oneToEdit);
      const url = auth.resendWelcome;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'POST', headers, body: data })
        .then((result) => result.json())
        .then((result) => { this.announce = result; });
    },
    async editRegistration(cmd) {
      if ((cmd !== 'delete') && (!this.oneToEdit.name || !this.oneToEdit.surname || !this.oneToEdit.email
      || !this.oneToEdit.company || !this.oneToEdit.office_phone)) {
        this.announce = ['Hold it right there...', 'You need to supply all required fields (name, surname, company, email and office phone)'];
        return;
      }

      this.oneToEdit.command = cmd;
      const data = JSON.stringify(this.oneToEdit);
      const url = auth.editRegistrations;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'POST', headers, body: data })
        .then(this.oneToEdit = null)
        .then((result) => result.json())
        .then((result) => { this.announce = result; });
    },
    editButton(rowObj) {
      let html = '';
      const syncStatus = this.syncHsCheck(rowObj);

      switch (syncStatus) {
        case 'suspicious':
          html = '<svg class="suspicious" xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M85.57 446.25h340.86a32 32 0 0 0 28.17-47.17L284.18 82.58c-12.09-22.44-44.27-22.44-56.36 0L57.4 399.08a32 32 0 0 0 28.17 47.17z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m250.26 195.39 5.74 122 5.73-121.95a5.74 5.74 0 0 0-5.79-6h0a5.74 5.74 0 0 0-5.68 5.95z"/><path d="M256 397.25a20 20 0 1 1 20-20 20 20 0 0 1-20 20z"/></svg>';
          break;
        case 'ok':
          html = '<svg class="all-ok" xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M352 176 217.6 336 160 272"/></svg>';
          break;
        case 'sync-me':
          html = '<svg class="sync-me" xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M434.67 285.59v-29.8c0-98.73-80.24-178.79-179.2-178.79a179 179 0 0 0-140.14 67.36m-38.53 82v29.8C76.8 355 157 435 256 435a180.45 180.45 0 0 0 140-66.92"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m32 256 44-44 46 44m358 0-44 44-46-44"/></svg>';
          break;
        default:
          html = '<svg class="all-ok" xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M352 176 217.6 336 160 272"/></svg></button>';
      }
      return html;
    },
    syncHsCheck(rowObj) {
      const suspicious = this.checkSuspiciousFields(rowObj);
      if (rowObj.hs_synched === '0' && suspicious === true) {
        return 'suspicious';
      }
      if (rowObj.hs_synched === '0') {
        return 'sync-me';
      }
      if (rowObj.hs_synched !== '0') {
        return 'ok';
      }
      return 'ok';
    },
    async syncWithHubspot(rowId) {
      let target = null;
      if (this.oneToEdit == null) {
        target = this.registrationsList[rowId];
      } else {
        target = this.oneToEdit;
      }
      if (!target.name || !target.surname || !target.email
      || !target.company || !target.office_phone) {
        this.announce = ['Hold it right there...', 'You need to supply all required fields (name, surname, company, email and office phone)'];
        return;
      }
      this.oneToEdit = null;
      const data = JSON.stringify(target);
      const url = auth.hubspotSync;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'POST', headers, body: data })
        .then((result) => result.json())
        .then((result) => { this.announce = result; });
    },
    concatName(rowObj) {
      return rowObj.name + ' ' + rowObj.surname;
    },
    paidAmount(rowObj) {
      const stat = (rowObj.payment_status);
      if (stat === 'Pending') {
        return 'Abandoned cart';
      }
      return rowObj.payment_status;
    },
    editRegistrant(rowId) {
      this.oneToEdit = this.registrationsList[rowId];
    },
    sortLikes(rowObj) {
      if (!rowObj.interests) return '';
      return rowObj.interests.replaceAll(',', ', ');
    },
    welcomedOrNot(rowObj) {
      if (rowObj.welcome_email_sent === '1' || rowObj.welcome_email_sent === 1) return 'Yes';
      return 'No';
    },
    registrationDate(rowObj) {
      if (!rowObj.sign_up_date) return '';
      const s = rowObj.sign_up_date;
      return s.split('-').reverse().join('-');
    },
    killMessage() {
      this.grabRegistrations();
      this.announce = null;
    },
    createRegistrant() {
      this.oneToEdit = {};
    },
    rowStyleClassFn(row) {
      return row.payment_status === 'pending' ? 'abandoned' : 'normal';
    },
    checkSuspiciousFields(rowObj) {
      let score = 0;
      // eslint-disable-next-line
      let company = rowObj.company;
      // eslint-disable-next-line
      const email = rowObj.email;
      if (email.indexOf('@') === -1) {
        return true;
      }
      const domain = email.split('@')[1];

      if (company.indexOf(' ') !== -1) {
        company = rowObj.company.split(' ')[0].toLowerCase();
        if (company.length < 3) {
          company = rowObj.company.split(' ')[1].toLowerCase();
        }
      }
      if (email.indexOf(rowObj.name.toLowerCase()) !== -1) {
        // eslint-disable-next-line
        score++;
      }
      if (email.indexOf(rowObj.surname.toLowerCase()) !== -1) {
        // eslint-disable-next-line
        score++;
      }
      if (domain.indexOf(company.toLowerCase()) !== -1) {
        // eslint-disable-next-line
        score++;
      }
      if (rowObj.website && rowObj.website.indexOf(domain) !== -1) {
        // eslint-disable-next-line
        score++;
      }

      if (score < 2) {
        return true;
      }
      return false;
    },
  },
  mounted() {
    this.grabRegistrations();
  },
};
</script>
