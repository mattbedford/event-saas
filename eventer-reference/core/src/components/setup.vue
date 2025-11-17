<template>
  <div class="sub-section">
    <div class="side-column">
        <ul class="submenu">
            <li :class="{'active' : menuItem == 'globals'}">
                <button @click="menuItem = 'globals'">Event globals</button>
            </li>
            <li :class="{'active' : menuItem == 'apis'}">
                <button @click="menuItem = 'apis'">APIs</button>
            </li>
            <li :class="{'active' : menuItem == 'venue'}">
                <button @click="menuItem = 'venue'">Venue and tickets</button>
            </li>
            <li :class="{'active' : menuItem == 'badges'}">
                <button @click="menuItem = 'badges'">Badge design</button>
            </li>
        </ul>
    </div>
    <message-announce v-if="announce" :message="announce" @closeMessage="killMessage()" />
    <div v-show="menuItem == 'globals'" class="main-column">
        <h3>Global setup</h3>
        <form id="globals" @submit.prevent="sendOptions('globals')">
            <label for="name">Event name
            <input type="text" id="name" v-model="globals.event_name"/></label>
            <label for="payoff">Event payoff
            <input type="text" id="payoff" v-model="globals.event_payoff"/></label>
            <label for="date">Event date
            <input type="date" id="date" v-model="globals.event_date"/></label>
            <div class="double">
                <label for="start">Check in time
                <input type="time" id="start" v-model="globals.event_start"/></label>
                <label for="end">Event end time
                <input type="time" id="end" v-model="globals.event_end"/></label>
            </div>

            <input type="submit" value="Save edits"/>
        </form>
    </div>

    <div v-show="menuItem == 'apis'" class="main-column">
        <h3>APIs setup</h3>
        <form id="apis" @submit.prevent="sendOptions('apis')">
            <label for="stripe_key">Stripe API key
            <input type="text" id="stripe_key" v-model="apis.alt_stripe_key"/></label>
            <label for="hubspot-api">Hubspot API key
            <input type="text" id="hubspot-api" v-model="apis.hubspot_key"/></label>
            <div class="double">
                <label for="hubspot-list">Hubspot list ID
                <input type="text" id="hubspot-list" v-model="apis.hubspot_list"/></label>
                <label for="tag">Hubspot event tag (used in form to link to static list)
                <input type="text" id="tag" v-model="apis.event_tag"/></label>
            </div>

            <input type="submit" value="Save edits"/>
        </form>
    </div>

    <div v-show="menuItem == 'venue'" class="main-column">
        <h3>Venue and tickets</h3>
        <form id="venue" @submit.prevent="sendOptions('venue')">
            <label for="venue_name">Name of venue
            <input type="text" id="venue_name" v-model="venue.venue_name"/></label>
            <label for="venue_address">Venue address
            <input type="text" id="venue_address" v-model="venue.venue_address"/></label>
            <div class="double">
              <label for="venue_city">Venue city
              <input type="text" id="venue_city" v-model="venue.venue_city"/></label>
              <label for="venue_country">Venue country
              <input type="text" id="venue_country" v-model="venue.venue_country"/></label>
            </div>
            <label for="venue_max">Maximum attendees permitted
            <input type="number" id="venue_max" v-model="venue.max_attendees"/></label>
            <label for="ticket_price">Ticket price
            <input type="number" id="ticket_price" v-model="venue.ticket_price"/></label>
            <input type="submit" value="Save edits"/>
        </form>
    </div>

    <div v-show="menuItem == 'badges'" class="main-column">
        <h3>Badge layout</h3>
        <form id="badge" @submit.prevent="sendOptions('badges')">
            <label for="badge_template">Badge template
              <span v-if="badges.badge_template" style="font-weight:600; color:#7fc41c;">
                Current: {{ badges.badge_template }}
              </span>
            <input type="file" id="badge_template" @change="badgeUpload()"/></label>

            <p>Text positioning badge page 1</p>
            <div class="double">
              <label for="p1x">X coordinate
              <input type="number" id="p1x" v-model="badges.badge_x"/></label>
              <label for="p1y">Y coordinate
              <input type="number" id="p1y" v-model="badges.badge_y"/></label>
            </div>
            <p>Text positioning badge page 2</p>
            <div class="double">
              <label for="p2x">X coordinate
              <input type="number" id="p2x" v-model="badges.badge_x_p2"/></label>
              <label for="p2y">Y coordinate
              <input type="number" id="p2y" v-model="badges.badge_y_p2"/></label>
            </div>
            <br>
            <hr>
            <p>Badge <strong>name field</strong></p>
            <label for="namebreak" style="display:flex;flex-direction: row;">
              <input type="checkbox" name="namebreak" v-model="badges.badge_namebreak"/>
              <span style="position: relative;top: -3px;">Break name into two lines?</span>
            </label>
            <div class="double">
              <label for="bnamecolor">Color
              <select id="bnamecolor" v-model="badges.badge_name_format.color">
                <option value="black">Black</option>
                <option value="dark">Dark</option>
                <option value="luxury">Luxury</option>
                <option value="blue">Dagorà blue</option>
                <option value="green">Dagorà green</option>
                <option value="purple">Dagorà purple</option>
              </select>
              </label>
              <label for="bnamefontsize">Font size
              <input type="number" id="bnamefontsize"
              v-model="badges.badge_name_format.fontsize"/></label>
            </div>
            <div class="double">
              <label for="bnamealign">Text align
              <select id="bnamealign" v-model="badges.badge_name_format.align">
                <option value="L">Left</option>
                <option value="C">Center</option>
                <option value="R">Right</option>
              </select>
              </label>
              <label for="bnamecaps">Capitalize text?
                <select id="bnamecaps" v-model="badges.badge_name_format.caps">
                <option value="no">No. Don't capitalize</option>
                <option value="yes">Yes. All caps.</option>
              </select>
              </label>
            </div>

            <hr>
            <p>Badge <strong>job title field</strong></p>
            <div class="double">
              <label for="bjobcolor">Color
              <select id="bjobcolor" v-model="badges.badge_job_format.color">
                <option value="black">Black</option>
                <option value="dark">Dark</option>
                <option value="luxury">Luxury</option>
                <option value="blue">Dagorà blue</option>
                <option value="green">Dagorà green</option>
                <option value="purple">Dagorà purple</option>
              </select>
              </label>
              <label for="bjobfontsize">Font size
              <input type="number" id="bjobfontsize"
              v-model="badges.badge_job_format.fontsize"/></label>
            </div>
            <div class="double">
              <label for="bjobalign">Text align
              <select id="bjobalign" v-model="badges.badge_job_format.align">
                <option value="L">Left</option>
                <option value="C">Center</option>
                <option value="R">Right</option>
              </select>
              </label>
              <label for="bjobcaps">Capitalize text?
                <select id="bjobcaps" v-model="badges.badge_job_format.caps">
                <option value="no">No. Don't capitalize</option>
                <option value="yes">Yes. All caps.</option>
              </select>
              </label>
            </div>

            <hr>
            <p>Badge <strong>company field</strong></p>
            <div class="double">
              <label for="bcompcolor">Color
              <select id="bcompcolor" v-model="badges.badge_company_format.color">
                <option value="black">Black</option>
                <option value="dark">Dark</option>
                <option value="luxury">Luxury</option>
                <option value="blue">Dagorà blue</option>
                <option value="green">Dagorà green</option>
                <option value="purple">Dagorà purple</option>
              </select>
              </label>
              <label for="bcompfontsize">Font size
              <input type="number" id="bcompfontsize"
              v-model="badges.badge_company_format.fontsize"/></label>
            </div>
            <div class="double">
              <label for="bcompalign">Text align
              <select id="bcompalign" v-model="badges.badge_company_format.align">
                <option value="L">Left</option>
                <option value="C">Center</option>
                <option value="R">Right</option>
              </select>
              </label>
              <label for="bcompcaps">Capitalize text?
                <select id="bcompcaps" v-model="badges.badge_company_format.caps">
                <option value="no">No. Don't capitalize</option>
                <option value="yes">Yes. All caps.</option>
              </select>
              </label>
            </div>

            <input type="submit" value="Save edits"/>
        </form>
    </div>
  </div>
