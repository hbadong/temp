<template>
  <div class="detail-page">
    <header class="header">
      <div class="container header-inner">
        <router-link to="/" class="logo">盘搜</router-link>
        <div class="search-wrap">
          <SearchBox v-model="keyword" @search="onSearch" />
        </div>
        <nav>
          <router-link to="/complain">投诉</router-link>
          <router-link to="/admin">后台</router-link>
          <ThemeToggle />
        </nav>
      </div>
    </header>

    <main class="container">
      <div v-if="loading" class="loading-container">
        <Skeleton card :count="3" />
      </div>

      <template v-else-if="detail">
        <nav class="breadcrumb" aria-label="面包屑导航">
          <router-link to="/" class="breadcrumb__item">首页</router-link>
          <span class="breadcrumb__separator">/</span>
          <router-link :to="{ path: '/search', query: { cloud: detail.cloud_type } }" class="breadcrumb__item">
            {{ cloudName(detail.cloud_type) }}
          </router-link>
          <span class="breadcrumb__separator">/</span>
          <span class="breadcrumb__item breadcrumb__item--current">{{ detail.title }}</span>
        </nav>

        <div class="detail-header card">
          <h1 class="detail-title">{{ detail.title }}</h1>
          <div class="detail-meta">
            <span class="tag tag--primary">{{ cloudName(detail.cloud_type) }}</span>
            <span class="tag" :class="resTypeTag">{{ resTypeName }}</span>
            <span v-if="detail.size_text" class="meta-item">{{ detail.size_text }}</span>
            <span v-if="detail.channel" class="meta-item">来源：{{ detail.channel }}</span>
            <span class="meta-item">{{ formatTime(detail.published_at) }}收录</span>
          </div>
        </div>

        <div class="detail-body">
          <div class="detail-main">
            <div class="card file-section">
              <div class="section-header">
                <h2 class="section__title">文件列表 ({{ totalFiles }} 个文件)</h2>
                <div class="section-actions">
                  <button class="btn btn-sm" @click="expandAll">展开全部</button>
                  <button class="btn btn-sm" @click="collapseAll">折叠全部</button>
                </div>
              </div>
              <div class="file-tree" ref="fileTree">
                <FileTreeNode
                  v-for="file in detail.files"
                  :key="file.id"
                  :file="file"
                  :level="0"
                  :expanded="true"
                />
              </div>
              <div v-if="!detail.files || detail.files.length === 0" class="empty">
                <p class="empty__icon">📁</p>
                <p class="empty__description">暂无文件列表信息</p>
              </div>
            </div>

            <div class="card info-section">
              <h3 class="section__title">资源信息</h3>
              <dl class="info-list">
                <div class="info-row">
                  <dt>网盘类型</dt>
                  <dd>{{ cloudName(detail.cloud_type) }}</dd>
                </div>
                <div class="info-row">
                  <dt>资源类型</dt>
                  <dd>{{ resTypeName }}</dd>
                </div>
                <div class="info-row">
                  <dt>提取码</dt>
                  <dd v-if="detail.password" class="password-code">{{ detail.password }}</dd>
                  <dd v-else>-</dd>
                </div>
                <div class="info-row">
                  <dt>收录时间</dt>
                  <dd>{{ formatDateTime(detail.published_at) }}</dd>
                </div>
                <div class="info-row">
                  <dt>资源状态</dt>
                  <dd>
                    <span :class="['tag', statusTag(detail.status)]">{{ statusName(detail.status) }}</span>
                  </dd>
                </div>
              </dl>
            </div>
          </div>

          <aside class="detail-sidebar">
            <div class="card action-card">
              <div class="action-buttons">
                <button
                  v-if="!isExpired"
                  class="btn btn-primary btn-lg"
                  @click="openRedirect"
                  style="width: 100%; justify-content: center;"
                >
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                    <polyline points="15 3 21 3 21 9" />
                    <line x1="10" y1="14" x2="21" y2="3" />
                  </svg>
                  打开网盘
                </button>
                <button v-else class="btn btn-danger btn-lg" style="width: 100%; justify-content: center;" disabled>
                  链接已失效
                </button>
              </div>
              <div class="action-secondary">
                <button class="btn" @click="copyLink">复制链接</button>
                <button v-if="detail.password" class="btn" @click="copyPassword">复制提取码</button>
                <button class="btn" @click="shareResource">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="18" cy="5" r="3" />
                    <circle cx="6" cy="12" r="3" />
                    <circle cx="18" cy="19" r="3" />
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                  </svg>
                  分享
                </button>
                <button class="btn btn-danger" @click="showReport = true">失效反馈</button>
              </div>
              <div class="action-hint">
                <p>温馨提示：资源来自公开网络，请在下载后 24 小时内删除</p>
              </div>
            </div>

            <div class="card report-card">
              <h3 class="section__title">举报信息</h3>
              <p class="report-count">已有 <strong>{{ detail.invalid_reports }}</strong> 人反馈失效</p>
              <p class="report-tip">若链接无法访问，请点击上方「失效反馈」帮助我们维护资源库</p>
            </div>

            <div class="card related-searches" v-if="relatedSearches.length">
              <h3 class="section__title">相关搜索</h3>
              <div class="related-tags">
                <router-link
                  v-for="kw in relatedSearches"
                  :key="kw"
                  :to="{ path: '/search', query: { q: kw, cloud: detail.cloud_type } }"
                  class="related-tag"
                >{{ kw }}</router-link>
              </div>
            </div>
          </aside>
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
      </template>

      <div v-else class="empty card">
        <LottiePlayer :animationData="emptyAnimation" width="100px" height="100px" />
        <p class="empty__description">资源不存在或已被屏蔽</p>
        <router-link to="/search" class="btn btn-primary" style="margin-top: 16px;">返回搜索</router-link>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<script>
