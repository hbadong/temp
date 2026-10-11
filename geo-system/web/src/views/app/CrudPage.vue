<template>
  <div class="card">
    <div class="toolbar">
      <div class="bar-left">
        <h2 class="page-title" style="margin:0">{{ cfg.title }}</h2>
      </div>
      <div class="row">
        <input v-if="cfg.search" class="input" style="width:210px" v-model="q" :placeholder="cfg.search" @keyup.enter="load" />
        <button v-if="cfg.create && !cfg.noCreate" class="btn" @click="openCreate">+ 新增</button>
      </div>
    </div>
    <p class="muted" v-if="cfg.desc">{{ cfg.desc }}</p>
    <div class="error" v-if="err" style="margin-bottom:10px">{{ err }}</div>
    <div class="okmsg" v-if="msg" style="margin-bottom:10px">{{ msg }}</div>

    <div v-if="cfg.formOnly" class="form-item">
      <textarea class="input" v-model="formText" :placeholder="cfg.placeholder" rows="6"></textarea>
      <button class="btn" style="margin-top:10px" @click="submitForm" :disabled="submitting">{{ submitting ? '提交中…' : cfg.submitText }}</button>
    </div>

    <template v-else>
      <table class="table">
        <thead><tr><th v-for="c in cfg.cols" :key="c.key">{{ c.label }}</th><th v-if="cfg.actions">操作</th></tr></thead>
        <tbody>
          <tr v-for="row in list" :key="row.id">
            <td v-for="c in cfg.cols" :key="c.key">
              <span v-if="isStatus(c)" class="tag" :class="tagClass(row[c.key])">{{ row[c.key] || '-' }}</span>
              <a v-else-if="isLink(c)" class="op" :href="row[c.key]" target="_blank" rel="noopener">{{ row[c.key] }}</a>
              <span v-else-if="isMuted(c)" class="muted">{{ row[c.key] || '-' }}</span>
              <span v-else-if="isDelta(c)" :class="deltaClass(row[c.key])">{{ display(row, c) }}</span>
              <span v-else>{{ display(row, c) }}</span>
            </td>
            <td v-if="cfg.actions">
              <a v-if="cfg.edit" class="op" href="javascript:;" @click="openEdit(row)">编辑</a>
              <a v-if="cfg.remove" class="op danger" href="javascript:;" style="margin-left:10px" @click="remove(row)">删除</a>
              <a v-if="cfg.pick" class="op" href="javascript:;" style="margin-left:10px" @click="pick(row)">{{ cfg.pickLabel || '选择' }}</a>
              <a v-if="cfg.retry && row.status === 'failed'" class="op" href="javascript:;" style="margin-left:10px" @click="retry(row)">重试</a>
            </td>
          </tr>
          <tr v-if="!loading && list.length === 0">
            <td :colspan="(cfg.cols.length || 1) + (cfg.actions ? 1 : 0)">
              <div class="empty">暂无数据</div>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="pager" v-if="total > pageSize">
        <button class="btn btn-ghost" @click="page--; load()" :disabled="page<=1">上一页</button>
        <span>{{ page }} / {{ Math.ceil(total/pageSize) }}</span>
        <button class="btn btn-ghost" @click="page++; load()" :disabled="page>=Math.ceil(total/pageSize)">下一页</button>
      </div>
    </template>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
      <div class="modal">
        <div class="modal-head">
          <h3>{{ editing ? '编辑' : '新增' }} · {{ cfg.title }}</h3>
          <button class="modal-close" @click="showModal=false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-item" v-for="f in cfg.fields" :key="f.key">
            <label>{{ f.label }}</label>
            <textarea v-if="f.type==='textarea'" class="input" v-model="form[f.key]"></textarea>
            <input v-else class="input" v-model="form[f.key]" :placeholder="f.label" />
          </div>
          <div v-if="costHint" class="costbox">预计扣费：{{ costHint }}</div>
          <div class="error" v-if="modalErr">{{ modalErr }}</div>
        </div>
        <div class="modal-foot">
          <button class="btn btn-ghost" @click="showModal=false">取消</button>
          <button class="btn" @click="save" :disabled="saving">{{ saving ? '保存中…' : '保存' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { api } from '../../api'

const props = defineProps({ page: String })
const pages = {
  keywords: { title: '关键词', search: '搜索关键词', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'word', label: '关键词' }, { key: 'created_at', label: '时间', time: true }], fields: [{ key: 'word', label: '关键词' }], list: '/api/keywords', createUrl: '/api/keywords' },
  titles: { title: '写作标题', search: '搜索标题', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'title', label: '标题' }], fields: [{ key: 'title', label: '标题' }, { key: 'keyword_id', label: '关键词ID' }], list: '/api/titles', createUrl: '/api/titles' },
  images: { title: '企业画像图库', create: true, remove: true, actions: true, cols: [{ key: 'name', label: '分组' }, { key: 'kind', label: '类型' }], fields: [{ key: 'name', label: '分组名' }, { key: 'kind', label: '类型(1图库/2视频)' }], list: '/api/image-groups', createUrl: '/api/image-groups' },
  knowledge: { title: '企业知识库', search: '搜索', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'title', label: '标题' }], fields: [{ key: 'title', label: '标题' }, { key: 'body', label: '正文', type: 'textarea' }], list: '/api/knowledge', createUrl: '/api/knowledge' },
  instructions: { title: '写作指令', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'name', label: '名称' }, { key: 'status', label: '状态' }], fields: [{ key: 'name', label: '名称' }, { key: 'body', label: '提示词', type: 'textarea' }], list: '/api/instructions', createUrl: '/api/instructions' },
  'article-types': { title: '文章分类', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'name', label: '分类' }], fields: [{ key: 'name', label: '分类名' }], list: '/api/article-types', createUrl: '/api/article-types' },
  'write-tasks': { title: '创作任务', create: true, poll: true, actions: true, retry: true, retryKey: 'id', cols: [{ key: 'id', label: 'ID' }, { key: 'status', label: '状态' }, { key: 'type', label: '类型' }, { key: 'error_message', label: '失败原因', muted: true }], fields: [{ key: 'keyword', label: '关键词/标题' }, { key: 'count', label: '数量' }, { key: 'instruction_id', label: '指令ID' }, { key: 'type_id', label: '分类ID' }], list: '/api/write-tasks', createUrl: '/api/write-tasks', price: { kind: 'write', unitKey: 'write', desc: '按篇计费' } },
  articles: { title: '文章列表', search: '搜索标题', edit: true, remove: true, actions: true, cols: [{ key: 'title', label: '标题' }, { key: 'keyword', label: '关键词' }, { key: 'source', label: '来源' }], fields: [{ key: 'title', label: '标题' }, { key: 'content', label: '正文', type: 'textarea' }, { key: 'keyword', label: '关键词' }], list: '/api/articles', createUrl: '/api/articles', noCreate: true },
  reprint: { title: '全网爆文复刻', formOnly: true, placeholder: '输入源 URL 或标题', submitText: '开始复刻', submitUrl: '/api/reprint', submitKey: 'source' },
  'reprint-batch': { title: '批量爆文复刻', formOnly: true, placeholder: '每行一条源标题或 URL', submitText: '批量复刻', submitUrl: '/api/reprint/batch', submitKey: 'text' },
  tdk: { title: '智能TDK分组', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'name', label: '分组' }], fields: [{ key: 'name', label: '分组名' }], list: '/api/tdk', createUrl: '/api/tdk' },
  'video-mix': { title: '视频混剪任务', create: true, actions: true, cols: [{ key: 'id', label: 'ID' }, { key: 'kind', label: '类型' }, { key: 'status', label: '状态' }, { key: 'count', label: '数量' }], fields: [{ key: 'count', label: '输出数量' }, { key: 'tdk_id', label: 'TDK分组ID' }], list: '/api/video-jobs', createUrl: '/api/video-mix' },
  'video-jobs': { title: '视频任务', poll: true, actions: true, retry: true, retryKey: 'task_id', cols: [{ key: 'id', label: 'ID' }, { key: 'kind', label: '类型' }, { key: 'status', label: '状态' }], list: '/api/video-jobs' },
  'video-records': { title: '视频发布记录', cols: [{ key: 'title', label: '标题' }, { key: 'platform', label: '平台' }, { key: 'status', label: '状态' }, { key: 'error', label: '失败原因' }], list: '/api/video-records' },
  'video-assets': { title: '视频素材库', cols: [{ key: 'filename', label: '文件' }, { key: 'kind', label: '类型' }], list: '/api/video-assets' },
  b2b: { title: 'B2B行业网站', pick: true, pickLabel: '加入发布', actions: true, cols: [{ key: 'name', label: '站点' }, { key: 'industry', label: '行业' }, { key: 'status', label: '状态' }], list: '/api/channels/b2b', extra: 'channel' },
  'b2b-tasks': { title: 'B2B自动发布任务', create: true, poll: true, actions: true, retry: true, retryKey: 'task_id', cols: [{ key: 'id', label: 'ID' }, { key: 'status', label: '状态' }, { key: 'kind', label: '渠道' }], fields: [{ key: 'article_ids', label: '文章ID,逗号分隔' }, { key: 'channel_ids', label: '站点ID,逗号分隔' }, { key: 'contact_id', label: '联系方式ID' }], list: '/api/publish/b2b/jobs', createUrl: '/api/publish/b2b', parseIds: true, price: { kind: 'publish', unitKey: 'publish', desc: '每篇文章×每个渠道为 1 条' } },
  contacts: { title: '联系方式管理', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'name', label: '联系人' }, { key: 'phone', label: '电话' }, { key: 'email', label: '邮箱' }, { key: 'company', label: '公司' }], fields: [{ key: 'name', label: '联系人' }, { key: 'phone', label: '电话' }, { key: 'email', label: '邮箱' }, { key: 'company', label: '公司' }], list: '/api/contacts', createUrl: '/api/contacts' },
  'b2b-records': { title: 'B2B发布记录', cols: [{ key: 'channel_name', label: '站点' }, { key: 'article_title', label: '文章' }, { key: 'status', label: '状态' }, { key: 'url', label: 'URL' }, { key: 'error', label: '失败原因' }], list: '/api/publish/b2b/records' },
  webmedia: { title: '单条发布媒体', cols: [{ key: 'name', label: '媒体' }, { key: 'industry', label: '行业' }], list: '/api/channels/webmedia' },
  'webmedia-tasks': { title: 'AI智能发布任务', create: true, poll: true, actions: true, retry: true, retryKey: 'task_id', cols: [{ key: 'id', label: 'ID' }, { key: 'status', label: '状态' }], fields: [{ key: 'article_ids', label: '文章ID,逗号分隔' }, { key: 'channel_ids', label: '媒体ID,逗号分隔' }], list: '/api/publish/webmedia/jobs', createUrl: '/api/publish/webmedia', parseIds: true, price: { kind: 'publish', unitKey: 'publish', desc: '每篇文章×每个渠道为 1 条' } },
  'webmedia-records': { title: '投稿记录', cols: [{ key: 'channel_name', label: '媒体' }, { key: 'article_title', label: '文章' }, { key: 'status', label: '状态' }, { key: 'url', label: 'URL' }], list: '/api/publish/webmedia/records' },
  'media-favs-1': { title: '收藏分组', create: true, remove: true, actions: true, cols: [{ key: 'name', label: '分组' }], fields: [{ key: 'name', label: '名称' }, { key: 'kind', label: '类型(1媒体)' }], list: '/api/media-favs', createUrl: '/api/media-favs' },
  zimedia: { title: '发布自媒体', cols: [{ key: 'name', label: '账号' }, { key: 'industry', label: '类型' }], list: '/api/channels/zimedia' },
  'zimedia-records': { title: '投稿记录', cols: [{ key: 'channel_name', label: '账号' }, { key: 'article_title', label: '文章' }, { key: 'status', label: '状态' }, { key: 'url', label: 'URL' }], list: '/api/publish/zimedia/records' },
  'media-favs-2': { title: '收藏分组', create: true, remove: true, actions: true, cols: [{ key: 'name', label: '分组' }], fields: [{ key: 'name', label: '名称' }, { key: 'kind', label: '类型(2自媒体)' }], list: '/api/media-favs', createUrl: '/api/media-favs' },
  'weixin-auth': { title: '账号授权', create: true, cols: [{ key: 'account_name', label: '账号' }, { key: 'status', label: '状态' }], fields: [{ key: 'account_name', label: '账号名' }], list: '/api/weixin/auth', createUrl: '/api/weixin/auth' },
  'weixin-tasks': { title: '发布任务', create: true, poll: true, actions: true, retry: true, retryKey: 'task_id', cols: [{ key: 'id', label: 'ID' }, { key: 'status', label: '状态' }], fields: [{ key: 'article_ids', label: '文章ID,逗号分隔' }, { key: 'channel_ids', label: '渠道ID(授权账号),逗号分隔' }], list: '/api/publish/weixin/jobs', createUrl: '/api/publish/weixin', parseIds: true, price: { kind: 'publish', unitKey: 'publish', desc: '每篇文章×每个授权账号为 1 条' } },
  'weixin-records': { title: '发布记录', cols: [{ key: 'article_title', label: '文章' }, { key: 'status', label: '状态' }, { key: 'url', label: 'URL' }], list: '/api/publish/weixin/records' },
  sites: { title: '站点管理', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'name', label: '名称' }, { key: 'domain', label: '域名' }], fields: [{ key: 'name', label: '名称' }, { key: 'domain', label: '域名' }], list: '/api/sites', createUrl: '/api/sites' },
  'site-tasks': { title: 'SEO发布任务', create: true, poll: true, cols: [{ key: 'id', label: 'ID' }], fields: [{ key: 'article_ids', label: '文章ID,逗号分隔' }, { key: 'site_ids', label: '站点ID,逗号分隔' }], list: '/api/site-publish/records', createUrl: '/api/site-publish', parseIds: true, site: true },
  'site-records': { title: '发布记录', cols: [{ key: 'site_name', label: '站点' }, { key: 'article_title', label: '文章' }, { key: 'url', label: 'URL' }, { key: 'status', label: '状态' }], list: '/api/site-publish/records' },
  stats: { title: '数据报表', cols: [{ key: 'day', label: '日期' }, { key: 'created', label: '创作' }, { key: 'published', label: '发布' }, { key: 'success', label: '成功' }, { key: 'failed', label: '失败' }], list: '/api/stats?days=7', series: true },
  inclusion: { title: '查询(带截图)', formOnly: true, placeholder: '关键词或 URL', submitText: '查询', submitUrl: '/api/inclusion', submitKey: 'query' },
  'inclusion-records': { title: '查询记录', cols: [{ key: 'query', label: '查询' }, { key: 'included', label: '是否收录' }, { key: 'snippet', label: '摘要' }], list: '/api/inclusion' },
  'score-log': { title: '点数明细', cols: [{ key: 'created_at', label: '时间', time: true }, { key: 'type', label: '类型' }, { key: 'delta', label: '变动' }, { key: 'balance_after', label: '剩余' }, { key: 'remark', label: '备注' }], list: '/api/ledger/score' },
  'money-log': { title: '余额明细', cols: [{ key: 'created_at', label: '时间', time: true }, { key: 'type', label: '类型' }, { key: 'delta', label: '变动(分)' }, { key: 'balance_after', label: '余额' }, { key: 'remark', label: '备注' }], list: '/api/ledger/money' },
  entitlements: { title: '账号权益', cols: [{ key: 'plan', label: '套餐' }, { key: 'expire', label: '到期', time: true }, { key: 'daily_write_quota', label: '日写作配额' }, { key: 'daily_pub_quota', label: '日发布配额' }], list: '/api/entitlements', single: true },
  proxy: { title: '全局代理IP', create: true, edit: true, remove: true, actions: true, cols: [{ key: 'ip', label: 'IP' }, { key: 'port', label: '端口' }, { key: 'protocol', label: '协议' }, { key: 'status', label: '状态' }], fields: [{ key: 'ip', label: 'IP' }, { key: 'port', label: '端口' }, { key: 'protocol', label: '协议' }, { key: 'account', label: '账号' }, { key: 'password', label: '密码' }], list: '/api/proxy', createUrl: '/api/proxy' },
}

