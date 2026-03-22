<template>
  <div class="kanxi-page">
    <header class="header">
      <div class="container">
        <router-link
          to="/"
          class="logo"
        >
          起名网
        </router-link>
        <nav class="nav">
          <router-link to="/baobao">
            宝宝起名
          </router-link>
          <router-link to="/bazi">
            八字起名
          </router-link>
          <router-link
            to="/kxzd"
            class="active"
          >
            康熙字典
          </router-link>
          <router-link to="/baijiaxing">
            百家姓
          </router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="search-section">
        <div class="container">
          <h1>康熙字典</h1>
          <p class="subtitle">
            查询汉字的拼音、五行、笔画、康熙字典解释
          </p>
          
          <a-form
            layout="inline"
            class="search-form"
          >
            <a-form-item>
              <a-input-search
                v-model:value="searchChar"
                placeholder="输入汉字查询"
                size="large"
                style="width: 300px"
                allow-clear
                @search="onSearch"
              >
                <template #prefix>
                  <SearchOutlined />
                </template>
              </a-input-search>
            </a-form-item>
            
            <a-form-item label="五行">
              <a-select
                v-model:value="filterElement"
                placeholder="选择五行"
                style="width: 120px"
                allow-clear
                @change="onFilterChange"
              >
                <a-select-option value="">
                  全部
                </a-select-option>
                <a-select-option value="金">
                  金
                </a-select-option>
                <a-select-option value="木">
                  木
                </a-select-option>
                <a-select-option value="水">
                  水
                </a-select-option>
                <a-select-option value="火">
                  火
                </a-select-option>
                <a-select-option value="土">
                  土
                </a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item label="笔画">
              <a-input-number
                v-model:value="filterStroke"
                placeholder="笔画数"
                style="width: 100px"
                :min="1"
                :max="64"
                allow-clear
                @change="onFilterChange"
              />
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section
        v-if="result"
        class="result-section"
      >
        <div class="container">
          <a-row :gutter="24">
            <a-col :span="8">
              <a-card class="char-card">
                <div class="big-char">
                  {{ result.character }}
                </div>
                <a-divider />
                <a-descriptions
                  :column="1"
                  size="small"
                >
                  <a-descriptions-item label="拼音">
                    {{ result.pinyin }}
                  </a-descriptions-item>
                  <a-descriptions-item label="声调">
                    {{ result.tone }}
                  </a-descriptions-item>
                  <a-descriptions-item label="部首">
                    {{ result.radical }}
                  </a-descriptions-item>
                  <a-descriptions-item label="笔画">
                    {{ result.total_stroke }}画
                  </a-descriptions-item>
                  <a-descriptions-item label="五行">
                    <a-tag :color="getElementColor(result.five_element)">
                      {{ result.five_element }}
                    </a-tag>
                  </a-descriptions-item>
                </a-descriptions>
              </a-card>
            </a-col>
            
            <a-col :span="16">
              <a-card title="基本解释">
                <p>{{ result.basic_meaning }}</p>
              </a-card>
              
              <a-card
                v-if="result.detail_meaning"
                title="详细解释"
                style="margin-top: 16px"
              >
                <p>{{ result.detail_meaning }}</p>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>

      <section
        v-if="!searchChar"
        class="browse-section"
      >
        <div class="container">
          <h2>按五行浏览</h2>
          <a-row :gutter="[16, 16]">
            <a-col
              v-for="element in elements"
              :key="element.name"
              :span="4"
            >
              <router-link
                :to="`/kxzd/element/${element.name}`"
                class="element-card"
                :style="{ background: element.color }"
              >
                <span class="element-name">{{ element.name }}</span>
                <span class="element-label">{{ element.label }}</span>
              </router-link>
            </a-col>
          </a-row>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { message } from 'ant-design-vue';
import { SearchOutlined } from '@ant-design/icons-vue';
import request from '@/utils/request';

const loading = ref(false);
const searchChar = ref('');
const filterElement = ref('');
const filterStroke = ref(null);
const result = ref(null);

const elements = [
  { name: '金', label: '金属性', color: '#ffd700' },
  { name: '木', label: '木属性', color: '#228b22' },
  { name: '水', label: '水属性', color: '#4169e1' },
  { name: '火', label: '火属性', color: '#ff4500' },
  { name: '土', label: '土属性', color: '#8b4513' }
];

const getElementColor = (element) => {
  const colors = { '金': '#ffd700', '木': '#228b22', '水': '#4169e1', '火': '#ff4500', '土': '#8b4513' };
  return colors[element] || 'default';
};

const onSearch = async () => {
  if (!searchChar.value) {
    result.value = null;
    return;
  }

  if (searchChar.value.length === 1) {
    loading.value = true;
    try {
      const res = await request.get(`/v1/kanxi/detail/${searchChar.value}`);
      result.value = res.data;
    } catch (error) {
      result.value = null;
      message.error('未找到该汉字');
    } finally {
      loading.value = false;
    }
  }
};

const onFilterChange = async () => {
  result.value = null;
};
</script>

<style lang="scss" scoped>
.kanxi-page { min-height: 100vh; background: #f5f5f5; }
.header { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); .container { display: flex; align-items: center; height: 64px; } .logo { font-size: 20px; font-weight: bold; color: #d4380d; margin-right: 48px; } .nav { display: flex; gap: 32px; a { color: #333; text-decoration: none; &:hover, &.active { color: #d4380d; } } } }
.main { padding-bottom: 48px; }
.search-section { background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%); padding: 48px 0; color: #fff; text-align: center; h1 { font-size: 32px; margin-bottom: 8px; } .subtitle { font-size: 16px; margin-bottom: 32px; } .search-form { justify-content: center; background: rgba(255,255,255,0.1); padding: 24px; border-radius: 8px; } }
.result-section { padding: 32px 0; .big-char { font-size: 120px; text-align: center; font-family: 'KaiTi', serif; color: #333; } }
.browse-section { padding: 32px 0; h2 { margin-bottom: 24px; } }
.element-card { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100px; border-radius: 8px; color: #fff; text-decoration: none; transition: transform 0.2s; &:hover { transform: scale(1.05); } .element-name { font-size: 32px; font-weight: bold; } .element-label { font-size: 14px; margin-top: 8px; } }
.container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
</style>
