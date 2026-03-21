export class AppError extends Error {
  constructor(message, statusCode, code) {
    super(message)
    this.statusCode = statusCode
    this.code = code
    this.isOperational = true
    
    Error.captureStackTrace(this, this.constructor)
  }
}

export class BadRequestError extends AppError {
  constructor(message = 'Bad Request', code = 'BAD_REQUEST') {
    super(message, 400, code)
  }
}

export class UnauthorizedError extends AppError {
  constructor(message = 'Unauthorized', code = 'UNAUTHORIZED') {
    super(message, 401, code)
  }
}

export class ForbiddenError extends AppError {
  constructor(message = 'Forbidden', code = 'FORBIDDEN') {
    super(message, 403, code)
  }
}

export class NotFoundError extends AppError {
  constructor(message = 'Not Found', code = 'NOT_FOUND') {
    super(message, 404, code)
  }
}

export class ConflictError extends AppError {
  constructor(message = 'Conflict', code = 'CONFLICT') {
    super(message, 409, code)
  }
}

export function errorHandler(err, req, res, next) {
  console.error('Error:', err)
  
  if (err.isOperational) {
    return res.status(err.statusCode).json({
      code: err.statusCode,
      message: err.message,
      error: {
        code: err.code,
        message: err.message
      }
    })
  }
  
  if (err.name === 'ValidationError') {
    return res.status(400).json({
      code: 400,
      message: 'Validation failed',
      error: {
        code: 'VALIDATION_ERROR',
        details: err.details
      }
    })
  }
  
  if (err.name === 'JsonWebTokenError') {
    return res.status(401).json({
      code: 401,
      message: 'Invalid token',
      error: {
        code: 'INVALID_TOKEN'
      }
    })
  }
  
  if (err.name === 'TokenExpiredError') {
    return res.status(401).json({
      code: 401,
      message: 'Token expired',
      error: {
        code: 'TOKEN_EXPIRED'
      }
    })
  }
  
  return res.status(500).json({
    code: 500,
    message: 'Internal server error',
    error: {
      code: 'INTERNAL_ERROR'
    }
  })
}
