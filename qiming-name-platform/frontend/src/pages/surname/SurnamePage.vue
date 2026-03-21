<template>
  <div class="surname-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">起名网</router-link>
        <nav class="nav">
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/baijiaxing" class="active">百家姓</router-link>
          <router-link to="/kxzd">康熙字典</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="search-section">
        <div class="container">
          <h1>百家姓</h1>
          <p class="subtitle">查询姓氏来源、名字大全</p>
          
          <a-form layout="inline" class="search-form">
            <a-form-item>
              <a-auto-complete
                v-model:value="searchSurname"
                placeholder="输入姓氏查询"
                :options="surnameOptions"
                size="large"
                style="width: 300px"
                @select="onSearch"
                allow-clear
              >
                <template #option="{ value }">
                  {{ value }}
                </template>
              </a-auto-complete>
            </a-form-item>
            
            <a-form-item label="性别">
              <a-radio-group v-model:value="filterGender" size="large" @change="onFilterChange">
                <a-radio-button :value="1">男孩</a-radio-button>
                <a-radio-button :value="2">女孩</a-radio-button>
              </a-radio-group>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section class="result-section" v-if="surnameDetail">
        <div class="container">
          <a-row :gutter="24">
            <a-col :span="12">
              <a-card :title="surnameDetail.surname + '姓'">
                <a-descriptions :column="2">
                  <a-descriptions-item label="拼音">{{ surnameDetail.pinyin }}</a-descriptions-item>
                  <a-descriptions-item label="人口排名">第{{ surnameDetail.population_rank }}名</a-descriptions-item>
                  <a-descriptions-item label="人口数量">{{ surnameDetail.population_count }}</a-descriptions-item>
                  <a-descriptions-item label="姓氏来源">{{ surnameDetail.origin }}</a-descriptions-item>
                </a-descriptions>
                <a-divider>姓氏含义</a-divider>
                <p>{{ surnameDetail.meaning }}</p>
              </a-card>
            </a-col>
            
            <a-col :span="12">
              <a-card title="历史名人">
                <p>{{ surnameDetail.historical_figures || '暂无数据' }}</p>
              </a-card>
            </a-col>
          </a-row>
          
          <a-row :gutter="24" style="margin-top: 24px">
            <a-col :span="12">
              <a-card title="男孩名字大全">
                <a-list :data-source="boyNames" size="small" :loading="loading">
                  <template #renderItem="{ item }">
                    <a-list-item>
                      <router-link :to="`/baobao?surname=${surname}&gender=1`" class="name-link">
                        {{ item.full_name }}
                      </router-link>
                    </a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
            
            <a-col :span="12">
              <a-card title="女孩名字大全">
                <a-list :data-source="girlNames" size="small" :loading="loading">
                  <template #renderItem="{ item }">
                    <a-list-item>
                      <router-link :to="`/baobao?surname=${surname}&gender=2`" class="name-link">
                        {{ item.full_name }}
                      </router-link>
                    </a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>

      <section class="list-section" v-if="!surnameDetail">
        <div class="container">
          <h2>姓氏排行榜</h2>
          <a-row :gutter="[16, 16]">
            <a-col :span="4" v-for="surname in topSurnames" :key="surname">
              <a-card hoverable @click="onSearch(surname)" class="surname-card">
                <div class="surname-char">{{ surname }}</div>
                <div class="surname-name">姓</div>
              </a-card>
            </a-col>
          </a-row>
          
          <h2 style="margin-top: 48px">全部姓氏</h2>
          <div class="surname-grid">
            <span
              v-for="surname in allSurnames"
              :key="surname"
              class="surname-item"
              @click="onSearch(surname)"
            >
              {{ surname }}
            </span>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import request from '@/utils/request'

const loading = ref(false)
const searchSurname = ref('')
const filterGender = ref(1)
const surnameDetail = ref(null)
const boyNames = ref([])
const girlNames = ref([])

const topSurnames = ['王', '李', '张', '刘', '陈', '杨', '黄', '赵', '吴', '周', '徐', '孙', '马', '朱', '胡', '郭', '何', '高', '林', '罗', '郑', '梁', '谢', '宋', '唐', '许', '韩', '冯', '邓', '曹', '彭', '曾', '萧', '蔡', '潘', '田', '董', '袁', '于', '余', '叶', '蒋', '杜', '苏', '魏', '程', '吕', '丁', '沈', '任', '姚', '卢', '传', '傅', '钟', '莹', '韦', '嘉']

const allSurnames = ['王', '李', '张', '刘', '陈', '杨', '黄', '赵', '吴', '周', '徐', '孙', '马', '朱', '胡', '郭', '何', '高', '林', '罗', '郑', '梁', '谢', '宋', '唐', '许', '韩', '冯', '邓', '曹', '彭', '曾', '萧', '蔡', '潘', '田', '董', '袁', '于', '余', '叶', '蒋', '杜', '苏', '魏', '程', '吕', '丁', '沈', '任', '姚', '卢', '傅', '钟', '韦', '嘉']

const surnameOptions = allSurnames.map(s => ({ value: s }))

const onSearch = async (value) => {
  const surname = value || searchSurname.value
  if (!surname) return

  loading.value = true
  searchSurname.value = surname

  try {
    const res = await request.get(`/v1/surnames/${surname}`)
    surnameDetail.value = res.data
    
    const namesRes = await request.get(`/v1/surnames/${surname}/names`, {
      params: { gender: filterGender.value, pageSize: 20 }
    })
    
    boyNames.value = namesRes.data?.filter(n => n.gender === 1) || []
    girlNames.value = namesRes.data?.filter(n => n.gender === 2) || []
  } catch (error) {
    surnameDetail.value = null
    message.error('未找到该姓氏')
  } finally {
    loading.value = false
  }
}

const onFilterChange = async () => {
  if (surnameDetail.value) {
    await onSearch(surnameDetail.value.surname)
  }
}
</script>

<style lang="scss" scoped>
.surname-page { min-height: 100vh; background: #f5f5f5; }
.header { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); .container { display: flex; align-items: center; height: 64px; } .logo { font-size: 20px; font-weight: bold; color: #d4380d; margin-right: 48px; } .nav { display: flex; gap: 32px; a { color: #333; text-decoration: none; &:hover, &.active { color: #d4380d; } } } }
.main { padding-bottom: 48px; }
.search-section { background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%); padding: 48px 0; color: #fff; text-align: center; h1 { font-size: 32px; margin-bottom: 8px; } .subtitle { font-size: 16px; margin-bottom: 32px; } .search-form { justify-content: center; background: rgba(255,255,255,0.1); padding: 24px; border-radius: 8px; } }
.result-section { padding: 32px 0; }
.list-section { padding: 32px 0; h2 { margin-bottom: 24px; } }
.surname-card { text-align: center; cursor: pointer; .surname-char { font-size: 36px; font-weight: bold; color: #d4380d; } .surname-name { font-size: 14px; color: #666; } }
.surname-grid { display: flex; flex-wrap: wrap; gap: 8px; .surname-item { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #fff; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 18px; cursor: pointer; transition: all 0.2s; &:hover { border-color: #d4380d; color: #d4380d; transform: scale(1.1); } } }
.name-link { color: #333; text-decoration: none; &:hover { color: #d4380d; } }
.container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
</style>
