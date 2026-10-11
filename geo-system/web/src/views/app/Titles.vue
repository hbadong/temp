<template>
  <div class="card">
    <div class="toolbar">
      <h2 class="page-title" style="margin:0">写作标题</h2>
      <div class="row">
        <input class="input" style="width:190px" v-model="q" placeholder="搜索标题" @keyup.enter="load" />
        <button class="btn" @click="distillOpen">AI蒸馏生成标题</button>
        <button class="btn" @click="openCreate">+ 新增</button>
      </div>
    </div>
    <p class="muted">选择若干关键词，可由 AI 蒸馏出意图问题作为写作标题</p>
    <div class="error" v-if="err" style="margin-bottom:10px">{{ err }}</div>
    <div class="okmsg" v-if="msg" style="margin-bottom:10px">{{ msg }}</div>
    <table class="table">
      <thead><tr><th>ID</th><th>标题</th><th>关键词ID</th><th>时间</th><th>操作</th></tr></thead>
      <tbody>
        <tr v-for="row in list" :key="row.id">
          <td>{{ row.id }}</td><td>{{ row.title }}</td><td>{{ row.keyword_id }}</td>
          <td class="muted">{{ fmt(row.created_at) }}</td>
          <td>
            <a class="op" href="javascript:;" @click="openEdit(row)">编辑</a>
            <a class="op danger" href="javascript:;" style="margin-left:10px" @click="del(row)">删除</a>
          </td>
        </tr>
        <tr v-if="!list.length"><td colspan="5"><div class="empty">暂无数据</div></td></tr>
      </tbody>
    </table>
    <div class="pager" v-if="total > pageSize">
      <button class="btn btn-ghost" @click="page--; load()" :disabled="page<=1">上一页</button>
      <span>{{ page }} / {{ Math.ceil(total/pageSize) }}</span>
      <button class="btn btn-ghost" @click="page++; load()" :disabled="page>=Math.ceil(total/pageSize)">下一页</button>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
      <div class="modal">
        <div class="modal-head">
          <h3>{{ editing ? '编辑' : '新增' }} · 写作标题</h3>
          <button class="modal-close" @click="showModal=false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-item"><label>标题</label><input class="input" v-model="form.title" /></div>
          <div class="form-item"><label>关键词ID</label><input class="input" v-model="form.keyword_id" /></div>
        </div>
        <div class="modal-foot">
          <button class="btn btn-ghost" @click="showModal=false">取消</button>
          <button class="btn" @click="save" :disabled="saving">{{ saving ? '保存中…' : '保存' }}</button>
        </div>
      </div>
    </div>

    <div v-if="distillOpen" class="modal-overlay" @click.self="distillOpen=false">
      <div class="modal" style="width:460px">
        <div class="modal-head">
          <h3>AI蒸馏生成标题</h3>
          <button class="modal-close" @click="distillOpen=false">×</button>
        </div>
        <div class="modal-body">
          <p class="muted">勾选关键词，蒸馏后每个关键词生成 5 条意图问题写入标题库</p>
          <div v-if="kws.length" class="kw-list">
            <label v-for="k in kws" :key="k.id" class="kw-item">
              <input type="checkbox" :value="k.id" v-model="sel" />
              <span>{{ k.word }}</span>
            </label>
          </div>
          <div v-else class="empty">暂无关键词，请先到「关键词」页添加</div>
          <div class="error" v-if="distillErr">{{ distillErr }}</div>
        </div>
        <div class="modal-foot">
          <button class="btn btn-ghost" @click="distillOpen=false">取消</button>
          <button class="btn" @click="distill" :disabled="distilling">{{ distilling ? '蒸馏中…' : '生成标题' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, reactive, ref } from 'vue'
import { api } from '../../api'
const list = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = 20
const q = ref('')
const err = ref('')
const msg = ref('')
const showModal = ref(false)
const editing = ref(null)
const form = reactive({})
const saving = ref(false)
const distillOpen = ref(false)
const kws = ref([])
const sel = ref([])
const distilling = ref(false)
const distillErr = ref('')

function fmt(t) { return t ? new Date(t * 1000).toLocaleString() : '' }
async function load() {
  err.value = ''
  try {
    const d = await api.get(`/api/titles?page=${page.value}&q=${encodeURIComponent(q.value)}`)
    list.value = d.list || []
    total.value = d.total
  } catch (e) { err.value = e.message }
}
function openCreate() { editing.value = null; Object.keys(form).forEach((k) => delete form[k]); showModal.value = true }
function openEdit(row) { editing.value = row; Object.keys(form).forEach((k) => delete form[k]); Object.assign(form, row); showModal.value = true }
async function save() {
  saving.value = true
  try {
    if (editing.value) await api.put('/api/titles/' + editing.value.id, form)
    else await api.post('/api/titles', form)
    showModal.value = false
    msg.value = '已保存'
    setTimeout(() => { msg.value = '' }, 2000)
    await load()
  } catch (e) { err.value = e.message } finally { saving.value = false }
}
async function del(row) {
  if (!confirm('确认删除？')) return
  try {
    await api.del('/api/titles/' + row.id)
    await load()
  } catch (e) { err.value = e.message }
}
async function distillOpen2() {
  distillOpen.value = true
  distillErr.value = ''
  sel.value = []
  try { kws.value = (await api.get('/api/keywords?page=1')).list } catch (e) { kws.value = [] }
}
async function distill() {
  distillErr.value = ''
  if (!sel.value.length) { distillErr.value = '请至少选择一个关键词'; return }
  distilling.value = true
  try {
    const d = await api.post('/api/titles/distill', { keyword_ids: sel.value })
    msg.value = `蒸馏完成，新增 ${d.count} 条标题`
    setTimeout(() => { msg.value = '' }, 2500)
    distillOpen.value = false
    await load()
  } catch (e) { distillErr.value = e.message } finally { distilling.value = false }
}
onMounted(load)
</script>
<style scoped>
.kw-list { max-height: 260px; overflow: auto; display: flex; flex-direction: column; gap: 6px; margin: 10px 0; }
.kw-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; }
</style>