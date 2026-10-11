<template>
  <div class="card">
    <div class="toolbar">
      <h2 class="page-title" style="margin:0">视频素材库</h2>
      <div class="row">
        <input class="input" style="width:160px" v-model="newGroup" placeholder="封面分组" />
        <button class="btn" @click="addGroup" :disabled="busy">+ 分组</button>
        <label class="btn">
          {{ uploading ? '上传中…' : '+ 上传素材' }}
          <input type="file" accept=".mp4,.mov,image/*" multiple style="display:none" :disabled="uploading" @change="upload" />
        </label>
      </div>
    </div>
    <p class="muted">支持上传 mp4 / mov 视频素材及 jpg / png 封面图，用于视频混剪与矩阵发布</p>
    <div class="vid-grid">
      <figure v-for="v in list" :key="v.id" class="vid-item">
        <video v-if="v.kind === 'video'" :src="'/api/files/' + v.path" controls></video>
        <img v-else :src="'/api/files/' + v.path" :alt="v.filename" />
        <figcaption>
          <span class="muted">{{ v.filename }}</span>
          <a class="op danger" href="javascript:;" @click="del(v)">删除</a>
        </figcaption>
      </figure>
      <div v-if="!list.length && !loading" class="empty">暂无素材，请上传</div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'
const list = ref([])
const newGroup = ref('')
const busy = ref(false)
const uploading = ref(false)
const loading = ref(false)

async function load() {
  loading.value = true
  try { list.value = (await api.get('/api/video-assets')).list } catch (e) { /* noop */ } finally { loading.value = false }
}
async function addGroup() {
  const name = newGroup.value.trim()
  if (!name) return
  busy.value = true
  try {
    await api.post('/api/image-groups', { name, kind: 2 })
    newGroup.value = ''
  } catch (e) { alert(e.message) } finally { busy.value = false }
}
async function del(v) {
  if (!confirm('删除该素材？')) return
  try {
    await api.del('/api/video-assets/' + v.id)
    await load()
  } catch (e) { alert(e.message) }
}
async function upload(e) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  uploading.value = true
  try {
    for (const f of files) {
      const isVideo = /\.(mp4|mov)$/i.test(f.name)
      const res = await fetch('/api/uploads', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'x-filename': encodeURIComponent(f.name),
          'x-group-id': '0',
          'x-kind': isVideo ? 'video' : 'cover',
        },
        body: f,
      })
      const data = await res.json()
      if (data.code !== 1) throw new Error(data.msg || '上传失败')
    }
    await load()
  } catch (err) { alert(err.message) } finally {
    uploading.value = false
    e.target.value = ''
  }
}
onMounted(load)
</script>
<style scoped>
.vid-grid { margin-top: 12px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.vid-item { margin: 0; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: #fff; }
.vid-item video, .vid-item img { width: 100%; height: 130px; object-fit: cover; display: block; background: #0f172a; }
.vid-item figcaption { display: flex; align-items: center; justify-content: space-between; gap: 6px; padding: 7px 9px; font-size: 12px; }
.vid-item figcaption span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>