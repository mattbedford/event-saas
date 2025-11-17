<template>
    <div class="front-desk sub-section">
        <message-announce v-if="announce" :message="announce" @closeMessage="killMessage()" />
        <div class="my-front-desk">
          <div class="registrations-list">
            <h3>Current registrations</h3>
              <vue-good-table
              :columns="columns"
              :rows="registrationsDesk"
              theme="black-rhino"
              :search-options="{
                enabled: true
              }"
              :pagination-options="{
                  enabled: true,
                  perPage: 50,
                  position: 'bottom',
              }"
              >
              <template slot="table-row" slot-scope="props">
                <div v-if="props.column.field === 'checkin'" class="last-col">
                  <button
                    v-if="props.row.checked_in === '0'"
                    style="display:flex;
                      width:70px;
                      align-items:center;
                      justify-content:space-between;
                      padding-right:10px;"
                      class="check-button"
                      @click="updateAttendance(props.row.id, 'add')">
                      <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M352 176 217.6 336 160 272"/></svg>
                     Check in
                  </button>
                  <span v-else>
                    <svg xmlns="http://www.w3.org/2000/svg" class="check-icon ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M352 176 217.6 336 160 272"/></svg>
                    Guest checked in
                  </span>
                  <button
                      style="display:flex;
                      width:70px;
                      align-items:center;
                      justify-content:space-between;
                      margin-left:auto;
                      background: #7fc41c;
                      padding-right:10px;"
                      class="check-button print"
                      @click="printBadge(props.row.id)">
                      <svg xmlns="http://www.w3.org/2000/svg" class="check-icon ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" d="M384 368h24a40.12 40.12 0 0 0 40-40V168a40.12 40.12 0 0 0-40-40H104a40.12 40.12 0 0 0-40 40v160a40.12 40.12 0 0 0 40 40h24"/><rect width="256" height="208" x="128" y="240" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" rx="24.32" ry="24.32"/><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" d="M384 128v-24a40.12 40.12 0 0 0-40-40H168a40.12 40.12 0 0 0-40 40v24"/><circle cx="392" cy="184" r="24"/></svg>
                      Print
                  </button>
                </div>
              </template>
            </vue-good-table>
          </div>
          <div class="ad-hoc-registration-form">
            <form @submit.prevent="adHocRegistration()">
              <h3>Add new registration</h3>
          <div class="double">
            <label for="fname">First name (Required)
              <input type="text" id="fname" v-model="newreg.name"/></label>

            <label for="lname">Last name (Required)
              <input type="text" id="lname" v-model="newreg.surname"/></label>
          </div>
          <div class="company_type_wrapper double">
            <label for="company">Company (Required)
              <input type="text" id="company" v-model="newreg.company"/></label>
                <label for="company_type">Company type (Required)
                  <select name="company_type" id="company_type" v-model="newreg.my_company_is">
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
                </label>
            </div>
            <div class="double">
              <label for="role">Role (Required)
                <input type="text" id="role" v-model="newreg.role"/></label>
                <label for="email">Email address (Required)
                <input type="email" id="email" v-model="newreg.email"/></label>
            </div>
            <div class="double">
              <label for="phone">Office phone (Required)
              <input type="tel" id="phone" v-model="newreg.office"/></label>
                <button class="form-button" style="margin-top:20px;"
                :class="{ 'ready' : this.ready}">Submit</button>
            </div>
          </form>
          </div>
        </div>
    </div>
</template>

<script>
/* eslint-disable */
import auth from '@/assets/auth';
import MessageAnnounce from './MessageAnnounce.vue';
import 'vue-good-table/dist/vue-good-table.css';
import { VueGoodTable } from 'vue-good-table';

export default {

  components: {
    VueGoodTable, MessageAnnounce,
  },
  data() {
    return {
      announce: null,
      sorts: {
        direction: 'desc',
        column: null,
      },
      registrationsDesk: [],
      filter: '',
      ready: false,
      newreg: {
        name: '',
        surname: '',
        company: '',
        my_company_is: '',
        role: '',
        email: '',
        office: '',
      },
      columns: [
        {
          label: 'Name',
          field: 'name',
        },
        {
          label: 'Surname',
          field: 'surname',
        },
        {
          label: 'Company',
          field: 'company',
        },
        {
          label: 'Badge link',
          field: this.checkPrintStatus,
          html: true,
        },
        {
          label: 'Checked in',
          field: 'checkin',
        },
      ],
    };
  },
  mounted() {
    this.grabAllRegistrations();
  },
  methods: {
    isCheckedIn(rowObj) {
      return rowObj.checked_in;
    },
    checkPrintStatus(rowObj) {
      if (rowObj.badge_link && rowObj.printed === '1') {
        return `<a href='${rowObj.badge_link}' target='_blank'>View</a>`;
      }
      if (!rowObj.badge_link && rowObj.printed === '1') {
        return `See <a href='${document.location.origin}/wp-content/plugins/eventer/badges/all_badges.zip' target='_blank'>zip folder</a> of all badges`;
      }
      return '';
    },
    async updateAttendance(id, cmd) {
      const url = '/wp-json/core-vue/do_my_check_in';
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      const data = JSON.stringify({ id, cmd });
      fetch(url, { method: 'POST', headers, body: data })
        .then((result) => result.json())
        .then((result) => {
          this.announce = result;
          if (this.announce[0] !== 'Success') {
            this.announce = ['Something went wrong', 'Please try again'];
          }
        });
    },
    async grabAllRegistrations() {
      this.registrationsList = [];
      const url = auth.allRegistrations;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => { this.registrationsDesk = result.reverse(); });
    },
    async adHocRegistration() {
      if (!this.newreg.name || !this.newreg.surname || !this.newreg.email
      || !this.newreg.company || !this.newreg.office) {
        this.announce = ['Hold it right there...', 'You need to supply all required fields (name, surname, company, email, role and phone)'];
        return;
      }
      this.ready = false;
      const data = JSON.stringify(this.newreg);
      const url = auth.adHocRegistration;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'POST', headers, body: data })
        .then(this.oneToEdit = null)
        .then((result) => result.json())
        .then((result) => {
          this.announce = result;
          if (this.announce[0] === 'Success') {
            this.resetForm();
          }
        });
    },
    killMessage() {
      this.grabAllRegistrations();
      this.announce = null;
    },
    resetForm() {
      this.newreg = {
        name: '',
        surname: '',
        company: '',
        my_company_is: '',
        role: '',
        email: '',
      };
      this.ready = false;
    },
    async printBadge(id) {
      const url = auth.printBadge;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      const data = JSON.stringify({ ids: id });
      fetch(url, { method: 'POST', headers, body: data })
        .then((result) => result.json())
        .then((result) => {
          this.announce = result;
          if (this.announce[0] === 'Success') {
            this.grabAllRegistrations();
          }
        });
    },
  },
  watch: {
    newreg: {
      handler() {
        if (this.newreg.name && this.newreg.surname && this.newreg.email
        && this.newreg.company) {
          this.ready = true;
        } else {
          this.ready = false;
        }
      },
      deep: true,
    },
  },
};
</script>
