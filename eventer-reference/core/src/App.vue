<template>
  <div>
    <NavBar selected="currentPage" @pageSelect="goToPage" />
    <section class="admin-main" v-if="currentPage == 'setup'">
      <h2><svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M261.56 101.28a8 8 0 0 0-11.06 0L66.4 277.15a8 8 0 0 0-2.47 5.79L63.9 448a32 32 0 0 0 32 32H192a16 16 0 0 0 16-16V328a8 8 0 0 1 8-8h80a8 8 0 0 1 8 8v136a16 16 0 0 0 16 16h96.06a32 32 0 0 0 32-32V282.94a8 8 0 0 0-2.47-5.79z"/><path d="m490.91 244.15-74.8-71.56V64a16 16 0 0 0-16-16h-48a16 16 0 0 0-16 16v32l-57.92-55.38C272.77 35.14 264.71 32 256 32c-8.68 0-16.72 3.14-22.14 8.63l-212.7 203.5c-6.22 6-7 15.87-1.34 22.37A16 16 0 0 0 43 267.56L250.5 69.28a8 8 0 0 1 11.06 0l207.52 198.28a16 16 0 0 0 22.59-.44c6.14-6.36 5.63-16.86-.76-22.97z"/></svg> Event setup</h2>
      <setup />
    </section>

    <section class="admin-main" v-if="currentPage == 'desk'">
      <h2><svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" version="1.0" viewBox="0 0 1588 1600"><path d="M778 .7c-39.6 3.8-74.6 18-104.5 42.4-10.3 8.3-27 26.1-34.7 37-53.5 75.1-45.9 176.2 18.1 242.2 31.5 32.6 70.5 51.9 115.1 57.2 57.4 6.8 114.6-13.2 156-54.6 46.4-46.4 65.5-111.9 51.4-175.8-3.6-16-7.3-26.3-15.9-43.6-18.7-37.7-47-66-85-85-22.1-11-41.5-16.8-65.7-19.4-9.1-1-26.7-1.2-34.8-.4zM670.5 414.5c-43 5.8-81.6 22.9-115.6 51.3-34.4 28.8-60.2 69.8-71.4 113.4-6.6 26-6.8 28.3-7.2 92.3l-.5 58H1111v-53.3c0-54.4-.5-64.6-4.1-83.2-13-67.3-56.8-125-118.6-156.3-20.1-10.2-38-16.2-62.1-21-10.5-2-13.4-2.1-128.2-2.3-93.5-.2-119.5.1-127.5 1.1z"/><path d="M1333 619v16l9.8.1c5.3.1 8.4.4 6.7.6-34 4-63.7 17-89 38.9-23.1 20.1-41.1 48.9-48.9 78.4l-2.3 8.5-593.4.5-593.4.5-4.3 2.3c-6.6 3.5-11.9 8.8-14.9 15l-2.8 5.7V857c0 70.9 0 71.5 2.1 76.1 3.2 6.9 9.1 12.8 15.9 16.1 5.9 2.8 6.2 2.8 25.2 2.8H63l.2 304.7c.3 280.6.4 305.1 2 309.1 3.6 9.5 10.7 16.5 19.9 19.7 3.6 1.3 93.7 1.5 708.5 1.5h704.3l5.8-2.1c6.8-2.6 13.8-9.1 17.3-16.4l2.5-5 .3-305.8.2-305.7h18.8c23.4 0 26.1-.7 34.8-9.4 10-10.1 9.5-4.9 9.2-87.2l-.3-70.9-2.7-5.1c-3.5-6.6-10.5-12.8-17.4-15.4-4.8-1.8-7.8-2-25.4-2h-19.9l-1.1-4.3c-4.7-18.4-14.7-40.3-25.3-55.4-7.9-11.3-26.7-30.1-38-38-21.4-15.1-47.8-25.1-75.2-28.6-1.6-.2 1.2-.5 6.3-.6l9.2-.1v-32h-64v16zm49 81.1c29 5.4 54.3 24.6 67.7 51.4 1.7 3.3 3.4 7 3.7 8.2l.7 2.3h-89c-49 0-89.1-.3-89.1-.7 0-.5 2-4.8 4.5-9.8 18.8-38 60.4-59 101.5-51.4z"/></svg> Front desk</h2>
      <FrontDesk />
    </section>

    <section class="admin-main" v-if="currentPage == 'registrations'">
      <h2><svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M32 376a56 56 0 0 0 56 56h336a56 56 0 0 0 56-56V222H32zm66-76a30 30 0 0 1 30-30h48a30 30 0 0 1 30 30v20a30 30 0 0 1-30 30h-48a30 30 0 0 1-30-30zM424 80H88a56 56 0 0 0-56 56v26h448v-26a56 56 0 0 0-56-56z"/></svg> Registrations</h2>
      <registrations-list />
    </section>

    <section class="admin-main" v-if="currentPage == 'coupon'">
      <h2><svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="m490.18 181.4-44.13-44.13a20 20 0 0 0-27-1 30.81 30.81 0 0 1-41.68-1.6 30.81 30.81 0 0 1-1.6-41.67 20 20 0 0 0-1-27L330.6 21.82a19.91 19.91 0 0 0-28.13 0l-70.35 70.34a39.87 39.87 0 0 0-9.57 15.5 7.71 7.71 0 0 1-4.83 4.83 39.78 39.78 0 0 0-15.5 9.58l-180.4 180.4a19.91 19.91 0 0 0 0 28.13L66 374.73a20 20 0 0 0 27 1 30.69 30.69 0 0 1 43.28 43.28 20 20 0 0 0 1 27l44.13 44.13a19.91 19.91 0 0 0 28.13 0l180.4-180.4a39.82 39.82 0 0 0 9.58-15.49 7.69 7.69 0 0 1 4.84-4.84 39.84 39.84 0 0 0 15.49-9.57l70.34-70.35a19.91 19.91 0 0 0-.01-28.09zm-228.37-29.65a16 16 0 0 1-22.63 0l-11.51-11.51a16 16 0 0 1 22.63-22.62l11.51 11.5a16 16 0 0 1 0 22.63zm44 44a16 16 0 0 1-22.62 0l-11-11a16 16 0 1 1 22.63-22.63l11 11a16 16 0 0 1 .01 22.66zm44 44a16 16 0 0 1-22.63 0l-11-11a16 16 0 0 1 22.63-22.62l11 11a16 16 0 0 1 .05 22.67zm44.43 44.54a16 16 0 0 1-22.63 0l-11.44-11.5a16 16 0 1 1 22.68-22.57l11.45 11.49a16 16 0 0 1-.01 22.63z"/></svg> Coupons & invitations</h2>
      <coupons-management />
    </section>

    <section class="admin-main" v-if="currentPage == 'badge'">
      <h2><svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M368 16H144a64.07 64.07 0 0 0-64 64v352a64.07 64.07 0 0 0 64 64h224a64.07 64.07 0 0 0 64-64V80a64.07 64.07 0 0 0-64-64zm-34.52 268.51c7.57 8.17 11.27 19.16 10.39 30.94C342.14 338.91 324.25 358 304 358s-38.17-19.09-39.88-42.55c-.86-11.9 2.81-22.91 10.34-31S292.4 272 304 272a39.65 39.65 0 0 1 29.48 12.51zM192 80a16 16 0 0 1 16-16h96a16 16 0 0 1 0 32h-96a16 16 0 0 1-16-16zm189 363.83a12.05 12.05 0 0 1-9.31 4.17H236.31a12.05 12.05 0 0 1-9.31-4.17 13 13 0 0 1-2.76-10.92c3.25-17.56 13.38-32.31 29.3-42.66C267.68 381.06 285.6 376 304 376s36.32 5.06 50.46 14.25c15.92 10.35 26.05 25.1 29.3 42.66a13 13 0 0 1-2.76 10.92z"/></svg> Badges</h2>
      <data-play />
    </section>

  </div>
</template>

<script>
import CouponsManagement from './components/couponsManagement.vue';
import DataPlay from './components/DataPlay.vue';
// eslint-disable-next-line
import NavBar from './components/NavBar.vue';
import RegistrationsList from './components/RegistrationsList.vue';
import setup from './components/setup.vue';
import FrontDesk from './components/FrontDesk.vue';

export default {
  name: 'App',
  data() {
    return {
      currentPage: 'setup',
    };
  },
  components: {
    NavBar, setup, RegistrationsList, CouponsManagement, DataPlay, FrontDesk,
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('section')) {
      this.goToPage(urlParams.get('section'));
    }
  },
  methods: {
    goToPage(slug) {
      this.currentPage = slug;
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.set('section', slug);
      window.history.replaceState({}, '', `${window.location.pathname}?${urlParams}`);
    },
  },
};
</script>
