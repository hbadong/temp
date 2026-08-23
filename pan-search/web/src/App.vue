<template>
  <div id="app">
    <a href="#main-content" class="skip-link">跳转到主要内容</a>
    <div id="main-content" role="main">
      <router-view />
    </div>
    <div class="live-region" aria-live="polite" aria-atomic="true" id="global-live-region"></div>
  </div>
</template>

<script>
export default {
  name: 'App',
}
</script>

<style>
/* Styles are in styles.css */
</style>