<template>
  <div class="il">
    <div class="card il-groups">
      <div class="toolbar">
        <h2 class="page-title" style="margin:0">企业画像图库</h2>
      </div>
      <div class="row" style="margin-top:12px">
        <input class="input" style="flex:1" v-model="newGroup" placeholder="新分组名" @keyup.enter="addGroup" />
        <button class="btn" @click="addGroup" :disabled="busy">+ 分组</button>
      </div>
      <div class="gl">
        <div v-for="g in groups" :key="g.id" class="gi" :class="{ on: g.id === groupId }" @click="pick(g)">
          <span class="txt">{{ g.name }}</span>
          <a class="op danger" href="javascript:;" @click.stop="delGroup(g)">删</a>
        </div>
        <div v-if="!groups.length" class="empty">暂无分组，请先创建</div>
      </div>
    </div>

    <div class="card il-main">
      <div class="toolbar">
        <h3 style="margin:0">{{ currentGroup }} 图片</h3>
        <label class="btn">
          {{ uploading ? '上传中…' : '+ 上传图片' }}
          <input type="file" accept="image/*" multiple style="display:none" :disabled="uploading" @change="upload" />
        </label>
      </div>
      <p class="muted">支持 jpg / png / bmp / jpeg / gif，单文件不超过 500MB 限额</p>
      <div class="img-grid">
        <figure v-for="im in images" :key="im.id" class="img-item">
          <img :src="im.path" :alt="im.filename" @click="preview = im.path" />
          <figcaption>
            <span class="muted">{{ im.filename }}</span>
            <a class="op danger" href="javascript:;" @click="delImage(im)">删除</a>
          </figcaption>
        </figure>
        <div v-if="!images.length && !loading" class="empty">该分组暂无图片</div>
      </div>
    </div>

    <div v-if="preview" class="modal-overlay" @click.self="preview=''">
      <div class="modal" style="max-width:720px;background:#fff;text-align:center">
        <div class="modal-head"><h3>图片预览</h3><button class="modal-close" @click="preview=''">×</button></div>
        <img :src="preview" style="max-width:100%;max-height:70vh" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../../api'
const groups = ref([])
const newGroup = ref('')
const groupId = ref(0)
const images = ref([])
const uploading = ref(false)
const loading = ref(false)
const busy = ref(false)
const preview = ref('')
const currentGroup = computed(() => groups.value.find((g) => g.id === groupId.value)?.name || '全部图片')

async function loadGroups() {
  groups.value = (await api.get('/api/image-groups?kind=1')).list
  if (!groupId.value && groups.value.length) groupId.value = groups.value[0].id
  await loadImages()
}
async function loadImages() {
  loading.value = true
  try {
    const d = await api.get(groupId.value ? `/api/images?group_id=${groupId.value}` : '/api/images')
    images.value = d.list.map((im) => ({ ...im, path: im.path.startsWith('http') ? im.path : '/api/files/' + im.path }))
  } catch (e) { /* noop */ } finally { loading.value = false }
}
async function addGroup() {
  const name = newGroup.value.trim()
  if (!name) return
  busy.value = true
  try {
    const g = await api.post('/api/image-groups', { name, kind: 1 })
    groups.value.unshift(g)
    groupId.value = g.id
    newGroup.value = ''
    await loadImages()
  } catch (e) { alert(e.message) } finally { busy.value = false }
}
async function delGroup(g) {
  if (!confirm(`删除分组「${g.name}」及其图片？`)) return
  try {
    await api.del('/api/image-groups/' + g.id)
    groups.value = groups.value.filter((x) => x.id !== g.id)
    if (groupId.value === g.id) { groupId.value = groups.value[0]?.id || 0 }
    await loadImages()
  } catch (e) { alert(e.message) }
}
async function delImage(im) {
  if (!confirm('删除该图片？')) return
  try {
    await api.del('/api/images/' + im.id)
    await loadImages()
  } catch (e) { alert(e.message) }
}
async function pick(g) {
  groupId.value = g.id
  await loadImages()
}
async function upload(e) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  if (!groupId.value) { alert('请先创建并选择分组'); return }
  uploading.value = true
  try {
    for (const f of files) {
      const res = await fetch('/api/uploads', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'x-filename': encodeURIComponent(f.name),
          'x-group-id': String(groupId.value),
          'x-kind': 'image',
        },
        body: f,
      })
      const data = await res.json()
      if (data.code !== 1) throw new Error(data.msg || '上传失败')
    }
    await loadImages()
  } catch (err) { alert(err.message) } finally {
    uploading.value = false
    e.target.value = ''
  }
}
onMounted(loadGroups)
</script>
<style scoped>
.il { display: grid; grid-template-columns: 240px 1fr; gap: 16px; align-items: start; }
.gl { margin-top: 12px; display: flex; flex-direction: column; gap: 6px; }
.gi { display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; border-radius: 10px; border: 1px solid var(--border); cursor: pointer; font-size: 13px; }
.gi.on { background: var(--primary-gradient); color: #fff; border-color: transparent; }
.gi .txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.img-grid { margin-top: 12px; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
.img-item { margin: 0; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: #fff; }
.img-item img { width: 100%; height: 110px; object-fit: cover; cursor: pointer; display: block; }
.img-item figcaption { display: flex; align-items: center; justify-content: space-between; gap: 6px; padding: 7px 9px; font-size: 12px; }
.img-item figcaption span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>