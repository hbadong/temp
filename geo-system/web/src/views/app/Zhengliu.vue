<template>
  <div class="card">
    <h2 class="page-title">AI蒸馏</h2>
    <div class="row">
      <input class="input" style="flex:1" v-model="keyword" placeholder="输入核心词" />
      <button class="btn" @click="run" :disabled="loading">{{ loading ? '蒸馏中…' : '蒸馏（10点数）' }}</button>
    </div>
    <textarea class="input" style="margin-top:12px;min-height:240px" v-model="out" placeholder="蒸馏结果将展示在这里"></textarea>
    <p class="muted" v-if="out">共 {{ out.split('\n').filter(Boolean).length }} 条拓展词</p>
    <div class="empty" v-else-if="queried">暂无蒸馏结果</div>
    <div class="error" v-if="err" style="margin-top:10px">{{ err }}</div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { api } from '../../api'
const keyword = ref('')
const out = ref('')
const err = ref('')
const loading = ref(false)
const queried = ref(false)
async function run() {
  err.value = ''
  if (!keyword.value.trim()) { err.value = '请输入核心词'; return }
  loading.value = true
  try {
    const d = await api.post('/api/tools/distill', { keyword: keyword.value })
    out.value = (d || []).join('\n')
    queried.value = true
  } catch (e) { err.value = e.message }
  finally { loading.value = false }
}
</script>
