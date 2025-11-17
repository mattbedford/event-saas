<template>
  <div class="navbar">
    <div class="navbar-inner">
        <img class="logo"
        alt="Dagorà logo"
        src="\wp-content\plugins\eventer\assets\logo_dagora_white.png"
        />
        <div class="menu">
            <ul>
                <li :class="{active: onPage === 'setup'}">
                  <button @click="($emit('pageSelect', 'setup'),
                  selectThis('setup'))">
                  Event setup
                </button></li>

                <li :class="{active: onPage === 'desk'}">
                  <button @click="($emit('pageSelect', 'desk'), selectThis('desk'))">
                    Front desk
                  </button></li>

                <li :class="{active: onPage === 'registrations'}">
                  <button @click="($emit('pageSelect', 'registrations'),
                selectThis('registrations'))">
                  Registrations
                  </button></li>

                <li :class="{active: onPage === 'coupon'}">
                  <button @click="($emit('pageSelect', 'coupon'), selectThis('coupon'))">
                    Coupons & invitations
                  </button></li>

                <li :class="{active: onPage === 'badge'}">
                  <button @click="($emit('pageSelect', 'badge'), selectThis('badge'))">
                    Badges
                  </button></li>
            </ul>
        </div>
    </div>
  </div>
</template>

<script>
export default {

  data() {
    return {
      onPage: 'setup',
    };
  },
  props: ['selected'],
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('section')) {
      this.onPage = urlParams.get('section');
    }
  },
  methods: {
    selectThis(item) {
      this.onPage = item;
    },
  },

};
</script>