</template>

<script>
import auth from '@/assets/auth';
import MessageAnnounce from './MessageAnnounce.vue';

export default {

  name: 'SetUp',
  components: {
    MessageAnnounce,
  },
  data() {
    return {
      menuItem: 'globals',
      announce: null,
      globals: {
        event_name: 'xxxx',
        event_payoff: 'ccccc',
        event_date: null,
        event_start: null,
        event_end: null,
      },
      apis: {
        alt_stripe_key: null,
        event_tag: null,
        hubspot_list: null,
        hubspot_key: null,
      },
      venue: {
        venue_name: null,
        venue_address: null,
        venue_city: null,
        venue_country: null,
        max_attendees: null,
        ticket_price: null,
      },
      badges: {
        badge_template: null,
        badge_x: null,
        badge_y: null,
        badge_x_p2: null,
        badge_y_p2: null,
        badge_namebreak: null,
        badge_company_format: {
          color: null,
          fontsize: null,
          align: null,
          caps: null,
        },
        badge_job_format: {
          color: null,
          fontsize: null,
          align: null,
          caps: null,
        },
        badge_name_format: {
          color: null,
          fontsize: null,
          align: null,
          caps: null,
        },
      },
    };
  },
  methods: {
    killMessage() {
      this.announce = null;
    },
    async sendOptions(dataObj) {
      const data = JSON.stringify(this[dataObj]);
      const url = auth.optionsRoute;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'POST', headers, body: data })
        .then((result) => result.json())
        .then((result) => { this.announce = result; });
    },
    async getAllOptions() {
      const url = auth.AllOptionsRoute;
      const headers = {
        credentials: 'same-origin',
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'GET', headers })
        .then((result) => result.json())
        .then((result) => {
          Object.entries(result).forEach(([key]) => {
            const reskey = result[key][0];
            const resval = result[key][1];
            if (this.globals[reskey] || this.globals[reskey] === null) {
              this.globals[reskey] = resval;
            }
            if (this.apis[reskey] || this.apis[reskey] === null) {
              this.apis[reskey] = resval;
            }
            if (this.venue[reskey] || this.venue[reskey] === null) {
              this.venue[reskey] = resval;
            }
            if (this.badges[reskey] || this.badges[reskey] === null) {
              this.badges[reskey] = resval;
            }
          });
        });
    },
    async badgeUpload() {
      const file = document.getElementById('badge_template').files[0];
      const formData = new FormData();
      formData.append('file', file);
      const url = `${auth.rootUrl}/wp-json/wp/v2/media/`;
      const headers = {
        credentials: 'same-origin',
        'X-WP-Nonce': this.nonce,
      };
      fetch(url, { method: 'POST', headers, body: formData })
        .then((result) => result.json())
        .then((result) => {
          if (result.id) {
            this.badges.badge_template = result.source_url;
          } else {
            this.announce = ['Error', 'Something went wrong with your upload. Please check file and try again.'];
          }
        });
    },
  },
  mounted() {
    this.getAllOptions();
  },
};
</script>
