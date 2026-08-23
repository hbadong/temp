#!/usr/bin/env node
/**
 * Sitemap Generator for PanSearch
 * Generates sitemap.xml with static and dynamic routes
 */

const fs = require('fs')
const path = require('path')
const Database = require('better-sqlite3')

// Configuration
const BASE_URL = process.env.SITE_URL || 'https://pansearch.example.com'
const OUTPUT_PATH = path.join(__dirname, '../web/public/sitemap.xml')
const DB_PATH = process.env.DB_PATH || path.join(__dirname, '../server/data/pan-search.db')

// Static routes with priorities
const STATIC_ROUTES = [
  { url: '/', changefreq: 'daily', priority: 1.0 },
  { url: '/search', changefreq: 'daily', priority: 0.9 },
  { url: '/complain', changefreq: 'monthly', priority: 0.5 },
  { url: '/admin', changefreq: 'yearly', priority: 0.3 },
]

async function getDynamicRoutes() {
  const routes = []

  try {
    // Connect to database and fetch resource IDs for detail pages
    const db = new Database(DB_PATH, { readonly: true })

    // Get recent resources for detail pages (limit to 1000 for sitemap size)
    const resources = db.prepare(
      `SELECT id, cloud_type FROM resources WHERE status = 'ok' ORDER BY published_at DESC LIMIT 1000`
    ).all()

    if (resources && resources.length > 0) {
      for (const res of resources) {
        routes.push({
          url: `/detail/${res.id}`,
          changefreq: 'weekly',
          priority: 0.7,
          lastmod: new Date().toISOString().split('T')[0],
        })
      }
    }

    // Get search queries for search result pages (popular searches)
    const searches = db.prepare(
      `SELECT keyword FROM search_logs WHERE result_count > 0 GROUP BY keyword ORDER BY COUNT(*) DESC LIMIT 100`
    ).all()

    if (searches && searches.length > 0) {
      for (const s of searches) {
        const encodedQuery = encodeURIComponent(s.keyword)
        routes.push({
          url: `/search?q=${encodedQuery}`,
          changefreq: 'weekly',
          priority: 0.6,
          lastmod: new Date().toISOString().split('T')[0],
        })
      }
    }

    db.close()
  } catch (error) {
    console.warn('Could not fetch dynamic routes from database:', error.message)
    console.warn('Generating sitemap with static routes only')
  }

  return routes
}

function generateSitemap(staticRoutes, dynamicRoutes) {
  const allRoutes = [...staticRoutes, ...dynamicRoutes]

  const xmlHeader = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">`

  const xmlFooter = `</urlset>`

  const urlEntries = allRoutes.map(route => {
    const lastmod = route.lastmod || new Date().toISOString().split('T')[0]
    return `  <url>
    <loc>${BASE_URL}${route.url}</loc>
    <lastmod>${lastmod}</lastmod>
    <changefreq>${route.changefreq}</changefreq>
    <priority>${route.priority}</priority>
  </url>`
  }).join('\n')

  return `${xmlHeader}\n${urlEntries}\n${xmlFooter}\n`
}

async function main() {
  console.log('Generating sitemap.xml...')

  const dynamicRoutes = await getDynamicRoutes()
  const sitemap = generateSitemap(STATIC_ROUTES, dynamicRoutes)

  // Ensure output directory exists
  const outputDir = path.dirname(OUTPUT_PATH)
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true })
  }

  fs.writeFileSync(OUTPUT_PATH, sitemap)
  console.log(`Sitemap generated at ${OUTPUT_PATH}`)
  console.log(`Total URLs: ${STATIC_ROUTES.length + dynamicRoutes.length}`)
}

main().catch(console.error)