const cfg = pages[props.page] || { title: props.page, cols: [], list: '/api/health' }
const list = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = 20
const q = ref('')
const err = ref('')
const msg = ref('')
const modalErr = ref('')
const showModal = ref(false)
const editing = ref(null)
const form = reactive({})
const formText = ref('')
const loading = ref(false)
const saving = ref(false)
const submitting = ref(false)
const prices = ref({})
let errTimer = null
let msgTimer = null
let pollTimer = null

function display(row, c) {
  let v = row[c.key]
  if (c.time && v) return new Date((v > 1e12 ? v : v * 1000)).toLocaleString()
  return v
}

function isStatus(c) { return c.key === 'status' || String(c.key).endsWith('_status') }
function isLink(c) { return c.key === 'url' }
function isMuted(c) { return c.key === 'error' || c.key === 'remark' || c.key === 'snippet' }
function isDelta(c) { return c.key === 'delta' || (c.label || '').includes('变动') }
function tagClass(v) {
  if (!v) return 'off'
  const s = String(v).toLowerCase()
  if (['success', 'ok', 'approved', 'active', 'done', 'succeed', 'succeeded', 'published'].some((x) => s.includes(x))) return 'ok'
  if (['queued', 'running', 'pending', 'wait', 'submitting'].some((x) => s.includes(x))) return 'warn'
  if (['failed', 'fail', 'error', 'rejected', 'cancel', 'invalid'].some((x) => s.includes(x))) return 'fail'
  if (['valid', 'enabled'].some((x) => s.includes(x))) return 'ok'
  return ''
}
function deltaClass(v) {
  const n = Number(v)
  if (v !== '' && Number.isFinite(n)) {
    if (n > 0) return 'ok'
    if (n < 0) return 'error'
  }
  return ''
}

