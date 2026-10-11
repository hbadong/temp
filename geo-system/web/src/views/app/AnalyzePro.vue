<template>
  <div class="card">
    <h2 class="page-title">AI可见度诊断【PRO版】</h2>
    <div class="form-item">
      <label>一、品牌名称</label>
      <div class="row">
        <input class="input" v-model="brand_name" placeholder="如: 美的空调" />
        <button class="btn" type="button" @click="parse">品牌全网分析</button>
      </div>
    </div>
    <div class="form-item"><label>二、企业/品牌全称</label><input class="input" v-model="full_name" placeholder="如：xxx公司  - 如未自动生成，请手动填写" /></div>
    <div class="form-item"><label>三、行业</label><input class="input" v-model="industry" placeholder="如：AI营销/生成式引擎优化（GEO）/企业数字化营销科技" /></div>
    <div class="form-item"><label>四、服务地区</label><input class="input" v-model="region" placeholder="如：服务全球 - 如未自动生成，请手动填写" /></div>
    <div class="form-item"><label>五、竞品公司</label><input class="input" v-model="competitors" placeholder="如：小米，比亚迪 - 如未自动生成，请手动填写" /></div>
    <button class="btn" @click="submit">创建AI诊断任务</button>
    <div class="error" v-if="err">{{ err }}</div>
    <div class="okmsg" v-if="msg">{{ msg }}</div>

    <div v-if="busy" class="loading-box">
      <div class="spinner"></div>
      <div class="loading-tip">{{ tip }}</div>
    </div>
  </div>
</template>
<script setup>
import { onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../api'
const router = useRouter()
const brand_name = ref('')
const full_name = ref('')
const industry = ref('')
const region = ref('')
const competitors = ref('')
const err = ref('')
const msg = ref('')
const busy = ref(false)
const tip = ref('正在搜索全网信息…')
const tips = ['正在搜索全网信息…', '正在抓取品牌百科与新闻…', '正在分析行业分类…', '正在识别竞品公司…']
let tipTimer = null
function startTip() {
  busy.value = true
  let i = 0
  tip.value = tips[0]
  tipTimer = setInterval(() => { i = (i + 1) % tips.length; tip.value = tips[i] }, 1200)
}
function stopTip() {
  busy.value = false
  clearInterval(tipTimer)
  tipTimer = null
}
async function parse() {
  err.value = ''
  if (!confirm('提交分析将扣除一定的余额，不可取消，确定分析品牌吗？')) return
  if (busy.value) return
  startTip()
  try {
    const d = await api.post('/api/analyze/pro/parse', { brand_name: brand_name.value })
    full_name.value = d.fullName
    industry.value = d.industry
    region.value = d.region
    competitors.value = d.competitors
    msg.value = '分析成功'
  } catch (e) { err.value = e.message } finally { stopTip() }
}
async function submit() {
  err.value = ''
  if (busy.value) return
  if (!confirm('提交诊断将扣除一定的余额，不可取消，确定诊断可见度吗？')) return
  try {
    await api.post('/api/analyze/pro', { brand_name: brand_name.value, full_name: full_name.value, industry: industry.value, region: region.value, competitors: competitors.value })
    msg.value = '创建成功'
    setTimeout(() => router.push('/app/analyze-reports'), 800)
  } catch (e) { err.value = e.message }
}
onBeforeUnmount(() => clearInterval(tipTimer))
</script>

<style scoped>
.loading-box {
  position: fixed; inset: 0; z-index: 50; background: rgba(23,28,48,.55);
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px;
}
.spinner {
  width: 44px; height: 44px; border-radius: 50%;
  border: 4px solid rgba(255,255,255,.25); border-top-color: #fff;
  animation: spin 1s linear infinite;
}
.loading-tip { color: #fff; font-size: 15px; font-weight: 600; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
