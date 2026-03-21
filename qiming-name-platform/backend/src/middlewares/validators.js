import Joi from 'joi'

export const registerSchema = Joi.object({
  username: Joi.string()
    .alphanum()
    .min(3)
    .max(30)
    .required()
    .messages({
      'string.alphanum': '用户名只能包含字母和数字',
      'string.min': '用户名长度不能少于3个字符',
      'string.max': '用户名长度不能超过30个字符',
      'any.required': '用户名不能为空'
    }),
  
  password: Joi.string()
    .min(6)
    .max(50)
    .required()
    .messages({
      'string.min': '密码长度不能少于6个字符',
      'string.max': '密码长度不能超过50个字符',
      'any.required': '密码不能为空'
    }),
  
  phone: Joi.string()
    .pattern(/^1[3-9]\d{9}$/)
    .allow('')
    .messages({
      'string.pattern.base': '手机号格式不正确'
    }),
  
  email: Joi.string()
    .email()
    .allow('')
    .messages({
      'string.email': '邮箱格式不正确'
    })
})

export const loginSchema = Joi.object({
  username: Joi.string().required().messages({
    'any.required': '用户名不能为空'
  }),
  password: Joi.string().required().messages({
    'any.required': '密码不能为空'
  })
})

export const nameTestSchema = Joi.object({
  name: Joi.string().min(2).max(20).required().messages({
    'string.min': '姓名长度不能少于2个字符',
    'string.max': '姓名长度不能超过20个字符',
    'any.required': '姓名不能为空'
  }),
  surname: Joi.string().min(1).max(10).required(),
  givenName: Joi.string().min(1).max(10).required(),
  gender: Joi.number().valid(0, 1, 2),
  birthDate: Joi.string().pattern(/^\d{4}-\d{2}-\d{2}$/),
  birthTime: Joi.string()
})

export const baziCalculateSchema = Joi.object({
  year: Joi.number().integer().min(1900).max(2100).required().messages({
    'number.min': '年份必须在1900-2100之间',
    'number.max': '年份必须在1900-2100之间',
    'any.required': '出生年份不能为空'
  }),
  month: Joi.number().integer().min(1).max(12).required().messages({
    'number.min': '月份必须在1-12之间',
    'number.max': '月份必须在1-12之间',
    'any.required': '出生月份不能为空'
  }),
  day: Joi.number().integer().min(1).max(31).required().messages({
    'number.min': '日期必须在1-31之间',
    'number.max': '日期必须在1-31之间',
    'any.required': '出生日期不能为空'
  }),
  hour: Joi.number().integer().min(0).max(23).required().messages({
    'number.min': '小时必须在0-23之间',
    'number.max': '小时必须在0-23之间',
    'any.required': '出生时辰不能为空'
  }),
  gender: Joi.number().valid(1, 2)
})

export const recommendSchema = Joi.object({
  surname: Joi.string().min(1).max(10).required().messages({
    'any.required': '姓氏不能为空'
  }),
  gender: Joi.number().valid(1, 2).required(),
  birthDate: Joi.string().pattern(/^\d{4}-\d{2}-\d{2}$/),
  birthTime: Joi.string(),
  firstElement: Joi.string().valid('金', '木', '水', '火', '土'),
  lastElement: Joi.string().valid('金', '木', '水', '火', '土'),
  expectTags: Joi.array().items(Joi.string()),
  poetryStyle: Joi.string().valid('tang', 'song', 'shijing', 'chuci'),
  excludeNames: Joi.array().items(Joi.string()),
  page: Joi.number().integer().min(1).default(1),
  pageSize: Joi.number().integer().min(1).max(100).default(20)
})

export const orderCreateSchema = Joi.object({
  serviceType: Joi.string().valid('bazi', 'shici', 'zhouyi', 'company', 'normal').required(),
  serviceName: Joi.string().required(),
  userName: Joi.string().min(1).max(50).required(),
  userGender: Joi.number().valid(1, 2),
  userBirthDate: Joi.string().pattern(/^\d{4}-\d{2}-\d{2}$/),
  userBirthTime: Joi.string(),
  requirements: Joi.string().max(500)
})