function flash(key, text) {
  if (key === 'msg') {
    err.value = ''
    msg.value = text
    clearTimeout(msgTimer)
    msgTimer = setTimeout(() => { msg.value = '' }, 3000)
  } else {
    msg.value = ''
    err.value = text
    modalErr.value = text
    clearTimeout(errTimer)
    errTimer = setTimeout(() => { err.value = ''; modalErr.value = '' }, 6000)
  }
}

async function load() {
  loading.value = true
  err.value = ''
  try {
    if (cfg.single) {
      const d = await api.get(cfg.list)
      list.value = [d]
      total.value = 1
      return
    }
    if (cfg.series) {
      const d = await api.get(cfg.list)
      list.value = d.series || []
      total.value = list.value.length
      return
    }
    const d = await api.get(cfg.list + (cfg.list.includes('?') ? '&' : '?') + `page=${page.value}&q=${encodeURIComponent(q.value)}`)
    list.value = d.list || []
    total.value = d.total || list.value.length
  } catch (e) { err.value = e.message } finally { loading.value = false }
}

function splitIds(v) {
  return String(v || '').split(',').map((x) => parseInt(x, 10)).filter((x) => x > 0)
}

const costHint = computed(() => {
  const pm = cfg.price
  if (!pm) return ''
  const unit = parseInt(prices.value[pm.unitKey] || 0, 10)
  if (!unit) return ''
  let count = 0
  if (pm.kind === 'write') {
    count = parseInt(form.count, 10) || 1
    const total = unit * count
    return `${count} 篇 × ${unit}分 = ${total}分（${(total / 100).toFixed(2)} 元）`
  }
  const a = splitIds(form.article_ids).length
  const c = splitIds(form.channel_ids).length
  const total = unit * a * c
  if (!a || !c) return `${pm.desc}，单价 ${unit}分/条`
  return `${a} 篇 × ${c} 个渠道 = ${a * c} 条 × ${unit}分 = ${total}分（${(total / 100).toFixed(2)} 元）`
})

