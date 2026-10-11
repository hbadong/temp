<template>
  <div class="card">
    <div class="toolbar">
      <h2 class="page-title" style="margin:0">数据报表</h2>
      <div class="row">
        <button class="btn" :class="{ 'btn-ghost': days !== 7 }" @click="setDays(7)">近7天</button>
        <button class="btn" :class="{ 'btn-ghost': days !== 30 }" @click="setDays(30)">近30天</button>
      </div>
    </div>
    <p class="muted">按日汇总创作量、发布量、成功量与失败量</p>
    <div class="error" v-if="err" style="margin-bottom:10px">{{ err }}</div>
    <table class="table">
      <thead><tr><th>日期</th><th>创作量</th><th>发布量</th><th>成功</th><th>失败</th></tr></thead>
      <tbody>
        <tr v-for="row in list" :key="row.day">
          <td>{{ row.day }}</td><td>{{ row.created }}</td><td>{{ row.published }}</td>
          <td><span class="tag ok">{{ row.success }}</span></td>
          <td><span class="tag fail">{{ row.failed }}</span></td>
        </tr>
        <tr v-if="!list.length"><td colspan="5"><div class="empty">暂无数据</div></td></tr>
      </tbody>
    </table>
    <div class="grid" style="grid-template-columns:1fr 1fr;margin-top:16px" v-if="list.length">
      <div class="card">
        <h3 style="font-size:15px;margin:0 0 10px">近{{ days }}日创作/发布走势</h3>
        <div style="height:220px"><canvas ref="cv"></canvas></div>
      </div>
      <div class="card">
        <h3 style="font-size:15px;margin:0 0 10px">发布成功率</h3>
        <div style="height:220px"><canvas ref="cv2"></canvas></div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'
import { Chart, BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js'
Chart.register(BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend)
const days = ref(7)
const list = ref([])
const err = ref('')
const cv = ref()
const cv2 = ref()
let chart = null
let chart2 = null
let cvs = []
let cvs2 = []
function render() {
  const labels = list.value.map((r) => r.day)
  const created = list.value.map((r) => r.created)
  const published = list.value.map((r) => r.published)
  const success = list.value.map((r) => r.success)
  const failed = list.value.map((r) => r.failed)
  const succRate = list.value.map((r) => {
    const total = r.success + r.failed
    return total ? Math.round((r.success / total) * 100) : 0
  })
  if (chart) chart.destroy()
  if (chart2) chart2.destroy()
  chart = new Chart(cv.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: '创作量', data: created, backgroundColor: '#4f6bff', borderRadius: 6, maxBarThickness: 22 },
        { label: '发布量', data: published, backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 22 },
      ],
    },
    options: { responsive: true, maintainAspectRatio: false },
  })
  chart2 = new Chart(cv2.value, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: '成功数', data: success, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', tension: .3 },
        { label: '失败数', data: failed, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.1)', tension: .3 },
        { label: '成功率%', data: succRate, borderColor: '#f59e0b', borderDash: [4,4], tension: .3, yAxisID: 'y1' },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      scales: { y: { beginAtZero: true }, y1: { position: 'right', min: 0, max: 100 } },
    },
  })
}
async function load() {
  err.value = ''
  try {
    const d = await api.get('/api/stats?days=' + days.value)
    list.value = d.series || []
    render()
  } catch (e) { err.value = e.message }
}
function setDays(v) {
  days.value = v
  load()
}
onMounted(load)
</script>