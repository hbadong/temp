<template>
  <div class="card">
    <h2 class="page-title">AI可见度诊断</h2>
    <div class="form-item">
      <label>诊断AI大模型平台</label>
      <div class="row">
        <button type="button" v-for="p in platforms" :key="p.id" class="btn" :class="{ 'btn-ghost': !selected.includes(p.id) }" @click="toggle(p.id)">{{ p.name }}</button>
      </div>
    </div>
    <div class="form-item">
      <label>一、品牌名称</label>
      <input class="input" v-model="brand" placeholder="如: 美的，小米" />
      <div class="muted">多个品牌请用英文逗号隔开，建议填写品牌全程+简称</div>
    </div>
    <div class="form-item">
      <label>二、行业词</label>
      <input class="input" v-model="industryInput" placeholder="如：喷墨打印机，AI诊断软件" @keydown.enter.prevent="addIndustry" />
      <div class="row" style="margin-top:8px">
        <span class="tag" v-for="(k,i) in industries" :key="i">{{ k }} <a href="javascript:;" @click="industries.splice(i,1)">x</a></span>
      </div>
      <div class="muted">多个行业词请按回车键，一次性最多支持输入 {{ industries.length }}/3 个词</div>
    </div>
    <div class="form-item">
      <label>三、是否需要优化建议</label>
      <label><input type="radio" :value="0" v-model="advice" /> 无需优化建议</label>
      <label style="margin-left:12px"><input type="radio" :value="1" v-model="advice" /> 需生成建议报告</label>
    </div>
    <button class="btn" @click="submit">创建AI诊断任务（消耗 {{ (price/100).toFixed(2) }} 元）</button>
    <div class="error" v-if="err">{{ err }}</div>
    <div class="okmsg" v-if="msg">{{ msg }}</div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../api'
const router = useRouter()
const platforms = [
  { id: 'doubao', name: '豆包' }, { id: 'yuanbao', name: '元宝' }, { id: 'tongyi', name: '通义' },
  { id: 'wenxin', name: '文心' }, { id: 'nami', name: '纳米' }, { id: 'zhipu', name: '智谱' },
]
const selected = ref(['doubao', 'yuanbao', 'tongyi'])
const brand = ref('')
const industryInput = ref('')
const industries = ref([])
const advice = ref(0)
const prices = ref({ diagnose: 10000, diagnoseAdvice: 10200 })
const err = ref('')
const msg = ref('')
const price = computed(() => advice.value ? prices.value.diagnoseAdvice : prices.value.diagnose)
onMounted(async () => { prices.value = await api.get('/api/prices') })
function toggle(id) {
  const i = selected.value.indexOf(id)
  if (i >= 0) selected.value.splice(i, 1)
  else selected.value.push(id)
}
function addIndustry() {
  const v = industryInput.value.trim()
  if (!v) return
  if (industries.value.length >= 3) { err.value = '一次性最多支持 3 个词'; return }
  industries.value.push(v)
  industryInput.value = ''
}
async function submit() {
  err.value = ''; msg.value = ''
  if (!selected.value.length) { err.value = '请至少选择一个AI大模型平台'; return }
  if (!brand.value.trim()) { err.value = '请输入品牌'; return }
  if (!industries.value.length) { err.value = '请输入行业'; return }
  if (!confirm('提交诊断将扣除一定的余额，不可取消，确定提交诊断吗？')) return
  try {
    await api.post('/api/analyze', { brand: brand.value, platforms: selected.value, keyword: industries.value, is_jianyi: advice.value })
    msg.value = '创建成功'
    setTimeout(() => router.push('/app/analyze-reports'), 800)
  } catch (e) { err.value = e.message }
}
</script>