function startPoll() {
  if (!cfg.poll || pollTimer) return
  pollTimer = setInterval(() => {
    const running = list.value.some((r) => r.status === 'queued' || r.status === 'running')
    if (!running) {
      clearInterval(pollTimer)
      pollTimer = null
      return
    }
    load()
  }, 2500)
}

function openCreate() {
  editing.value = null
  modalErr.value = ''
  Object.keys(form).forEach((k) => delete form[k])
  showModal.value = true
  startPoll()
}
function openEdit(row) {
  editing.value = row
  modalErr.value = ''
  Object.keys(form).forEach((k) => delete form[k])
  Object.assign(form, row)
  showModal.value = true
}
async function save() {
  modalErr.value = ''
  saving.value = true
  try {
    let body = { ...form }
    if (cfg.parseIds) {
      if (body.article_ids) body.article_ids = splitIds(body.article_ids)
      if (body.channel_ids) body.channel_ids = splitIds(body.channel_ids)
      if (body.site_ids) body.site_ids = splitIds(body.site_ids)
      body.count = parseInt(body.count, 10) || 1
    }
    if (editing.value && cfg.createUrl) await api.put(cfg.createUrl + '/' + editing.value.id, body)
    else await api.post(cfg.createUrl, body)
    showModal.value = false
    flash('msg', '已保存')
    await load()
    startPoll()
  } catch (e) { flash('err', e.message) } finally { saving.value = false }
}
async function remove(row) {
  if (!confirm('确认删除？')) return
  try {
    await api.del((cfg.createUrl || cfg.list) + '/' + row.id)
    flash('msg', '已删除')
    await load()
  } catch (e) { flash('err', e.message) }
}
async function submitForm() {
  err.value = ''
  submitting.value = true
  try {
    await api.post(cfg.submitUrl, { [cfg.submitKey]: formText.value })
    flash('msg', '已提交，可在「创作任务」查看进度')
    formText.value = ''
  } catch (e) { flash('err', e.message) } finally { submitting.value = false }
}
function pick() { flash('msg', '已记录，请到发布任务中填写对应 ID') }
async function retry(row) {
  if (!confirm('重试该失败任务？')) return
  try {
    const rid = row[cfg.retryKey || 'id']
    await api.post('/api/tasks/' + rid + '/retry')
    flash('msg', '已重新入队')
    await load()
    startPoll()
  } catch (e) { flash('err', e.message) }
}

watch(() => props.page, () => { page.value = 1; load() })
onMounted(async () => {
  if (cfg.price) {
    try { prices.value = await api.get('/api/prices') } catch {}
  }
  await load()
})
onBeforeUnmount(() => {
  clearTimeout(errTimer)
  clearTimeout(msgTimer)
  clearInterval(pollTimer)
})
</script>