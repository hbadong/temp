import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  timeout: 10000
})

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export const userApi = {
  sendCode: (phone: string) => api.post('/user/send-code', { phone }),
  
  login: (phone: string, code: string) => api.post('/user/login', { phone, code }),
  
  getUserInfo: () => api.get('/user/info'),
  
  getProducts: (platform?: string) => 
    api.get('/products', { params: { platform } }),
  
  getProductDetail: (id: number) => api.get(`/products/${id}`),
  
  exchangeMobile: (productId: number, phone: string, code: string) => 
    api.post('/exchange/mobile', { productId, targetAccount: phone, code }),
  
  exchangeCard: (cardNo: string, targetAccount: string) => 
    api.post('/exchange/card', { cardNo, targetAccount }),
  
  getOrders: (page: number = 1, pageSize: number = 20) => 
    api.get('/orders', { params: { page, pageSize } }),
  
  getOrderDetail: (id: number) => api.get(`/orders/${id}`)
}

export default api
