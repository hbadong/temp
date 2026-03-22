import axios from 'axios';

const request = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json'
  }
});

const USE_MOCK = true;

const mockData = {
  '/v1/names/recommend': {
    items: generateMockNames(20),
    total: 150,
    page: 1,
    pageSize: 20,
    totalPages: 8
  },
  '/v1/names/test': generateMockTestResult(),
  '/v1/bazi/calculate': generateMockBaziResult(),
  '/v1/almanac/today': generateMockAlmanac(),
  '/v1/names/popular': generateMockNames(10),
  '/v1/names/ranks': generateMockNames(30)
};

function generateMockNames(count) {
  const surnames = ['李', '王', '张', '刘', '陈', '杨', '赵', '黄', '周', '吴'];
  const boyNames = ['俊豪', '煜晨', '铭轩', '梓翔', '昊然', '思远', '文博', '家瑞', '宇航', '志豪', '子轩', '一鸣', '承锐', '宏泽', '梓航', '璟桓', '颢宁', '颢哲', '璟霆', '翔士'];
  const girlNames = ['欣怡', '梓涵', '雨桐', '诗涵', '思琪', '雅婷', '欣悦', '梦瑶', '佳怡', '雪丽', '宁丽', '宁汐', '宁萱', '宇妃', '宇妍', '宁婕', '颜菲', '孟馨', '颜菲', '千惠'];
  const elements = ['金', '木', '水', '火', '土'];
  const meanings = [
    '才智超群，豪迈大气',
    '光明照耀，晨曦微露',
    '铭记于心，气宇轩昂',
    '生机勃勃，含养深厚',
    '正直勇敢，志向远大'
  ];
  
  const results = [];
  for (let i = 0; i < count; i++) {
    const surname = surnames[Math.floor(Math.random() * surnames.length)];
    const isBoy = Math.random() > 0.5;
    const givenName = isBoy 
      ? boyNames[Math.floor(Math.random() * boyNames.length)]
      : girlNames[Math.floor(Math.random() * girlNames.length)];
    const fullName = surname + givenName;
    const score = 85 + Math.floor(Math.random() * 15);
    const element = elements[Math.floor(Math.random() * elements.length)];
    
    results.push({
      id: i + 1,
      surname,
      given_name: givenName,
      full_name: fullName,
      pinyin: `${surname.toLowerCase()} ${givenName.split('').map(c => getPinyin(c)).join(' ')}`,
      five_element: element,
      total_score: score,
      stroke_count: getStrokeCount(surname) + getStrokeCount(givenName),
      wu_ge_tian: getStrokeCount(surname) + 1,
      wu_ge_di: getStrokeCount(givenName),
      wu_ge_ren: getStrokeCount(surname) + getStrokeCount(givenName[0]),
      wu_ge_zong: getStrokeCount(surname) + getStrokeCount(givenName),
      wu_ge_lucky: '吉',
      meaning: meanings[Math.floor(Math.random() * meanings.length)],
      gender: isBoy ? 1 : 2
    });
  }
  return results;
}

function getPinyin(char) {
  const map = { '俊': 'jun', '豪': 'hao', '煜': 'yu', '晨': 'chen', '铭': 'ming', '轩': 'xuan', '梓': 'zi', '翔': 'xiang', '昊': 'hao', '然': 'ran' };
  return map[char] || 'yi';
}

function getStrokeCount(name) {
  let count = 0;
  for (const c of name) {
    count += getCharStroke(c);
  }
  return count;
}

function getCharStroke(char) {
  const map = { '李': 7, '王': 4, '张': 11, '刘': 15, '陈': 16, '杨': 13, '赵': 9, '黄': 12, '周': 8, '吴': 7, '俊': 9, '豪': 14, '煜': 13, '晨': 11, '铭': 14, '轩': 10, '梓': 11, '翔': 12, '昊': 8, '然': 12, '思': 9, '远': 11, '文': 4, '博': 12, '家': 10, '瑞': 14, '宇': 6, '航': 10, '志': 7 };
  return map[char] || 8;
}

function generateMockTestResult() {
  return {
    name: '李俊豪',
    pinyin: 'li jun hao',
    strokes: [7, 9, 14],
    fiveElement: '火',
    total: 92,
    level: '大吉',
    scores: { yin: 88, xing: 85, yi: 94, shu: 90, li: 88, yun: 85, jing: 82, de: 80, ming: 88 },
    wuGe: {
      tian: { value: 8, lucky: '吉', meaning: '万事如意' },
      di: { value: 23, lucky: '吉', meaning: '天瑞吉祥' },
      ren: { value: 16, lucky: '吉', meaning: '厚重安稳' },
      wai: { value: 7, lucky: '吉', meaning: '刚毅果断' },
      zong: { value: 30, lucky: '半吉', meaning: '吉凶各半' }
    }
  };
}

function generateMockBaziResult() {
  return {
    bazi: {
      year: { branch: '丙午', gan: '丙', zhi: '午', wuXing: '火' },
      month: { branch: '辛卯', gan: '辛', zhi: '卯', wuXing: '木' },
      day: { branch: '乙未', gan: '乙', zhi: '未', wuXing: '木' },
      hour: { branch: '戊子', gan: '戊', zhi: '子', wuXing: '水' }
    },
    fiveElements: { 木: 2, 火: 1, 土: 2, 金: 1, 水: 2 },
    dayMasterStrength: -15,
    xiYongSheng: '水',
    jiShen: '火',
    dayMaster: '乙',
    strengthLevel: '偏弱'
  };
}

function generateMockAlmanac() {
  const today = new Date();
  return {
    date: today.toISOString().split('T')[0],
    lunarYear: '乙巳',
    lunarMonth: '二月',
    lunarDay: '初四',
    zodiac: '马',
    solarTerm: '春分',
    constellation: '白羊座',
    weekday: '星期日',
    yi: ['嫁娶', '祭祀', '开光', '祈福', '求嗣', '出行'],
    ji: ['动土', '伐木', '安葬', '行丧'],
    chongSha: '丁酉',
    chongZodiac: '兔',
    luckyHours: ['子', '丑', '卯', '午'],
    caiShen: '东北',
    xiShen: '西北',
    fuShen: '西南'
  };
}

request.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

request.interceptors.response.use(
  response => {
    const { code, message, data } = response.data;
    
    if (code !== 200) {
      console.error('API Error:', message);
      return Promise.reject(new Error(message));
    }
    
    return response.data;
  },
  error => {
    if (error.response) {
      const { status, data } = error.response;
      
      if (status === 401) {
        localStorage.removeItem('token');
        window.location.href = '/';
      }
      
      return Promise.reject(new Error(data.message || '请求失败'));
    }
    
    return Promise.reject(error);
  }
);

request.mockGet = (url, params = {}) => {
  return new Promise((resolve) => {
    setTimeout(() => {
      const mockKey = Object.keys(mockData).find(key => url.includes(key));
      if (mockKey) {
        resolve({ code: 200, message: 'success', data: mockData[mockKey] });
      } else {
        resolve({ code: 200, message: 'success', data: null });
      }
    }, 300);
  });
};

export { USE_MOCK };
export default request;
