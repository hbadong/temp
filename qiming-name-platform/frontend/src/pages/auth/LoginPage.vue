<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-box">
        <div class="login-header">
          <router-link
            to="/"
            class="logo"
          >
            <img
              src="/images/logo.png"
              alt="起名网"
            >
          </router-link>
          <h2>用户登录</h2>
          <p>欢迎回到起名网</p>
        </div>
        <a-form
          :model="formState"
          @finish="onFinish"
        >
          <a-form-item
            name="username"
            :rules="[{ required: true, message: '请输入用户名或手机号' }]"
          >
            <a-input
              v-model:value="formState.username"
              size="large"
              placeholder="用户名 / 手机号"
            >
              <template #prefix>
                <i class="iconfont icon-user" />
              </template>
            </a-input>
          </a-form-item>
          <a-form-item
            name="password"
            :rules="[{ required: true, message: '请输入密码' }]"
          >
            <a-input-password
              v-model:value="formState.password"
              size="large"
              placeholder="密码"
            >
              <template #prefix>
                <i class="iconfont icon-lock" />
              </template>
            </a-input-password>
          </a-form-item>
          <a-form-item>
            <div class="login-options">
              <a-checkbox v-model:checked="formState.remember">
                记住我
              </a-checkbox>
              <a href="/forgot">忘记密码？</a>
            </div>
          </a-form-item>
          <a-form-item>
            <a-button
              type="primary"
              html-type="submit"
              size="large"
              block
              :loading="loading"
            >
              登录
            </a-button>
          </a-form-item>
          <div class="login-footer">
            <span>还没有账号？</span>
            <router-link to="/register">
              立即注册
            </router-link>
          </div>
        </a-form>
        <div class="login-divider">
          <span>或</span>
        </div>
        <div class="social-login">
          <a-button size="large">
            <i class="iconfont icon-wechat" />微信登录
          </a-button>
          <a-button size="large">
            <i class="iconfont icon-qq" />QQ登录
          </a-button>
        </div>
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

const formState = reactive({
  username: '',
  password: '',
  remember: false
});

const onFinish = async () => {
  loading.value = true;
  try {
    await new Promise(resolve => setTimeout(resolve, 1000));
    message.success('登录成功');
    router.push('/');
  } catch (error) {
    message.error('登录失败，请检查用户名和密码');
  } finally {
    loading.value = false;
  }
};
</script>

<style lang="scss" scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #a93121 0%, #c92009 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.login-container {
  width: 100%;
  max-width: 420px;
}

.login-box {
  background: #fff;
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.login-header {
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

.login-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;

  a {
    color: #a93121;
    font-size: 14px;

    &:hover {
      color: #c92009;
    }
  }
}

.login-footer {
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

.login-divider {
  text-align: center;
  margin: 25px 0;
  position: relative;

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background: #eee;
  }

  span {
    position: relative;
    background: #fff;
    padding: 0 15px;
    color: #999;
    font-size: 14px;
  }
}

.social-login {
  display: flex;
  gap: 15px;

  .ant-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    .iconfont {
      font-size: 18px;
    }
  }
}

.iconfont {
  color: #999;
}
</style>
