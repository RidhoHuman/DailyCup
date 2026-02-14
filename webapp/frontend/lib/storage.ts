/**
 * Storage helper — resolves product/upload image paths to absolute URLs
 * Uses NEXT_PUBLIC_API_URL to derive backend root (removes /api and /backend suffixes).
 */
export function getImageUrl(path?: string | null) {
  if (!path) return null;

  // absolute URL -> return as-is
  if (/^https?:\/\//i.test(path)) return path;

  const apiUrl = (process.env.NEXT_PUBLIC_API_URL || '').replace(/^(https?:\/\/)(https?:?\/\/)+/i, '$1');
  let root = apiUrl;
  if (root.endsWith('/api')) root = root.slice(0, -4);
  if (root.endsWith('/backend')) root = root.slice(0, -8);
  if (root.endsWith('/')) root = root.slice(0, -1);

  // If path already starts with '/', treat it as absolute path under backend root
  if (path.startsWith('/')) {
    return root ? `${root}${path}` : path;
  }

  // Otherwise treat as filename inside /uploads/products/
  return root ? `${root}/uploads/products/${path}` : `/uploads/products/${path}`;
}
