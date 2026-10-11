<template>
  <div class="card" style="max-width:640px">
    <h2 class="page-title">实名认证</h2>
    <p>当前状态：<span class="tag" :class="tagClass(curStatus)">{{ curStatusText }}</span></p>
    <p class="muted" v-if="curStatus==='pending'">实名资料已提交，请等待管理员审核通过。</p>
    <p class="muted" v-if="curStatus==='approved'">已通过实名认证，可正常使用写作、发布、数据中心等能力。</p>
    <p class="muted" v-else-if="curStatus==='rejected'">您的实名认证被拒绝，请修改资料后重新提交。</p>
    <template v-if="curStatus!=='approved'">
      <div class="row" style="margin-top:14px">
        <button class="btn" @click="mode='personal'">个人实名认证</button>
        <button class="btn btn-ghost" @click="mode='company'">企业实名认证</button>
      </div>
      <div v-if="mode==='personal'" style="margin-top:16px">
        <div class="form-item"><label>真实姓名</label><input class="input" v-model="p.real_name" /></div>
        <div class="form-item"><label>身份证号码</label><input class="input" v-model="p.id_number" /></div>
        <div class="form-item"><label>手机号</label><input class="input" v-model="p.mobile" /></div>
        <button class="btn" @click="submitP" :disabled="saving">{{ saving ? '提交中…' : '提交认证' }}</button>
      </div>
      <div v-else style="margin-top:16px">
        <div class="form-item"><label>企业名称</label><input class="input" v-model="c.company_name" /></div>
        <div class="form-item"><label>营业执照注册号</label><input class="input" v-model="c.license_no" /></div>
        <div class="form-item"><label>营业执照照片</label><input class="input" v-model="c.license_photo" placeholder="填写图片URL或占位" /></div>
        <div class="form-item"><label>管理员姓名</label><input class="input" v-model="c.admin_name" /></div>
        <div class="form-item"><label>管理员身份证号</label><input class="input" v-model="c.admin_id_number" /></div>
        <div class="form-item"><label>管理员身份证人像面</label><input class="input" v-model="c.admin_id_photo" /></div>
        <button class="btn" @click="submitC" :disabled="saving">{{ saving ? '提交中…' : '提交认证' }}</button>
      </div>
    </template>
    <div class="error" v-if="err">{{ err }}</div>
    <div class="okmsg" v-if="msg">{{ msg }}</div>
  </div>
</template>
<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { api } from '../../api'
const me = inject('me')
const mode = ref('personal')
const p = reactive({})
const c = reactive({})
const info = ref({})
const err = ref('')
const msg = ref('')
const saving = ref(false)
const curStatus = computed(() => info.value.kyc_status || me.value.kyc_status || 'none')
const statusText = { none: '未认证', pending: '审核中', approved: '已通过', rejected: '已拒绝' }
const curStatusText = computed(() => statusText[curStatus.value] || curStatus.value)
function tagClass(s) {
  if (s === 'approved') return 'ok'
  if (s === 'pending') return 'warn'
  if (s === 'rejected') return 'fail'
  return 'off'
}
onMounted(async () => { info.value = await api.get('/api/kyc') })
async function submitP() {
  saving.value = true; err.value = ''
  try { await api.post('/api/kyc/personal', p); info.value.kyc_status = 'pending'; me.value.kyc_status = 'pending'; msg.value = '已提交，状态：审核中' }
  catch (e) { err.value = e.message } finally { saving.value = false }
}
async function submitC() {
  saving.value = true; err.value = ''
  try { await api.post('/api/kyc/company', c); info.value.kyc_status = 'pending'; me.value.kyc_status = 'pending'; msg.value = '已提交，状态：审核中' }
  catch (e) { err.value = e.message } finally { saving.value = false }
}
</script>
