import { Request, Response, NextFunction } from 'express'
import Joi from 'joi'
import { error, ErrorCodes } from '../utils/response'

export function validateBody(schema: Joi.ObjectSchema) {
  return (req: Request, res: Response, next: NextFunction): void => {
    const { error: validationError } = schema.validate(req.body, {
      abortEarly: false,
      stripUnknown: true
    })

    if (validationError) {
      const details = validationError.details.map(detail => detail.message).join(', ')
      res.status(400).json(error(ErrorCodes.VALIDATION_ERROR, details))
      return
    }

    next()
  }
}

export function validateQuery(schema: Joi.ObjectSchema) {
  return (req: Request, res: Response, next: NextFunction): void => {
    const { error: validationError } = schema.validate(req.query, {
      abortEarly: false,
      stripUnknown: true
    })

    if (validationError) {
      const details = validationError.details.map(detail => detail.message).join(', ')
      res.status(400).json(error(ErrorCodes.VALIDATION_ERROR, details))
      return
    }

    next()
  }
}

export function validateParams(schema: Joi.ObjectSchema) {
  return (req: Request, res: Response, next: NextFunction): void => {
    const { error: validationError } = schema.validate(req.params, {
      abortEarly: false,
      stripUnknown: true
    })

    if (validationError) {
      const details = validationError.details.map(detail => detail.message).join(', ')
      res.status(400).json(error(ErrorCodes.VALIDATION_ERROR, details))
      return
    }

    next()
  }
}
