import { Router } from 'express'
import userRoutes from './userController'
import productRoutes from './productController'

const router = Router()

router.use('/user', userRoutes)
router.use('/products', productRoutes)

export default router
