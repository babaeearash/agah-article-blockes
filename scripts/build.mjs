// Builds the minified frontend assets the plugin loads in production.
// Usage: npm install && npm run build
import { build } from 'esbuild';

const root = new URL('../aagah-article-blocks/assets/', import.meta.url).pathname;
const targets = ['chrome90', 'firefox90', 'safari14', 'edge90'];

await Promise.all([
  build({ entryPoints: [root + 'css/aab-frontend.css'], outfile: root + 'css/aab-frontend.min.css', minify: true, target: targets, logLevel: 'info' }),
  build({ entryPoints: [root + 'js/aab-frontend.js'], outfile: root + 'js/aab-frontend.min.js', minify: true, target: targets, logLevel: 'info' }),
]);
