<template>
  <article class="res-card card" :class="{ expired: isExpired }" role="region" :aria-label="`资源: ${item.title}, 网盘: ${cloudName}, 类型: ${resTypeName}`">
    <div class="main">
      <div class="title-row">
        <span class="tag tag--primary">{{ cloudName }}</span>
        <router-link :to="{ path: '/detail/' + item.id }" class="title-link" :aria-label="`查看资源详情: ${item.title}`">
          <component :is="'span'" class="title">
            <template v-for="(seg, i) in segments" :key="i">
              <span v-if="seg.hit" class="hl">{{ seg.text }}</span>
              <template v-else>{{ seg.text }}</template>
            </template>
          </component>
        </router-link>
        <span v-if="isExpired" class="tag tag--danger" aria-label="资源已失效">已失效</span>
      </div>
      <div class="meta">
        <span class="tag" :class="resTypeTag" :aria-label="`资源类型: ${resTypeName}`">{{ resTypeName }}</span>
        <span v-if="item.size_text" class="meta-item" :aria-label="`文件大小: ${item.size_text}`">{{ item.size_text }}</span>
        <span v-if="item.channel" class="meta-item" :aria-label="`来源频道: ${item.channel}`">来自 {{ item.channel }}</span>
        <span class="meta-item" :aria-label="`收录时间: ${formatTime(item.published_at)}`">{{ formatTime(item.published_at) }}收录</span>
      </div>
    </div>
    <div class="actions" role="group" aria-label="资源操作">
      <button class="btn btn-sm" @click="copyPassword" :aria-label="`复制提取码: ${item.password}`" :disabled="!item.password">
        复制码
      </button>
      <button class="btn btn-sm" @click="copyLink" aria-label="复制资源链接">复制链接</button>
      <a
        v-if="!isExpired"
        class="btn btn-primary btn-sm"
        :href="openLink"
        target="_blank"
        rel="nofollow noopener"
        :aria-label="`在新窗口打开 ${cloudName} 网盘链接`"
      >
        打开网盘
      </a>
      <button class="btn btn-sm" @click="shareResource" aria-label="分享资源">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="18" cy="5" r="3" />
          <circle cx="6" cy="12" r="3" />
          <circle cx="18" cy="19" r="3" />
          <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
          <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
        </svg>
        分享
      </button>
      <button class="btn btn-sm btn-danger" @click="showReport = true" aria-label="提交失效反馈">失效反馈</button>
    </div>

    <teleport to="body">
      <div v-if="showReport" class="dialog-mask" @click.self="showReport = false" role="dialog" aria-modal="true" aria-labelledby="report-dialog-title">
        <div class="dialog" role="document">
          <div class="dialog__header">
            <span id="report-dialog-title" class="dialog__title">链接失效反馈</span>
            <button class="dialog__close" @click="showReport = false" aria-label="关闭反馈对话框">&times;</button>
          </div>
          <div class="dialog__body">
            <div class="form-item">
              <label id="report-content-label" class="form-item__label">问题描述（可选）</label>
              <div class="form-item__content">
                <textarea
                  class="form-item__textarea"
                  v-model="reportContent"
                  placeholder="例如：链接显示文件已被取消分享"
                  rows="3"
                  aria-describedby="report-content-label"
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
  </article>
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
    async copyPassword() {
      if (!this.item.password) return
      try {
        await navigator.clipboard.writeText(this.item.password)
        this.$toast('提取码已复制：' + this.item.password)
      } catch {
        this.$toast('复制失败，请手动复制')
      }
    },
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.openLink)
        this.$toast('链接已复制')
      } catch {
        this.$toast('复制失败，请手动复制')
      }
    },
    async shareResource() {
      const shareData = {
        title: this.item.title,
        text: `${this.item.title} - ${this.cloudName}`,
        url: window.location.origin + '/detail/' + this.item.id,
      }
      try {
        if (navigator.share) {
          await navigator.share(shareData)
        } else {
          await navigator.clipboard.writeText(shareData.url)
          this.$toast('链接已复制到剪贴板')
        }
      } catch (err) {
        if (err.name !== 'AbortError') {
          await navigator.clipboard.writeText(shareData.url)
          this.$toast('链接已复制到剪贴板')
        }
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

.title-link {
  flex: 1;
  min-width: 0;
  text-decoration: none;
  color: inherit;
}

.title-link:hover .title {
  color: var(--primary);
}

.title {
  font-size: 16px;
  font-weight: 500;
  color: var(--text-primary);
  word-break: break-all;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
  transition: color var(--transition-fast);
}

.meta {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  flex-wrap: wrap;
  margin-top: 10px;
}

.meta-item {
  color: var(--text-secondary);
  display: inline-block;
  border: 1px solid var(--border-base);
  background: var(--bg-hover);
  border-radius: var(--radius-base);
  padding: 1px 4px;
}

.actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.hl {
  color: #c184ff;
  background-color: transparent;
  text-decoration: underline;
}

.res-card.expired .title {
  text-decoration: line-through;
  color: var(--text-disabled);
}

.res-card.expired {
  filter: grayscale(60%);
}

@media (max-width: 700px) {
  .res-card {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .actions {
    justify-content: flex-start;
  }

  .title {
    font-size: 14px;
  }
}
</style>