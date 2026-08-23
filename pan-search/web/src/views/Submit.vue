<template>
  <div class="submit-page">
    <header class="header">
      <div class="container header-inner">
        <router-link to="/" class="logo">盘搜</router-link>
        <span class="crumb">提交资源</span>
        <ThemeToggle />
      </div>
    </header>

    <main class="container main">
      <div class="card form-card">
        <h2 class="form-card__title">提交资源</h2>
        <p class="tip">
          欢迎分享您发现的网盘资源链接。提交后将由管理员审核，审核通过后即可在搜索结果中展示。
          请确保链接有效、标题准确，便于其他用户快速找到所需资源。
        </p>
        <form @submit.prevent="submit" class="form">
          <div class="form-item">
            <label class="form-item__label">资源标题</label>
            <div class="form-item__content">
              <input
                v-model="title"
                type="text"
                class="form-item__input"
                placeholder="例如：三体全集 刘慈欣 EPUB"
                maxlength="200"
                required
              />
            </div>
          </div>
          <div class="form-item">
            <label class="form-item__label">网盘链接</label>
            <div class="form-item__content">
              <input
                v-model="link"
                type="url"
                class="form-item__input"
                placeholder="例如：https://www.alipan.com/s/xxxxx"
                required
              />
            </div>
          </div>
          <div class="form-item">
            <label class="form-item__label">提取码（可选）</label>
            <div class="form-item__content">
              <input
                v-model="password"
                type="text"
                class="form-item__input"
                placeholder="如链接需要提取码请填写"
                maxlength="20"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-item">
              <label class="form-item__label">网盘类型</label>
              <div class="form-item__content">
                <select v-model="cloudType" class="form-item__select">
                  <option v-for="(name, key) in CLOUD_NAMES" :key="key" :value="key">{{ name }}</option>
                </select>
              </div>
            </div>
            <div class="form-item">
              <label class="form-item__label">资源类型</label>
              <div class="form-item__content">
                <select v-model="resType" class="form-item__select">
                  <option v-for="(name, key) in RES_TYPE_NAMES" :key="key" :value="key">{{ name }}</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-item">
            <label class="form-item__label">文件大小（可选）</label>
            <div class="form-item__content">
              <input
                v-model="sizeText"
                type="text"
                class="form-item__input"
                placeholder="例如：2.3 GB 或 500 MB"
                maxlength="50"
              />
            </div>
          </div>
          <div class="form-item">
            <label class="form-item__label">联系方式（可选）</label>
            <div class="form-item__content">
              <input
                v-model="submitter"
                type="text"
                class="form-item__input"
                placeholder="邮箱，便于审核结果通知"
                maxlength="100"
              />
            </div>
          </div>
          <div class="form-item form-item--submit">
            <button class="btn btn-primary btn-lg" type="submit" :disabled="submitting">
              {{ submitting ? '提交中...' : '提交资源' }}
            </button>
          </div>
        </form>
        <div v-if="success" class="success-tip">
          资源已提交，等待管理员审核。审核通过后将在搜索结果中展示。
        </div>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<script>
import { submitResource } from '../api'
import { CLOUD_NAMES, RES_TYPE_NAMES } from '../constants'
import ThemeToggle from '../components/ThemeToggle.vue'
import SiteFooter from '../components/SiteFooter.vue'

export default {
  name: 'SubmitView',
  components: { ThemeToggle, SiteFooter },
  data() {
    return {
      title: '',
      link: '',
      password: '',
      cloudType: 'aliyun',
      resType: 'other',
      sizeText: '',
      submitter: '',
      submitting: false,
      success: false,
    }
  },
  methods: {
    async submit() {
      if (this.title.trim().length < 2) {
        this.$toast('标题至少 2 个字符')
        return
      }
      this.submitting = true
      this.success = false
      try {
        await submitResource({
          title: this.title.trim(),
          link: this.link.trim(),
          password: this.password.trim() || undefined,
          cloud_type: this.cloudType,
          res_type: this.resType,
          size_text: this.sizeText.trim() || undefined,
          submitter: this.submitter.trim() || undefined,
        })
        this.$toast('资源已提交，等待审核')
        this.success = true
        this.reset()
      } catch (err) {
        this.$toast(err.message)
      } finally {
        this.submitting = false
      }
    },
    reset() {
      this.title = ''
      this.link = ''
      this.password = ''
      this.sizeText = ''
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

.form-row {
  display: flex;
  gap: 20px;
}

.form-row .form-item {
  flex: 1;
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

.success-tip {
  margin-top: 20px;
  padding: 12px 16px;
  background: rgba(103, 194, 58, 0.1);
  border: 1px solid rgba(103, 194, 58, 0.3);
  border-radius: var(--radius-base);
  color: #67c23a;
  font-size: var(--font-size-sm);
}

@media (max-width: 700px) {
  .form-card {
    padding: 20px 16px;
  }

  .form-card__title {
    font-size: var(--font-size-lg);
  }

  .form-row {
    flex-direction: column;
    gap: 20px;
  }
}
</style>
