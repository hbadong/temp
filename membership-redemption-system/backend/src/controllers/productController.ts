import { Router, Request, Response, NextFunction } from 'express'
import { productService } from '../services/productService'
import { userAuth } from '../middleware/userAuth'
import { success } from '../utils/response'

const router = Router()

router.get('/', userAuth, async (req: Request, res: Response, next: NextFunction) => {
  try {
    const { platform, status } = req.query
    let products

    if (platform) {
      products = await productService.findByPlatform(
        platform as string,
        status !== undefined ? parseInt(status as string) : 1
      )
    } else {
      products = await productService.findAll(1)
    }

    res.json(success(products.map(p => ({
      id: p.id,
      platform: p.platform,
      name: p.name,
      description: p.description,
      durationDays: p.durationDays,
      price: p.price,
      stock: p.stock,
      status: p.status
    }))))
  } catch (err) {
    next(err)
  }
})

router.get('/:id', userAuth, async (req: Request, res: Response, next: NextFunction) => {
  try {
    const product = await productService.findById(parseInt(req.params.id))

    if (!product) {
      res.status(404).json({ code: 40002, message: '套餐不存在' })
      return
    }

    res.json(success({
      id: product.id,
      platform: product.platform,
      name: product.name,
      description: product.description,
      durationDays: product.durationDays,
      price: product.price,
      stock: product.stock,
      status: product.status
    }))
  } catch (err) {
    next(err)
  }
})

export default router
