<template>
  <a-config-provider :locale="zhCN">
    <router-view />
  </a-config-provider>
</template>

<script setup>
import { ConfigProvider as AConfigProvider } from 'ant-design-vue'
import zhCN from 'ant-design-vue/es/locale/zh_CN'
</script>

<style>
#app {
  font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif;
}
</style>
