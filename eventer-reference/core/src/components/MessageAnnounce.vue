<template>
  <div class="message-announce">
    <h2>{{message[0]}}</h2>
    <p v-html="message[1]"></p>
    <button class="closer" @click="$emit('closeMessage')">&#x2716;</button>
  </div>
</template>

<script>

export default {
  props: {
    message: {
      type: Array,
      default() {
        return ['All OK', 'Options saved.'];
      },
    },
  },
};
</script>
