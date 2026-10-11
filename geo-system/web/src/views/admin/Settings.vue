<template>
  <div class="card" style="max-width:520px">
    <h2 class="page-title">标价配置</h2>
    <div class="form-item" v-for="(v,k) in form" :key="k">
      <label>{{ labels[k] || k }}</label>
      <input class="input" v-model="form[k]" :placeholder="hints[k] || ''" />
    </div>
    <button class="btn" @click="save" :disabled="saving">{{ saving ? '保存中…' : '保存' }}</button>
    <div class="error" v-if="err">{{ err }}</div>
    <div class="okmsg" v-if="msg">{{ msg }}</div>
  </div>
</template>
<script setup>
import { onMounted, reactive, ref } from 'vue'
import { api } from '../../api'
const form = reactive({})
const msg = ref('')
const err = ref('')
const saving = ref(false)
let timer = null
const labels = {
  diagnose_price: '标准诊断价',
  diagnose_advice_price: '建议诊断价',
  pro_parse_price: 'Pro品牌解析价',
  pro_diagnose_price: 'Pro诊断价',
  index_score_cost: '指数查询点数',
  distill_score_cost: '蒸馏点数',
  write_price: '写作单价（分/篇）',
  publish_price: '发布单价（分/条）',
}
const hints = {
  diagnose_price: '单位：分，如 10000 = 100.00 元',
  diagnose_advice_price: '单位：分',
  pro_parse_price: '单位：分，0 表示免费',
  pro_diagnose_price: '单位：分',
  index_score_cost: '如 10 = 10 点/次',
  distill_score_cost: '如 10 = 10 点/次',
  write_price: '如 200 = 2.00 元/篇',
  publish_price: '每篇文章×每个渠道为 1 条',
}
onMounted(async () => { Object.assign(form, await api.get('/api/admin/settings')) })
async function save() {
  err.value = ''
  saving.value = true
  try {
    const patch = {}
    for (const k of Object.keys(form)) {
      const v = Number(form[k])
      if (Number.isFinite(v) && v >= 0) patch[k] = v
    }
    await api.put('/api/admin/settings', patch)
    Object.assign(form, patch)
    msg.value = '已保存'
    clearTimeout(timer)
    timer = setTimeout(() => { msg.value = '' }, 3000)
  } catch (e) { err.value = e.message } finally { saving.value = false }
}
</script>
