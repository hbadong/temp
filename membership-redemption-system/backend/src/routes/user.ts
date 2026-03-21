import { Router } from 'express'
import userRoutes from '../controllers/userController'
import productRoutes from '../controllers/productController'

const router = Router()

router.use('/user', userRoutes)
router.use('/products', productRoutes)

export default router
