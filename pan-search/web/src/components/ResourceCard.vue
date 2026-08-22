<template>
  <div class="res-card card">
    <div class="main">
      <div class="title-row">
        <span class="tag tag--primary">{{ cloudName }}</span>
        <component :is="'span'" class="title">
          <template v-for="(seg, i) in segments" :key="i">
            <span v-if="seg.hit" class="hl">{{ seg.text }}</span>
            <template v-else>{{ seg.text }}</template>
          </template>
        </component>
        <span v-if="isExpired" class="tag tag--danger">已失效</span>
      </div>
      <div class="meta">
        <span class="tag" :class="resTypeTag">{{ resTypeName }}</span>
        <span v-if="item.size_text" class="meta-item">{{ item.size_text }}</span>
        <span v-if="item.channel" class="meta-item">来自 {{ item.channel }}</span>
        <span class="meta-item">{{ formatTime(item.published_at) }}收录</span>
      </div>
    </div>
    <div class="actions">
      <button class="btn btn-sm" @click="$toast('提取码已复制：' + item.password)">
        复制码
      </button>
      <button class="btn btn-sm" @click="copyLink">复制链接</button>
      <a
        v-if="!isExpired"
        class="btn btn-primary btn-sm"
        :href="openLink"
        target="_blank"
        rel="nofollow noopener"
      >
        打开网盘
      </a>
      <button class="btn btn-sm btn-danger" @click="showReport = true">失效反馈</button>
    </div>

    <teleport to="body">
      <div v-if="showReport" class="dialog-mask" @click.self="showReport = false">
        <div class="dialog">
          <div class="dialog__header">
            <span class="dialog__title">链接失效反馈</span>
            <span class="dialog__close" @click="showReport = false">&times;</span>
          </div>
          <div class="dialog__body">
            <div class="form-item">
              <label class="form-item__label">问题描述（可选）</label>
              <div class="form-item__content">
                <textarea
                  class="form-item__textarea"
                  v-model="reportContent"
                  placeholder="例如：链接显示文件已被取消分享"
                  rows="3"
                ></textarea>
              </div>
            </div>
          </div>
          <div class="dialog__footer">
            <button class="btn" @click="showReport = false">取消</button>
            <button class="btn btn-primary" @click="submitReport">提交反馈</button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script>
import { CLOUD_NAMES, RES_TYPE_NAMES, RES_TYPE_TAGS, formatTime, highlightTitle } from '../constants'
import { reportResource } from '../api'

export default {
  name: 'ResourceCard',
  props: {
    item: { type: Object, required: true },
    keyword: { type: String, default: '' },
  },
  data() {
    return { showReport: false, reportContent: '' }
  },
  computed: {
    cloudName() {
      return CLOUD_NAMES[this.item.cloud_type] || '其他网盘'
    },
    resTypeName() {
      return RES_TYPE_NAMES[this.item.res_type] || '其他'
    },
    resTypeTag() {
      return `tag--${RES_TYPE_TAGS[this.item.res_type] || 'info'}`
    },
    isExpired() {
      return this.item.status === 'expired'
    },
    openLink() {
      const link = this.item.link
      if (this.item.password && link.includes('pan.baidu.com') && !link.includes('pwd=')) {
        return `${link}?pwd=${this.item.password}`
      }
      return link
    },
    segments() {
      return highlightTitle(this.item.title, this.keyword)
    },
  },
  methods: {
    formatTime,
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.openLink)
        this.$toast('链接已复制')
      } catch {
        this.$toast('复制失败，请手动复制')
      }
    },
    async submitReport() {
      try {
        await reportResource(this.item.id, {
          type: 'invalid',
          content: this.reportContent || null,
        })
        this.$toast('感谢反馈，我们会尽快核实')
        this.showReport = false
        this.reportContent = ''
      } catch (err) {
        this.$toast(err.message)
      }
    },
  },
}
</script>

<style scoped>
.res-card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 20px;
  margin-bottom: 12px;
  transition: box-shadow var(--transition-base), border-color var(--transition-fast);
}

.res-card:hover {
  box-shadow: var(--shadow-base);
  border-color: var(--border-light);
}

.title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.title {
  font-size: 15px;
  font-weight: 500;
  color: var(--text-primary);
  word-break: break-all;
  line-height: 1.5;
}

.meta {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  flex-wrap: wrap;
}

.meta-item {
  color: var(--text-secondary);
}

.actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.hl {
  color: var(--danger);
  font-weight: 600;
}

@media (max-width: 767px) {
  .res-card {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .actions {
    justify-content: flex-start;
  }
}
</style>