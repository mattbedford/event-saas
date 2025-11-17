<template>
  <div class="sub-section" style="flex-direction:column;">
      <message-announce v-if="announce" :message="announce" @closeMessage="killMessage()" />
      <vue-good-table
        :columns="columns"
        :rows="badgesList"
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
          <div v-if="props.column.field == 'print'">
            <button
              style="display:flex;
                width:70px;
                align-items:center;
                justify-content:space-between;
                padding-right:10px;"
                @click="printSingleBadge(props.row.id)">
              <svg style="width:20px;margin-right:10px;" xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" d="M384 368h24a40.12 40.12 0 0 0 40-40V168a40.12 40.12 0 0 0-40-40H104a40.12 40.12 0 0 0-40 40v160a40.12 40.12 0 0 0 40 40h24"/><rect width="256" height="208" x="128" y="240" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" rx="24.32" ry="24.32"/><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" d="M384 128v-24a40.12 40.12 0 0 0-40-40H168a40.12 40.12 0 0 0-40 40v24"/><circle cx="392" cy="184" r="24"/></svg> Print
            </button>
          </div>
        </template>
      </vue-good-table>
      <div class="export-section" style="margin-top:50px !important;">
        <h3>
          <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M336 176h40a40 40 0 0 1 40 40v208a40 40 0 0 1-40 40H136a40 40 0 0 1-40-40V216a40 40 0 0 1 40-40h40"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m176 272 80 80 80-80M256 48v288"/></svg>
          Print all badges
        </h3>
        <p>This will print all people registered to the event, saving the file as a zip of PDFs.
          Before using, please ensure you have synced with Hubspot
          (Registrations page) to get the latest data.
        </p>
        <button class='exporter-butt'
          @click="printEverything"
          style="background: #7fc41c;
            padding: 8px 25px;
            outline:none;
            color: white;
            text-decoration: none;
            border:none;
            cursor:pointer;">
          Print all badges
        </button>
      </div>

      <div class="export-section" style="margin-top:50px !important;">
        <h3>
          <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="28" d="M288 193s12.18-6-32-6a80 80 0 1 0 80 80"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="28" d="m256 149 40 40-40 40"/><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64z"/></svg>
          Reset printed status
        </h3>
        <p>This will reset the badge status of each registration to <em>not printed</em>.
           This is useful if you want to print all badges again.</p>
        <button class='exporter-butt'
          @click="resetBadgeStatus"
          style="background: #d76163;
            padding: 8px 25px;
            outline:none;
            color: white;
            text-decoration: none;
            border:none;
            cursor:pointer;">
          Reset all badges
        </button>
      </div>
  </div>
</template>

<script>
import auth from '@/assets/auth';
import 'vue-good-table/dist/vue-good-table.css';
import { VueGoodTable } from 'vue-good-table';
import MessageAnnounce from './MessageAnnounce.vue';

export default {
  name: 'DataPlay',
  components: {
    VueGoodTable, MessageAnnounce,
  },
  data() {
    return {
      announce: null,
      badgesList: [],
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
          label: 'Printed?',
          field: this.isPrinted,
        },
        {
          label: 'Print',
          field: 'print',
        },
        {
          label: 'Link',
          field: this.checkPrintStatus,
          html: true,
        },
      ],
    };
  },
  mounted() {
    this.getAllRegistrants();
  },
  methods: {
    checkPrintStatus(rowObj) {
      if (rowObj.badge_link && rowObj.printed === '1') {
        return `<a href='${rowObj.badge_link}' target='_blank'>View</a>`;
      }
      if (!rowObj.badge_link && rowObj.printed === '1') {
        return `See <a href='${document.location.origin}/wp-content/plugins/eventer/badges/all_badges.zip' target='_blank'>zip folder</a> of all badges`;
      }
      return 'Not printed yet';
    },
    async getAllRegistrants() {
      this.badgesList = [];
      const url = auth.allRegistrations;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => {
          this.badgesList = result;
        });
    },
    async printMe(arr) {
      const url = auth.printBadge;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      const body = JSON.stringify({ ids: arr });
      fetch(url, { method: 'POST', headers, body })
        .then((result) => result.json())
        .then((result) => {
          this.announce = result;
        });
    },
    async printEverything() {
      const allIds = this.badgesList.map((row) => row.id);
      this.printMe(allIds);
    },
    isPrinted(rowObj) {
      return rowObj.printed === '1' ? 'Yes' : 'No';
    },
    killMessage() {
      this.getAllRegistrants();
      this.announce = null;
    },
    printSingleBadge(rowId) {
      this.printMe([rowId]);
    },
    printAllBadges() {
      // get ids from every row in badgesList variable
      const allIds = this.badgesList.map((row) => row.id);
      this.printMe(allIds);
    },
    resetBadgeStatus() {
      const url = auth.resetBadges;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => {
          this.announce = result;
        });
    },
  },
};
</script>
