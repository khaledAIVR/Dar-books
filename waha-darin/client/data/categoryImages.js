/**
 * Fallback image URLs for book categories when the API does not provide one.
 * Uses Unsplash (https://unsplash.com) – replace with your own assets in production
 * and ensure compliance with Unsplash license if you keep these.
 * Size 400x400 for consistent category tiles.
 */
const SIZE = '400x400'
const BASE = 'https://images.unsplash.com'

const CATEGORY_IMAGE_MAP = {
    'سير ومذكرات': `${BASE}/photo-1455390692773-6737e5e7a468?w=${SIZE.split('x')[0]}&h=${SIZE.split('x')[1]}&fit=crop`,
    'تاريخ وجغرافيا': `${BASE}/photo-1524661135-423995f22d0f?w=400&h=400&fit=crop`,
    'روايات': `${BASE}/photo-1512820790803-83ca734da794?w=400&h=400&fit=crop`,
    'فلسفة وفكر ومقالات': `${BASE}/photo-1457369804613-52c61a468e7d?w=400&h=400&fit=crop`,
    'علم نفس واجتماع': `${BASE}/photo-1529156069898-49953e39b3ac?w=400&h=400&fit=crop`,
    'فنون وشعر': `${BASE}/photo-1513364776144-60967b0f800f?w=400&h=400&fit=crop`,
    'روايات مترجمة': `${BASE}/photo-1544947950-fa07a98d237f?w=400&h=400&fit=crop`,
    'علوم طبية': `${BASE}/photo-1579684385127-1ef15d508118?w=400&h=400&fit=crop`,
    'علوم سياسية وقانون': `${BASE}/photo-1589829545856-d10d557cf95f?w=400&h=400&fit=crop`,
    'اديان وعقائد': `${BASE}/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop`,
    'لغات وقواميس': `${BASE}/photo-1544716278-ca5e3f4abd8c?w=400&h=400&fit=crop`,
    'علوم اللغة والترجمة': `${BASE}/photo-1456513080510-7bf3a84b82f8?w=400&h=400&fit=crop`,
    'ادارة واقتصاد': `${BASE}/photo-1554224155-8d04cb21cd6c?w=400&h=400&fit=crop`,
    'تربية طفل': `${BASE}/photo-1587654780291-39c9404d746b?w=400&h=400&fit=crop`,
    'أطفال وناشئة': `${BASE}/photo-1503676260728-1c00da094a0b?w=400&h=400&fit=crop`,
    'أدب ألماني': `${BASE}/photo-1495446815901-a7297e893e8f?w=400&h=400&fit=crop`,
    'اهداء رفيق شامي': `${BASE}/photo-1543002588-f3c3d6c8e1b3?w=400&h=400&fit=crop`
}

const PLACEHOLDER_PATTERNS = ['/holder.jpg', 'holder.jpg', '']
const isPlaceholderUrl = (url) =>
    !url ||
    PLACEHOLDER_PATTERNS.some((p) => url === p || url.endsWith(p)) ||
    String(url).includes('holder')
const DEFAULT_IMAGE = `${BASE}/photo-1495446815901-a7297e893e8f?w=400&h=400&fit=crop`

/**
 * @param {{ name: string, image_url?: string }} category - Category from API
 * @returns {string} URL to use for the category image
 */
export function getCategoryImageUrl(category) {
    if (!category) return DEFAULT_IMAGE
    const url = category.image_url || ''
    if (!isPlaceholderUrl(url)) return url
    return CATEGORY_IMAGE_MAP[category.name] || DEFAULT_IMAGE
}

export { CATEGORY_IMAGE_MAP }