import SearchBox from '../components/SearchBox.vue'
import FileTreeNode from '../components/FileTreeNode.vue'
import LottiePlayer from '../components/LottiePlayer.vue'
import Skeleton from '../components/Skeleton.vue'
import ThemeToggle from '../components/ThemeToggle.vue'
import SiteFooter from '../components/SiteFooter.vue'
import { getResourceDetail } from '../api'
import { CLOUD_NAMES, RES_TYPE_NAMES, RES_TYPE_TAGS, formatTime } from '../constants'
import { reportResource } from '../api'
import loadingAnimation from '../assets/lottie/loading.json'
import emptyAnimation from '../assets/lottie/empty-folder.json'

export default {
  name: 'DetailView',
  components: { SearchBox, FileTreeNode, LottiePlayer, Skeleton, ThemeToggle, SiteFooter },
  props: {
    id: { type: [String, Number], required: true },
    type: { type: String, default: '' },
  },
  data() {
    return {
      detail: null,
      loading: true,
      loadingAnimation,
      emptyAnimation,
      keyword: '',
      showReport: false,
      reportContent: '',
    }
  },
  computed: {
    cloudName() {
      return (type) => CLOUD_NAMES[type] || '其他网盘'
    },
    resTypeName() {
      return RES_TYPE_NAMES[this.detail?.res_type] || '其他'
    },
    resTypeTag() {
      return `tag--${RES_TYPE_TAGS[this.detail?.res_type] || 'info'}`
    },
    isExpired() {
      return this.detail?.status === 'expired'
    },
    totalFiles() {
      const countFiles = (files) => {
        let count = 0
        for (const f of files || []) {
          count++
          if (f.children) count += countFiles(f.children)
        }
        return count
      }
      return countFiles(this.detail?.files)
    },
    relatedSearches() {
      if (!this.detail) return []
      // Extract keywords from the current resource title
      const title = this.detail.title || ''
      const words = title.match(/[\u4e00-\u9fa5a-zA-Z0-9]+/g) || []
      // Filter out common words and short words
      const keywords = words.filter(w => w.length >= 2 && !/^(资源|教程|视频|电影|破解|版|最新|全集|合集|入门|精通|高码率|便携|绿色|免安装|直装)$/.test(w))
      // Return top 6 unique keywords
      return [...new Set(keywords)].slice(0, 6)
    },
  },
  async mounted() {
    await this.loadDetail()
  },
  async beforeRouteUpdate(to) {
    this.id = to.params.id
    this.type = to.params.type
    this.detail = null
    this.loading = true
    await this.loadDetail()
  },
  methods: {
    async loadDetail() {
      this.loading = true
      try {
        this.detail = await getResourceDetail(this.id)
      } catch (err) {
        this.$toast(err.message)
        this.detail = null
      } finally {
        this.loading = false
      }
    },
    onSearch(q) {
      this.keyword = q
      this.$router.push({ path: '/search', query: { q } })
    },
    formatTime,
    formatDateTime(iso) {
      if (!iso) return ''
      return new Date(iso).toLocaleString('zh-CN')
    },
    statusName(s) {
      return { ok: '正常', expired: '已失效', blocked: '已屏蔽' }[s] || s
    },
    statusTag(s) {
      return { ok: 'tag--success', expired: 'tag--danger', blocked: 'tag--info' }[s] || ''
    },
    directLink() {
      const link = this.detail?.link
      if (this.detail?.password && link?.includes('pan.baidu.com') && !link.includes('pwd=')) {
        return `${link}?pwd=${this.detail.password}`
      }
      return link
    },
    expandAll() {
      this.$nextTick(() => {
        const tree = this.$refs.fileTree
        if (tree) this.expandAllNodes(tree)
      })
    },
    collapseAll() {
      this.$nextTick(() => {
        const tree = this.$refs.fileTree
        if (tree) this.collapseAllNodes(tree)
      })
    },
    expandAllNodes(node) {
      node.expanded = true
      if (node.$children) {
        for (const child of node.$children) {
          this.expandAllNodes(child)
        }
      }
    },
    collapseAllNodes(node) {
      node.expanded = false
      if (node.$children) {
        for (const child of node.$children) {
          this.collapseAllNodes(child)
        }
      }
    },
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.directLink)
        this.$toast('链接已复制')
      } catch {
        this.$toast('复制失败，请手动复制')
      }
    },
    async copyPassword() {
      try {
        await navigator.clipboard.writeText(this.detail.password)
        this.$toast('提取码已复制')
      } catch {
        this.$toast('复制失败，请手动复制')
      }
    },
    async shareResource() {
      const shareData = {
        title: this.detail.title,
        text: `${this.detail.title} - ${this.cloudName(this.detail.cloud_type)}`,
        url: window.location.href,
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
    openRedirect() {
      if (this.isExpired) return
      this.$router.push({ path: '/redirect/' + this.detail.id })
    },
    async submitReport() {
      try {
        await reportResource(this.detail.id, {
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
.detail-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--bg-page);
}

.header {
  position: sticky;
  top: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--border-divider);
  z-index: 50;
}

.header-inner {
  display: flex;
  align-items: center;
  gap: 16px;
  height: 56px;
}

.logo {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  flex-shrink: 0;
}

.search-wrap {
  flex: 1;
  max-width: 560px;
}

.header nav {
  margin-left: auto;
  display: flex;
  gap: 16px;
  flex-shrink: 0;
}

.header nav a {
  color: var(--text-secondary);
  font-size: var(--font-size-base);
  transition: color var(--transition-fast);
}

.header nav a:hover {
  color: var(--primary);
}

.breadcrumb {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  padding: 12px 0;
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.breadcrumb__item {
  display: flex;
  align-items: center;
  color: var(--text-regular);
  transition: color var(--transition-fast);
}

.breadcrumb__item:hover {
  color: var(--primary);
}

.breadcrumb__item--current {
  color: var(--text-primary);
  font-weight: 500;
  pointer-events: none;
}

.breadcrumb__separator {
  color: var(--text-secondary);
}

.detail-header {
  margin: 20px 0;
  padding: 24px;
}

.detail-title {
  font-size: 22px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 16px;
  word-break: break-word;
  line-height: 1.4;
}

.detail-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
}

.meta-item {
  color: var(--text-secondary);
}

.detail-body {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 24px;
  margin-bottom: 32px;
}

@media (max-width: 992px) {
  .detail-body {
    grid-template-columns: 1fr;
  }
}

.detail-main {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detail-sidebar {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.file-section,
.info-section {
  overflow: hidden;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-divider);
  margin: -20px -20px 0;
}

.section__title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.section-actions {
  display: flex;
  gap: 8px;
}

.file-tree {
  padding: 16px 20px;
  max-height: 500px;
  overflow-y: auto;
}

.file-node {
  font-size: var(--font-size-sm);
}

.file-node__row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-radius: var(--radius-base);
  cursor: pointer;
  transition: background-color var(--transition-fast);
  user-select: none;
}

.file-node__row:hover {
  background: var(--bg-hover);
}

.file-node__indent {
  width: 16px;
  flex-shrink: 0;
}

.file-node__toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: var(--text-secondary);
  flex-shrink: 0;
  transition: transform var(--transition-fast);
}

.file-node__toggle.expanded {
  transform: rotate(90deg);
}

.file-node__icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-node__name {
  flex: 1;
  min-width: 0;
  word-break: break-all;
  color: var(--text-primary);
}

.file-node__size {
  color: var(--text-secondary);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  flex-shrink: 0;
  padding-left: 12px;
}

.file-node__children {
  padding-left: 16px;
  border-left: 1px solid var(--border-divider);
  margin-left: 10px;
}

.file-node--file .file-node__icon {
  color: var(--text-secondary);
}

.file-node--dir .file-node__icon {
  color: var(--warning);
}

.info-list {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: grid;
  grid-template-columns: 80px 1fr;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
}

.info-row dt {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  font-weight: 500;
}

.info-row dd {
  color: var(--text-primary);
  font-size: var(--font-size-sm);
  word-break: break-all;
}

.password-code {
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Mono', 'Droid Sans Mono', monospace;
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--primary);
  background: var(--primary-bg);
  padding: 4px 8px;
  border-radius: var(--radius-base);
  display: inline-block;
}

.action-card {
  padding: 20px;
}

.action-buttons {
  margin-bottom: 16px;
}

.action-secondary {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.action-secondary .btn {
  width: 100%;
  justify-content: center;
}

.action-hint {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--border-divider);
}

.action-hint p {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.6;
}

.report-card {
  padding: 20px;
  background: var(--danger-bg);
  border-color: var(--danger);
}

.report-count {
  font-size: var(--font-size-base);
  color: var(--danger);
  margin-bottom: 8px;
}

.report-tip {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.6;
}

.related-searches {
  padding: 20px;
}

.related-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.related-tag {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  font-size: var(--font-size-sm);
  color: var(--text-regular);
  background: var(--bg-hover);
  border: 1px solid var(--border-base);
  border-radius: var(--radius-base);
  transition: all var(--transition-fast);
}

.related-tag:hover {
  background: var(--primary-bg);
  border-color: var(--primary);
  color: var(--primary);
  text-decoration: none;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}
</style>