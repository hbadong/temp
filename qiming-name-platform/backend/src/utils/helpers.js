export class ResponseHelper {
  static success(data = null, message = 'success') {
    return {
      code: 200,
      message,
      data
    }
  }

  static created(data = null, message = '创建成功') {
    return {
      code: 201,
      message,
      data
    }
  }

  static error(message = '操作失败', code = 400) {
    return {
      code,
      message
    }
  }

  static paginated(data, pagination) {
    return {
      code: 200,
      message: 'success',
      data,
      pagination
    }
  }

  static unauthorized(message = '未登录') {
    return {
      code: 401,
      message
    }
  }

  static forbidden(message = '权限不足') {
    return {
      code: 403,
      message
    }
  }

  static notFound(message = '资源不存在') {
    return {
      code: 404,
      message
    }
  }

  static serverError(message = '服务器错误') {
    return {
      code: 500,
      message
    }
  }
}

export class ValidatorHelper {
  static validate(schema) {
    return async (ctx, next) => {
      const { error, value } = schema.validate(ctx.request.body, {
        abortEarly: false,
        stripUnknown: true
      })

      if (error) {
        const details = error.details.map(d => ({
          field: d.path.join('.'),
          message: d.message
        }))

        ctx.status = 400
        ctx.body = {
          code: 400,
          message: '参数验证失败',
          error: {
            code: 'VALIDATION_ERROR',
            details
          }
        }
        return
      }

      ctx.request.body = value
      await next()
    }
  }
}

export class LogHelper {
  static request(ctx, duration = 0) {
    return {
      method: ctx.method,
      url: ctx.url,
      ip: ctx.ip,
      userAgent: ctx.get('user-agent'),
      duration: `${duration}ms`,
      status: ctx.status
    }
  }

  static error(error) {
    return {
      message: error.message,
      stack: error.stack,
      name: error.name
    }
  }
}
