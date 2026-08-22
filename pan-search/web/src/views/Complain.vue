<template>
  <div class="complain-page">
    <header class="header">
      <div class="container header-inner">
        <router-link to="/" class="logo">盘搜</router-link>
        <span class="crumb">侵权投诉</span>
      </div>
    </header>

    <main class="container main">
      <div class="card form-card">
        <h2>侵权投诉 / DMCA</h2>
        <p class="tip">
          本站索引内容均来自公开网络渠道（Telegram 频道等），不存储任何文件。
          若您是权利人，认为本站索引的内容侵犯了您的合法权益，请通过以下表单提交投诉，
          我们将在核实后 24 小时内断开相关链接的搜索展示。
        </p>
        <form @submit.prevent="submit">
          <div class="form-item">
            <label>投诉类型</label>
            <select v-model="type">
              <option value="infringe">版权侵权</option>
              <option value="other">其他侵权</option>
            </select>
          </div>
          <div class="form-item">
            <label>涉及资源链接或资源 ID</label>
            <input v-model="target" type="text" placeholder="例如：https://www.alipan.com/s/xxxx 或资源 ID 123" required />
          </div>
          <div class="form-item">
            <label>投诉说明</label>
            <textarea
              v-model="content"
              placeholder="请说明权利归属及侵权情况：作品名称、权利证明、被投诉内容位置等"
              required
            ></textarea>
          </div>
          <div class="form-item">
            <label>联系方式</label>
            <input v-model="contact" type="text" placeholder="邮箱或其他联系方式" required />
          </div>
          <button class="btn btn-primary" type="submit" :disabled="submitting">
            {{ submitting ? '提交中...' : '提交投诉' }}
          </button>
        </form>
      </div>
    </main>
  </div>
</template>

<script>
import { reportResource, submitFeedback } from '../api'

export default {
  name: 'ComplainView',
  data() {
    return {
      type: 'infringe',
      target: '',
      content: '',
      contact: '',
      submitting: false,
    }
  },
  methods: {
    async submit() {
      this.submitting = true
      try {
        const trimmed = this.target.trim()
        const resourceId = /^\d+$/.test(trimmed) ? parseInt(trimmed, 10) : null
        if (resourceId) {
          await reportResource(resourceId, {
            type: 'infringe',
            content: `[${this.type}] ${this.content}`,
            contact: this.contact,
          })
        } else {
          await submitFeedback({
            type: this.type,
            content: `${this.target}\n${this.content}`,
            contact: this.contact,
          })
        }
        this.$toast('投诉已提交，我们会尽快处理')
        this.reset()
      } catch (err) {
        this.$toast(err.message)
      } finally {
        this.submitting = false
      }
    },
    reset() {
      this.target = ''
      this.content = ''
      this.contact = ''
    },
  },
}
</script>

<style scoped>
.header {
  background: #fff;
  border-bottom: 1px solid var(--border);
}

.header-inner {
  height: 56px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.logo {
  font-size: 22px;
  font-weight: 700;
  color: var(--primary);
}

.crumb {
  color: var(--text-3);
  font-size: 13px;
}

.main {
  padding-top: 30px;
  padding-bottom: 40px;
}

.form-card {
  max-width: 640px;
  margin: 0 auto;
  padding: 26px;
}

.form-card h2 {
  font-size: 18px;
  margin-bottom: 12px;
}

.tip {
  color: var(--text-2);
  font-size: 13px;
  line-height: 1.8;
  background: #f7f9fd;
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 18px;
}
</style>
