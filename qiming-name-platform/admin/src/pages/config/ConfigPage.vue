<template>
  <div class="config-page">
    <div class="page-header">
      <h1>系统配置</h1>
    </div>

    <a-card title="基本设置">
      <a-form :model="basicConfig" layout="vertical">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="网站名称">
              <a-input v-model:value="basicConfig.siteName" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="网站Logo">
              <a-input v-model:value="basicConfig.siteLogo" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="联系电话">
              <a-input v-model:value="basicConfig.phone" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="联系邮箱">
              <a-input v-model:value="basicConfig.email" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-form-item>
          <a-button type="primary" @click="saveBasicConfig">保存设置</a-button>
        </a-form-item>
      </a-form>
    </a-card>

    <a-card title="起名规则设置" style="margin-top: 24px">
      <a-form :model="nameConfig" layout="vertical">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="名字最小笔画">
              <a-input-number v-model:value="nameConfig.minStroke" :min="1" :max="30" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="名字最大笔画">
              <a-input-number v-model:value="nameConfig.maxStroke" :min="1" :max="30" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="推荐名字数量">
              <a-input-number v-model:value="nameConfig.recommendCount" :min="5" :max="50" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-form-item>
          <a-button type="primary" @click="saveNameConfig">保存设置</a-button>
        </a-form-item>
      </a-form>
    </a-card>

    <a-card title="价格设置" style="margin-top: 24px">
      <a-table :columns="priceColumns" :data-source="priceData" :pagination="false" bordered size="small">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'price'">
            <a-input-number v-model:value="record.price" :min="0" :precision="2" prefix="¥" />
          </template>
          <template v-else-if="column.key === 'action'">
            <a @click="savePrice(record)">保存</a>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message } from 'ant-design-vue'

const basicConfig = reactive({
  siteName: '起名网',
  siteLogo: '/images/logo.png',
  phone: '400-888-9999',
  email: 'service@qiming.cn'
})

const nameConfig = reactive({
  minStroke: 2,
  maxStroke: 25,
  recommendCount: 20
})

const priceColumns = [
  { title: '服务类型', dataIndex: 'serviceName', key: 'serviceName' },
  { title: '价格(元)', dataIndex: 'price', key: 'price' },
  { title: '操作', key: 'action' }
]

const priceData = ref([
  { id: 1, serviceName: '宝宝起名', price: 99 },
  { id: 2, serviceName: '八字起名', price: 199 },
  { id: 3, serviceName: '诗词起名', price: 129 },
  { id: 4, serviceName: '周易起名', price: 299 },
  { id: 5, serviceName: '姓名测试', price: 29 },
  { id: 6, serviceName: '公司起名', price: 999 }
])

const saveBasicConfig = () => {
  message.success('基本设置已保存')
}

const saveNameConfig = () => {
  message.success('起名规则已保存')
}

const savePrice = (record) => {
  message.success(`${record.serviceName}价格已更新为 ¥${record.price}`)
}
</script>

<style lang="scss" scoped>
.config-page {
  .page-header {
    margin-bottom: 24px;

    h1 {
      font-size: 20px;
      margin: 0;
    }
  }
}
</style>
