<template>
  <div class="register-page">
    <div class="register-container">
      <div class="register-box">
        <div class="register-header">
          <router-link
            to="/"
            class="logo"
          >
            <img
              src="/images/logo.png"
              alt="起名网"
            >
          </router-link>
          <h2>用户注册</h2>
          <p>加入起名网，开启好名字之旅</p>
        </div>
        <a-form
          :model="formState"
          @finish="onFinish"
        >
          <a-form-item
            name="phone"
            :rules="[{ required: true, message: '请输入手机号' }, { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号' }]"
          >
            <a-input
              v-model:value="formState.phone"
              size="large"
              placeholder="手机号"
            >
              <template #prefix>
                <i class="iconfont icon-phone" />
              </template>
            </a-input>
          </a-form-item>
          <a-form-item
            name="password"
            :rules="[{ required: true, message: '请输入密码' }, { min: 6, message: '密码至少6位' }]"
          >
            <a-input-password
              v-model:value="formState.password"
              size="large"
              placeholder="密码（至少6位）"
            >
              <template #prefix>
                <i class="iconfont icon-lock" />
              </template>
            </a-input-password>
          </a-form-item>
          <a-form-item
            name="confirmPassword"
            :rules="[{ required: true, message: '请确认密码' }, { validator: validatePassword, trigger: 'change' }]"
          >
            <a-input-password
              v-model:value="formState.confirmPassword"
              size="large"
              placeholder="确认密码"
            >
              <template #prefix>
                <i class="iconfont icon-lock" />
              </template>
            </a-input-password>
          </a-form-item>
          <a-form-item
            name="code"
            :rules="[{ required: true, message: '请输入验证码' }]"
          >
            <div class="code-input">
              <a-input
                v-model:value="formState.code"
                size="large"
                placeholder="验证码"
              >
                <template #prefix>
                  <i class="iconfont icon-safe" />
                </template>
              </a-input>
              <a-button
                size="large"
                :disabled="countdown > 0"
                @click="sendCode"
              >
                {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
              </a-button>
            </div>
          </a-form-item>
          <a-form-item>
            <a-checkbox v-model:checked="formState.agree">
              我已阅读并同意
              <a href="/service">《服务条款》</a>
              和
              <a href="/privacy">《隐私政策》</a>
            </a-checkbox>
          </a-form-item>
          <a-form-item>
            <a-button
              type="primary"
              html-type="submit"
              size="large"
              block
              :loading="loading"
              :disabled="!formState.agree"
            >
              注册
            </a-button>
          </a-form-item>
          <div class="register-footer">
            <span>已有账号？</span>
            <router-link to="/login">
              立即登录
            </router-link>
          </div>
        </a-form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';

const router = useRouter();
const loading = ref(false);
const countdown = ref(0);

const formState = reactive({
  phone: '',
  password: '',
  confirmPassword: '',
  code: '',
  agree: false
});

const validatePassword = async (_rule, value) => {
  if (value !== formState.password) {
    return Promise.reject('两次输入的密码不一致');
  }
  return Promise.resolve();
};

const sendCode = async () => {
  if (!/^1[3-9]\d{9}$/.test(formState.phone)) {
    message.warning('请输入正确的手机号');
    return;
  }
  countdown.value = 60;
  const timer = setInterval(() => {
    countdown.value--;
    if (countdown.value <= 0) {
      clearInterval(timer);
    }
  }, 1000);
  message.success('验证码已发送');
};

const onFinish = async () => {
  loading.value = true;
  try {
    await new Promise(resolve => setTimeout(resolve, 1000));
    message.success('注册成功');
    router.push('/login');
  } catch (error) {
    message.error('注册失败，请重试');
  } finally {
    loading.value = false;
  }
};
</script>

<style lang="scss" scoped>
.register-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #a93121 0%, #c92009 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.register-container {
  width: 100%;
  max-width: 420px;
}

.register-box {
  background: #fff;
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.register-header {
  text-align: center;
  margin-bottom: 30px;

  .logo {
    display: inline-block;
    margin-bottom: 20px;

    img {
      height: 49px;
    }
  }

  h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 8px;
  }

  p {
    color: #999;
    font-size: 14px;
  }
}

.code-input {
  display: flex;
  gap: 10px;

  .ant-input {
    flex: 1;
  }

  .ant-btn {
    flex-shrink: 0;
    min-width: 110px;
  }
}

.register-footer {
  text-align: center;
  margin-top: 20px;
  color: #666;
  font-size: 14px;

  a {
    color: #a93121;
    margin-left: 5px;

    &:hover {
      text-decoration: underline;
    }
  }
}

.iconfont {
  color: #999;
}

:deep(.ant-checkbox-wrapper) {
  font-size: 13px;

  a {
    color: #a93121;
    margin: 0 3px;

    &:hover {
      text-decoration: underline;
    }
  }
}
</style>
