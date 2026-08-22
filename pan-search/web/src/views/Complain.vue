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
        <h2 class="form-card__title">侵权投诉 / DMCA</h2>
        <p class="tip">
          本站索引内容均来自公开网络渠道（Telegram 频道等），不存储任何文件。
          若您是权利人，认为本站索引的内容侵犯了您的合法权益，请通过以下表单提交投诉，
          我们将在核实后 24 小时内断开相关链接的搜索展示。
        </p>
        <form @submit.prevent="submit" class="form">
          <div class="form-item">
            <label class="form-item__label">投诉类型</label>
            <div class="form-item__content">
              <select v-model="type" class="form-item__select">
                <option value="infringe">版权侵权</option>
                <option value="other">其他侵权</option>
              </select>
            </div>
          </div>
          <div class="form-item">
            <label class="form-item__label">涉及资源链接或资源 ID</label>
            <div class="form-item__content">
              <input
                v-model="target"
                type="text"
                class="form-item__input"
                placeholder="例如：https://www.alipan.com/s/xxxx 或资源 ID 123"
                required
              />
            </div>
          </div>
          <div class="form-item">
            <label class="form-item__label">投诉说明</label>
            <div class="form-item__content">
              <textarea
                v-model="content"
                class="form-item__textarea"
                placeholder="请说明权利归属及侵权情况：作品名称、权利证明、被投诉内容位置等"
                required
                rows="4"
              ></textarea>
            </div>
          </div>
          <div class="form-item">
            <label class="form-item__label">联系方式</label>
            <div class="form-item__content">
              <input
                v-model="contact"
                type="text"
                class="form-item__input"
                placeholder="邮箱或其他联系方式"
                required
              />
            </div>
          </div>
          <div class="form-item form-item--submit">
            <button class="btn btn-primary btn-lg" type="submit" :disabled="submitting">
              {{ submitting ? '提交中...' : '提交投诉' }}
            </button>
          </div>
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
  background: var(--bg-color);
  border-bottom: 1px solid var(--border-divider);
}

.header-inner {
  height: 56px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.logo {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
}

.crumb {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
}

.main {
  padding-top: 30px;
  padding-bottom: 40px;
}

.form-card {
  max-width: 640px;
  margin: 0 auto;
  padding: 30px;
}

.form-card__title {
  font-size: var(--font-size-title);
  line-height: 24px;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 16px;
}

.tip {
  color: var(--text-regular);
  font-size: var(--font-size-sm);
  line-height: 1.8;
  background: var(--bg-light);
  border-radius: var(--radius-card);
  padding: 14px 16px;
  margin-bottom: 24px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-item--submit {
  margin-top: 8px;
}

.form-item__select {
  width: 100%;
  height: 36px;
  padding: 0 35px 0 12px;
  border: 1px solid var(--border-base);
  border-radius: var(--radius-base);
  background: var(--bg-color);
  color: var(--text-primary);
  cursor: pointer;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23909399' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
}

.form-item__select:hover {
  border-color: var(--primary);
}

.form-item__select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(180, 106, 255, 0.2);
}

@media (max-width: 767px) {
  .form-card {
    padding: 20px 16px;
  }

  .form-card__title {
    font-size: var(--font-size-lg);
  }
}
</style>